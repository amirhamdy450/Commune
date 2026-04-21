<?php
/**
 * FeedAlgorithm.php
 * Personalized feed scoring with DB-backed cache.
 */

define('FEED_CACHE_TTL', 20 * 60);   // 20 minutes
define('FEED_SCORE_POOL', 50);       // How many scored posts to cache per user
define('FEED_PAGE_SIZE', 5);         // Posts returned per page
define('FEED_MIN_FRESH_POSTS', 10);  // Backfill with recycled posts if fresh inventory drops below this
define('FEED_FRESH_PRIORITY_BONUS', 1000000); // Keeps fresh posts ahead of recycled ones in the cache order

/**
 * Returns an array of fully-formatted post rows for the given user.
 *
 * @return array{posts: array, hasMore: bool}
 */
function GetPersonalizedFeed(PDO $pdo, int $UID, int $offset = 0, bool $forceRefresh = false): array {

    if (!$forceRefresh && isset($_GET['nocache']) && $_GET['nocache'] === '1') {
        $forceRefresh = true;
    }

    if (!$forceRefresh) {
        $cached = GetFromFeedCache($pdo, $UID, $offset);
        if ($cached !== null) {
            return $cached;
        }
    }

    BuildFeedCache($pdo, $UID);

    $result = GetFromFeedCache($pdo, $UID, $offset);
    return $result ?? ['posts' => [], 'hasMore' => false];
}

/**
 * Reads from feed_cache if it exists and is within TTL.
 */
function GetFromFeedCache(PDO $pdo, int $UID, int $offset): ?array {

    $stmtAge = $pdo->prepare(
        "SELECT MIN(CachedAt) AS OldestEntry FROM feed_cache WHERE UID = ?"
    );
    $stmtAge->execute([$UID]);
    $row = $stmtAge->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$row['OldestEntry']) {
        return null;
    }

    $ageSeconds = time() - strtotime($row['OldestEntry']);
    if ($ageSeconds > FEED_CACHE_TTL) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT PostID FROM feed_cache WHERE UID = ?
         ORDER BY Score DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bindValue(1, $UID, PDO::PARAM_INT);
    $stmt->bindValue(2, FEED_PAGE_SIZE + 1, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $cachedIDs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($cachedIDs)) {
        return ['posts' => [], 'hasMore' => false];
    }

    $hasMore = count($cachedIDs) > FEED_PAGE_SIZE;
    if ($hasMore) {
        array_pop($cachedIDs);
    }

    $placeholders = implode(',', array_fill(0, count($cachedIDs), '?'));
    $orderList = implode(',', $cachedIDs);

    $sqlPosts = "SELECT
                    posts.id AS PID, posts.*,
                    users.Fname, users.Lname, users.Username, users.ProfilePic, users.IsBlueTick,
                    CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                    CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                    CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved,
                    pg.Name AS PageName, pg.Handle AS PageHandle,
                    pg.Logo AS PageLogo, pg.IsVerified AS PageIsVerified
                 FROM posts
                 INNER JOIN users ON posts.UID = users.id
                 LEFT JOIN pages pg ON posts.OrgID = pg.id
                 LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                 LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                 LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                 WHERE posts.id IN ($placeholders)
                   AND posts.Status = 1
                 ORDER BY FIELD(posts.id, $orderList)";

    $params = [$UID, $UID, $UID, ...$cachedIDs];
    $stmtPosts = $pdo->prepare($sqlPosts);
    $stmtPosts->execute($params);
    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

    return ['posts' => $posts, 'hasMore' => $hasMore];
}

/**
 * Runs the scoring query and rewrites feed_cache for this user.
 *
 * First pass:
 * - unseen
 * - unliked
 * - uncommented
 * - unsaved
 *
 * If the number of fresh candidates is too low, the algorithm backfills with
 * already-seen or already-interacted posts using the same score ordering.
 */
function BuildFeedCache(PDO $pdo, int $UID): void {

    $baseSelect = "
        SELECT
            posts.id AS PostID,
            (
                (100.0 / (TIMESTAMPDIFF(HOUR, posts.Date, NOW()) + 2))
                + (CASE WHEN f.UserID IS NOT NULL THEN 25 ELSE 0 END)
                + (LEAST(COALESCE(aff.LikedCount, 0), 5) * 10)
                + (LOG(posts.LikeCounter + posts.CommentCounter + 1) * 6)
                + (CASE WHEN sv.UID IS NOT NULL THEN 15 ELSE 0 END)
            ) AS Score

        FROM posts
        INNER JOIN users ON posts.UID = users.id
        LEFT JOIN followers f  ON f.UserID  = users.id AND f.FollowerID = ?
        LEFT JOIN followers f2 ON f2.UserID = ?        AND f2.FollowerID = users.id
        LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?
        LEFT JOIN (
            SELECT p2.UID AS AuthorUID, COUNT(*) AS LikedCount
            FROM likes lk
            INNER JOIN posts p2 ON p2.id = lk.PostID
            WHERE lk.UID = ?
            GROUP BY p2.UID
        ) aff ON aff.AuthorUID = posts.UID
        LEFT JOIN (
            SELECT DISTINCT p3.UID AS AuthorUID, ? AS UID
            FROM saved_posts sp
            INNER JOIN posts p3 ON p3.id = sp.PostID
            WHERE sp.UID = ?
        ) sv ON sv.AuthorUID = posts.UID
    ";

    $visibilityWhere = "
        posts.Status = 1
        AND b.id IS NULL
        AND (
            posts.UID = ?
            OR posts.OrgID IS NOT NULL
            OR posts.Visibility = 0
            OR (posts.Visibility = 1 AND f.UserID IS NOT NULL)
            OR (posts.Visibility = 2 AND f2.UserID IS NOT NULL)
            OR (posts.Visibility = 3 AND f.UserID IS NOT NULL AND f2.UserID IS NOT NULL)
        )
    ";

    $freshSql = $baseSelect . "
        LEFT JOIN post_views pv ON pv.PostID = posts.id AND pv.UID = ?
        LEFT JOIN likes lkex ON lkex.PostID = posts.id AND lkex.UID = ?
        LEFT JOIN comments cmex ON cmex.PostID = posts.id AND cmex.UID = ?
        LEFT JOIN saved_posts spex ON spex.PostID = posts.id AND spex.UID = ?

        WHERE
            $visibilityWhere
            AND pv.PostID IS NULL
            AND lkex.id IS NULL
            AND cmex.id IS NULL
            AND spex.id IS NULL

        ORDER BY Score DESC
        LIMIT " . FEED_SCORE_POOL . "
    ";

    $freshParams = [$UID, $UID, $UID, $UID, $UID, $UID, $UID, $UID, $UID, $UID, $UID];
    $stmt = $pdo->prepare($freshSql);
    $stmt->execute($freshParams);
    $freshPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($freshPosts as &$row) {
        $row['CacheScore'] = (float)$row['Score'] + FEED_FRESH_PRIORITY_BONUS;
    }
    unset($row);

    $scored = $freshPosts;

    if (count($freshPosts) < FEED_MIN_FRESH_POSTS) {
        $existingIDs = array_map(static fn($row) => (int)$row['PostID'], $freshPosts);
        $excludeSql = '';
        $recycledParams = [$UID, $UID, $UID, $UID, $UID, $UID, $UID];

        if (!empty($existingIDs)) {
            $placeholders = implode(',', array_fill(0, count($existingIDs), '?'));
            $excludeSql = " AND posts.id NOT IN ($placeholders)";
            $recycledParams = array_merge($recycledParams, $existingIDs);
        }

        $recycledSql = $baseSelect . "
            WHERE
                $visibilityWhere
                $excludeSql

            ORDER BY Score DESC
            LIMIT " . FEED_SCORE_POOL . "
        ";

        $stmtRecycled = $pdo->prepare($recycledSql);
        $stmtRecycled->execute($recycledParams);
        $recycled = $stmtRecycled->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recycled as $row) {
            $postId = (int)$row['PostID'];
            if (!in_array($postId, $existingIDs, true)) {
                $row['CacheScore'] = (float)$row['Score'];
                $scored[] = $row;
                $existingIDs[] = $postId;
            }
            if (count($scored) >= FEED_SCORE_POOL) {
                break;
            }
        }
    }

    if (empty($scored)) {
        return;
    }

    $pdo->prepare("DELETE FROM feed_cache WHERE UID = ?")->execute([$UID]);

    $now = date('Y-m-d H:i:s');
    $insertSQL = "INSERT INTO feed_cache (UID, PostID, Score, CachedAt) VALUES (?, ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertSQL);

    foreach ($scored as $row) {
        $insertStmt->execute([$UID, (int)$row['PostID'], (float)($row['CacheScore'] ?? $row['Score']), $now]);
    }
}

/**
 * Deletes feed_cache for one or more users so next load triggers a rebuild.
 */
function InvalidateFeedCache(PDO $pdo, array $UIDs): void {
    if (empty($UIDs)) return;
    $placeholders = implode(',', array_fill(0, count($UIDs), '?'));
    $pdo->prepare("DELETE FROM feed_cache WHERE UID IN ($placeholders)")->execute($UIDs);
}

/**
 * Returns up to $limit users the viewer doesn't follow yet, ranked by:
 * 1. Friends-of-friends
 * 2. Authors of posts the viewer liked but doesn't follow
 * 3. Random fallback users
 */
function GetSmartWhoToFollow(PDO $pdo, int $UID, int $limit = 5): array {

    $sql = "
        SELECT
            u.id, u.Fname, u.Lname, u.Username, u.ProfilePic, u.IsBlueTick,
            SUM(
                CASE WHEN fof.UID IS NOT NULL THEN 3 ELSE 0 END
              + CASE WHEN la.AuthorUID IS NOT NULL THEN 2 ELSE 0 END
            ) AS RelevanceScore
        FROM users u
        LEFT JOIN (
            SELECT f2.UserID AS UID
            FROM followers f1
            INNER JOIN followers f2 ON f2.FollowerID = f1.UserID
            WHERE f1.FollowerID = ?
              AND f2.UserID != ?
        ) fof ON fof.UID = u.id
        LEFT JOIN (
            SELECT DISTINCT p.UID AS AuthorUID
            FROM likes lk
            INNER JOIN posts p ON p.id = lk.PostID
            WHERE lk.UID = ?
              AND p.UID != ?
        ) la ON la.AuthorUID = u.id
        WHERE
            u.id != ?
            AND u.Privilege < " . (int)PRIV_ADMIN . "
            AND u.id NOT IN (
                SELECT UserID FROM followers WHERE FollowerID = ?
            )
            AND (fof.UID IS NOT NULL OR la.AuthorUID IS NOT NULL)
        GROUP BY u.id
        ORDER BY RelevanceScore DESC
        LIMIT " . (int)$limit . "
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$UID, $UID, $UID, $UID, $UID, $UID]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) < $limit) {
        $existingIDs = array_column($results, 'id');
        $existingIDs[] = $UID;
        $exclude = implode(',', array_map('intval', $existingIDs));
        $needed = $limit - count($results);

        $fallback = $pdo->prepare(
            "SELECT id, Fname, Lname, Username, ProfilePic, IsBlueTick
             FROM users
             WHERE id NOT IN ($exclude)
               AND Privilege < " . (int)PRIV_ADMIN . "
               AND id NOT IN (SELECT UserID FROM followers WHERE FollowerID = ?)
             ORDER BY RAND()
             LIMIT $needed"
        );
        $fallback->execute([$UID]);
        $results = array_merge($results, $fallback->fetchAll(PDO::FETCH_ASSOC));
    }

    return $results;
}
