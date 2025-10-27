<?php 
$PATH="../../";

require_once $PATH."Includes/Config.php";
require_once $PATH.'Includes/UserAuth.php';  //include validation to get user data
include_once $PATH.'Origin/Validation.php';






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
    }
    if($_POST['ReqType']==3){ //update personal info
    }
}
