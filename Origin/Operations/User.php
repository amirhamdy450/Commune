<?php 
$PATH="../../";

require_once $PATH."Includes/Config.php";
require_once $PATH.'Includes/UserAuth.php';  //include validation to get user data
require_once $PATH.'Includes/Encryption.php';
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
    }else if($_POST['ReqType'] == 5){ // FETCH MORE PROFILE POSTS (Infinite Scroll)
        
        $TargetEncUID = $_POST['TargetUID'];
        $LastPostEncID = $_POST['LastPostID'];

        $TargetUID = (int)Decrypt($TargetEncUID, "Positioned");
        $LastPostID = (int)Decrypt($LastPostEncID, "Positioned");

        // Fetch 5 posts OLDER than the last one, strictly for this TargetUID
        $sql = "SELECT 
                posts.id AS PID, posts.*, users.*,
                CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
                FROM posts 
                INNER JOIN users ON posts.UID = users.id
                LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                WHERE posts.Status = 1 AND posts.UID = ? AND posts.id < ?
                ORDER BY posts.Date DESC 
                LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        // Params: [LoggedInUser (for like status), TargetProfileUser, LastPostID]
        $stmt->execute([$UID, $TargetUID, $LastPostID]);
        $NewPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [];

        foreach ($NewPosts as $FeedPost) {
            $timestamp = strtotime($FeedPost['Date']);
            $encryptedFeedPostID = Encrypt($FeedPost['PID'], "Positioned", ["Timestamp" => $timestamp]);
            $encryptedUserID = Encrypt($FeedPost['UID'], "Positioned", ["Timestamp" => $timestamp]);

            // Format Profile Pic
            $PostProfilePic = (isset($FeedPost['ProfilePic']) && !empty($FeedPost['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($FeedPost['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

            // Handle Media
            $MediaFolder = $PATH . $FeedPost['MediaFolder'];
            $media = [];
            if (is_dir($MediaFolder)) {
                $MediaFiles = scandir($MediaFolder);
                foreach ($MediaFiles as $file) {
                    if (!in_array($file, ['.', '..'])) {
                        $media[] = ['name' => $file, 'path' => $FeedPost['MediaFolder'] . '/' . $file];
                    }
                }
            }

            $response[] = [
                'PID' => $encryptedFeedPostID,
                'UID' => $encryptedUserID,
                'name' => $FeedPost['Fname'] . ' ' . $FeedPost['Lname'],
                'Content' => $FeedPost['Content'],
                'LikeCounter' => $FeedPost['LikeCounter'],
                'CommentCounter' => $FeedPost['CommentCounter'],
                'MediaFolder' => $media,
                'MediaType' => (int)$FeedPost['Type'],
                'CurrentUserPrivilege' => (int)$User['Privilege'],
                'liked' => $FeedPost['liked'],
                'ProfilePic' => $PostProfilePic,
                // 'Self' logic is handled by JS comparing IDs if needed, or we can pass it
                'Self' => ($FeedPost['UID'] == $UID) ? 1 : 0
            ];
        }

        echo json_encode($response);
        die();
    }else if ($_POST['ReqType'] == 6) {
        $sql = "SELECT COUNT(*) FROM notifications WHERE ToUID = ? AND IsRead = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$UID]);
        $count = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'count' => $count]);
        die();
    }

    // [ReqType 8] FETCH NOTIFICATIONS & MARK READ
    else if ($_POST['ReqType'] == 7) {
        
        // 1. Mark all as read immediately upon fetching
        $updateSql = "UPDATE notifications SET IsRead = 1 WHERE ToUID = ? AND IsRead = 0";
        $pdo->prepare($updateSql)->execute([$UID]);

        // 2. Fetch the notifications with Actor details
        $sql = "SELECT n.*, 
                u.Fname, u.Lname, u.ProfilePic, u.Username 
                FROM notifications n
                LEFT JOIN users u ON n.FromUID = u.id
                WHERE n.ToUID = ? 
                ORDER BY n.Date DESC 
                LIMIT 20"; // Limit to recent 20
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$UID]);
        $rawNotifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $formatted = [];
        foreach ($rawNotifs as $n) {
            $timestamp = strtotime($n['Date']);
            
            // Encrypt IDs for safe linking
            $encRefID = $n['ReferenceID'] ? Encrypt($n['ReferenceID'], "Positioned", ["Timestamp" => $timestamp]) : null;
            $encFromUID = $n['FromUID'] ? Encrypt($n['FromUID'], "Positioned", ["Timestamp" => $timestamp]) : null;
            
            $ProfilePic = (isset($n['ProfilePic']) && !empty($n['ProfilePic']))
                ? 'MediaFolders/profile_pictures/' . htmlspecialchars($n['ProfilePic'])
                : 'Imgs/Icons/unknown.png';

            $formatted[] = [
                'id' => $n['id'],
                'Type' => (int)$n['Type'],
                'ActorName' => $n['Fname'] . ' ' . $n['Lname'],
                'ActorPic' => $ProfilePic,
                'RefID' => $encRefID,
                'FromUID' => $encFromUID,
                'Date' => date("M d, H:i", $timestamp),
                'MetaInfo' => $n['MetaInfo']
            ];
        }

        echo json_encode(['success' => true, 'notifications' => $formatted]);
        die();
    }
    // --- END OF NEW BLOCK ---

}
