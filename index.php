<?php
$PATH = '';

include $PATH . 'Includes/RouteController.php';
include $PATH . 'Includes/UserAuth.php';
include_once $PATH . 'Includes/Encryption.php';
include_once $PATH . 'Includes/FeedAlgorithm.php';
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
    <link rel="stylesheet" href="Styles/PageProfile.css">
    <link rel="stylesheet" href="Styles/PostView.css">
    <title>Community</title>
</head>

<body class="Posts FetchPostsOnScroll">

    <?php include 'Includes/NavBar.php'; ?>

    <div class="FlexContainer">

        <?php include 'Includes/LeftBar.php'; ?>

        <div class="FeedContainer">
            <?php
            $FeedResult = GetPersonalizedFeed($pdo, $UID, 0);
            $FeedPosts  = $FeedResult['posts'];

            $PostProfilePic = (isset($User['ProfilePic']) && !empty($User['ProfilePic']))
                ? 'MediaFolders/profile_pictures/' . htmlspecialchars($User['ProfilePic'])
                : 'Imgs/Icons/unknown.png';

            if (!empty($FeedPosts)) {
                foreach ($FeedPosts as $FeedPost) {
                    $PostViewModel = BuildPostCardViewModel($FeedPost, $UID);
                    RenderPostCard($PostViewModel);
                }
            } else {
                echo '<div class="FeedEmptyState" id="FeedEmptyState">
                    <img src="Imgs/Icons/no-posts.svg" alt="">
                    <h3>Nothing here yet</h3>
                    <p>Follow people or pages to start seeing posts in your feed.</p>
                </div>';
            }
            ?>

            <div class="FeedLoader hidden">
                <div class="Loader"></div>
            </div>
        </div>

        <?php include 'Includes/RightBar.php'; ?>

    </div>

    <div class="InfoBox"></div>

    <?php include 'Includes/Modals/CreatePost.php'; ?>
    <?php include 'Includes/Modals/CommentSection.php'; ?>
    <?php include 'Includes/Modals/Confirmation.php'; ?>
    <?php include 'Includes/Modals/CreateOrg.php'; ?>

    <script src="Scripts/modal.js"></script>
    <script type="module" src="Scripts/Feed.js"></script>
    <script type="module" src="Scripts/Org.js"></script>
</body>

</html>
