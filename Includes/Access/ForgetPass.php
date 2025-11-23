<?php
if(!isset($PATH)){
    die("Path not set");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $PATH ?>Styles/Global.css">
    <link rel="stylesheet" href="<?php echo $PATH ?>Styles/Auth.css">
    <title>Forgot Password</title>
</head>
<body class="Login">
    <div class="AuthContainer">
        <div class="AuthBox Login ">
            
            <form id="RequestResetForm">
                <h1>Reset Your Password</h1>
                <p>Enter your email address and we will send you a link to reset your password.</p>
                
                <div class="FormResponse"></div> <div class="TextField">
                    <input type="text" name="email" placeholder="Your Email Address" >
                </div>  

                <input type="submit" name="Request" value="Send Reset Link" class="BrandBtn">
                <div class="Loader hidden"></div>

                <p class="SecondaryAction">Remember your password? <a href="<?php echo $PATH ?>index.php">Login</a></p>
            </form>

            <div class="AuthBoxMessage hidden" id="ResetSuccessView">
                <img src="Imgs/Icons/Checkmark.svg" alt="Success">
                <h2>Check Your Inbox</h2>
                <p>A password reset link has been sent to your email address (if it exists in our system).</p>
                <a href="index.php" class="BrandBtn Dark">Back to Login</a>
            </div>
            </div>
    </div>
<script  type="module" src="<?php echo $PATH ?>Scripts/Auth.js"></script>
</body>
</html>