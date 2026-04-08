<?php



function generateToken() {
    $token=bin2hex(random_bytes(32));
    $secretKey=ENCRYPTION_KEY;
    $HashedToken=hash_hmac('sha256', $token, $secretKey); // Generates HMAC

    return $HashedToken;
}

function setTokenCookie($token,$token2) {
    $expiry =time() + (86400 * 30); // 30 days
    setcookie("user_token",  $token,  $expiry, "/", "", false, true);
    setcookie("user_token2", $token2, $expiry, "/", "", false, true);
}

function NormalizeIP($ip) {
    // Map IPv6 loopback and IPv4-mapped IPv6 to plain IPv4
    if ($ip === '::1') return '127.0.0.1';
    if (strpos($ip, '::ffff:') === 0) return substr($ip, 7);
    return $ip;
}

function InsertTokens($token, $token2, $UID) {
    global $pdo;

    $IP = NormalizeIP($_SERVER['REMOTE_ADDR']);
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
