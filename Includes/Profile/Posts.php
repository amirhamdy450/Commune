<?php
include_once $PATH . 'Includes/Components/PostCard.php';
include_once $PATH . 'Includes/Components/EmptyState.php';

$params = ['Timestamp' => time()];
$EncryptedProfileUID = Encrypt($ProfileUserID, 'Positioned', $params);

echo '<input type="hidden" id="UserProfileID" value="' . $EncryptedProfileUID . '">';

$sql = "SELECT 
            posts.id AS PID,
            posts.*,
            users.*,
            CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
            CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved
        FROM posts
        INNER JOIN users ON posts.UID = users.id
        LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
        LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
        WHERE posts.Status = 1 AND posts.UID = ?
        ORDER BY posts.Date DESC
        LIMIT 5";

$stmt = $pdo->prepare($sql);
$stmt->execute([$UID, $UID, $ProfileUserID]);
$FeedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($FeedPosts)) {
    RenderEmptyState('Imgs/Icons/no-posts.svg', 'No posts yet', 'When posts are shared, they\'ll appear here.');
}

foreach ($FeedPosts as $FeedPost) {
    $PostViewModel = BuildPostCardViewModel($FeedPost, $UID);
    RenderPostCard($PostViewModel, [
        'ProfileHrefMode' => 'redirected',
        'OpenDocumentsInNewTab' => false,
    ]);
}
?>

<div class="FeedLoader hidden" id="ProfilePostLoader">
    <div class="Loader"></div>
</div>
