<?php
$PATH = '';  //root path


/* //check if url has a pid param to show a specific post
if (isset($_GET['pid'])) {
    $PostID = $_GET['pid'];
    include 'post.php';
    die();
}
 */

include $PATH.'Includes/RouteController.php';
include $PATH.'Includes/UserAuth.php';
include_once $PATH.'Includes/Encryption.php';
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

<body class="Posts FetchPostsOnScroll" >


    <!-- Action Menu -->
    <?php include 'Includes/NavBar.php'; ?>






    <div class="FlexContainer">

        <div class="FeedContainer">
            <?php

            $sql = "SELECT
                posts.id AS PID,
                posts.*,
                users.*,
                CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved,
                pg.Name AS PageName,
                pg.Handle AS PageHandle,
                pg.Logo AS PageLogo,
                pg.IsVerified AS PageIsVerified
                FROM posts
                INNER JOIN users ON posts.UID = users.id
                LEFT JOIN pages pg ON posts.OrgID = pg.id
                LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?
                LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                LEFT JOIN followers f2 ON f2.UserID = ? AND f2.FollowerID = users.id
                LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                WHERE posts.Status = 1 AND b.id IS NULL
                AND (
                    posts.UID = ?
                    OR posts.OrgID IS NOT NULL
                    OR posts.Visibility = 0
                    OR (posts.Visibility = 1 AND f.UserID IS NOT NULL)
                    OR (posts.Visibility = 2 AND f2.UserID IS NOT NULL)
                    OR (posts.Visibility = 3 AND f.UserID IS NOT NULL AND f2.UserID IS NOT NULL)
                )
                ORDER BY posts.Date DESC
                LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$UID, $UID, $UID, $UID, $UID, $UID]);
            $FeedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Set logged-in user's profile pic once — used by CreatePost modal
            $PostProfilePic = (isset($User['ProfilePic']) && !empty($User['ProfilePic']))
                ? 'MediaFolders/profile_pictures/' . htmlspecialchars($User['ProfilePic'])
                : 'Imgs/Icons/unknown.png';

            foreach ($FeedPosts as $FeedPost) {

                //$FeedPostID = 'D' . $FeedPost['Date'] . 'I' . $FeedPost['PID'];  // D is  for Date, I is for ID

                $IsSelfPost = (int)false;
                if($UID == $FeedPost['UID']){
                    $IsSelfPost = (int) true;
                }

                $IsSavedPost = (int)false;
                if($FeedPost['saved']){
                    $IsSavedPost = (int) true;
                }


                $params=[
                    //convert Date to a Unix timestamp
                    "Timestamp"=> strtotime($FeedPost['Date'])
                ];

                $encryptedFeedPostID=Encrypt($FeedPost['PID'],"Positioned",$params);

                $encryptedUserID=Encrypt($FeedPost['UID'],"Positioned",$params);

                if (isset($FeedPost['ProfilePic']) && !empty($FeedPost['ProfilePic'])) {
                    $FeedPostProfilePic = 'MediaFolders/profile_pictures/' . htmlspecialchars($FeedPost['ProfilePic']);
                } else {
                    $FeedPostProfilePic = 'Imgs/Icons/unknown.png';
                }
                // --- END N

                /*   $decrypted=  openssl_decrypt($encryptedFeedPostID, 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV);
                echo $decrypted; */

                $PostTimestamp = strtotime($FeedPost['Date']);
                echo '<div class="FeedPost' . ($FeedPost['PageName'] ? ' PageFeedPost' : '') . '" PID="' . $encryptedFeedPostID . '" UID="' . $encryptedUserID . '" Self="' . $IsSelfPost . '" Saved="' . $IsSavedPost . '">
                    <div class="FeedPostHeader">
                        <div class="FeedPostAuthorContainer">';

                            if ($FeedPost['PageName']) {
                                $PageLogoSrc = $FeedPost['PageLogo'] ? 'MediaFolders/page_logos/' . htmlspecialchars($FeedPost['PageLogo']) : null;
                                echo '<a class="FeedPageBadge" href="index.php?target=page&handle=' . urlencode($FeedPost['PageHandle']) . '">';
                                if ($PageLogoSrc) {
                                    echo '<img class="FeedPageLogo" src="' . $PageLogoSrc . '" alt="">';
                                } else {
                                    echo '<div class="FeedPageLogoPlaceholder">' . mb_strtoupper(mb_substr($FeedPost['PageName'], 0, 1)) . '</div>';
                                }
                                echo '<div class="FeedPostAuthorInfo">
                                        <div class="FeedPostNameRow">
                                            <p class="FeedPostAuthorName">' . htmlspecialchars($FeedPost['PageName']) . '</p>
                                            ' . ($FeedPost['PageIsVerified'] ? '<span class="BlueTick" title="Verified"></span>' : '') . '
                                            <svg class="FeedPageIcon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Page"><path d="M3 2v12M3 2h8.5l-2 3.5 2 3.5H3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <span class="FeedPostTime" data-date="' . $PostTimestamp . '"></span>
                                        </div>
                                        <span class="FeedPostUsername">@' . htmlspecialchars($FeedPost['PageHandle']) . '</span>
                                    </div>
                                </a>';
                            } else {
                                echo '<a class="FeedPostAuthor" href="index.php?target=profile&uid=' . urlencode($encryptedUserID) . '">
                                    <img src="' . $FeedPostProfilePic . '" alt="Profile Picture">
                                    <div class="FeedPostAuthorInfo">
                                        <div class="FeedPostNameRow">
                                            <p class="FeedPostAuthorName">' . htmlspecialchars($FeedPost['Fname'] . ' ' . $FeedPost['Lname']) . '</p>
                                            ' . ($FeedPost['IsBlueTick'] ? '<span class="BlueTick" title="Verified"></span>' : '') . '
                                            <span class="FeedPostTime" data-date="' . $PostTimestamp . '"></span>
                                        </div>
                                        <span class="FeedPostUsername">@' . htmlspecialchars($FeedPost['Username']) . '</span>
                                    </div>
                                </a>';
                                if (!$IsSelfPost) {
                                    echo '<button class="BrandBtn FollowBtn ' . ($FeedPost['following'] ? 'Followed' : '') . '" uid="' . $encryptedUserID . '"> ' . ($FeedPost['following'] ? 'Following' : 'Follow') . '</button>';
                                }
                            }
                    echo '</div>

                        <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>

                    </div>

                    <div class="FeedPostContent">
                        <p>' . preg_replace('/@([\w]+)/', '<a class="MentionLink" href="index.php?target=profile&username=$1">@$1</a>', htmlspecialchars($FeedPost['Content'])) . '</p>';

                $MediaFolder = $FeedPost['MediaFolder'];
                if (is_dir($MediaFolder)) {
                    $Media = scandir($MediaFolder);
                    $MediaType = (int)$FeedPost['Type'];
                    if ($MediaType === 2) {
                        foreach ($Media as $image) {
                            if (in_array(strtolower($image), ['.', '..'])) continue;
                            $ImagePath = $MediaFolder . '/' . $image;
                            echo '<img src="' . $ImagePath . '" alt="">';
                        }
                    } else if ($MediaType === 3) {
                        foreach ($Media as $document) {
                            if (in_array(strtolower($document), ['.', '..'])) continue;
                            $DocumentPath = $MediaFolder . '/' . $document;
                            echo '<a class="FeedPostLink" href="' . APP_URL . '/' . $DocumentPath . '">
                            <div class="UploadedFile">
                                <img src="Imgs/Icons/Document.svg" >
                                <p>' . $document . ' </p>
                            </div>
                            </a>';
                        }
                    }
                }

                $LikeIcon = $FeedPost['liked'] ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';

echo ' </div>
                    <div class="FeedPostInteractionCounters">
                        <p><span class="PostLikesCNT">' . $FeedPost['LikeCounter'] . '</span>  likes</p>

                        <p>' . $FeedPost['CommentCounter'] . ' Comments</p>
                    </div>
                    
                    <div class="FeedPostInteractions">
                        <div class="Interaction FeedPostLike">
                            <img src="' . $LikeIcon . '">
                            Like
                        </div>
                        
                        <div class="Interaction FeedPostComment">
                            <img src="Imgs/Icons/comment.svg">
                            Comment
                        </div>

                        <div class="Interaction FeedPostShare">
                            <img src="Imgs/Icons/share.svg">
                            Share
                        
                        </div>

                    </div>
                </div>';
            }

            ?>

            <div class="FeedLoader">
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