<?php
$PATH="../../";
include_once $PATH.'Includes/DB.php';
include_once $PATH.'Origin/Validation.php';
include_once $PATH.'Origin/Auth/Tokens.php';


// --- START NEW PHPMailer SETUP ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $PATH.'vendor/phpmailer/phpmailer/src/Exception.php';
require $PATH.'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require $PATH.'vendor/phpmailer/phpmailer/src/SMTP.php';
// --- END NEW PHPMailer SETUP ---

//Error codes:
/*  severity increases with number
41-49 - Client-side validation errors
51-59 - Server-side validation errors (authentication failures, db entry verification, etc.)
61-69 - Database errors (connections, query failures, etc.)
71-79 - internal server errors (unexpected conditions , server downtime, etc.)

*/


function UsernameExists($Username){
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Username = ?");
    $stmt->execute([$Username]);

    return $stmt->fetchColumn() > 0;
}




if($_SERVER['REQUEST_METHOD']==='POST'){



    if($_POST['ReqType'] == 1){ //Register User

        $FirstName = $_POST['fname'];
        $LastName = $_POST['lname'];
        $Email = $_POST['email'];
        $Birthday = $_POST['bday'];
        $Gender=(int)$_POST['gender'];
        $Country = $_POST['country'];
        $Password = $_POST['pass'];




        //validate (server side)
        if(!ValidateName($FirstName)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid First Name'
            ]);
            die();
        }else if(!ValidateName($LastName)){
             echo json_encode([
                'status' => false,
                'message' => 'Invalid Last Name'
            ]);
            die();

        }else if(!ValidateEmail($Email)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Email !'
            ]);
            die();

        }else if(!validateBirthYear($Birthday)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Birthday !'
            ]);
            die();

        }else if(!validateBoolean($Gender)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Gender !'
            ]);
            die();
        }else if(!validateCountryCode($Country)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Country Code !'
            ]);
            die();

        }else if(!ValidatePassword($Password)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Password !'
            ]);
            die();

        }

        //check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ?");
        $stmt->execute([$Email]);
        if($stmt->fetchColumn() > 0){
            echo json_encode([
                'status' => false,
                'message' => 'Email already registered !'
            ]);
            die();
        }



        //check what country id corresponds to the code
        $sql = "SELECT id FROM countries WHERE code = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$Country]);
        $CountryID = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

        if(!$CountryID){
            echo json_encode([
                'status' => false,
                'message' => 'Country not found!'
            ]);
            die();
        }




        //hash the password using MD5
        $Password = password_hash($Password, PASSWORD_BCRYPT);


        //Cocatenate the first and last name into a single string
        //$FullName = $FirstName . ' ' . $LastName;

        //generate a random username
        $base = strtolower($FirstName .'_'. $LastName);
        $random_suffix = bin2hex(random_bytes(4)); 
        $Suggested_Username = $base . $random_suffix;

        if(UsernameExists($Suggested_Username)){
            $counter=0;
            $Suggested_Username = $base . '_'. $random_suffix . $counter;
            while(UsernameExists($Suggested_Username)){
                $counter++;
                $random_suffix = bin2hex(random_bytes(4)); 
                $Suggested_Username = $base . '_'. $random_suffix . $counter;
            }
        }


        //insert into DB (IsVerified=0 until email is confirmed)
        $sql = "INSERT INTO `users` (`Fname`, `Lname`,`Username`, `Email`, `Birthday` , `Gender`, `CountryID`, `Password`, `IsVerified`) VALUES ( ?, ? ,?, ?, ?, ?, ?, ?, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$FirstName, $LastName, $Suggested_Username, $Email, $Birthday, $Gender, $CountryID, $Password]);

        // Generate email verification token and send it
        $verifyToken = bin2hex(random_bytes(32));
        $verifyExpires = time() + 86400; // 24 hours

        $pdo->prepare("DELETE FROM email_verifications WHERE email = ?")->execute([$Email]);
        $pdo->prepare("INSERT INTO email_verifications (email, token, expires) VALUES (?, ?, ?)")->execute([$Email, $verifyToken, $verifyExpires]);

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($Email);
            $mail->isHTML(true);
            $mail->Subject = 'Verify your Commune account';
            $verifyLink = APP_URL . '/index.php?target=verify-email&token=' . $verifyToken;
            $mail->Body = "Hi " . htmlspecialchars($FirstName) . ",<br><br>Please click the link below to verify your email address:<br><a href='$verifyLink'>$verifyLink</a><br><br>This link will expire in 24 hours.";
            $mail->send();
        } catch (Exception $e) {
            error_log("Verification email error: " . $mail->ErrorInfo);
        }

        echo json_encode([
            'status' => true,
            'verify' => true,
            'message' => 'Registration successful! Please check your email to verify your account.'
        ]);


    
    }else if($_POST['ReqType'] == 2){ //Login User

        // Rate Limiting
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
            $time_since_last_attempt = time() - $_SESSION['login_timestamp'];
            if ($time_since_last_attempt < 300) { // 5 minutes
                $time_left = 300 - $time_since_last_attempt;
                echo json_encode([
                    'status' => false,
                    'code' => 53, // Rate limit exceeded
                    'message' => '<p><b>Too many failed attempts:</b> Please try again in ' . ceil($time_left / 60) . ' minutes.</p>'
                ]);
                die();
            } else {
                // Reset attempts after 5 minutes
                unset($_SESSION['login_attempts']);
                unset($_SESSION['login_timestamp']);
            }
        }
        
        $Email = $_POST['email'];
        $Password = $_POST['pr_pass'];

   
        if(!ValidateEmail($Email)){
            echo json_encode([
                'status' => false,
                'code'=>49, //client-side validation error
                'message' => '<p<b>Invalid Email : </b> Please enter a valid email address </p>'
            ]);
            die();
        }else if(!ValidatePassword($Password)){
            echo json_encode([
                'status' => false,
                'code'=>49, //client-side validation error
                'message' => '<p><b>Invalid Password : </b> Please enter a valid password </p>'
            ]);
            die();
        }



        //check if email exists
        $stmt = $pdo->prepare("SELECT id, Password, IsVerified, IsBanned FROM users WHERE Email = ?");
        $stmt->execute([$Email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$user){
            echo json_encode([
                'status' => false,
                'code'=>40, //db entry not found
                'message' => '<p><b>Email does not exist : </b> This email is not registered with us.</p>'
            ]);
            die();
        }

        // Block banned accounts
        if ((int)$user['IsBanned'] === 1) {
            $BanStmt = $pdo->prepare("SELECT Type, Reason, EndDate FROM user_bans WHERE UID = ? AND IsActive = 1 ORDER BY id DESC LIMIT 1");
            $BanStmt->execute([$user['id']]);
            $Ban = $BanStmt->fetch(PDO::FETCH_ASSOC);

            // Auto-lift expired temporary ban
            if ($Ban && (int)$Ban['Type'] === 1 && $Ban['EndDate'] !== null && strtotime($Ban['EndDate']) < time()) {
                $pdo->prepare("UPDATE user_bans SET IsActive = 0 WHERE UID = ? AND IsActive = 1")->execute([$user['id']]);
                $pdo->prepare("UPDATE users SET IsBanned = 0 WHERE id = ?")->execute([$user['id']]);
                $Ban = null;
            }

            if ($Ban) {
                $TypeLabel = match((int)$Ban['Type']) {
                    0 => 'warned',
                    2 => 'permanently banned',
                    default => 'temporarily suspended',
                };
                echo json_encode([
                    'status' => false,
                    'code' => 55,
                    'message' => '<p><b>Account suspended:</b> Your account has been ' . $TypeLabel . '. Please contact support if you believe this is a mistake.</p>'
                ]);
                die();
            }
        }

        // Block unverified accounts
        if (!$user['IsVerified']) {
            echo json_encode([
                'status' => false,
                'code' => 54,
                'message' => '<p><b>Email not verified:</b> Please check your inbox and click the verification link before logging in.</p>'
            ]);
            die();
        }

        //verify password
        if (!password_verify($Password, $user['Password'])) {
            if (!isset($_SESSION['login_attempts'])) {
                $_SESSION['login_attempts'] = 1;
                $_SESSION['login_timestamp'] = time();
            } else {
                $_SESSION['login_attempts']++;
            }

            echo json_encode([
                'status' => false,
                'code'=>52, //ceredentials mismatch (server-side validation error)
                'message' => '<p><b>Invalid Ceredentials : </b> Email or Password is incorrect</p>'
            ]);
            die();

        }




        // On success, reset login attempts
        unset($_SESSION['login_attempts']);
        unset($_SESSION['login_timestamp']);

        $token = generateToken();
        $token2 = bin2hex(random_bytes(32));
        setTokenCookie($token, $token2);



        $UID = $user['id'];

        InsertTokens($token, $token2, $UID);


        echo json_encode([
            'status' => true,
            'message' => 'Login Successful'
        ]);


    }else if($_POST['ReqType'] == 3){ //Request Password Reset
        
        $Email = $_POST['email'];
        if(!ValidateEmail($Email) || !RowExists('users', 'Email', $Email)){
            echo json_encode([
                'status' => true, // We lie to the user for security
                'message' => 'If an account with this email exists, a reset link has been sent.'
            ]);
            die();
        }

        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expires = time() + 3600; // 1 hour

        try {
            // Delete any old tokens for this email
            $sql = "DELETE FROM password_reset_tokens WHERE email = ?";
            $pdo->prepare($sql)->execute([$Email]);

            // Insert the new token
            $sql = "INSERT INTO password_reset_tokens (email, token, expires) VALUES (?, ?, ?)";
            $pdo->prepare($sql)->execute([$Email, $token, $expires]);

            // --- Send the email ---
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($Email);

            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset Link';

            $resetLink = APP_URL . '/index.php?target=reset-password&token=' . $token;
            
            $mail->Body    = "Hello,<br><br>Please click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a><br><br>This link will expire in 1 hour.";

            $mail->send();

            echo json_encode([
                'status' => true,
                'message' => 'If an account with this email exists, a reset link has been sent.'
            ]);
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            echo json_encode([
                'status' => false,
                'message' => 'Could not send email. Please contact support.'
            ]);
        }
        die();

    // --- START NEW REQTYPE 4 ---
    }else if($_POST['ReqType'] == 4){ // Perform Password Reset
        
        $email = $_POST['email'];
        $token = $_POST['token'];
        $password = $_POST['pass'];

        // 1. Validate Password
        if(!ValidatePassword($password)){
            echo json_encode([
                'status' => false,
                'message' => 'Password must be at least 8 characters long and include an uppercase, lowercase, and number.'
            ]);
            die();
        }

        // 2. Validate Token
        $sql = "SELECT id FROM password_reset_tokens WHERE email = ? AND token = ? AND expires > ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $token, time()]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRow) {
            echo json_encode([
                'status' => false,
                'message' => 'This reset link is invalid or has expired. Please try again.'
            ]);
            die();
        }

        // 3. Update User's Password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET Password = ? WHERE Email = ?";
        $pdo->prepare($sql)->execute([$hashedPassword, $email]);

        // 4. Delete the used token
        $sql = "DELETE FROM password_reset_tokens WHERE email = ?";
        $pdo->prepare($sql)->execute([$email]);
        
        // 5. Delete all *other* login tokens for this user for security
        $sql = "DELETE T FROM tokens T JOIN users U ON T.UID = U.id WHERE U.Email = ?";
        $pdo->prepare($sql)->execute([$email]);

        echo json_encode([
            'status' => true,
            'message' => 'Password reset successfully! You can now login.'
        ]);
        die();

    }else if($_POST['ReqType'] == 5){ // Verify Email

        $token = $_POST['token'] ?? '';

        if (empty($token)) {
            echo json_encode(['status' => false, 'message' => 'Invalid verification link.']);
            die();
        }

        $stmt = $pdo->prepare("SELECT email FROM email_verifications WHERE token = ? AND expires > ?");
        $stmt->execute([$token, time()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['status' => false, 'message' => 'This verification link is invalid or has expired.']);
            die();
        }

        $pdo->prepare("UPDATE users SET IsVerified = 1 WHERE Email = ?")->execute([$row['email']]);
        $pdo->prepare("DELETE FROM email_verifications WHERE email = ?")->execute([$row['email']]);

        echo json_encode(['status' => true, 'message' => 'Email verified! You can now log in.']);
        die();

    }else if($_POST['ReqType'] == 6){ // Resend Verification Email

        $Email = $_POST['email'] ?? '';

        if (!ValidateEmail($Email)) {
            echo json_encode(['status' => false, 'message' => 'Invalid email address.']);
            die();
        }

        // Only resend if user exists and is NOT yet verified
        $stmt = $pdo->prepare("SELECT id, Fname FROM users WHERE Email = ? AND IsVerified = 0");
        $stmt->execute([$Email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Always respond the same way to avoid email enumeration
        if (!$user) {
            echo json_encode(['status' => true, 'message' => 'If your email is pending verification, a new link has been sent.']);
            die();
        }

        $verifyToken = bin2hex(random_bytes(32));
        $verifyExpires = time() + 86400;

        $pdo->prepare("DELETE FROM email_verifications WHERE email = ?")->execute([$Email]);
        $pdo->prepare("INSERT INTO email_verifications (email, token, expires) VALUES (?, ?, ?)")->execute([$Email, $verifyToken, $verifyExpires]);

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($Email);
            $mail->isHTML(true);
            $mail->Subject = 'Verify your Commune account';
            $verifyLink = APP_URL . '/index.php?target=verify-email&token=' . $verifyToken;
            $mail->Body = "Hi " . htmlspecialchars($user['Fname']) . ",<br><br>Here is your new verification link:<br><a href='$verifyLink'>$verifyLink</a><br><br>This link will expire in 24 hours.";
            $mail->send();
        } catch (Exception $e) {
            error_log("Resend verification email error: " . $mail->ErrorInfo);
        }

        echo json_encode(['status' => true, 'message' => 'If your email is pending verification, a new link has been sent.']);
        die();
    }


}