<?php
if(!isset($PATH)){
    die("Path not set");
}

include_once $PATH.'Includes/DB.php';

$token = $_GET['token'] ?? '';
$isValidToken = false;
$userEmail = '';

if (!empty($token)) {
    // Check if token exists and has not expired
    $sql = "SELECT email, expires FROM password_reset_tokens WHERE token = ? AND expires > ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token, time()]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $isValidToken = true;
        $userEmail = $result['email'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $PATH ?>Styles/Global.css">
    <link rel="stylesheet" href="<?php echo $PATH ?>Styles/Auth.css">
    <title>Reset Password</title>
</head>
<body class="Login">
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

        <div class="AuthBox Login ">
            <?php if ($isValidToken): ?>
                <form id="ResetPasswordForm">
                    <h1>Create New Password</h1>
                    <p>Please create a new password for your account: <strong><?php echo htmlspecialchars($userEmail); ?></strong></p>
                    
                    <div class="FormResponse"></div> <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="TextField Pass">
                        <div class="IconCont">
                            <input type="password" placeholder="New Password" name="pass" id="pass">
                            <img src="<?php echo $PATH ?>Imgs/Icons/EyeOff.svg" alt="Toggle">
                        </div>
                    </div>  
                    <div class="TextField Pass">
                        <div class="IconCont">
                            <input type="password" placeholder="Confirm New Password" name="cpass" id="cpass">
                            <img src="<?php echo $PATH ?>Imgs/Icons/EyeOff.svg" alt="Toggle">
                        </div>
                    </div>  

                    <input type="submit" name="Reset" value="Save New Password" class="BrandBtn">
                </form>
            <?php else: ?>
                <div class="AuthBoxMessage">
                    <h1>Invalid or Expired Link</h1>
                    <p>This password reset link is not valid or has expired. Please request a new one.</p>
                    <a href="index.php?redirect=forgot-password" class="BrandBtn">Request New Link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<script  type="module" src="<?php echo $PATH ?>Scripts/Auth.js"></script>
</body>
</html>