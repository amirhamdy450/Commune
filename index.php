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
include $PATH.'Includes/UserValidation.php';
$DocumentExtensions = '.pdf, .doc, .docx, .txt ,.xls,.xlsx,.ppt,.pptx';


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Feed.css">
    <title>Community</title>

</head>

<body class="Posts FetchPostsOnScroll">


    <!-- Action Menu -->
    <?php include 'Includes/NavBar.php'; ?>






    <div class="FlexContainer">

        <div class="FeedContainer">
            <?php

            $sql = "SELECT 
            posts.id AS PID ,
            posts.*, users.* ,
            CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
            FROM posts 
            LEFT JOIN likes ON posts.id=likes.PostID AND likes.UID=?
            INNER JOIN users ON posts.UID=users.id WHERE posts.Status=1 
            ORDER BY posts.Date DESC LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$UID]);
            $FeedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($FeedPosts as $FeedPost) {

                $FeedPostID = 'D' . $FeedPost['Date'] . 'I' . $FeedPost['PID'];  // D is  for Date, I is for ID

                $encryptedFeedPostID =  base64_encode(openssl_encrypt($FeedPostID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv));;



                /*   $decrypted=  openssl_decrypt($encryptedFeedPostID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);
                echo $decrypted; */


                echo '<div class="FeedPost" PID=' . $encryptedFeedPostID . '>
                    <div class="FeedPostHeader">
                        <img src="Imgs/Icons/unknown.png" alt="">
                        <p>' . $FeedPost['name'] . '</p>';
                if ((int)$UserData['Privilege'] === 1) {
                    echo '<div class="DeleteBtn PostDeleteBtn">
                            <img src="Imgs/Icons/trash.png" alt="">
                            </div>';
                }

                echo '</div>

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







    </div>


    <div class="InfoBox"></div>

    <?php include 'Includes/Modals/CreatePost.php'; ?>
    <?php include 'Includes/Modals/CommentSection.php'; ?>
    <?php include 'Includes/Modals/Confirmation.php'; ?>




    <script src="Scripts/modal.js"></script>
    <script type="module" src="Scripts/script.js"></script>
</body>

</html>