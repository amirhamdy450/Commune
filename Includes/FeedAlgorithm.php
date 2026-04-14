<?php
/**
 * FeedAlgorithm.php
 * Personalized feed scoring with DB-backed cache (Option B).
 *
 * Strategy:
 *   1. Check feed_cache for this user — if fresh (within FEED_CACHE_TTL seconds), serve it.
 *   2. If stale or empty, run the scoring query, write results to feed_cache, serve.
 *   3. Cache is invalidated explicitly on follow/like events via InvalidateFeedCache().
 *
 * Scoring formula (higher = more relevant):
 *   - Post recency:              decays over time  (max ~100 pts, halves every 24h)
 *   - Viewer follows author:     +25 pts flat bonus
 *   - Author affinity:           +0–50 pts based on how many of author's posts viewer liked
 *   - Post engagement:           +0–30 pts based on likes + comments (log-scaled)
 *   - Viewer saved author post:  +15 pts flat bonus
 *   - Already seen by viewer:    excluded entirely (post_views table)
 */

// ── Configuration ─────────────────────────────────────────────────────────────
define('FEED_CACHE_TTL', 20 * 60);  // 20 minutes in seconds — tune this for scale
define('FEED_SCORE_POOL', 50);      // How many scored posts to store in cache per user
define('FEED_PAGE_SIZE', 5);        // Posts returned per page

/**
 * GetPersonalizedFeed
 * Returns an array of fully-formatted post rows for the given user.
 *
 * @param PDO $pdo
 * @param int $UID          Logged-in user ID
 * @param int $offset       Pagination offset (0 for first page)
 * @param bool $forceRefresh  Skip cache check and rebuild immediately
 * @return array  [ 'posts' => [...], 'hasMore' => bool ]
 */
function GetPersonalizedFeed(PDO $pdo, int $UID, int $offset = 0, bool $forceRefresh = false): array {

    // Dev override: append ?nocache=1 to the URL to force a cache rebuild
    if (!$forceRefresh && isset($_GET['nocache']) && $_GET['nocache'] === '1') {
        $forceRefresh = true;
    }

    // 1. Try serving from cache unless forced refresh
    if (!$forceRefresh) {
        $cached = GetFromFeedCache($pdo, $UID, $offset);
        if ($cached !== null) {
            return $cached;
        }
    }

    // 2. Cache miss or stale — rebuild
    BuildFeedCache($pdo, $UID);

    // 3. Serve from freshly built cache
    $result = GetFromFeedCache($pdo, $UID, $offset);
    return $result ?? ['posts' => [], 'hasMore' => false];
}

/**
 * GetFromFeedCache
 * Reads from feed_cache if it exists and is within TTL.
 * Returns null if cache is missing or expired.
 */
function GetFromFeedCache(PDO $pdo, int $UID, int $offset): ?array {

    // Check freshness — look at the oldest CachedAt in this user's cache set
    $stmtAge = $pdo->prepare(
        "SELECT MIN(CachedAt) AS OldestEntry FROM feed_cache WHERE UID = ?"
    );
    $stmtAge->execute([$UID]);
    $row = $stmtAge->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$row['OldestEntry']) {
        return null; // No cache exists
    }

    $ageSeconds = time() - strtotime($row['OldestEntry']);
    if ($ageSeconds > FEED_CACHE_TTL) {
        return null; // Cache expired
    }

    // Fetch the scored post IDs from cache
    $stmt = $pdo->prepare(
        "SELECT PostID FROM feed_cache WHERE UID = ?
         ORDER BY Score DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bindValue(1, $UID, PDO::PARAM_INT);
    $stmt->bindValue(2, FEED_PAGE_SIZE + 1, PDO::PARAM_INT); // +1 to detect hasMore
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $cachedIDs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($cachedIDs)) {
        return ['posts' => [], 'hasMore' => false];
    }

    $hasMore = count($cachedIDs) > FEED_PAGE_SIZE;
    if ($hasMore) array_pop($cachedIDs);

    // Fetch full post data for these IDs (maintaining score order via FIELD)
    $placeholders = implode(',', array_fill(0, count($cachedIDs), '?'));
    $orderList    = implode(',', $cachedIDs);

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
                 ORDER BY FIELD(posts.id, $orderList)";

    $params = [$UID, $UID, $UID, ...$cachedIDs];
    $stmtPosts = $pdo->prepare($sqlPosts);
    $stmtPosts->execute($params);
    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

    return ['posts' => $posts, 'hasMore' => $hasMore];
}

/**
 * BuildFeedCache
 * Runs the scoring query and (re)writes feed_cache for this user.
 */
function BuildFeedCache(PDO $pdo, int $UID): void {

    // Scoring query — all math done in SQL, no PHP loops needed
    // Signals used:
    //   A. Recency decay:       100 / (hours_old + 2)   — post from 0h ago ≈ 50pts, 24h ago ≈ 3.8pts
    //   B. Follow bonus:        +25 if viewer follows author
    //   C. Author affinity:     viewer's like count on this author's posts, capped at 5, ×10
    //   D. Engagement score:    LOG(likes+comments+1) × 6 — log-scaled to prevent viral domination
    //   E. Save affinity:       +15 if viewer saved any post by this author before

    $sql = "
        SELECT
            posts.id AS PostID,
            (
                /* A. Recency decay */
                (100.0 / (TIMESTAMPDIFF(HOUR, posts.Date, NOW()) + 2))

                /* B. Follow bonus */
                + (CASE WHEN f.UserID IS NOT NULL THEN 25 ELSE 0 END)

                /* C. Author affinity — how many of this author's posts has the viewer liked */
                + (LEAST(COALESCE(aff.LikedCount, 0), 5) * 10)

                /* D. Engagement (log-scaled) */
                + (LOG(posts.LikeCounter + posts.CommentCounter + 1) * 6)

                /* E. Save affinity — viewer saved a post by this author */
                + (CASE WHEN sv.UID IS NOT NULL THEN 15 ELSE 0 END)

            ) AS Score

        FROM posts
        INNER JOIN users ON posts.UID = users.id

        /* Visibility joins */
        LEFT JOIN followers f  ON f.UserID  = users.id AND f.FollowerID = ?
        LEFT JOIN followers f2 ON f2.UserID = ?        AND f2.FollowerID = users.id

        /* Block filter */
        LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?

        /* Author affinity subquery */
        LEFT JOIN (
            SELECT p2.UID AS AuthorUID, COUNT(*) AS LikedCount
            FROM likes lk
            INNER JOIN posts p2 ON p2.id = lk.PostID
            WHERE lk.UID = ?
            GROUP BY p2.UID
        ) aff ON aff.AuthorUID = posts.UID

        /* Save affinity subquery */
        LEFT JOIN (
            SELECT DISTINCT p3.UID AS AuthorUID, ? AS UID
            FROM saved_posts sp
            INNER JOIN posts p3 ON p3.id = sp.PostID
            WHERE sp.UID = ?
        ) sv ON sv.AuthorUID = posts.UID

        /* Already-seen exclusion */
        LEFT JOIN post_views pv ON pv.PostID = posts.id AND pv.UID = ?

        /* Already-interacted exclusion */
        LEFT JOIN likes lkex ON lkex.PostID = posts.id AND lkex.UID = ?
        LEFT JOIN comments cmex ON cmex.PostID = posts.id AND cmex.UID = ?
        LEFT JOIN saved_posts spex ON spex.PostID = posts.id AND spex.UID = ?

        WHERE
            posts.Status = 1
            AND b.id IS NULL
            AND pv.PostID IS NULL          /* Exclude already-seen posts */
            AND lkex.id IS NULL            /* Exclude already-liked posts */
            AND cmex.id IS NULL            /* Exclude already-commented posts */
            AND spex.id IS NULL            /* Exclude already-saved posts */
            AND (
                posts.UID = ?
                OR posts.OrgID IS NOT NULL
                OR posts.Visibility = 0
                OR (posts.Visibility = 1 AND f.UserID IS NOT NULL)
                OR (posts.Visibility = 2 AND f2.UserID IS NOT NULL)
                OR (posts.Visibility = 3 AND f.UserID IS NOT NULL AND f2.UserID IS NOT NULL)
            )

        ORDER BY Score DESC
        LIMIT " . FEED_SCORE_POOL . "
    ";

    // Params: f.FollowerID, f2.UserID, b.BlockerUID, aff lk.UID, sv UID, sv sp.UID,
    //         pv.UID, lkex.UID, cmex.UID, spex.UID, posts.UID (visibility)
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(2,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(3,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(4,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(5,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(6,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(7,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(8,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(9,  $UID, PDO::PARAM_INT);
    $stmt->bindValue(10, $UID, PDO::PARAM_INT);
    $stmt->bindValue(11, $UID, PDO::PARAM_INT);
    $stmt->execute();
    $scored = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($scored)) return;

    // Clear old cache for this user
    $pdo->prepare("DELETE FROM feed_cache WHERE UID = ?")->execute([$UID]);

    // Write new scored results
    $now = date('Y-m-d H:i:s');
    $insertSQL = "INSERT INTO feed_cache (UID, PostID, Score, CachedAt) VALUES (?, ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertSQL);

    foreach ($scored as $row) {
        $insertStmt->execute([$UID, (int)$row['PostID'], (float)$row['Score'], $now]);
    }
}

/**
 * InvalidateFeedCache
 * Deletes feed_cache for one or more users so next load triggers a rebuild.
 * Call this when a user follows/unfollows or likes a post.
 *
 * @param PDO   $pdo
 * @param array $UIDs   Array of user IDs whose cache should be cleared
 */
function InvalidateFeedCache(PDO $pdo, array $UIDs): void {
    if (empty($UIDs)) return;
    $placeholders = implode(',', array_fill(0, count($UIDs), '?'));
    $pdo->prepare("DELETE FROM feed_cache WHERE UID IN ($placeholders)")->execute($UIDs);
}

/**
 * GetSmartWhoToFollow
 * Returns up to $limit users the viewer doesn't follow yet, ranked by:
 *   1. Friends-of-friends (people followed by people you follow)
 *   2. Authors of posts you liked but don't follow
 *   3. Fallback: random unrelated users
 *
 * @param PDO $pdo
 * @param int $UID
 * @param int $limit
 * @return array
 */
function GetSmartWhoToFollow(PDO $pdo, int $UID, int $limit = 5): array {

    $sql = "
        SELECT
            u.id, u.Fname, u.Lname, u.Username, u.ProfilePic, u.IsBlueTick,
            SUM(
                CASE WHEN fof.UID IS NOT NULL THEN 3 ELSE 0 END  /* friend-of-friend */
              + CASE WHEN la.AuthorUID IS NOT NULL THEN 2 ELSE 0 END  /* liked author */
            ) AS RelevanceScore
        FROM users u

        /* Friend-of-friend: people followed by users the viewer follows */
        LEFT JOIN (
            SELECT f2.UserID AS UID
            FROM followers f1
            INNER JOIN followers f2 ON f2.FollowerID = f1.UserID
            WHERE f1.FollowerID = ?
              AND f2.UserID != ?
        ) fof ON fof.UID = u.id

        /* Liked authors: authors whose posts the viewer liked but doesn't follow */
        LEFT JOIN (
            SELECT DISTINCT p.UID AS AuthorUID
            FROM likes lk
            INNER JOIN posts p ON p.id = lk.PostID
            WHERE lk.UID = ?
              AND p.UID != ?
        ) la ON la.AuthorUID = u.id

        WHERE
            u.id != ?
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

    // If not enough smart suggestions, pad with random users
    if (count($results) < $limit) {
        $existingIDs = array_column($results, 'id');
        $existingIDs[] = $UID;
        $exclude = implode(',', array_map('intval', $existingIDs));
        $needed  = $limit - count($results);

        $fallback = $pdo->prepare(
            "SELECT id, Fname, Lname, Username, ProfilePic, IsBlueTick
             FROM users
             WHERE id NOT IN ($exclude)
               AND id NOT IN (SELECT UserID FROM followers WHERE FollowerID = ?)
             ORDER BY RAND()
             LIMIT $needed"
        );
        $fallback->execute([$UID]);
        $results = array_merge($results, $fallback->fetchAll(PDO::FETCH_ASSOC));
    }

    return $results;
}
