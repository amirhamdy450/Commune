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
    <title>Verify Your Email</title>
</head>
<body class="PendingVerification">
    <div class="AuthContainer">
        <div class="BrandPanel">
            <div class="HeroContainer">
                <div class="Logo" href="index.php">
                    <img src="<?php echo $PATH ?>Imgs/Logo/logo.svg" alt="Logo">
                    OMMUNE
                </div>
                <p>The place where you can truly connect with people all over the world and share your thoughts </p>
            </div>
        </div>

        <div class="AuthBox Login">
            <div class="AuthBoxMessage">
                <h1>Check Your Email</h1>
                <p>We sent a verification link to <strong id="PendingEmail"></strong>. Click the link in the email to activate your account.</p>
                <p style="color:#888; font-size:13px; margin-top:8px;">Didn't receive it? Check your spam folder or resend below.</p>
                <div class="FormResponse" id="ResendResponse"></div>
                <button class="BrandBtn" id="ResendVerificationBtn">Resend Email</button>
                <p class="SecondaryAction" style="margin-top:16px;">Wrong email? <a href="index.php?redirect=reg">Register again</a></p>
            </div>
        </div>
    </div>
    <script type="module" src="<?php echo $PATH ?>Scripts/Auth.js"></script>
</body>
</html>
