<?php 
$PATH="../../";

require_once $PATH."Includes/Config.php";
require_once $PATH.'Includes/UserAuth.php';  //include validation to get user data
include_once $PATH.'Origin/Validation.php';



$ProfilePicsFolder=$PATH."MediaFolders/profile_pictures/";

$CoverPicsFolder=$PATH."MediaFolders/cover_pictures/";


if($_SERVER['REQUEST_METHOD']==='POST'){

    $UID=$User['id'];

    if($_POST['ReqType']==1){ //fetch new user posts
        $EncFeedPostAtr=$_POST['LastFeedPostPID']; //Enc stands for encrypted and atr stands for atribute
        $FeedPostAtr= openssl_decrypt(base64_decode($EncFeedPostAtr), 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV);

        // Find the position of the 'I' to retrieve the  post id bieng liked
        $PostIDPosition = strpos($FeedPostAtr, 'I');
        $FeedPostID=(int)substr($FeedPostAtr, $PostIDPosition + 1); //the position after I is the id , retrieve it and convert it to integer


        $sql='SELECT 
              posts.id as PID,posts.*,users.* ,CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
              FROM posts 
              LEFT JOIN likes ON posts.id=likes.PostID AND likes.UID=?
              INNER JOIN users ON posts.UID=users.id 
              WHERE posts.id<? AND posts.Status=1 AND posts.UID=?
              ORDER BY posts.Date DESC  LIMIT 5 ';
        $stmt = $pdo->prepare($sql);
        
        if($stmt->execute([$UID,$FeedPostID,$UID])){

            $NewPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [];

            foreach ($NewPosts as $FeedPost) {
                //convert Date to a Unix timestamp
                $timestamp = strtotime($FeedPost['Date']);
                $FeedPostID = 'D'.$timestamp.'I'.$FeedPost['PID'];
                $encryptedFeedPostID = base64_encode(openssl_encrypt($FeedPostID, 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, ENCRYPTION_IV));
                
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
                    'name' => $FeedPost['name'],
                    'Content' => $FeedPost['Content'],
                    'LikeCounter' => $FeedPost['LikeCounter'],
                    'CommentCounter' => $FeedPost['CommentCounter'],
                    'MediaFolder' => $media,
                    'MediaType'=> (int)$FeedPost['Type'],
                    'CurrentUserPrivilege'=> (int)$User['Privilege'],
                    'liked'=>$FeedPost['liked']
                ];


            }


            echo json_encode($response);
        }
    }if($_POST['ReqType']==2){ //update profile photo
        
        if (isset($_FILES['profile_pic'])) {
            $file = $_FILES['profile_pic'];

            // 1. Validation
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // We use $AllowedImagesExtensions from UserAuth.php
            if (!in_array($fileExtension, $AllowedImagesExtensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a valid image.']);
                die();
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5 MB limit
                echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
                die();
            }



            $Time=strtotime("now");
            // 2. Define Path and Create Directory
            $UploadDir = $ProfilePicsFolder . $Time . $UID . '/';

            $RootUploadDir = $Time . $UID . '/'; // Path to store in DB
            
            if (!is_dir($UploadDir)) {
                mkdir($UploadDir, 0777, TRUE);
            }

            // 3. Generate Unique Filename
            $newFilename = $UID . '_' . uniqid() . '.' . $fileExtension;
            $targetPath = $UploadDir . $newFilename;
            $dbPath = $RootUploadDir . $newFilename;

            // 4. Move File
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                
                // 5. Update Database
                try {
                    // TODO: Add logic to delete the OLD profile picture from the server
                    
                    $sql = "UPDATE users SET ProfilePic = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$dbPath, $UID]);

                    // Update session
                    $_SESSION['user_data']['ProfilePic'] = $dbPath;

                    echo json_encode([
                        'success' => true,
                        'message' => 'Profile picture updated!',
                        'newImagePath' => $dbPath 
                    ]);

                } catch (PDOException $e) {
                    error_log("DB Error (ProfilePic): " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error. Could not save changes.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Server error. Could not move uploaded file.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file was uploaded.']);
        }
        die();
    }
    else if($_POST['ReqType']==3){ //update personal info
        
        $FirstName = trim($_POST['fname']);
        $LastName = trim($_POST['lname']);
        $Username = trim($_POST['username']);
        $Bio = trim($_POST['bio']); // Bio can be empty
        $Birthday = trim($_POST['bday']);
        $Gender = trim($_POST['gender']);
        $CountryID = trim($_POST['country']);
        
        // 1. Server-side validation
        if (!ValidateName($FirstName)) {
            echo json_encode(['success' => false, 'message' => 'Invalid First Name.']);
            die();
        }
        if (!ValidateName($LastName)) {
            echo json_encode(['success' => false, 'message' => 'Invalid Last Name.']);
            die();
        }
        
        if (empty($Username) ) {
             echo json_encode(['success' => false, 'message' => 'Username cannot be empty.']);
            die();
        }

        if (!validateBirthYear($Birthday)) { // Using validation from Origin/Validation.php
             echo json_encode(['success' => false, 'message' => 'Invalid Birthday.']);
            die();
        }
        
        if (!validateBoolean($Gender)) { // Using validation from Origin/Validation.php
             echo json_encode(['success' => false, 'message' => 'Invalid Gender.']);
            die();
        }

        // Check if CountryID is a valid integer (and exists, optional but good)
        if (!filter_var($CountryID, FILTER_VALIDATE_INT) || !RowExists('countries', 'id', $CountryID)) {
            echo json_encode(['success' => false, 'message' => 'Invalid Country.']);
            die();
        }

        // 2. Check if Username already exists (for a DIFFERENT user)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Username = ? AND id != ?");
        $stmt->execute([$Username, $UID]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'This username is already taken.']);
            die();
        }

        // 3. Update the database
        try {
            $sql = "UPDATE users SET 
                        Fname = ?, Lname = ?, Username = ?, Bio = ?, 
                        BirthDay = ?, Gender = ?, CountryID = ? 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$FirstName, $LastName, $Username, $Bio, $Birthday, $Gender, $CountryID, $UID]);
            
            // 4. Send back a success response with the new data
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'newData' => [
                    'Fname' => $FirstName,
                    'Lname' => $LastName,
                    'Username' => $Username
                ]
            ]);
            
            // Update the session data as well
            $_SESSION['user_data']['Fname'] = $FirstName;
            $_SESSION['user_data']['Lname'] = $LastName;
            $_SESSION['user_data']['Username'] = $Username;
            
        } catch (PDOException $e) {
            error_log("Profile Update Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'A database error occurred. Could not update profile.']);
        }
        die();
    }if($_POST['ReqType']==4){ //update cover photo
        
        if (isset($_FILES['cover_photo'])) {
            $file = $_FILES['cover_photo'];

            // 1. Validation
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // We use $AllowedImagesExtensions from UserAuth.php
            if (!in_array($fileExtension, $AllowedImagesExtensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a valid image.']);
                die();
            }

            if ($file['size'] > 10 * 1024 * 1024) { // 10 MB limit for cover photo
                echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 10MB.']);
                die();
            }


            $Time=strtotime("now");
            // 2. Define Path and Create Directory
            $UploadDir = $CoverPicsFolder . $Time .$UID . '/';
            $RootUploadDir = $Time .$UID . '/'; // Path to store in DB
            
            if (!is_dir($UploadDir)) {
                mkdir($UploadDir, 0777, TRUE);
            }

            // 3. Generate Unique Filename
            $newFilename = $UID . '_' . uniqid() . '.' . $fileExtension;
            $targetPath = $UploadDir . $newFilename;
            $dbPath = $RootUploadDir . $newFilename;

            // 4. Move File
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                
                // 5. Update Database
                try {
                    // TODO: Add logic to delete the OLD cover photo from the server
                    
                    $sql = "UPDATE users SET CoverPhoto = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$dbPath, $UID]);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Cover photo updated!',
                        'newImagePath' => $dbPath 
                    ]);

                } catch (PDOException $e) {
                    error_log("DB Error (CoverPhoto): " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error. Could not save changes.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Server error. Could not move uploaded file.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file was uploaded.']);
        }
        die();
    }
    // --- END OF NEW BLOCK ---

}
