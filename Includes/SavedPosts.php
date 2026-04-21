<?php
if (!isset($PATH)) {
    $PATH = '';
}

include $PATH . 'Includes/UserAuth.php';
include_once $PATH . 'Includes/Encryption.php';
include_once $PATH . 'Includes/Components/PostCard.php';
$DocumentExtensions = '.pdf, .doc, .docx, .txt ,.xls,.xlsx,.ppt,.pptx';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $CsrfToken; ?>">
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Feed.css">
    <title>My Saved Posts</title>
</head>
<body class="Posts">
    <?php include 'Includes/NavBar.php'; ?>

    <div class="FlexContainer">
        <div class="FeedContainer" id="SavedPostsContainer">
            <h2 style="text-align: center; width: 100%; margin: 15px 0;">My Saved Posts</h2>
            <?php
            $sql = "SELECT 
                        posts.id AS PID,
                        posts.*,
                        users.*,
                        CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                        CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                        TRUE AS saved
                    FROM saved_posts sp
                    INNER JOIN posts ON sp.PostID = posts.id
                    INNER JOIN users ON posts.UID = users.id
                    LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?
                    LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                    LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                    WHERE sp.UID = ? AND posts.Status = 1 AND b.id IS NULL
                    ORDER BY sp.SavedOn DESC
                    LIMIT 5";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$UID, $UID, $UID, $UID]);
            $FeedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($FeedPosts) {
                foreach ($FeedPosts as $FeedPost) {
                    $PostViewModel = BuildPostCardViewModel($FeedPost, $UID);
                    RenderPostCard($PostViewModel);
                }
            } else {
                echo '<div class="FeedEmptyState" id="SavedEmptyState">
                    <img src="Imgs/Icons/no-saved.svg" alt="">
                    <h3>No saved posts yet</h3>
                    <p>Bookmark posts you want to come back to and they\'ll appear here.</p>
                </div>';
            }
            ?>
            <div class="FeedLoader hidden" id="SavedPostsLoader">
                <div class="Loader"></div>
            </div>
        </div>
    </div>

    <div class="InfoBox"></div>

    <?php include 'Includes/Modals/CreatePost.php'; ?>
    <?php include 'Includes/Modals/CommentSection.php'; ?>
    <?php include 'Includes/Modals/Confirmation.php'; ?>

    <script src="Scripts/modal.js"></script>
    <script type="module" src="Scripts/Feed.js"></script>
    <script type="module" src="Scripts/SavedPosts.js"></script>
</body>
</html>
