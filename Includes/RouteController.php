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




//check if url has a pid param to show a specific post
if (isset($_GET['pid'])) {
    $PostID = $_GET['pid'];
    include 'post.php';
    die();
}


