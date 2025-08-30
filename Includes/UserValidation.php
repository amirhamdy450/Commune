<?php
include "DB.php";

$LoggedIn = false;



if (isset($_COOKIE['user_token']) && isset($_COOKIE['user_token2'])) {
    //get tokens from cookie
    $Token1 = $_COOKIE['user_token'];
    $Token2 = $_COOKIE['user_token2'];

    $sql="SELECT UID FROM tokens Where Token = :Token1 AND Token_2=:Token2";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":Token1",$Token1,PDO::PARAM_STR);
    $stmt->bindParam(":Token2",$Token2,PDO::PARAM_STR);
    $stmt->execute();
    

    $UserData=0;
    if($stmt->rowCount()===1){
        $row =$stmt->fetch(PDO::FETCH_ASSOC);   
        $UID = $row['UID'];  //get the UID column corresponding to those tokens
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
       
    }


}else{
    $LoggedIn = false;
   // header("Location: Includes/Access/Login.php");
    include "Includes/Access/Login.php";
    exit();
}


?>