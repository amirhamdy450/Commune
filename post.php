<?php
include 'Includes/UserAuth.php';
include 'Includes/Encryption.php';

$DocumentExtensions = '.pdf, .doc, .docx, .txt ,.xls,.xlsx,.ppt,.pptx';

// Decrypt the Post ID from URL
$PostID = (int)Decrypt($PostID, "Positioned");
if($PostID <= 0){ header("Location: 404.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $CsrfToken; ?>">
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Feed.css">
    <link rel="stylesheet" href="Styles/PostView.css"> 
    <title>Post View</title>
</head>
<body class="PostView">

    <?php include 'Includes/NavBar.php'; ?>

    <div class="FlexContainer">
        <div class="FeedContainer" id="SinglePostContainer">
            <div class="FeedLoader"><div class="Loader"></div></div>
        </div>
    </div>

    <?php
        // 1. Fetch Post
        $sql = "SELECT posts.id AS PID, posts.*, users.Fname, users.Lname, users.Username, users.ProfilePic,
                CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved
                FROM posts 
                INNER JOIN users ON posts.UID = users.id
                LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                WHERE posts.id = ? AND posts.Status = 1 LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$UID, $UID, $UID, $PostID]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        $jsonPayload = "";
        
        if ($post) {
            // 2. Fetch Comments
            $sqlComments = 'SELECT comments.id as CID, comments.*, users.Fname, users.Lname, users.Username, users.ProfilePic,
                            CASE WHEN CL.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
                            FROM comments 
                            INNER JOIN users ON comments.UID=users.id 
                            LEFT JOIN comments_likes CL ON comments.id=CL.CommentID AND CL.UID=?       
                            WHERE comments.PostID=? ORDER BY comments.Date DESC';
            
            $stmtComm = $pdo->prepare($sqlComments);
            $stmtComm->execute([$UID, $PostID]);
            $rawComments = $stmtComm->fetchAll(PDO::FETCH_ASSOC);
            
            // Format Comments
            $formattedComments = [];
            foreach($rawComments as $c) {
                // *** FIX START: Calculate IsSelf BEFORE encrypting UID ***
                $c['IsSelf'] = ($c['UID'] == $UID);
                // *** FIX END ***

                $cTimestamp = strtotime($c['Date']);
                $c['CID'] = Encrypt($c['id'], "Positioned", ["Timestamp"=>$cTimestamp]);
                $c['UID'] = Encrypt($c['UID'], "Positioned", ["Timestamp"=>$cTimestamp]); // Encrypt AFTER check
                
                $c['ProfilePic'] = (isset($c['ProfilePic']) && !empty($c['ProfilePic']))
                    ? 'MediaFolders/profile_pictures/' . htmlspecialchars($c['ProfilePic'])
                    : 'Imgs/Icons/unknown.png';
                
                $formattedComments[] = $c;
            }

            // 3. Format Post
            $timestamp = strtotime($post['Date']);
            $encryptedFeedPostID = Encrypt($post['PID'], "Positioned", ["Timestamp" => $timestamp]);
            $encryptedUserID = Encrypt($post['UID'], "Positioned", ["Timestamp" => $timestamp]);
            
            $PostProfilePic = (isset($post['ProfilePic']) && !empty($post['ProfilePic'])) 
                ? 'MediaFolders/profile_pictures/' . htmlspecialchars($post['ProfilePic']) 
                : 'Imgs/Icons/unknown.png';

            $MediaFolder = $PATH . $post['MediaFolder'];
            $media = [];
            if (is_dir($MediaFolder)) {
                $MediaFiles = scandir($MediaFolder);
                foreach ($MediaFiles as $file) {
                    if (!in_array($file, ['.', '..'])) {
                        $media[] = ['name' => $file, 'path' => $post['MediaFolder'] . '/' . $file];
                    }
                }
            }

            $finalData = [
                'post' => [
                    'PID' => $encryptedFeedPostID,
                    'UID' => $encryptedUserID,
                    'name' => htmlspecialchars($post['Fname'] . ' ' . $post['Lname']),
                    'Username' => htmlspecialchars($post['Username']),
                    'ProfilePic' => $PostProfilePic,
                    'Content' => htmlspecialchars($post['Content']),
                    'LikeCounter' => $post['LikeCounter'],
                    'CommentCounter' => $post['CommentCounter'],
                    'MediaFolder' => $media,
                    'MediaType' => (int)$post['Type'],
                    'liked' => (bool)$post['liked'],
                    'following' => (bool)$post['following'],
                    'Self' => (int)($post['UID'] == $UID),
                    'saved' => (int)$post['saved']
                ],
                'comments' => $formattedComments
            ];
            
            $jsonPayload = htmlspecialchars(json_encode($finalData), ENT_QUOTES, 'UTF-8');
        }
    ?>

    <div id="PageData" data-payload="<?php echo $jsonPayload; ?>" class="hidden"></div>

    <?php include 'Includes/Modals/CreatePost.php'; ?>
    <?php include 'Includes/Modals/CommentSection.php'; ?>
    <?php include 'Includes/Modals/Confirmation.php'; ?>

    <script src="Scripts/modal.js"></script>
    <script type="module" src="Scripts/Feed.js"></script>
    <script type="module" src="Scripts/PostView.js"></script>
</body>
</html>