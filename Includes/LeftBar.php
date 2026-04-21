<?php
// Sidebar counts for the profile card and activity snapshot
$stmtCounts = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM followers WHERE FollowerID = ?) AS FollowingCount,
        (SELECT COUNT(*) FROM followers WHERE UserID = ?)    AS FollowerCount,
        (SELECT COUNT(*) FROM posts WHERE UID = ? AND Status = 1) AS PostCount,
        (SELECT COUNT(*) FROM saved_posts WHERE UID = ?) AS SavedCount,
        (SELECT COUNT(*) FROM notifications WHERE ToUID = ? AND IsRead = 0) AS UnreadNotifCount,
        (SELECT COUNT(*) FROM pages WHERE OwnerUID = ?) AS PageCount"
);
$stmtCounts->execute([$UID, $UID, $UID, $UID, $UID, $UID]);
$LeftBarCounts = $stmtCounts->fetch(PDO::FETCH_ASSOC);

$LeftBarProfilePic = (!empty($User['ProfilePic']))
    ? 'MediaFolders/profile_pictures/' . htmlspecialchars($User['ProfilePic'])
    : 'Imgs/Icons/unknown.png';

// Fetch user's pages (owned)
$sqlMyPages = "SELECT id, Name, Handle, Logo FROM pages WHERE OwnerUID = ? ORDER BY CreatedAt DESC LIMIT 5";
$stmtMyPages = $pdo->prepare($sqlMyPages);
$stmtMyPages->execute([$UID]);
$MyPages = $stmtMyPages->fetchAll(PDO::FETCH_ASSOC);

// Trending topics — top keywords from posts in the last 48 hours by engagement
$sqlTrending = "SELECT Content, UID, LikeCounter, CommentCounter FROM posts
                WHERE Status = 1 AND Date >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
                ORDER BY Date DESC
                LIMIT 150";
$stmtTrending = $pdo->prepare($sqlTrending);
$stmtTrending->execute();
$trendingPosts = $stmtTrending->fetchAll(PDO::FETCH_ASSOC);

// Extract meaningful words (3+ chars, skip stop words)
$StopWords = ['the','is','a','an','and','or','but','for','of','at','by','to','in','on',
              'with','as','new','my','his','her','it','i','you','he','she','we','they',
              'what','which','who','so','that','this','be','from','just','got','really',
              'good','very','nice','have','want','need','was','were','had','not','can',
              'could','would','should','will','do','does','did','get','make','go','more',
              'most','here','there','when','where','how','then','than','now','today','its',
              'are','our','your','their','been','has','about','also','than','even','some',
              'all','out','up','one','two','three','first','last','after','before'];

$TopicStats = [];
foreach ($trendingPosts as $post) {
    $content = $post['Content'] ?? '';
    $authorId = (int)($post['UID'] ?? 0);
    $engagementWeight = 1 + min(3, ((int)($post['LikeCounter'] ?? 0) + (int)($post['CommentCounter'] ?? 0)) / 10);

    $words = preg_split('/\s+/', strtolower(preg_replace('/[^a-zA-Z0-9\s]/u', '', $content)));
    $words = array_unique(array_filter($words));

    foreach ($words as $word) {
        $word = trim($word);
        if (strlen($word) < 3 || is_numeric($word) || in_array($word, $StopWords, true)) continue;

        if (!isset($TopicStats[$word])) {
            $TopicStats[$word] = [
                'posts' => 0,
                'authors' => [],
                'score' => 0,
            ];
        }

        $TopicStats[$word]['posts']++;
        $TopicStats[$word]['authors'][$authorId] = true;
        $TopicStats[$word]['score'] += $engagementWeight;
    }
}

$FilteredTopics = [];
foreach ($TopicStats as $word => $stats) {
    $authorCount = count($stats['authors']);
    if ($stats['posts'] < 3 || $authorCount < 2) continue;

    $FilteredTopics[$word] = [
        'posts' => $stats['posts'],
        'authors' => $authorCount,
        'score' => $stats['score'],
    ];
}

uasort($FilteredTopics, function ($a, $b) {
    if ($a['score'] === $b['score']) {
        if ($a['posts'] === $b['posts']) {
            return $b['authors'] <=> $a['authors'];
        }
        return $b['posts'] <=> $a['posts'];
    }
    return $b['score'] <=> $a['score'];
});

$TrendingTopics = array_slice(array_keys($FilteredTopics), 0, 6);
?>

<div class="LeftSidebar">

    <!-- Mini profile card -->
    <a href="index.php?redirect=self" class="LeftBarProfileCard">
        <img src="<?php echo $LeftBarProfilePic; ?>" alt="" class="LeftBarProfileAvatar">
        <div class="LeftBarProfileInfo">
            <div class="LeftBarProfileName">
                <?php echo htmlspecialchars($User['Fname'] . ' ' . $User['Lname']); ?>
                <?php if (!empty($User['IsBlueTick'])): ?>
                    <span class="BlueTick" title="Verified"></span>
                <?php endif; ?>
            </div>
            <div class="LeftBarProfileHandle">@<?php echo htmlspecialchars($User['Username']); ?></div>
            <div class="LeftBarProfileStats">
                <span><strong><?php echo (int)$LeftBarCounts['FollowingCount']; ?></strong> Following</span>
                <span><strong><?php echo (int)$LeftBarCounts['FollowerCount']; ?></strong> Followers</span>
            </div>
        </div>
    </a>

    <div class="LeftBarSection">
        <h4 class="LeftBarSectionTitle">Quick Access</h4>

        <div class="LeftBarNavList">
            <a href="index.php" class="LeftBarNavItem Active">
                <span class="LeftBarNavIcon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </span>
                <span class="LeftBarNavBody">
                    <span class="LeftBarNavLabel">Home Feed</span>
                    <span class="LeftBarNavHint">Catch up with new posts</span>
                </span>
            </a>

            <a href="index.php?redirect=saved" class="LeftBarNavItem">
                <span class="LeftBarNavIcon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="LeftBarNavBody">
                    <span class="LeftBarNavLabel">Saved Posts</span>
                    <span class="LeftBarNavHint">Your private collection</span>
                </span>
                <span class="LeftBarNavBadge"><?php echo (int)$LeftBarCounts['SavedCount']; ?></span>
            </a>

            <a href="index.php?target=settings" class="LeftBarNavItem">
                <span class="LeftBarNavIcon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </span>
                <span class="LeftBarNavBody">
                    <span class="LeftBarNavLabel">Settings</span>
                    <span class="LeftBarNavHint">Privacy, account and security</span>
                </span>
            </a>
        </div>
    </div>

    <div class="LeftBarSection">
        <h4 class="LeftBarSectionTitle">Your Activity</h4>

        <div class="LeftBarMetrics">
            <div class="LeftBarMetric">
                <span class="LeftBarMetricValue"><?php echo (int)$LeftBarCounts['PostCount']; ?></span>
                <span class="LeftBarMetricLabel">Posts</span>
            </div>
            <div class="LeftBarMetric">
                <span class="LeftBarMetricValue"><?php echo (int)$LeftBarCounts['SavedCount']; ?></span>
                <span class="LeftBarMetricLabel">Saved</span>
            </div>
            <div class="LeftBarMetric">
                <span class="LeftBarMetricValue"><?php echo (int)$LeftBarCounts['PageCount']; ?></span>
                <span class="LeftBarMetricLabel">Pages</span>
            </div>
            <div class="LeftBarMetric">
                <span class="LeftBarMetricValue"><?php echo (int)$LeftBarCounts['UnreadNotifCount']; ?></span>
                <span class="LeftBarMetricLabel">Unread</span>
            </div>
        </div>

        <p class="LeftBarSectionText">
            <?php if ((int)$LeftBarCounts['UnreadNotifCount'] > 0): ?>
                You have <?php echo (int)$LeftBarCounts['UnreadNotifCount']; ?> unread notification<?php echo (int)$LeftBarCounts['UnreadNotifCount'] === 1 ? '' : 's'; ?> waiting for you.
            <?php elseif ((int)$LeftBarCounts['PostCount'] > 0 || (int)$LeftBarCounts['SavedCount'] > 0 || (int)$LeftBarCounts['PageCount'] > 0): ?>
                You have already started building your space here. New likes, comments, follows, and alerts will appear as activity comes in.
            <?php else: ?>
                Your account is quiet right now. Fresh interactions will show up here as people engage with you.
            <?php endif; ?>
        </p>
    </div>

    <!-- Trending Topics -->
    <div class="LeftBarSection">
        <h4 class="LeftBarSectionTitle">Trending</h4>
        <?php if (!empty($TrendingTopics)): ?>
            <div class="TrendingList">
                <?php foreach ($TrendingTopics as $topic): ?>
                    <a href="index.php?target=search&query=<?php echo urlencode($topic); ?>" class="TrendingTopic">
                        <span class="TrendingHash">#</span><?php echo htmlspecialchars($topic); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="LeftBarSectionText">As more public posts roll in, the strongest topics in your network will show up here.</p>
        <?php endif; ?>
    </div>

    <!-- My Pages -->
    <div class="LeftBarSection">
        <h4 class="LeftBarSectionTitle">Your Pages</h4>

        <?php if (!empty($MyPages)): ?>
            <div class="LeftBarPageList">
                <?php foreach ($MyPages as $page): ?>
                    <?php $pageLogo = $page['Logo'] ? 'MediaFolders/page_logos/' . htmlspecialchars($page['Logo']) : null; ?>
                    <a href="index.php?target=page&handle=<?php echo urlencode($page['Handle']); ?>" class="LeftBarPageItem">
                        <?php if ($pageLogo): ?>
                            <img src="<?php echo $pageLogo; ?>" alt="" class="LeftBarPageLogo">
                        <?php else: ?>
                            <div class="LeftBarPageLogoPlaceholder"><?php echo mb_strtoupper(mb_substr($page['Name'], 0, 1)); ?></div>
                        <?php endif; ?>
                        <span class="LeftBarPageMeta">
                            <span class="LeftBarPageName"><?php echo htmlspecialchars($page['Name']); ?></span>
                            <span class="LeftBarPageHandle">@<?php echo htmlspecialchars($page['Handle']); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <p class="LeftBarSectionText">
                <?php echo (int)$LeftBarCounts['PageCount']; ?> page<?php echo (int)$LeftBarCounts['PageCount'] === 1 ? '' : 's'; ?> under your control.
            </p>
        <?php else: ?>
            <div class="LeftBarEmptyState">
                <div class="LeftBarEmptyTitle">Create your first page</div>
                <p>Perfect for a business, a creator identity, a community, or any project that deserves its own voice.</p>
                <button type="button" class="LeftBarMiniBtn" data-open-create-page>Create a Page</button>
            </div>
        <?php endif; ?>
    </div>
</div>
