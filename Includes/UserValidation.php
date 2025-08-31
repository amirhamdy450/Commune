<?php
include $PATH."Includes/DB.php";
include $PATH."Origin/Auth/Tokens.php";

$LoggedIn = false;



if (isset($_COOKIE['user_token']) && isset($_COOKIE['user_token2'])) {
    //get tokens from cookie
    $Token1 = $_COOKIE['user_token'];
    $Token2 = $_COOKIE['user_token2'];

    $IP = $_SERVER['REMOTE_ADDR'];
    $UserAgent = $_SERVER['HTTP_USER_AGENT'];

    // Validate tokens, IP, and user agent
    $sql = "SELECT id AS EntryID , UID, UpdatedOn FROM tokens WHERE Token = ? AND Token_2 = ? AND IP = ? AND UserAgent = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$Token1, $Token2, $IP, $UserAgent]);
    

    $UserData=0;
    if($stmt->rowCount()===1){
        $row =$stmt->fetch(PDO::FETCH_ASSOC);   
        $UID = $row['UID'];  //get the UID column corresponding to those tokens
        $UpdatedOn = $row['UpdatedOn']; // Get the last updated timestamp
        $EntryID = $row['EntryID'];
        $Now = strtotime("now");

        $thirtyDaysInSeconds = 30 * 24 * 60 * 60; // 30 days in seconds

        if ($Now - $UpdatedOn > $thirtyDaysInSeconds) {
            // Delete the token entry from the database
            $deleteSql = "DELETE FROM tokens WHERE id = ?";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([$EntryID]);

            // Reset cookies
            setcookie("user_token", "", time() - 3600, "/", "localhost", false, true); // Expire the cookie
            setcookie("user_token2", "", time() - 3600, "/", "localhost", false, true); // Expire the cookie

            // Redirect to the login page
            include "Includes/Access/Login.php";
            exit();
        }

        if( $Now > $UpdatedOn ){
            // Regenerate tokens
            $newToken = generateToken();
            $newToken2 = bin2hex(random_bytes(32));
            setTokenCookie($newToken, $newToken2);

            // Update tokens in the database
            $updateSql = "UPDATE tokens SET Token = ?, Token_2 = ?, UpdatedOn = ? WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$newToken, $newToken2, $Now, $EntryID]);

        }

        //echo $UID;
        $sql="SELECT * FROM users WHERE id=:UID";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":UID",$UID,PDO::PARAM_INT);
        $stmt->execute();
        $UserData =$stmt->fetch(PDO::FETCH_ASSOC);   

        $CompanyName='Commune'; 
        $iv = "COMMUNE2025_9831"; //like secret key and will be used for decrypting AES later


        //allowed extensions array
        $AllowedDocumentExtensions = ['pdf', 'doc', 'docx', 'txt','xls','xlsx','ppt','pptx'];




        $AllowedImagesExtensions=['xbm', 'tif', 'jfif', 'ico', 'tiff', 'gif', 'svg', 'webp', 'svgz', 'jpg', 'jpeg', 'png', 'bmp', 'pjp', 'apng', 'pjpeg', 'avif'];

        $LoggedIn = true;
       
    }else{
        $LoggedIn = false;
       // header("Location: Includes/Access/Login.php");
        include "Includes/Access/Login.php";
        exit();
    }


}else{
    $LoggedIn = false;
   // header("Location: Includes/Access/Login.php");
    include "Includes/Access/Login.php";
    exit();
}


?>