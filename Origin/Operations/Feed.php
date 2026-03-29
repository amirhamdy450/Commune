<?php 
$PATH="../../";

require_once $PATH."Includes/Config.php";
require_once $PATH.'Includes/UserAuth.php';  //include validation to get user data
require_once $PATH.'Includes/Encryption.php';
include_once $PATH.'Origin/Validation.php';


function CreateNotification($ToUID, $FromUID, $Type, $ReferenceID = null, $MetaInfo = null) {
    global $pdo;

    // Don't notify if user acts on themselves (e.g., liking own post)
    if ($ToUID == $FromUID && $FromUID !== null) {
        return;
    }

    $sql = "INSERT INTO notifications (ToUID, FromUID, Type, ReferenceID, MetaInfo, Date) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ToUID, $FromUID, $Type, $ReferenceID, $MetaInfo]);
}




if($_SERVER['REQUEST_METHOD']==='POST'){

    ValidateCsrf();
    $UID=$User['id'];


    if($_POST['ReqType'] == 1){ //add new post to DB
        $PostContent = $_POST['content']; //get the text in post
        //echo $content;
    
    
        
        $FolderPath=''; //folder path relative to the current file (Operations/Feed.php)
        $RootMediaFolderPath=''; //root folder path to the post relative to index (use it for DB Insertion)
    
    
    
        // we will use these flags later  to check if any files are uploaded and what type when we insert to the database
    
        $ImagesFound=false;
        $DocumentFound=false;
    
    
        // Check if any files were uploaded
        if (isset($_FILES)) {
    
            if(isset($_FILES['document']) && isset($_FILES['images'])){ //double check that he didn't upload both images and files
    
                echo json_encode([
                    'success' => false,
                    'message' => "Error: Multiple File Types Detected !"
                  ]);
    
                die();
            }
    
    
            $CreationTime=strtotime("now"); // will be used in multiple names so we store it in a variable
    
    
            //create a folder to contain the files of either documents or images
            $FolderName=$CreationTime.$UID.uniqid();
            $FolderPath=$PATH."MediaFolders/posts/". $FolderName; //create the new folder path in a variable because we will use it later
            $RootMediaFolderPath="MediaFolders/posts/". $FolderName; // root path

            mkdir($FolderPath, 0777, TRUE); //make new folder
    
    
    
            if (isset($_FILES['document'])) { //check if the files type is documents
             $DocumentFound=true;
    
             $DocumentFiles=$_FILES['document'];
             //print_r($DocumentFiles);
             for ($i = 0; $i < count($DocumentFiles['name']); $i++) { //loop through all files uploaded (assuming there are more than one file)
                    $fileExtension = pathinfo($DocumentFiles['name'][$i], PATHINFO_EXTENSION);
                    $newFilename=$CreationTime. $UID ."_file". $i+1 . "." .$fileExtension;  //create a new filename with the creation time , user id and iteration value
                    $targetPath = $FolderPath . "/" . $newFilename;

                    if (in_array(strtolower($fileExtension), $AllowedDocumentExtensions)) {
    
                        if(!move_uploaded_file($DocumentFiles['tmp_name'][$i], $targetPath)){ //this will move file to the directory and if it fails send back to the js
                        
                            echo json_encode([
                                'success' => false,
                                'message' => "Error: Failed to move ".$DocumentFiles['name'][$i]." to the specified directory"
                            ]);
    
                            die();
                        }
                    }else{
                        echo json_encode([
                            'success' => false,
                            'message' => "Error: The File Extension Of ".$DocumentFiles['name'][$i]." is not allowed !"
                        ]);

                        die();

                    }
    
                }
    
            }
    
            if (isset($_FILES['images'])) { //check if any images are attached
                $ImagesFound=true;
    
    
                $ImageFiles=$_FILES['images'];
                for ($i = 0; $i < count($ImageFiles['name']); $i++) { //loop through all files uploaded
                    $fileExtension = pathinfo($ImageFiles['name'][$i], PATHINFO_EXTENSION);
                    $newFilename=$CreationTime. $UID ."_file". $i+1 . "." .$fileExtension;  //create a new filename with the creation time , user id and iteration value
                    $targetPath = $FolderPath . "/" . $newFilename;
                    
                    $ScaleSupportedExtension=false; //we will detect if the image extension can be scaled or not
                    $image = null; // Initialize $image to null
    
                    if (!in_array(strtolower($fileExtension), $AllowedImagesExtensions)) {
                        echo json_encode([
                            'success' => false,
                            'message' => "Error: The File Extension Of ".$ImageFiles['name'][$i]." is not allowed !"
                        ]);
                        die();
                    }

                    if ($fileExtension === 'jpeg' || $fileExtension === 'jpg') {
                        $image = imagecreatefromjpeg($ImageFiles['tmp_name'][$i]);
                        $ScaleSupportedExtension=true; 
                    } else if ($fileExtension === 'png') {
                        $image = imagecreatefrompng($ImageFiles['tmp_name'][$i]);
                        $ScaleSupportedExtension=true; 
    
                    }
    
                    // Get image dimensions
                    if($image){
                    $width = imagesx($image);
                    $height = imagesy($image);
                    }
                   // print_r('Width:  ' . $width . ' Height: ' . $height.' ScaleSupport: '.$ScaleSupportedExtension.' ');
    
    
                    if($ScaleSupportedExtension && $width >= 1920 && $height >= 900){  //if the extension supports scaling and it fits the correct width and height
    
    
                        if ($width > $height) {
    
    
                            $newheight = 900;
    
                            $newwidth = ($newheight * $width) / $height;
                            $image = imagescale($image,$newwidth,$newheight);
                        } else {
                            $newwidth = 900;
                            $newheight = ($newwidth * $height) / $width;
                            $image = imagescale($image,$newwidth,$newheight);
                        }
    
    
                        $ImageMoved = false; //a flag to detect if the image was moved directory successfully or not
    
                        // Save the processed image to the target directory
                        if ($fileExtension === 'jpeg' || $fileExtension === 'jpg' ) {
                            if (!imagejpeg($image, $targetPath)) {
                                echo json_encode([
                                    'success' => false,
                                    'message' => "Error: Failed to move ".$ImageFiles['name'][$i]." to the specified directory"
                                ]);
                                die();
    
                            } 
                        } else if ($fileExtension === 'png') {
                            if (!imagepng($image, $targetPath)) {
                                echo json_encode([
                                    'success' => false,
                                    'message' => "Error: Failed to move ".$ImageFiles['name'][$i]." to the specified directory"
                                ]);
                                die();
    
                            }
                        }
    
                        // Clean up memory
                        imagedestroy($image);
                    
    
                    } else{
    
    
                        $fileExtension = pathinfo($ImageFiles['name'][$i], PATHINFO_EXTENSION);
                        $newFilename=$CreationTime. $UID ."_file". $i+1 . "." .$fileExtension;  //create a new filename with the creation time , user id and iteration value
                        $targetPath = $FolderPath . "/" . $newFilename;
                        if(!move_uploaded_file($ImageFiles['tmp_name'][$i], $targetPath)){
                            echo json_encode([
                                'success' => false,
                                'message' => "Error:".$i+1 ." Failed to move ".$ImageFiles['name'][$i]." to the specified directory"
                            ]);
                            die();
    
                        }
    
    
    
                    }
    
                   // move_uploaded_file($ImageFiles['tmp_name'][$i], $targetPath);
    
    
                }
    
    
            }
    
       
       
       
       
       
        }
    
    
        //insert to the  database
        if($DocumentFound){
            $type=3;
            
        }else if($ImagesFound){
            $type=2;
        }else{
            $type=1;
        }
    
        $data=[$PostContent,$type,$RootMediaFolderPath,date("Y-m-d H:i:s"),1,$UID];
        //print_r($data);
                $sql = "INSERT INTO posts (Content,Type, Mediafolder, Date,Status,UID)  VALUES (?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        if(!$stmt->execute($data)){
            echo json_encode([
                'success' => false,
                'message' => "Error: Failed to insert data into the database"
            ]);
            die();
        }

        $lastInsertId = $pdo->lastInsertId();

        // Fetch the new post
        $sql = 'SELECT posts.id AS PID, posts.*, users.*, FALSE AS liked
                FROM posts
                INNER JOIN users ON posts.UID = users.id
                WHERE posts.id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lastInsertId]);
        $newPost = $stmt->fetch(PDO::FETCH_ASSOC);

        // Prepare the post data for the client
        $timestamp = strtotime($newPost['Date']);
        $encryptedFeedPostID = Encrypt($newPost['PID'],"Positioned",["Timestamp"=>$timestamp]);

        $media = [];
        if (is_dir($FolderPath)) {
            $mediaFiles = scandir($FolderPath);
            foreach ($mediaFiles as $file) {
                if ($file !== '.' && $file !== '..') {
                    $media[] = ['name' => $file, 'path' => $FolderPath . '/' . $file];
                }
            }
        }


        $PostProfilePic = (isset($newPost['ProfilePic']) && !empty($newPost['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($newPost['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        $responsePost = [
            'PID' => $encryptedFeedPostID,
            'name' => $newPost['Fname'] . ' ' . $newPost['Lname'],
            'Content' => $newPost['Content'],
            'LikeCounter' => $newPost['LikeCounter'],
            'CommentCounter' => $newPost['CommentCounter'],
            'MediaFolder' => $media,
            'MediaType' => (int)$newPost['Type'],
            'CurrentUserPrivilege' => (int)$User['Privilege'],
            'liked' => false,
            'ProfilePic' => $PostProfilePic
        ];

        echo json_encode([
            'success' => true,
            'message' => "Post added successfully",
            'post' => $responsePost
        ]);
        die();
    


    
    } else if($_POST['ReqType'] == 2){  //like/unlike a post

        $EncFeedPostAtr=$_POST['FeedPostID']; //Enc stands for encrypted and atr stands for atribute

        $FeedPostID=Decrypt($EncFeedPostAtr,"Positioned"); 

        //first we need to check if he liked  the post before

        $sql='SELECT * FROM likes WHERE  PostID=? AND UID=? ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([ $FeedPostID,$UID]);
        if($stmt->rowCount() <1){

            //Add the Like to the database
            $sql='INSERT INTO likes(PostID,UID) VALUES (?,?)'; //add the id of the  post and the id of the user who liked it

            $stmt = $pdo->prepare($sql);
            
            if($stmt->execute([$FeedPostID,$UID])){ //execute the query and if successful we will do another query inside the posts table

                //increment the likes count  in the posts table
                
                $sql='UPDATE posts SET LikeCounter=LikeCounter+1 WHERE id=?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$FeedPostID]);


                // 1. Fetch Post Owner
                $ownerSQL = "SELECT UID FROM posts WHERE id = ?";
                $stmtOwner = $pdo->prepare($ownerSQL);
                $stmtOwner->execute([$FeedPostID]);
                $PostOwnerUID = $stmtOwner->fetchColumn();

              
                CreateNotification($PostOwnerUID, $UID, 1, $FeedPostID);

                echo json_encode([
                    'success' => true,
                    'message' => "Like Added",
                    'liked'=> true,
                    'Insertion'=> 1     // this will make the client-side know if its a normal insertion or is it deletion
    
                ]);


            }
        } else{
            //if he liked the post before ,delete it

            $sql='DELETE FROM  likes WHERE PostID =? AND UID =?'; //add the id of the  post and the id of the user who liked it
            $stmt = $pdo->prepare($sql);

            if($stmt->execute([$FeedPostID,$UID])){ //execute the query and if successful we will do another query inside the posts table

                //decrement the likes count  in the posts table
                
                $sql='UPDATE posts SET LikeCounter=LikeCounter-1 WHERE id=?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$FeedPostID]);

                echo json_encode([
                    'success' => true,
                    'message' => "Like Removed",
                    'liked'=> false,
                    'Insertion'=> -1  // this will make the client-side know if its a normal insertion or is it deletion

    
                ]);


            }




        }







    } else if ($_POST['ReqType'] == 3) { //add new comment

        $EncFeedPostAtr=$_POST['FeedPostID']; //Enc stands for encrypted and atr stands for atribute
/*         $FeedPostAtr= openssl_decrypt(base64_decode($EncFeedPostAtr), 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV);

        // Find the position of the 'I' to retrieve the  post id bieng liked
        $PostIDPosition = strpos($FeedPostAtr, 'I'); */
        $FeedPostID=(int)Decrypt($EncFeedPostAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer

        $CommentContent=$_POST['CommentContent'];


        //Add the comment to the database
        $sql = 'INSERT INTO comments(comment,PostID,UID) VALUES (?,?,?)'; //add the id of the  post and the id of the user who liked it

        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$CommentContent,$FeedPostID, $UID])) { //execute the query and if successful we will do another query inside the posts table

            //increment the likes count  in the posts table

            $sql = 'UPDATE posts SET CommentCounter=CommentCounter+1 WHERE id=?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$FeedPostID]);


            // 1. Fetch Post Owner
            $ownerSQL = "SELECT UID FROM posts WHERE id = ?";
            $stmtOwner = $pdo->prepare($ownerSQL);
            $stmtOwner->execute([$FeedPostID]);
            $PostOwnerUID = $stmtOwner->fetchColumn();

            // 2. Create Notification (Type 2 = Comment)
            CreateNotification($PostOwnerUID, $UID, 2, $FeedPostID);

            echo json_encode([
                'success' => true,
                'message' => "Comment added successfully"

            ]);


        }else{
            echo json_encode([
                'success' => false,
                'message' => "Error: Failed To insert comment"

            ]);
        }
    } else if($_POST["ReqType"] == 4){ //fetch comments
        $EncFeedPostAtr=$_POST['FeedPostID']; //Enc stands for encrypted and atr stands for atribute

        $FeedPostID=(int)Decrypt($EncFeedPostAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer


        if(!RowExists('posts','id',$FeedPostID)){
            echo json_encode([
                'success' => false,
                'message' => "Error: Post Not Found"

            ]);
            die();
        }



        $sql='SELECT comments.id as CID,comments.*,users.* ,
            CASE WHEN CL.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
            FROM comments 
            INNER JOIN users ON comments.UID=users.id 
            LEFT JOIN comments_likes CL ON comments.id=CL.CommentID AND CL.UID=?       
            WHERE comments.PostID=? ';

        $stmt = $pdo->prepare($sql);

        if($stmt->execute([$UID,$FeedPostID])){

            $Comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //encrypt the comment ids (CID)
            foreach($Comments as &$Comment){
                //convert Date to a Unix timestamp
                $timestamp = strtotime($Comment['Date']);

                $Comment['CID'] = Encrypt($Comment['CID'],"Positioned",["Timestamp"=>$timestamp]); // Makes it JSON-safe

                $Comment['ProfilePic'] = (isset($Comment['ProfilePic']) && !empty($Comment['ProfilePic']))
                    ? 'MediaFolders/profile_pictures/' . htmlspecialchars($Comment['ProfilePic'])
                    : 'Imgs/Icons/unknown.png';

                $Comment['IsSelf'] = ($Comment['UID'] == $UID);

                //encrypt the user id (UID)
                //$FormattedID = 'D'.$timestamp.'I'.$Comment['UID'];

                $Comment['UID'] = Encrypt($Comment['UID'],"Positioned",["Timestamp"=>$timestamp]); // Makes it JSON-safe
            }
            unset($Comment);



            echo json_encode($Comments);

        }


    } else if ($_POST["ReqType"] == 5) { //fetch new posts to feed
        $EncFeedPostAtr=$_POST['LastFeedPostPID']; //Enc stands for encrypted and atr stands for atribute

        $FeedPostID=(int)Decrypt($EncFeedPostAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer

        //check if search filter is set
        if (isset($_POST['Search'])) {
            $SearchTerm = '%' . $_POST['Search'] . '%';
        }


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
                WHERE posts.id < ? AND posts.Status = 1 AND b.id IS NULL
                ORDER BY posts.Date DESC 
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        
        if($stmt->execute([$UID,$UID, $UID , $UID,$FeedPostID])){

        $NewPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [];

        foreach ($NewPosts as $FeedPost) {
            //convert Date to a Unix timestamp
            $timestamp = strtotime($FeedPost['Date']);
           /*  $FeedPostID = 'D'.$timestamp.'I'.$FeedPost['PID']; */
            $encryptedFeedPostID = Encrypt($FeedPost['PID'],"Positioned",["Timestamp"=>$timestamp]); // Makes it JSON-safe

           /*  $UserID = 'D'.$timestamp.'I'.$FeedPost['UID']; */
            $encryptedUserID = Encrypt($FeedPost['UID'],"Positioned",["Timestamp"=>$timestamp]); // Makes it JSON-safe


            $PostProfilePic = (isset($FeedPost['ProfilePic']) && !empty($FeedPost['ProfilePic']))
                    ? 'MediaFolders/profile_pictures/' . htmlspecialchars($FeedPost['ProfilePic'])
                    : 'Imgs/Icons/unknown.png';


            $MediaFolder = $PATH.$FeedPost['MediaFolder'];
            $media = [];
            if (is_dir($MediaFolder)) {
                $MediaFiles = scandir($MediaFolder);
               // echo $MediaFiles.'\n';
                foreach ($MediaFiles as $file) {
                    if (in_array(strtolower($file), ['.', '..'])) { //this to ignore dots that are treated as files in scandir , (.) represents current directory and (..) represents parent directory

                        continue;  //skip this iteration
                    }

                    $filePath = $FeedPost['MediaFolder'] . '/' . $file;

                    $media[] = [
                        'name'=>$file,
                        'path' => $filePath,
                    ];
                    
                }
            }/* else{
                echo "Media Folder Not Found\n";
            } */
        
            // Add post details to the response array
            $response[] = [
                'PID' => $encryptedFeedPostID,
                'UID' => $encryptedUserID,
                'name' => $FeedPost['Fname'] . ' ' . $FeedPost['Lname'],
                'Content' => $FeedPost['Content'],
                'LikeCounter' => $FeedPost['LikeCounter'],
                'CommentCounter' => $FeedPost['CommentCounter'],
                'MediaFolder' => $media,
                'MediaType'=> (int)$FeedPost['Type'],
                'CurrentUserPrivilege'=> (int)$User['Privilege'],
                'liked'=>$FeedPost['liked'],
                'following'=>$FeedPost['following'],
                'Self' => (int)($FeedPost['UID'] == $UID), //identify if the post belongs to the user
                'saved'=>(int)$FeedPost['saved'],
                'ProfilePic' => $PostProfilePic
            ];


        }


        echo json_encode($response);
        }

    } else if($_POST["ReqType"] == 6){ //delete a post
        $EncFeedPostAtr=$_POST['FeedPostID']; //Enc stands for encrypted and atr stands for atribute

        $FeedPostID=(int)Decrypt($EncFeedPostAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer
        
        $sql="UPDATE posts SET Status=0 WHERE id=? AND UID=?";
        $stmt=$pdo->prepare($sql);
        if($stmt->execute([$FeedPostID,$UID])){
            if($stmt->rowCount() > 0){
                echo json_encode([
                    'success' => true,
                    'message' => "Post Deleted",
                ]);
            }else{
                echo json_encode([
                    'success' => false,
                    'message' => "Post not found or you do not have permission to delete it.",
                ]);
            }
        }

    } else if ($_POST["ReqType"] == 7) { //like/unlike a comment
        $EncCommentAtr=$_POST['CommentID']; //Enc stands for encrypted and atr stands for atribute

        $CommentID=(int)Decrypt($EncCommentAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer

        if(!RowExists('comments','id',$CommentID)){
            echo json_encode([
                'success' => false,
                'message' => "Comment not found",
            ]);
            die();
        }

        

        //first we need to check if he liked  the comment before in table `comments_likes`
        $sql="SELECT Count(*) FROM comments_likes WHERE CommentID=? AND UID=?";
        $stmt=$pdo->prepare($sql);
        $stmt->execute([$CommentID,$UID]);

        if($stmt->fetchColumn() == 0){
            $sql='INSERT INTO comments_likes(CommentID,UID) VALUES (?,?)';
            $stmt=$pdo->prepare($sql);

            if($stmt->execute([$CommentID,$UID])){
                $sql="UPDATE comments SET LikeCounter=LikeCounter+1 WHERE id=?";
                $stmt=$pdo->prepare($sql);
                if($stmt->execute([$CommentID])){
                    // 1. Fetch Comment Owner and the Post it belongs to
                    $sqlDetails = "SELECT UID, PostID FROM comments WHERE id = ?";
                    $stmtDetails = $pdo->prepare($sqlDetails);
                    $stmtDetails->execute([$CommentID]);
                    $details = $stmtDetails->fetch(PDO::FETCH_ASSOC);
                    
                    $CommentOwnerUID = $details['UID'];
                    $ReferencePostID = $details['PostID'];

                    // 2. Create Notification (Type 5 = Like Comment)
                    CreateNotification($CommentOwnerUID, $UID, 5, $ReferencePostID);

                    echo json_encode([
                        'success' => true,
                        'message' => "Comment Liked",
                        'liked'=> true,
                        'Insertion'=> 1  
                    ]);
                }

            }



        }else{
            $sql = "DELETE FROM comments_likes WHERE CommentID = ? AND UID = ?";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$CommentID, $UID])){
                $sql="UPDATE comments SET LikeCounter=LikeCounter-1 WHERE id=?";
                $stmt=$pdo->prepare($sql);
                if($stmt->execute([$CommentID])){
                    echo json_encode([
                        'success' => true,
                        'message' => "Comment Unliked",
                        'liked'=> false,
                        'Insertion'=> -1  
                    ]);
                }
            }

         }








   
    }else if($_POST["ReqType"]== 8){ //replying to a comment
        
        $EncCommentAtr=$_POST['CommentID']; //Enc stands for encrypted and atr stands for atribute

        $CommentID=(int)Decrypt($EncCommentAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer

        if(!RowExists('comments','id',$CommentID)){
            echo json_encode([
                'success' => false,
                'message' => "Comment not found",
            ]);
            die();
        }


        if(isset($_POST['ReplyTo'])){
            $EncUserAtr=$_POST['ReplyTo']; //Enc stands for encrypted and atr stands for atribute

            $UserID=(int)Decrypt($EncUserAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer

            if(!RowExists('users','id',$UserID)){
                echo json_encode([
                    'success' => false,
                    'message' => "User not found",
                ]);
                die();
            }

            $TaggedUser=$UserID;
        }else{
            $TaggedUser=NULL;
        }

        $Date=date('Y-m-d H:i:s');
        $Reply= $_POST['Reply'];

        $sql="INSERT INTO comments_replies (CommentID,UID,Reply,Tagged,`Date`) VALUES (?,?,?,?,?)";
        $stmt=$pdo->prepare($sql);
        if(!$stmt->execute([$CommentID,$UID,$Reply,$TaggedUser,$Date])){
            echo json_encode([
                'success' => false,
                'message' => "Error inserting reply",
            ]);
        }


        //Increment the reply counter in comments table
        $sql="UPDATE comments SET ReplyCounter=ReplyCounter+1 WHERE id=?";
        $stmt=$pdo->prepare($sql);
        if(!$stmt->execute([$CommentID])){
            echo json_encode([
                'success' => false,
                'message' => "Error incrementing reply counter",
            ]);
        }


        // A. Fetch Thread Details (We need the PostID for the link, and the Main Comment Owner)
        $stmtDetails = $pdo->prepare("SELECT UID, PostID FROM comments WHERE id = ?");
        $stmtDetails->execute([$CommentID]);
        $CommentDetails = $stmtDetails->fetch(PDO::FETCH_ASSOC);
        
        $CommentOwnerUID = $CommentDetails['UID'];
        $ReferencePostID = $CommentDetails['PostID'];

        // B. Determine Who to Notify
        // If a specific user was tagged, they are the priority target.
        // If no one was tagged, it's a direct reply to the main comment owner.
        $TargetUID = ($TaggedUser !== NULL) ? $TaggedUser : $CommentOwnerUID;

        // C. Send Notification (Type 3 = Reply)
        // Note: We link to the ReferencePostID so the user lands on the correct post.
        CreateNotification($TargetUID, $UID, 3, $ReferencePostID);


        echo json_encode([
            'success' => true,
            'message' => "Reply inserted",
        ]);


    }else if($_POST['ReqType']==9){ //like a reply
        $EncReplyAtr=$_POST['ReplyID']; //Enc stands for encrypted and atr stands for atribute

        $ReplyID=(int)Decrypt($EncReplyAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer

        if(!RowExists('comments_replies','id',$ReplyID)){
            echo json_encode([
                'success' => false,
                'message' => "Comment not found",
            ]);
            die();
        }


        //first we need to check if he liked  the comment before in table `comments_likes`
        $sql="SELECT Count(*) FROM comments_replies_likes WHERE ReplyID=? AND UID=?";
        $stmt=$pdo->prepare($sql);
        $stmt->execute([$ReplyID,$UID]);

        if($stmt->fetchColumn() == 0){
            $sql='INSERT INTO comments_replies_likes(ReplyID,UID) VALUES (?,?)';
            $stmt=$pdo->prepare($sql);

            if($stmt->execute([$ReplyID,$UID])){
                $sql="UPDATE comments_replies SET LikeCounter=LikeCounter+1 WHERE id=?";
                $stmt=$pdo->prepare($sql);
                if($stmt->execute([$ReplyID])){


                    $sqlReply = "SELECT UID, CommentID FROM comments_replies WHERE id = ?";
                    $stmtReply = $pdo->prepare($sqlReply);
                    $stmtReply->execute([$ReplyID]);
                    $replyData = $stmtReply->fetch(PDO::FETCH_ASSOC);
                    
                    $ReplyOwnerUID = $replyData['UID'];
                    $ParentCommentID = $replyData['CommentID'];

                    // 2. Fetch Post ID from the Parent Comment
                    $sqlComment = "SELECT PostID FROM comments WHERE id = ?";
                    $stmtComment = $pdo->prepare($sqlComment);
                    $stmtComment->execute([$ParentCommentID]);
                    $ReferencePostID = $stmtComment->fetchColumn();

                    // 3. Create Notification (Type 6 = Like Reply)
                    CreateNotification($ReplyOwnerUID, $UID, 6, $ReferencePostID);

                    echo json_encode([
                        'success' => true,
                        'message' => "Comment Liked",
                        'liked'=> true,
                        'Insertion'=> 1  
                    ]);
                }

            }



        }else{
            $sql = "DELETE FROM comments_replies_likes WHERE ReplyID = ? AND UID = ?";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$ReplyID, $UID])){
                $sql="UPDATE comments_replies SET LikeCounter=LikeCounter-1 WHERE id=?";
                $stmt=$pdo->prepare($sql);
                if($stmt->execute([$ReplyID])){
                    echo json_encode([
                        'success' => true,
                        'message' => "Comment Unliked",
                        'liked'=> false,
                        'Insertion'=> -1  
                    ]);
                }
            }

         }






    }else if ($_POST["ReqType"]== 10){ //fetch comment replies
        $EncCommentAtr=$_POST['CommentID']; //Enc stands for encrypted and atr stands for atribute

        $CommentID=(int)Decrypt($EncCommentAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer

        if(!RowExists('comments','id',$CommentID)){
            echo json_encode([
                'success' => false,
                'message' => "Comment not found",
            ]);
            die();
        }

        //get al replies to that comment
        $sql="SELECT CR.id AS CRID, CR.UID,CR.Reply,CR.LikeCounter, CR.Date,CONCAT(Sender.Fname,' ',Sender.Lname)  AS Sender,
        Sender.Username AS SenderUsername, Sender.ProfilePic AS SenderProfilePic, Tagged.Username AS TaggedUser ,
        CASE WHEN CRL.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
        FROM comments_replies CR
        INNER JOIN users Sender ON CR.UID=Sender.id
        LEFT JOIN users Tagged ON CR.Tagged=Tagged.id
        LEFT JOIN comments_replies_likes CRL ON CRL.ReplyID=CR.id AND CRL.UID=?
        WHERE CommentID=? ORDER BY CR.id ASC";
        $stmt=$pdo->prepare($sql);
        $stmt->execute([$UID,$CommentID]);


        $Replies=$stmt->fetchAll(PDO::FETCH_ASSOC);


        foreach($Replies as &$Reply){
            //convert Date to a Unix timestamp
            $timestamp = strtotime($Reply['Date']);

            $Reply['CRID'] = Encrypt($Reply['CRID'],"Positioned",["Timestamp"=>$timestamp]); // Makes it JSON-safe


            //encrypt the user id (UID)

            $Reply['SenderProfilePic'] = (isset($Reply['SenderProfilePic']) && !empty($Reply['SenderProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($Reply['SenderProfilePic'])
            : 'Imgs/Icons/unknown.png';

            $Reply['IsSelf'] = ($Reply['UID'] == $UID);

            $Reply['UID'] = Encrypt($Reply['UID'],"Positioned",["Timestamp"=>$timestamp]); // Makes it JSON-safe
        }
        unset($Reply);

        echo json_encode($Replies);

   
    }else if ($_POST["ReqType"]==11){ //follow/unfollow a user
        $EncUserAtr=$_POST['UserID']; //Enc stands for encrypted and atr stands for atribute



        $TargetUserID=(int)Decrypt($EncUserAtr,"Positioned"); //the position after I is the id , retrieve it and convert it to integer


        //first we need to check if he is already following the user
        $sql="SELECT * FROM followers WHERE FollowerID=? AND UserID=?";
        $stmt=$pdo->prepare($sql);
        $stmt->execute([$UID,$TargetUserID]);
        $Following=$stmt->fetch(PDO::FETCH_ASSOC);
        if($Following){
            $sql="DELETE FROM followers WHERE FollowerID=? AND UserID=?";
            $stmt=$pdo->prepare($sql);
            if($stmt->execute([$UID,$TargetUserID])){
                // Decrement counters
                $pdo->prepare("UPDATE users SET Followers=Followers-1 WHERE id=?")->execute([$TargetUserID]);
                $pdo->prepare("UPDATE users SET Following=Following-1 WHERE id=?")->execute([$UID]);
                echo json_encode([
                    'success' => true,
                    'message' => "Unfollowed",
                    'Followed'=> false,
                ]);
            }
        }else{
            $sql="INSERT INTO followers (FollowerID,UserID) VALUES (?,?)";
            $stmt=$pdo->prepare($sql);
            if($stmt->execute([$UID,$TargetUserID])){
                // Increment counters
                $pdo->prepare("UPDATE users SET Followers=Followers+1 WHERE id=?")->execute([$TargetUserID]);
                $pdo->prepare("UPDATE users SET Following=Following+1 WHERE id=?")->execute([$UID]);
                CreateNotification($TargetUserID, $UID, 4);
                echo json_encode([
                    'success' => true,
                    'message' => "Followed",
                    'Followed'=> true,
                ]);
            }
        }

    
    
    }else if ($_POST["ReqType"] == 12) { // Save/Unsave Post
        $EncPostAtr = $_POST['PostID'];
        $PostID = (int)Decrypt($EncPostAtr, "Positioned");

        if (!RowExists('posts', 'id', $PostID)) {
            echo json_encode(['success' => false, 'message' => 'Post not found.']);
            exit;
        }

        // Check if already saved
        $sql_check = "SELECT id FROM saved_posts WHERE UID = ? AND PostID = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$UID, $PostID]);

        if ($stmt_check->fetch()) {
            // Already saved, so unsave
            $sql_delete = "DELETE FROM saved_posts WHERE UID = ? AND PostID = ?";
            $pdo->prepare($sql_delete)->execute([$UID, $PostID]);
            echo json_encode(['success' => true, 'message' => 'Post unsaved!', 'Saved' => false]);
        } else {
            // Not saved, so save
            $sql_insert = "INSERT INTO saved_posts (UID, PostID) VALUES (?, ?)";
            $pdo->prepare($sql_insert)->execute([$UID, $PostID]);
            echo json_encode(['success' => true, 'message' => 'Post saved!', 'Saved' => true]);
        }
        exit;

    }else if ($_POST["ReqType"] == 13) { // Block User
        $EncUserAtr = $_POST['BlockedUID'];
        $BlockedUID = (int)Decrypt($EncUserAtr, "Positioned");

        if ($BlockedUID == $UID) {
            echo json_encode(['success' => false, 'message' => 'You cannot block yourself.']);
            exit;
        }

        if (!RowExists('users', 'id', $BlockedUID)) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        // Check if already blocked
        $sql_check = "SELECT id FROM blocked_users WHERE BlockerUID = ? AND BlockedUID = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$UID, $BlockedUID]);

        if ($stmt_check->fetch()) {
            // Already blocked (future: unblock?)
            echo json_encode(['success' => true, 'message' => 'User is already blocked.']);
        } else {
            // Not blocked, so block
            $sql_insert = "INSERT INTO blocked_users (BlockerUID, BlockedUID) VALUES (?, ?)";
            $pdo->prepare($sql_insert)->execute([$UID, $BlockedUID]);
            echo json_encode(['success' => true, 'message' => 'User blocked.']);
        }
        exit;
    }else if ($_POST["ReqType"] == 14) { // FETCH POST FOR EDITING
        $EncFeedPostAtr = $_POST['FeedPostID'];
        $FeedPostID = (int)Decrypt($EncFeedPostAtr, "Positioned");

        // 1. Verify Ownership
        $sql = "SELECT Content, Type, MediaFolder FROM posts WHERE id = ? AND UID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$FeedPostID, $UID]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found or you do not have permission to edit it.']);
            die();
        }

        // 2. Get Media Files
        $media = [];
        $FolderPath = $PATH . $post['MediaFolder'];
        if ($post['Type'] == 2 || $post['Type'] == 3) {
             if (is_dir($FolderPath)) {
                $mediaFiles = scandir($FolderPath);
                foreach ($mediaFiles as $file) {
                    if ($file !== '.' && $file !== '..') {
                        // We send the filename and the client-facing path
                        $media[] = [
                            'name' => $file, 
                            'path' => $post['MediaFolder'] . '/' . $file
                        ];
                    }
                }
            }
        }
        
        // 3. Send data to the client
        echo json_encode([
            'success' => true,
            'Content' => $post['Content'],
            'MediaType' => (int)$post['Type'],
            'MediaFiles' => $media
        ]);
        die();

    } else if ($_POST["ReqType"] == 15) { // SUBMIT POST EDIT
        $EncFeedPostAtr = $_POST['PostID'];
        $FeedPostID = (int)Decrypt($EncFeedPostAtr, "Positioned");
        $PostContent = $_POST['content'];
        $filesToDelete = isset($_POST['files_to_delete']) ? json_decode($_POST['files_to_delete']) : [];

        // 1. Verify Ownership and get MediaFolder
        $sql = "SELECT MediaFolder, Type FROM posts WHERE id = ? AND UID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$FeedPostID, $UID]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found or you do not have permission to edit it.']);
            die();
        }

        $FolderPath = $PATH . $post['MediaFolder']; // Full server path to media
        $RootMediaFolderPath = $post['MediaFolder']; // DB path
        $currentType = (int)$post['Type'];

        if (!empty($post['MediaFolder']) && !is_dir($FolderPath)) {
            if (!mkdir($FolderPath, 0777, true)) {
                echo json_encode(['success' => false, 'message' => "Error: Could not create media directory."]);
                die();
            }
        }

        // 2. Delete marked files
        if (!empty($filesToDelete)) {
            foreach ($filesToDelete as $filename) {
                $filePath = $FolderPath . '/' . basename($filename); // Sanitize filename
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        // 3. Handle new file uploads (re-using logic from ReqType 1)
        $ImagesFound = false;
        $DocumentFound = false;

        // Check file types (logic from ReqType 1)
        if (isset($_FILES['document']) && isset($_FILES['images'])) {
             echo json_encode(['success' => false, 'message' => "Error: Multiple File Types Detected !"]);
             die();
        }
        
        // Check if new documents were uploaded
        if (isset($_FILES['document'])) {
            $DocumentFound = true;
            $DocumentFiles = $_FILES['document'];
            $CreationTime = strtotime("now"); // Use for new file names
             
             for ($i = 0; $i < count($DocumentFiles['name']); $i++) {
                $fileExtension = pathinfo($DocumentFiles['name'][$i], PATHINFO_EXTENSION);
                $newFilename = $CreationTime . $UID . "_file" . ($i + 1) . "." . $fileExtension;
                $targetPath = $FolderPath . "/" . $newFilename;

                if (in_array(strtolower($fileExtension), $AllowedDocumentExtensions)) {
                    if (!move_uploaded_file($DocumentFiles['tmp_name'][$i], $targetPath)) {
                        echo json_encode(['success' => false, 'message' => "Error: Failed to move ".$DocumentFiles['name'][$i]]);
                        die();
                    }
                } else {
                     echo json_encode(['success' => false, 'message' => "Error: File Extension Of ".$DocumentFiles['name'][$i]." is not allowed !"]);
                     die();
                }
             }
        }
        
        // Check if new images were uploaded (simplified from ReqType 1 - add scaling back if needed)
        if (isset($_FILES['images'])) {
            $ImagesFound = true;
            $ImageFiles = $_FILES['images'];
            $CreationTime = strtotime("now");

            for ($i = 0; $i < count($ImageFiles['name']); $i++) {
                $fileExtension = strtolower(pathinfo($ImageFiles['name'][$i], PATHINFO_EXTENSION));
                $newFilename = $CreationTime . $UID . "_file" . ($i + 1) . "." . $fileExtension;
                $targetPath = $FolderPath . "/" . $newFilename;

                if (!in_array($fileExtension, $AllowedImagesExtensions)) {
                    echo json_encode(['success' => false, 'message' => "Error: File Extension Of ".$ImageFiles['name'][$i]." is not allowed !"]);
                    die();
                }
                
                // Simplified move_uploaded_file. Add image scaling logic from ReqType 1 back here if you need it.
                if (!move_uploaded_file($ImageFiles['tmp_name'][$i], $targetPath)) {
                    echo json_encode(['success' => false, 'message' => "Error: Failed to move ".$ImageFiles['name'][$i]]);
                    die();
                }
            }
        }

        // 4. Determine new post type
        $remainingFiles = glob($FolderPath . "/*");
        $fileCount = 0;
        if ($remainingFiles) {
            $fileCount = count($remainingFiles);
        }

        $newType = $currentType;
        if ($fileCount == 0) {
            $newType = 1; // Text only
        } else if ($DocumentFound) {
            $newType = 3; // Documents
        } else if ($ImagesFound) {
            $newType = 2; // Images
        } else if ($currentType == 3 && !$DocumentFound) {
            // No new docs, but old docs remain
            $newType = 3;
        } else if ($currentType == 2 && !$ImagesFound) {
            // No new images, but old images remain
            $newType = 2;
        }


        // 5. Update the database
        $sql = "UPDATE posts SET Content = ?, Type = ? WHERE id = ? AND UID = ?";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute([$PostContent, $newType, $FeedPostID, $UID])) {
            echo json_encode(['success' => false, 'message' => "Error: Failed to update post in database."]);
            die();
        }

        // 6. Fetch and return the fully updated post data
        $sql = 'SELECT posts.id AS PID, posts.*, users.Fname, users.Lname, users.Username, users.ProfilePic,
                CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved
                FROM posts
                INNER JOIN users ON posts.UID = users.id
                LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                WHERE posts.id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$UID, $UID, $UID, $FeedPostID]);
        $updatedPost = $stmt->fetch(PDO::FETCH_ASSOC);

        // Manually format the post for the client
        $timestamp = strtotime($updatedPost['Date']);
        $encryptedFeedPostID = Encrypt($updatedPost['PID'], "Positioned", ["Timestamp" => $timestamp]);
        $encryptedUserID = Encrypt($updatedPost['UID'], "Positioned", ["Timestamp" => $timestamp]);
        
        $PostProfilePic = (isset($updatedPost['ProfilePic']) && !empty($updatedPost['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($updatedPost['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        $media = [];
        $MediaFolder = $PATH . $updatedPost['MediaFolder'];
        if ($updatedPost['Type'] > 1 && is_dir($MediaFolder)) {
            $MediaFiles = scandir($MediaFolder);
            foreach ($MediaFiles as $file) {
                if ($file !== '.' && $file !== '..') {
                    $media[] = ['name' => $file, 'path' => $updatedPost['MediaFolder'] . '/' . $file];
                }
            }
        }
        
        $responsePost = [
            'PID' => $encryptedFeedPostID,
            'UID' => $encryptedUserID,
            'name' => $updatedPost['Fname'] . ' ' . $updatedPost['Lname'],
            'Username' => $updatedPost['Username'],
            'ProfilePic' => $PostProfilePic,
            'Content' => $updatedPost['Content'],
            'LikeCounter' => $updatedPost['LikeCounter'],
            'CommentCounter' => $updatedPost['CommentCounter'],
            'MediaFolder' => $media,
            'MediaType'=> (int)$updatedPost['Type'],
            'liked'=> (bool)$updatedPost['liked'],
            'following'=> (bool)$updatedPost['following'],
            'Self' => (int)($updatedPost['UID'] == $UID),
            'saved'=>(int)$updatedPost['saved']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Post updated successfully',
            'post' => $responsePost
        ]);
        die();
    }else if ($_POST["ReqType"] == 16) { // DELETE COMMENT
        $EncCommentAtr = $_POST['CommentID'];
        $CommentID = (int)Decrypt($EncCommentAtr, "Positioned");

        if ($CommentID <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Comment ID.']);
            die();
        }

        // 1. Verify Ownership & Get PostID
        // We select UID to verify ownership, and PostID to update the counter later
        $sql = "SELECT UID, PostID FROM comments WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$CommentID]);
        $commentData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$commentData) {
            echo json_encode(['success' => false, 'message' => 'Comment not found.']);
            die();
        }

        if ($commentData['UID'] != $UID) {
            echo json_encode(['success' => false, 'message' => 'Permission denied. You do not own this comment.']);
            die();
        }
        
        $PostID = $commentData['PostID'];

        try {
            $pdo->beginTransaction();

            // 2. DELETE DEPENDENCIES (Manual Cascade)
            
            // A. Delete Likes on Replies to this comment
            // "Delete from likes where the reply belongs to this comment"
            $sql = "DELETE FROM comments_replies_likes 
                    WHERE ReplyID IN (SELECT id FROM comments_replies WHERE CommentID = ?)";
            $pdo->prepare($sql)->execute([$CommentID]);

            // B. Delete Replies to this comment
            $sql = "DELETE FROM comments_replies WHERE CommentID = ?";
            $pdo->prepare($sql)->execute([$CommentID]);

            // C. Delete Likes on this comment
            $sql = "DELETE FROM comments_likes WHERE CommentID = ?";
            $pdo->prepare($sql)->execute([$CommentID]);

            // 3. DELETE THE COMMENT
            // Now that all children are gone, this will succeed
            $sql = "DELETE FROM comments WHERE id = ?";
            $pdo->prepare($sql)->execute([$CommentID]);

            // 4. Update Post Counter
            // We decrement the counter, ensuring it doesn't go below zero
            $sql = "UPDATE posts SET CommentCounter = GREATEST(0, CommentCounter - 1) WHERE id = ?";
            $pdo->prepare($sql)->execute([$PostID]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Comment and its replies deleted successfully']);

        } catch (Exception $e) {
            $pdo->rollBack();
            // Log the specific DB error for debugging
            error_log("Delete Comment Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred while deleting.']);
        }

    } else if ($_POST["ReqType"] == 17) { // DELETE REPLY
        $EncReplyAtr = $_POST['ReplyID'];
        $ReplyID = (int)Decrypt($EncReplyAtr, "Positioned");

        // 1. Verify Ownership & Get CommentID
        $sql = "SELECT CommentID FROM comments_replies WHERE id = ? AND UID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ReplyID, $UID]);
        $replyData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$replyData) {
            echo json_encode(['success' => false, 'message' => 'Reply not found or permission denied.']);
            die();
        }

        $CommentID = $replyData['CommentID'];

        try {
            $pdo->beginTransaction();

            // 2. Delete Likes on this Reply
            $sql = "DELETE FROM comments_replies_likes WHERE ReplyID = ?";
            $pdo->prepare($sql)->execute([$ReplyID]);

            // 3. Delete the Reply
            $sql = "DELETE FROM comments_replies WHERE id = ?";
            $pdo->prepare($sql)->execute([$ReplyID]);

            // 4. Update Comment Counter (ReplyCounter)
            $sql = "UPDATE comments SET ReplyCounter = GREATEST(0, ReplyCounter - 1) WHERE id = ?";
            $pdo->prepare($sql)->execute([$CommentID]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Reply deleted']);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error deleting reply']);
        }
    }
    





}