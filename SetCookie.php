<?php
// Assuming you have a function that checks user credentials:

include "Includes/DB.php";
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

if(isset($_GET['UID'])){
    $UID = intval($_GET['UID']);

    $token = generateToken();
    $token2=generateToken();
    setTokenCookie($token,$token2);
    
    try{
        $sql="INSERT INTO Tokens (Token,Token_2,UID) VALUES(:token,:token2,:UID) ";
        $stmt=$pdo->prepare($sql);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':token2', $token2, PDO::PARAM_STR);

        $stmt->bindParam(':UID', $UID, PDO::PARAM_INT);
        $stmt->execute();
    
    echo "Logged in successfully!";
    } catch (PDOException $e) {
        // Handle database error (log, display a generic message, etc.)
        echo "Error: Login failed. Please try again later.";
        error_log("Database error: " . $e->getMessage(), 0); // Log the error for debugging
      }
}





 
?>