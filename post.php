<?php
include 'Includes/UserValidation.php';

//check if url has a pid param to show a specific post

$PostID_Token= openssl_decrypt(base64_decode($PostID), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);

if($PostID_Token===false){
    header("Location: 404.php");
    exit();
}


$PostIDPosition = strpos($PostID_Token, 'I');
$PostID=(int)substr($PostID_Token, $PostIDPosition + 1);


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Community</title>

</head>

<body>


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
            INNER JOIN users ON posts.UID=users.id 
            WHERE posts.id=? AND posts.Status=1 
            LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$UID,$PostID]);
            $FeedPost = $stmt->fetch(PDO::FETCH_ASSOC);


            //Check for comments too
             $sql='SELECT comments.id as CID,comments.*,users.* ,
            CASE WHEN CL.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
            FROM comments 
            INNER JOIN users ON comments.UID=users.id 
            LEFT JOIN comments_likes CL ON comments.id=CL.CommentID
            WHERE comments.PostID=? ';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$PostID]);
            $Comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($FeedPost) {
                
                $FeedPostID = 'D' . $FeedPost['Date'] . 'I' . $FeedPost['PID'];  // D is  for Date, I is for ID

                $encryptedFeedPostID =  base64_encode(openssl_encrypt($FeedPostID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv));;



                /*   $decrypted=  openssl_decrypt($encryptedFeedPostID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);
                echo $decrypted; */


                echo '<div class="FeedPost Opened" PID=' . $encryptedFeedPostID . '>
                        <div class="FeedPostHeader">
                            <img src="Imgs/Icons/unknown.png" alt="">
                            <p>' . $FeedPost['name'] . '</p>';
                            if((int)$UserData['Privilege']===1){
                                echo '<div class="DeleteBtn PostDeleteBtn">
                                <img src="Imgs/Icons/trash.png" alt="">
                                </div>';
                            }

                        echo'</div>

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
                            echo '<a class="FeedPostLink" href="http://localhost/projects/commune/' . $DocumentPath . '">
                            <div class="UploadedFile">
                                <img src="Imgs/Icons/document.png" >
                                <p>'. $document . '</p>
                            </div>
                            </a>';
                        }
                    }
                }


                if($FeedPost['liked']){

                    $LikeIcon='Imgs/Icons/liked.svg';

                }else{

                    $LikeIcon='Imgs/Icons/like.svg';
                }

                echo ' </div>
                    <div class="FeedPostInteractionCounters">
                        <p><span class="PostLikesCNT">' . $FeedPost['LikeCounter'] . '</span>  likes</p>

                        <p>' . $FeedPost['CommentCounter'] . ' Comments</p>
                    </div>
                    
                    <div class="FeedPostInteractions">
                        <div class="Interaction FeedPostLike">
                            <img src="'.$LikeIcon.'">
                            Like
                        </div>
                        
                        <div class="Interaction FeedPostComment Disabled">
                            <img src="Imgs/Icons/comment.svg">
                            Comment
                        </div>

                        <div class="Interaction FeedPostShare">
                            <img src="Imgs/Icons/share.svg">
                            Share
                        
                        </div>

                    </div>
                ';
            }

            ?>

            <div class="Cont">
                <div class="ModalCommentsContainer">
                    <?php 

                    
                        if($Comments){

                            foreach($Comments as $Comment){
                                $timestamp = strtotime($Comment['Date']);
                                $FormattedID = 'D'.$timestamp.'I'.$Comment['CID'];

                                $encrypted = openssl_encrypt($FormattedID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);
                                $CommentID = base64_encode($encrypted); // Makes it JSON-safe


                                if($Comment['liked']){
                                    $likeIcon='Imgs/Icons/liked.svg';
                                }else{
                                    $likeIcon='Imgs/Icons/like.svg';
                                }
                                
                                echo '
                                <div class="CommentContainer" cid="'.$CommentID.'">
                                <div class="ModalComment">
                                    <div class="ModalCommentHeader">
                                    <img src="Imgs/Icons/unknown.png" alt="">
                                    <p>'.$Comment['name'].'</p>
                                    </div>
                                    <div class="ModalCommentContent">
                                    <p>'.$Comment['comment'].'</p>
                                    </div>
                                </div>

                                <div class="FeedPostInteractions">
                                    <div class="Interaction FeedPostLike">
                                        <img src="'.$LikeIcon.'">
                                        <p class="CommentLikesCNT">'.$Comment['LikeCounter'].'</p>
                                    </div>
                                    
                                    <div class="Interaction FeedPostComment">
                                        <img src="Imgs/Icons/comment.svg">
                                        Reply
                                    </div>



                                </div>

                                
                                <div class="ViewRepliesBtn">500 Replies</div>

                                <div class="RepliesContainer hidden">

                                    <div class="CommentContainer" cid="${comment.CID}">
                                    <div class="ModalComment">
                                        <div class="ModalCommentHeader">
                                        <img src="Imgs/Icons/unknown.png" alt="">
                                        <p>${comment.name}</p>
                                        </div>
                                        <div class="ModalCommentContent">
                                        <p>${comment.comment}</p>
                                        </div>
                                    </div>

                                    <div class="FeedPostInteractions">
                                        <div class="Interaction FeedPostLike">
                                            <img src="${likeIcon}">
                                            <p class="CommentLikesCNT">${comment.LikeCounter}</p>
                                        </div>
                                        
                                        <div class="Interaction FeedPostComment">
                                            <img src="Imgs/Icons/comment.svg">
                                            Comment
                                        </div>



                                    </div>
                                    </div>

                                </div>

                                </div>
                                ';
                            }

                        }else{
                            echo '<div class="InfoBox">
                                <p>No Comments Found !</p>
                            </div>';
                        }
                    
                    
                    ?>

                </div>

            </div>
                
                <form class="CreateModalComment" id="CreateNewComment">
                    <textarea class="CommentInput" placeholder="Add a comment" rows="2"></textarea>
                    <input type="submit" value="" class="BrandBtn CommentSubmitBtn">

                </form>


        </div>
        </div>




    </div>


    <?php include 'Includes/Modals/CreatePost.php' ;?>
    <?php include 'Includes/Modals/Confirmation.php' ;?>






    <script src="Scripts/modal.js"></script>
    <script src="Scripts/script.js"></script>
</body>

</html>