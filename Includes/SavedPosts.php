<?php
// This is the new file: Includes/SavedPosts.php

if(!isset($PATH)){
    $PATH = '';  // Set path since this is included by RouteController
}

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
    <title>My Saved Posts</title>
</head>
<body class="Posts"> <?php include 'Includes/NavBar.php'; ?>

    <div class="FlexContainer">
        <div class="FeedContainer" id="SavedPostsContainer">
            <h2 style="text-align: center; width: 100%; margin: 15px 0;">My Saved Posts</h2>
            <?php

            // THIS IS THE MODIFIED QUERY
            $sql = "SELECT 
                posts.id AS PID,
                posts.*, 
                users.*,
                CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                TRUE AS saved -- It's a saved post, so this is always true
                FROM saved_posts sp
                INNER JOIN posts ON sp.PostID = posts.id
                INNER JOIN users ON posts.UID = users.id
                LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?
                LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                WHERE sp.UID = ? AND posts.Status = 1 AND b.id IS NULL
                ORDER BY sp.SavedOn DESC LIMIT 5"; // Order by when you saved it
            
            $stmt = $pdo->prepare($sql);
            // All params are the current user's ID
            $stmt->execute([$UID, $UID, $UID, $UID]);
            $FeedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($FeedPosts) {
                foreach ($FeedPosts as $FeedPost) {

                    $IsSelfPost = (int)($UID == $FeedPost['UID']);
                    $IsSavedPost = (int)true; // Always true here

                    $params=["Timestamp"=> strtotime($FeedPost['Date'])];
                    $encryptedFeedPostID=Encrypt($FeedPost['PID'],"Positioned",$params);
                    $encryptedUserID=Encrypt($FeedPost['UID'],"Positioned",$params);


                    if (isset($FeedPost['ProfilePic']) && !empty($FeedPost['ProfilePic'])) {
                        $PostProfilePic = 'MediaFolders/profile_pictures/' . htmlspecialchars($FeedPost['ProfilePic']);
                    } else {
                        $PostProfilePic = 'Imgs/Icons/unknown.png'; // Fallback
                    }

                    $PostTimestamp = strtotime($FeedPost['Date']);
                    // This post-rendering loop is identical to index.php
                    echo '<div class="FeedPost" PID="' . $encryptedFeedPostID . '" UID="' . $encryptedUserID . '" Self="' . $IsSelfPost . '" Saved="' . $IsSavedPost . '">
                        <div class="FeedPostHeader">
                            <div class="FeedPostAuthorContainer">
                                <a class="FeedPostAuthor" href="index.php?target=profile&uid=' . urlencode($encryptedUserID). '">
                                    <img src="' . $PostProfilePic . '" alt="Profile Picture">
                                    <div class="FeedPostAuthorInfo">
                                        <div class="FeedPostNameRow">
                                            <p class="FeedPostAuthorName">' . htmlspecialchars($FeedPost['Fname'] . ' ' . $FeedPost['Lname']) . '</p>
                                            <span class="FeedPostTime" data-date="' . $PostTimestamp . '"></span>
                                        </div>
                                        <span class="FeedPostUsername">@' . htmlspecialchars($FeedPost['Username']) . '</span>
                                    </div>
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
                        $Media = scandir($MediaFolder); 
                        $MediaType = (int)$FeedPost['Type'];  
                        if ($MediaType === 2) {
                            foreach ($Media as $image) {
                                if (in_array(strtolower($image), ['.', '..'])) { continue; }
                                $ImagePath = $MediaFolder . '/' . $image;
                                echo '<img src="' . $ImagePath . '" alt="">';
                            }
                        } else if ($MediaType === 3) {
                            foreach ($Media as $document) {
                                if (in_array(strtolower($document), ['.', '..'])) { continue; }
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
            } else {
                // Show a message if no saved posts are found
                echo '<p style="font-style:italic; color:gray; text-align:center; margin-top:20px;">You haven\'t saved any posts yet.</p>';
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