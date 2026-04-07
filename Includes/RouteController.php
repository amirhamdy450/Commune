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

    if($Redirect == "saved"){
        include $PATH.'Includes/SavedPosts.php';
        die();
    }


    if($Redirect == "forgot-password"){
        include $PATH.'Includes/Access/ForgetPass.php';
        die();
    }

    if($Redirect == "pending-verification"){
        include $PATH.'Includes/Access/PendingVerification.php';
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

    // --- POST ---
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


    // --- SEARCH ---
    if($target == "search"){
        if (isset($_GET['query'])) {
            $SearchQuery = $_GET['query']; // This var will be used by search.php
            include $PATH.'Includes/Search.php';
            die();
        } else {
            // No query? Just go to the main page.
            header("Location: index.php");
            exit();
        }
    }


    if($target == "profile"){

        if (isset($_GET['uid'])) {
            $ProfileUserID = $_GET['uid'];
            include 'VProfile.php';
            die();

        }else if (isset($_GET['username'])) {
            // VProfile.php will resolve the username after its own includes are loaded
            $ProfileUserID = null;
            include 'VProfile.php';
            die();

        }else{
            header("Location: 404.php");
            exit();
        }
    }

    if($target == "settings"){
        include 'Settings.php';
        die();
    }

    if($target == "admin"){
        include $PATH.'Admin.php';
        die();
    }

    if($target == "page"){
        if (isset($_GET['handle'])) {
            $PageHandle = $_GET['handle'];
            include $PATH.'PageProfile.php';
            die();
        } else {
            header("Location: index.php");
            exit();
        }
    }

    if($target == "get-verified"){
        include $PATH.'Includes/Access/GetVerified.php';
        die();
    }


    if($target == "verify-email"){
        if (isset($_GET['token'])) {
            $VerifyToken = $_GET['token'];
            include $PATH.'Includes/Access/VerifyEmail.php';
            die();
        } else {
            header("Location: index.php");
            exit();
        }
    }

    if($target == "reset-password"){
        if (isset($_GET['token'])) {
            $ResetToken = $_GET['token']; // This var will be used by reset-password.php
            include $PATH.'Includes/Access/ResetPass.php';
            die();
        } else {
            // No token? Go to forgot password page.
            header("Location: index.php?redirect=forgot-password");
            exit();
        }
    }


}

