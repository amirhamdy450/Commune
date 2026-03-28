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
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Feed.css">
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
                CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved
                FROM posts 
                INNER JOIN users ON posts.UID = users.id
                LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?
                LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                WHERE posts.Status = 1 AND b.id IS NULL
                ORDER BY posts.Date DESC 
                LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$UID, $UID, $UID, $UID]);
            $FeedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    $PostProfilePic = 'MediaFolders/profile_pictures/' . htmlspecialchars($FeedPost['ProfilePic']);
                } else {
                    $PostProfilePic = 'Imgs/Icons/unknown.png'; // Fallback
                }
                // --- END N

                /*   $decrypted=  openssl_decrypt($encryptedFeedPostID, 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV);
                echo $decrypted; */

                echo '<div class="FeedPost" PID=' . $encryptedFeedPostID . ' UID=' . $encryptedUserID . ' Self=' . $IsSelfPost . ' Saved=' . $IsSavedPost . '>
                    <div class="FeedPostHeader">
                        <div class="FeedPostAuthorContainer">
                            <a class="FeedPostAuthor" href="index.php?target=profile&uid=' . urlencode($encryptedUserID). '">
                                <img src="' . $PostProfilePic . '" alt="Profile Picture">                        
                                 <p>' . $FeedPost['Fname'] . ' ' . $FeedPost['Lname'] . '</p>
                            </a>';

                            if(!$IsSelfPost){
                                echo '<button class="BrandBtn FollowBtn ' . ($FeedPost['following'] ? 'Followed' : '') . '" uid="' . $encryptedUserID . '"> ' . ($FeedPost['following'] ? 'Following' : 'Follow') . '</button>';
                            }
                    echo '</div>

                        <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>
                        
                    </div>

                    <div class="FeedPostContent">
                        <p>' . $FeedPost['Content'] . '</p>';

                $MediaFolder = $FeedPost['MediaFolder'];
                if (is_dir($MediaFolder)) {
                    $Media = scandir($MediaFolder); //scan the entire folder
                    $MediaType = (int)$FeedPost['Type'];  //scan the type
                    if ($MediaType === 2) {
                        foreach ($Media as $image) {
                            if (in_array(strtolower($image), ['.', '..'])) { //this to ignore dots that are treated as files in scandir , (.) represents current directory and (..) represents parent directory

                                continue;  //skip this iteration
                            }


                            $ImagePath = $MediaFolder . '/' . $image;
                            echo '<img src="' . $ImagePath . '" alt="">';
                        }
                    } else if ($MediaType === 3) {
                        foreach ($Media as $document) {
                            if (in_array(strtolower($document), ['.', '..'])) { //this to ignore dots that are treated as files in scandir , (.) represents current directory and (..) represents parent directory

                                continue;  //skip this iteration
                            }
                            $DocumentPath = $MediaFolder . '/' . $document;
                            echo '<a class="FeedPostLink" href="http://localhost/projects/igamify/' . $DocumentPath . '">
                            <div class="UploadedFile">
                                <img src="Imgs/Icons/Document.svg" >
                                <p>' . $document . ' </p>
                            </div>
                            </a>';
                        }
                    }
                }


                if ($FeedPost['liked']) {

                    $LikeIcon = 'Imgs/Icons/liked.svg';
                } else {

                    $LikeIcon = 'Imgs/Icons/like.svg';
                }

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




    <script src="Scripts/modal.js"></script>
    <script type="module" src="Scripts/Feed.js"></script>
</body>

</html>