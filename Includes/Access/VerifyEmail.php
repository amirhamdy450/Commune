<?php
if(!isset($PATH)){
    die("Path not set");
}

include_once $PATH.'Includes/DB.php';

$token = $_GET['token'] ?? '';
$isValidToken = false;

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT email FROM email_verifications WHERE token = ? AND expires > ?");
    $stmt->execute([$token, time()]);
    $isValidToken = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $PATH ?>Styles/Global.css">
    <link rel="stylesheet" href="<?php echo $PATH ?>Styles/Auth.css">
    <title>Verify Email</title>
</head>
<body class="VerifyEmail">
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
            <?php if ($isValidToken): ?>
                <div class="AuthBoxMessage" id="VerifyEmailBox">
                    <h1>Verify Your Email</h1>
                    <p>Click the button below to verify your email address and activate your account.</p>
                    <div class="FormResponse"></div>
                    <button class="BrandBtn" id="VerifyEmailBtn" data-token="<?php echo htmlspecialchars($token); ?>">Verify Email</button>
                </div>
            <?php else: ?>
                <div class="AuthBoxMessage">
                    <h1>Invalid or Expired Link</h1>
                    <p>This verification link is not valid or has already been used. Please register again.</p>
                    <a href="index.php?redirect=reg" class="BrandBtn">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script type="module" src="<?php echo $PATH ?>Scripts/Auth.js"></script>
</body>
</html>
