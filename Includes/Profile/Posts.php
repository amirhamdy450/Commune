<?php



    $sql = "SELECT 
                posts.id AS PID ,
                posts.*, users.* ,
                CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
                FROM posts 
                INNER JOIN users ON posts.UID=users.id 
                LEFT JOIN likes ON posts.id=likes.PostID AND likes.UID=users.id
                WHERE posts.Status=1 AND posts.UID=?
                ORDER BY posts.Date DESC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ProfileUserID]);
    $FeedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($FeedPosts as $FeedPost) {

        /* $FeedPostID = 'D' . $FeedPost['Date'] . 'I' . $FeedPost['PID'];  // D is  for Date, I is for ID */
        /* convert Date to a Unix timestamp */
        $timestamp = strtotime($FeedPost['Date']);
        

        $encryptedFeedPostID = Encrypt($FeedPost['PID'],"Positioned",["Timestamp"=>$timestamp]); // Makes it JSON-safe





        echo '<div class="FeedPost" PID=' . $encryptedFeedPostID . '>
                        <div class="FeedPostHeader">
                            <a class="FeedPostAuthor" href="">
                                <img src="Imgs/Icons/unknown.png" alt="">
                                <p>' . $FeedPost['Fname'] . ' ' . $FeedPost['Lname'] . '</p>
                            </a>';


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
                        echo '<a class="FeedPostLink" href="http://localhost/projects/commune/' . $DocumentPath . '">
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
