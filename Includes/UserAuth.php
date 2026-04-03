<?php
require_once $PATH."Includes/DB.php";
require_once $PATH."Origin/Auth/Tokens.php";

$LoggedIn = false;

function fetchUserData($UID) {
    global $pdo;
    // This function fetches the CORE user data for the session.
    $sql = "SELECT id, Fname, Lname, Username, Email, ProfilePic, Privilege, IsBlueTick, IsBanned FROM users WHERE id = :UID";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":UID", $UID, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}





if (isset($_COOKIE['user_token']) && isset($_COOKIE['user_token2'])) {
    //get tokens from cookie
    $Token1 = $_COOKIE['user_token'];
    $Token2 = $_COOKIE['user_token2'];

    $IP = NormalizeIP($_SERVER['REMOTE_ADDR']);
    $UserAgent = $_SERVER['HTTP_USER_AGENT'];

    // Validate tokens, IP, and user agent
    $sql = "SELECT id AS EntryID , UID, UpdatedOn FROM tokens WHERE Token = ? AND Token_2 = ? AND IP = ? AND UserAgent = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$Token1, $Token2, $IP, $UserAgent]);
    

    //$User=0;
    if($stmt->rowCount()===1){
        $row =$stmt->fetch(PDO::FETCH_ASSOC);   
        $UID = $row['UID'];  //get the UID column corresponding to those tokens
        $UpdatedOn = $row['UpdatedOn']; // Get the last updated timestamp
        $EntryID = $row['EntryID'];
        $Now = strtotime("now");

        $thirtyDaysInSeconds = 30 * 24 * 60 * 60; // 30 days in seconds

        $oneDayInSeconds = 24 * 60 * 60; // 24 hours in seconds


        if ($Now - $UpdatedOn > $thirtyDaysInSeconds) {  // If token is older than 30 days (expired)
            // Delete the token entry from the database
            $deleteSql = "DELETE FROM tokens WHERE id = ?";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([$EntryID]);

            // Reset cookies
            setcookie("user_token", "", time() - 3600, "/", "localhost", false, true); // Expire the cookie
            setcookie("user_token2", "", time() - 3600, "/", "localhost", false, true); // Expire the cookie

            // Redirect to the login page
            include "Includes/Access/Login.php";
            exit();
        }

        if($Now - $UpdatedOn > $oneDayInSeconds ){ // Only renew token if it's older than 1 day
            // Regenerate tokens
            $newToken = generateToken();
            $newToken2 = bin2hex(random_bytes(32));
            setTokenCookie($newToken, $newToken2);

            // Update tokens in the database
            $updateSql = "UPDATE tokens SET Token = ?, Token_2 = ?, UpdatedOn = ? WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$newToken, $newToken2, $Now, $EntryID]);

        }

        //echo $UID;
        // Check if user data is already in the session to avoid a database query
        if (isset($_SESSION['user_data']) && $_SESSION['user_data']['id'] == $UID && isset($_SESSION['user_data']['IsBanned'])) {
            $User = $_SESSION['user_data'];
        } else {
            // If not in session or schema has changed, fetch from DB and store it in the session
            $User = fetchUserData($UID);
            $_SESSION['user_data'] = $User;
        }

        // Admin accounts are completely separate — redirect to dashboard and block access to the regular site
        if ((int)$User['Privilege'] >= PRIV_ADMIN) {
            $CurrentFile = basename($_SERVER['SCRIPT_FILENAME']);
            $IsAdminTarget = isset($_GET['target']) && $_GET['target'] === 'admin';
            if ($CurrentFile !== 'Admin.php' && !$IsAdminTarget) {
                header("Location: index.php?target=admin");
                exit();
            }
        }

        // Block banned users — fetch active ban detail and show ban screen
        if ((int)$User['IsBanned'] === 1) {
            // Check if a temp ban has expired — if so, lift it automatically
            $stmtBan = $pdo->prepare("
                SELECT Type, Reason, EndDate
                FROM user_bans
                WHERE UID = ? AND IsActive = 1
                ORDER BY StartDate DESC LIMIT 1
            ");
            $stmtBan->execute([$UID]);
            $ActiveBan = $stmtBan->fetch(PDO::FETCH_ASSOC);

            // Auto-lift expired temporary bans
            if ($ActiveBan && (int)$ActiveBan['Type'] === 1 && $ActiveBan['EndDate'] !== null && strtotime($ActiveBan['EndDate']) < time()) {
                $pdo->prepare("UPDATE user_bans SET IsActive = 0 WHERE UID = ? AND IsActive = 1")->execute([$UID]);
                $pdo->prepare("UPDATE users SET IsBanned = 0 WHERE id = ?")->execute([$UID]);
                unset($_SESSION['user_data']); // force re-fetch next request
                $ActiveBan = null;
            }

            if ($ActiveBan) {
                session_destroy();
                setcookie("user_token", "", time() - 3600, "/", "localhost", false, true);
                setcookie("user_token2", "", time() - 3600, "/", "localhost", false, true);

                $BanType   = (int)$ActiveBan['Type'];
                $BanReason = htmlspecialchars($ActiveBan['Reason']);
                $BanEnd    = $ActiveBan['EndDate'];

                $TypeLabel = match($BanType) {
                    0 => 'Warning',
                    2 => 'Permanent Ban',
                    default => 'Temporary Ban',
                };
                $EndInfo = '';
                if ($BanType === 1 && $BanEnd) {
                    $EndInfo = '<p class="BanEnd">Your access will be restored on <strong>' . date('F j, Y', strtotime($BanEnd)) . '</strong>.</p>';
                } elseif ($BanType === 2) {
                    $EndInfo = '<p class="BanEnd">This ban has no expiry date.</p>';
                }

                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Account Suspended — Commune</title>
                <style>
                    *{margin:0;padding:0;box-sizing:border-box}
                    body{font-family:system-ui,sans-serif;background:#f0f2f5;display:flex;align-items:center;justify-content:center;min-height:100vh}
                    .BanCard{background:#fff;border-radius:18px;padding:48px 44px;max-width:500px;width:90%;box-shadow:0 4px 30px rgba(0,0,0,0.09)}
                    .BanIconWrap{width:64px;height:64px;background:#fef2f2;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px}
                    .BanIconWrap svg{color:#ef4444}
                    h1{font-size:22px;font-weight:700;color:#111827;margin-bottom:6px;text-align:center}
                    .BanType{display:inline-block;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;margin:0 auto 16px;text-align:center;width:100%}
                    .BanType.warning{color:#b45309;background:#fef3c7}
                    .BanType.temp{color:#1d4ed8;background:#eff6ff}
                    .BanType.permanent{color:#991b1b;background:#fef2f2}
                    .BanMsg{font-size:14px;color:#6b7280;line-height:1.7;margin-bottom:20px;text-align:center}
                    .BanReasonBox{background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;font-size:14px;color:#374151}
                    .BanReasonLabel{font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px}
                    .BanEnd{font-size:13px;color:#6b7280;margin-top:14px;text-align:center}
                    .BanEnd strong{color:#111827}
                </style></head><body>
                <div class="BanCard">
                    <div class="BanIconWrap">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    </div>
                    <h1>Account Suspended</h1>
                    <span class="BanType ' . ($BanType===0?'warning':($BanType===2?'permanent':'temp')) . '">' . $TypeLabel . '</span>
                    <p class="BanMsg">Your account has been suspended from Commune. If you believe this is a mistake, please contact support.</p>
                    <div class="BanReasonBox">
                        <span class="BanReasonLabel">Reason</span>
                        ' . $BanReason . '
                    </div>
                    ' . $EndInfo . '
                </div>
                </body></html>';
                exit();
            }
        }

/*         ENCRYPTION_KEY='Commune'; 
        ENCRYPTION_IV = "COMMUNE2025_9831"; //like secret key and will be used for decrypting AES later */


        //allowed extensions array
        $AllowedDocumentExtensions = ['pdf', 'doc', 'docx', 'txt','xls','xlsx','ppt','pptx'];




        $AllowedImagesExtensions=['xbm', 'tif', 'jfif', 'ico', 'tiff', 'gif', 'svg', 'webp', 'svgz', 'jpg', 'jpeg', 'png', 'bmp', 'pjp', 'apng', 'pjpeg', 'avif'];

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $CsrfToken = $_SESSION['csrf_token'];

        $LoggedIn = true;
       
    }else{
        $LoggedIn = false;
       // header("Location: Includes/Access/Login.php");
        include "Includes/Access/Login.php";
        exit();
    }


}else{
    $LoggedIn = false;
   // header("Location: Includes/Access/Login.php");
    include "Includes/Access/Login.php";
    exit();
}


?>