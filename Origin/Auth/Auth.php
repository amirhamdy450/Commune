<?php
session_start();
$PATH="../../";
include_once $PATH.'Includes/DB.php';
include_once $PATH.'Origin/Validation.php';



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


function generateToken() {
    $token=bin2hex(random_bytes(32));
    $secretKey="Commune2024";
    $HashedToken=hash_hmac('sha256', $token, $secretKey); // Generates HMAC

    return $HashedToken;
}

function setTokenCookie($token,$token2) {
    $expiry = time() + (60 * 60 *5); // 5 hours 
    setcookie("user_token", $token, $expiry, "/", "localhost", false, true); // Secure and HttpOnly
    setcookie("user_token2", $token2, $expiry, "/", "localhost", false, true); // Secure and HttpOnly

}

function InsertTokens($token, $token2, $UID) {
    global $pdo;
    try{
        $sql="INSERT INTO Tokens (Token,Token_2,UID) VALUES(:token,:token2,:UID) ";
        $stmt=$pdo->prepare($sql);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':token2', $token2, PDO::PARAM_STR);

        $stmt->bindParam(':UID', $UID, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        // Handle database error (log, display a generic message, etc.)
        echo json_encode([
            'status' => false,
            'code' => 61, // Database error
            'message' => 'Error: Login failed. Please try again later.'
        ]);
        error_log("Database error: " . $e->getMessage(), 0); // Log the error for debugging
        exit;
      }
}


if($_SERVER['REQUEST_METHOD']==='POST'){

    //$UID=$UserData['id'];


    if($_POST['ReqType'] == 1){ //Register User

        $FirstName = $_POST['fname'];
        $LastName = $_POST['lname'];
        $Email = $_POST['email'];
        $Birthday = $_POST['bday'];
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
                'message' => 'Invalid First Name'
            ]);
            die();

        }else if(!ValidateEmail($Email)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Email !'
            ]);
            die();

        }else if(!ValidateDate($Birthday)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Birthday !'
            ]);
            die();

        }else if(!ValidatePassword($Password)){
            echo json_encode([
                'status' => false,
                'message' => 'Invalid Password !'
            ]);
            die();

        }


        //hash the password using MD5
        $Password = password_hash($Password, PASSWORD_BCRYPT);


        //Cocatenate the first and last name into a single string
        $FullName = $FirstName . ' ' . $LastName;

        //generate a random username
        $base = strtolower($FirstName .'_'. $LastName);
        $random_suffix = bin2hex(random_bytes(4)); 
        $Suggested_Username = $base . $random_suffix;

        if(UsernameExists($Suggested_Username)){
            $counter=0;
            while(UsernameExists($Suggested_Username)){
                $random_suffix = bin2hex(random_bytes(4)); 
                $Suggested_Username = $base . '_'. $random_suffix . $counter;
            }
        }


        //insert into DB
        $sql = "INSERT INTO `users` (`name`,`Username`, `Email`, `Birthday`, `Password`) VALUES ( ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$FullName, $Suggested_Username, $Email, $Birthday, $Password]);

        echo json_encode([
            'status' => true,
            'message' => 'User Registered Successfully !'
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



        //check if enmail exists
        $stmt = $pdo->prepare("SELECT id, Password FROM users WHERE Email = ?");
        $stmt->execute([$Email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $emailExists = $stmt->rowCount() > 0;

        if(!$emailExists){
            echo json_encode([
                'status' => false,
                'code'=>40, //db entry not found
                'message' => '<p><b>Email does not exist : </b> This email is not registered with us.</p>'
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


    }


}