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
include_once $PATH.'Includes/FeedAlgorithm.php';
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

        <?php include 'Includes/LeftBar.php'; ?>

        <div class="FeedContainer">
            <?php

            // Personalized feed — served from cache if fresh, rebuilt if stale
            $FeedResult = GetPersonalizedFeed($pdo, $UID, 0);
            $FeedPosts  = $FeedResult['posts'];

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
                            $DocExt = strtoupper(pathinfo($document, PATHINFO_EXTENSION));
                            $DocName = htmlspecialchars(pathinfo($document, PATHINFO_FILENAME));
                            echo '<a class="FeedPostLink" href="' . APP_URL . '/' . $DocumentPath . '" target="_blank" rel="noopener">
                                <div class="UploadedFile">
                                    <div class="UploadedFileIcon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                                    </div>
                                    <div class="UploadedFileBody">
                                        <div class="UploadedFileName">' . $DocName . '</div>
                                        <div class="UploadedFileExt">' . $DocExt . ' Document</div>
                                    </div>
                                    <svg class="UploadedFileArrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
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