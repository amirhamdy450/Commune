<?php

if(!isset($PATH)){
    die("Path not set");
}


//check if redirect is set to registeration
if (isset($_GET['redirect'])) {
    $Redirect = $_GET['redirect'];
    if($Redirect == "reg"){

        include $PATH.'Includes/Access/Register.php';
        die();

    }

    if($Redirect == "self"){
        include $PATH.'Includes/Profile/Profile.php';
        die();
    }



    if($Redirect == "logout"){
        include $PATH.'Includes/Access/Logout.php';
        die();
    }

}




//checking target if its not redirect
if(isset($_GET['target'])){
    $target=$_GET['target'];

    //check if url has a pid param to show a specific post
    if($target == "post"){
        if (isset($_GET['pid'])) {

            $PostID = $_GET['pid'];
            include 'post.php';
            die();
        
        }else{
            header("Location: 404.php");
            exit();
        }
    }


    if($target == "profile"){
        if (isset($_GET['uid'])) {
            $ProfileUserID = $_GET['uid'];
            include 'VProfile.php';
            die();
        
        }else{
            header("Location: 404.php");
            exit();
        }
    }


}

