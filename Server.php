<?php 
include 'Includes/UserValidation.php';  //include validation to get user data


function RowExists($Table,$Column,$Value){ //check if row exists

    global $pdo; // Use the global PDO instance


    // Ensure $Column and $Value are treated as arrays for consistent processing
    $Columns = is_array($Column) ? $Column : [$Column];
    $Values = is_array($Value) ? $Value : [$Value];

    if (count($Columns) !== count($Values)) {
        // You might want to throw an exception or return false with an error message
        // For simplicity here, we'll just return false.
        echo "RowExists: Mismatch between number of columns and values.";
        return false;
    }

    // Prepare the SQL query with placeholders for each column
    $conditions = [];
    $boundParams = [];

    for ($i = 0; $i < count($Columns); $i++) {
        $conditions[] = "`" . $Columns[$i] . "` = ?"; // Add backticks for column names for safety
        $boundParams[] = $Values[$i];
    }

    // Join conditions with 'AND'
    $whereClause = implode(' AND ', $conditions);

    //check if valid in DB by only retrieving one row
    $query = "SELECT 1 FROM `$Table` WHERE " . $whereClause . " LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute($boundParams);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return true; // Row exists
    } else {
        return false; // Row does not exist
    }
}




if($_SERVER['REQUEST_METHOD']==='POST'){

    $UID=$UserData['id'];


    if($_POST['ReqType'] == 1){ //add new post to DB
        $PostContent = $_POST['content']; //get the text in post
        //echo $content;
    
    
        //we will use this later in folder creation and then the DB insertion
        $FolderPath='';
    
    
    
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
            $FolderPath="MediaFolders/". $CreationTime.$UID.uniqid(); //create the new folder path in a variable because we will use it later
    
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
    
        $data=[$PostContent,$type,$FolderPath,date("Y-m-d H:i:s"),1,$UID];
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
        $feedPostID = 'D' . $timestamp . 'I' . $newPost['PID'];
        $encryptedFeedPostID = base64_encode(openssl_encrypt($feedPostID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv));

        $media = [];
        if (is_dir($FolderPath)) {
            $mediaFiles = scandir($FolderPath);
            foreach ($mediaFiles as $file) {
                if ($file !== '.' && $file !== '..') {
                    $media[] = ['name' => $file, 'path' => $FolderPath . '/' . $file];
                }
            }
        }

        $responsePost = [
            'PID' => $encryptedFeedPostID,
            'name' => $newPost['name'],
            'Content' => $newPost['Content'],
            'LikeCounter' => $newPost['LikeCounter'],
            'CommentCounter' => $newPost['CommentCounter'],
            'MediaFolder' => $media,
            'MediaType' => (int)$newPost['Type'],
            'CurrentUserPrivilege' => (int)$UserData['Privilege'],
            'liked' => false
        ];

        echo json_encode([
            'success' => true,
            'message' => "Post added successfully",
            'post' => $responsePost
        ]);
        die();
    

        //send back the post
/*         $response[] = [
            'PID' => $encryptedFeedPostID,
            'name' => $FeedPost['name'],
            'Content' => $FeedPost['Content'],
            'LikeCounter' => $FeedPost['LikeCounter'],
            'CommentCounter' => $FeedPost['CommentCounter'],
            'MediaFolder' => $media,
            'MediaType'=> (int)$FeedPost['Type'],
            'CurrentUserPrivilege'=> (int)$UserData['Privilege'],
        ];
 */

    
    } else if($_POST['ReqType'] == 2){  //like a post

        $EncFeedPostAtr=$_POST['FeedPostID']; //Enc stands for encrypted and atr stands for atribute
        $FeedPostAtr= openssl_decrypt(base64_decode($EncFeedPostAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);

        // Find the position of the 'I' to retrieve the  post id bieng liked
        $PostIDPosition = strpos($FeedPostAtr, 'I');
        $FeedPostID=(int)substr($FeedPostAtr, $PostIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer


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
        $FeedPostAtr= openssl_decrypt(base64_decode($EncFeedPostAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);

        // Find the position of the 'I' to retrieve the  post id bieng liked
        $PostIDPosition = strpos($FeedPostAtr, 'I');
        $FeedPostID=(int)substr($FeedPostAtr, $PostIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer

        $CommentContent=$_POST['CommentContent'];


        //Add the comment to the database
        $sql = 'INSERT INTO comments(comment,PostID,UID) VALUES (?,?,?)'; //add the id of the  post and the id of the user who liked it

        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$CommentContent,$FeedPostID, $UID])) { //execute the query and if successful we will do another query inside the posts table

            //increment the likes count  in the posts table

            $sql = 'UPDATE posts SET CommentCounter=CommentCounter+1 WHERE id=?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$FeedPostID]);


            echo json_encode([
                'success' => true,
                'message' => "Error: Failed To insert comment"

            ]);


        }else{
            echo json_encode([
                'success' => false,
                'message' => "Error: Failed To insert comment"

            ]);
        }
    } else if($_POST["ReqType"] == 4){ //fetch comments
        $EncFeedPostAtr=$_POST['FeedPostID']; //Enc stands for encrypted and atr stands for atribute
        $FeedPostAtr= openssl_decrypt(base64_decode($EncFeedPostAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);

        // Find the position of the 'I' to retrieve the  post id bieng liked
        $PostIDPosition = strpos($FeedPostAtr, 'I');
        $FeedPostID=(int)substr($FeedPostAtr, $PostIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer


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
                $FormattedID = 'D'.$timestamp.'I'.$Comment['CID'];

                $encrypted = openssl_encrypt($FormattedID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);
                $Comment['CID'] = base64_encode($encrypted); // Makes it JSON-safe


                //encrypt the user id (UID)
                $FormattedID = 'D'.$timestamp.'I'.$Comment['UID'];

                $encrypted = openssl_encrypt($FormattedID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);
                $Comment['UID'] = base64_encode($encrypted); // Makes it JSON-safe
            }
            unset($Comment);



            echo json_encode($Comments);

        }


    } else if ($_POST["ReqType"] == 5) { //fetch new posts to feed
        $EncFeedPostAtr=$_POST['LastFeedPostPID']; //Enc stands for encrypted and atr stands for atribute
        $FeedPostAtr= openssl_decrypt(base64_decode($EncFeedPostAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);

        // Find the position of the 'I' to retrieve the  post id bieng liked
        $PostIDPosition = strpos($FeedPostAtr, 'I');
        $FeedPostID=(int)substr($FeedPostAtr, $PostIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer


        $sql='SELECT 
              posts.id as PID,posts.*,users.* ,CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
              FROM posts 
              LEFT JOIN likes ON posts.id=likes.PostID AND likes.UID=?
              INNER JOIN users ON posts.UID=users.id 
              WHERE posts.id<? AND posts.Status=1 
              ORDER BY posts.Date DESC  LIMIT 5 ';
        $stmt = $pdo->prepare($sql);
        
        if($stmt->execute([$UID,$FeedPostID])){

        $NewPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [];

        foreach ($NewPosts as $FeedPost) {
            //convert Date to a Unix timestamp
            $timestamp = strtotime($FeedPost['Date']);
            $FeedPostID = 'D'.$timestamp.'I'.$FeedPost['PID'];
            $encryptedFeedPostID = base64_encode(openssl_encrypt($FeedPostID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv));
            
            $MediaFolder = $FeedPost['MediaFolder'];
            $media = [];
            if (is_dir($MediaFolder)) {
                $MediaFiles = scandir($MediaFolder);
                foreach ($MediaFiles as $file) {
                    if (in_array(strtolower($file), ['.', '..'])) { //this to ignore dots that are treated as files in scandir , (.) represents current directory and (..) represents parent directory

                        continue;  //skip this iteration
                    }

                    $filePath = $MediaFolder . '/' . $file;

                    $media[] = [
                        'name'=>$file,
                        'path' => $filePath,
                    ];
                    
                }
            }
        
            // Add post details to the response array
            $response[] = [
                'PID' => $encryptedFeedPostID,
                'name' => $FeedPost['name'],
                'Content' => $FeedPost['Content'],
                'LikeCounter' => $FeedPost['LikeCounter'],
                'CommentCounter' => $FeedPost['CommentCounter'],
                'MediaFolder' => $media,
                'MediaType'=> (int)$FeedPost['Type'],
                'CurrentUserPrivilege'=> (int)$UserData['Privilege'],
                'liked'=>$FeedPost['liked']
            ];


        }


        echo json_encode($response);
        }

    } else if($_POST["ReqType"] == 6){ //delete a post
        $EncFeedPostAtr=$_POST['FeedPostID']; //Enc stands for encrypted and atr stands for atribute
        $FeedPostAtr= openssl_decrypt(base64_decode($EncFeedPostAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);

        // Find the position of the 'I' to retrieve the  post id bieng liked
        $PostIDPosition = strpos($FeedPostAtr, 'I');
        $FeedPostID=(int)substr($FeedPostAtr, $PostIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer
        
        $sql="UPDATE posts SET Status=0 WHERE id=?";
        $stmt=$pdo->prepare($sql);
        if($stmt->execute([$FeedPostID])){
            
            echo json_encode([
                'success' => true,
                'message' => "Post Deleted",

            ]);

        }

    } else if ($_POST["ReqType"] == 7) { //like a comment
        $EncCommentAtr=$_POST['CommentID']; //Enc stands for encrypted and atr stands for atribute
        $CommentAtr= openssl_decrypt(base64_decode($EncCommentAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv); 
   
        // Find the position of the 'I' to retrieve the  comment id bieng liked
        $CommentIDPosition = strpos($CommentAtr, 'I');
        $CommentID=(int)substr($CommentAtr, $CommentIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer

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
        $CommentAtr= openssl_decrypt(base64_decode($EncCommentAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);

        // Find the position of the 'I' to retrieve the  post id bieng liked
        $CommentIDPosition = strpos($CommentAtr, 'I');
        $CommentID=(int)substr($CommentAtr, $CommentIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer

        if(!RowExists('comments','id',$CommentID)){
            echo json_encode([
                'success' => false,
                'message' => "Comment not found",
            ]);
            die();
        }


        if(isset($_POST['ReplyTo'])){
            $EncUserAtr=$_POST['ReplyTo']; //Enc stands for encrypted and atr stands for atribute
            $UserAtr= openssl_decrypt(base64_decode($EncUserAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv); 
   
            // Find the position of the 'I' to retrieve the  comment id bieng liked
            $UserIDPosition = strpos($UserAtr, 'I');
            $UserID=(int)substr($UserAtr, $UserIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer

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


        echo json_encode([
            'success' => true,
            'message' => "Reply inserted",
        ]);


    }else if($_POST['ReqType']==9){ //like a reply
        $EncReplyAtr=$_POST['ReplyID']; //Enc stands for encrypted and atr stands for atribute
        $ReplyAtr= openssl_decrypt(base64_decode($EncReplyAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv); 
   
        // Find the position of the 'I' to retrieve the  comment id bieng liked
        $ReplyIDPosition = strpos($ReplyAtr, 'I');
        $ReplyID=(int)substr($ReplyAtr, $ReplyIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer

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
        $CommentAtr= openssl_decrypt(base64_decode($EncCommentAtr), 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv); 
   
        // Find the position of the 'I' to retrieve the  comment id bieng liked
        $CommentIDPosition = strpos($CommentAtr, 'I');
        $CommentID=(int)substr($CommentAtr, $CommentIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer

        if(!RowExists('comments','id',$CommentID)){
            echo json_encode([
                'success' => false,
                'message' => "Comment not found",
            ]);
            die();
        }

        //get al replies to that comment
        $sql="SELECT CR.id AS CRID, CR.UID,CR.Reply,CR.LikeCounter, CR.Date,Sender.Name AS Sender,
        Sender.Username AS SenderUsername,Tagged.Username AS TaggedUser ,
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
            $FormattedID = 'D'.$timestamp.'I'.$Reply['CRID'];

            $encrypted = openssl_encrypt($FormattedID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);
            $Reply['CRID'] = base64_encode($encrypted); // Makes it JSON-safe


            //encrypt the user id (UID)
            $FormattedID = 'D'.$timestamp.'I'.$Reply['UID'];

            $encrypted = openssl_encrypt($FormattedID, 'aes-256-cbc', $CompanyName, OPENSSL_RAW_DATA, $iv);
            $Reply['UID'] = base64_encode($encrypted); // Makes it JSON-safe
        }
        unset($Reply);

        echo json_encode($Replies);

   
    }
    





}