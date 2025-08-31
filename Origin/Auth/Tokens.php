<?php

function generateToken() {
    $token=bin2hex(random_bytes(32));
    $secretKey="Commune2024";
    $HashedToken=hash_hmac('sha256', $token, $secretKey); // Generates HMAC

    return $HashedToken;
}

function setTokenCookie($token,$token2) {
    $expiry =0; // infiinite expiry (we handle it elsewhere in user validation) 
    setcookie("user_token", $token, $expiry, "/", "localhost", false, true); // Secure and HttpOnly
    setcookie("user_token2", $token2, $expiry, "/", "localhost", false, true); // Secure and HttpOnly

}

function InsertTokens($token, $token2, $UID) {
    global $pdo;

    $IP = $_SERVER['REMOTE_ADDR'];
    $UserAgent = $_SERVER['HTTP_USER_AGENT'];
    $Now=strtotime("now");  
    try{
        $sql="INSERT INTO Tokens (UID, Token, Token_2, IP, UserAgent, UpdatedOn) VALUES(?, ?, ?, ?, ?, ?) ";
        $stmt=$pdo->prepare($sql);

        $stmt->execute([$UID, $token, $token2, $IP, $UserAgent, $Now]);
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
