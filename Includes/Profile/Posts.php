<?php
    // 1. Prepare the Target User ID for the JS (CRITICAL FIX)
    // We use the $ProfileUserID which is available in this scope
    $params = ["Timestamp" => time()];
    $EncryptedProfileUID = Encrypt($ProfileUserID, "Positioned", $params);

    echo '<input type="hidden" id="UserProfileID" value="' . $EncryptedProfileUID . '">';

    // 2. Updated Query to fetch 'saved' status
    $sql = "SELECT 
                posts.id AS PID ,
                posts.*, users.* ,
                CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved
                FROM posts 
                INNER JOIN users ON posts.UID=users.id 
                LEFT JOIN likes ON posts.id=likes.PostID AND likes.UID=?
                LEFT JOIN saved_posts sp ON posts.id=sp.PostID AND sp.UID=?
                WHERE posts.Status=1 AND posts.UID=?
                ORDER BY posts.Date DESC LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    // Params: [LoggedInUser, LoggedInUser, ProfileUser]
    $stmt->execute([$UID, $UID, $ProfileUserID]);
    
    $FeedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($FeedPosts)) {
        echo '<div class="ProfileEmptyState">
            <img src="Imgs/Icons/no-posts.svg" alt="">
            <h3>No posts yet</h3>
            <p>When posts are shared, they\'ll appear here.</p>
        </div>';
    }

    foreach ($FeedPosts as $FeedPost) {

        $timestamp = strtotime($FeedPost['Date']);
        $encryptedFeedPostID = Encrypt($FeedPost['PID'],"Positioned",["Timestamp"=>$timestamp]); 
        $encryptedUserID = Encrypt($FeedPost['UID'],"Positioned",["Timestamp"=>$timestamp]);

        // Profile Pic Logic
        $PostProfilePic = (isset($FeedPost['ProfilePic']) && !empty($FeedPost['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($FeedPost['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        // Logic for Attributes
        $IsSelfPost = ($UID == $FeedPost['UID']) ? 1 : 0;
        $IsSavedPost = ($FeedPost['saved']) ? 1 : 0;

        // 3. Added Attributes (UID, Self, Saved) to the div
        echo '<div class="FeedPost" PID="' . $encryptedFeedPostID . '" UID="' . $encryptedUserID . '" Self="' . $IsSelfPost . '" Saved="' . $IsSavedPost . '">
                        <div class="FeedPostHeader">
                            <div class="FeedPostAuthorContainer">
                                <a class="FeedPostAuthor" href="index.php?redirected_from=profile&target=profile&uid=' . urlencode($encryptedUserID) . '">
                                    <img src="' . $PostProfilePic . '" alt="">
                                    <div class="FeedPostAuthorInfo">
                                        <div class="FeedPostNameRow">
                                            <p class="FeedPostAuthorName">' . htmlspecialchars($FeedPost['Fname'] . ' ' . $FeedPost['Lname']) . '</p>
                                            ' . ($FeedPost['IsBlueTick'] ? '<span class="BlueTick" title="Verified"></span>' : '') . '
                                            <span class="FeedPostTime" data-date="' . $timestamp . '"></span>
                                        </div>
                                        <span class="FeedPostUsername">@' . htmlspecialchars($FeedPost['Username']) . '</span>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>
                        </div>

                        <div class="FeedPostContent">
                            <p>' . htmlspecialchars($FeedPost['Content']) . '</p>';

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
                        echo '<a class="FeedPostLink" href="' . $DocumentPath . '">
                                    <div class="UploadedFile">
                                        <img src="Imgs/Icons/Document.svg" >
                                        <p>' . $document . ' </p>
                                    </div>
                                </a>';
                    }
                }
            }

            $LikeIcon = ($FeedPost['liked']) ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';

        echo ' </div>
                        <div class="FeedPostInteractionCounters">
                            <p><span class="PostLikesCNT">' . $FeedPost['LikeCounter'] . '</span>  likes</p>
                            <p>' . $FeedPost['CommentCounter'] . ' Comments</p>
                        </div>
                        
                        <div class="FeedPostInteractions">
                            <div class="Interaction FeedPostLike">
                                <img src="' . $LikeIcon . '"> Like
                            </div>
                            <div class="Interaction FeedPostComment">
                                <img src="Imgs/Icons/comment.svg"> Comment
                            </div>
                            <div class="Interaction FeedPostShare">
                                <img src="Imgs/Icons/share.svg"> Share
                            </div>
                        </div>
                    </div>';
    }
?>

<div class="FeedLoader hidden" id="ProfilePostLoader">
    <div class="Loader"></div>
</div>