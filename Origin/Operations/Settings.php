<?php
$PATH = "../../";

require_once $PATH . "Includes/Config.php";
require_once $PATH . 'Includes/UserAuth.php';
require_once $PATH . 'Includes/Encryption.php';
require_once $PATH . 'Origin/Validation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    ValidateCsrf();
    $ReqType = $_POST['ReqType'];

    // [ReqType 1] UPDATE ACCOUNT INFO
    if ($ReqType == 1) {
        $Email = $_POST['email'];
        // Removed CountryID

        if (!ValidateEmail($Email)) {
            echo json_encode(['success' => false, 'message' => 'Invalid Email format.']);
            die();
        }

        // Check if email is taken
        $sql = "SELECT id FROM users WHERE Email = ? AND id != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$Email, $UID]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This email is already in use.']);
            die();
        }

        try {
            $sql = "UPDATE users SET Email = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$Email, $UID]);
            
            $_SESSION['user_data']['Email'] = $Email;

            echo json_encode(['success' => true, 'message' => 'Account information updated.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Update failed.']);
        }
        die();
    }

    // [ReqType 2] CHANGE PASSWORD
    if ($ReqType == 2) {
        $CurrentPass = $_POST['current_pass'];
        $NewPass = $_POST['new_pass'];
        $ConfirmPass = $_POST['confirm_pass'];

        // 1. Verify Current Password
        $sql = "SELECT Password FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$UID]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($CurrentPass, $hash)) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            die();
        }

        // 2. Validate New Password
        if ($NewPass !== $ConfirmPass) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
            die();
        }
        if (!ValidatePassword($NewPass)) {
            echo json_encode(['success' => false, 'message' => 'Password must be 8+ chars with upper, lower, and number.']);
            die();
        }

        // 3. Update
        $newHash = password_hash($NewPass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET Password = ? WHERE id = ?")->execute([$newHash, $UID]);

        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
        die();
    }

    // [ReqType 3] FETCH ACTIVE SESSIONS
    if ($ReqType == 3) {
        // Get the current session token from the cookie
        $currentToken = $_COOKIE['user_token'] ?? ''; 
        
        // Select Token column as well to compare
        $sql = "SELECT id, IP, UserAgent, UpdatedOn, Token FROM tokens WHERE UID = ? ORDER BY UpdatedOn DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$UID]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = [];
        foreach ($sessions as $s) {
            $ua = $s['UserAgent'];
            
            // Device Detection
            $device = "Unknown Device";
            if (strpos($ua, 'Windows') !== false) $device = "Windows PC";
            elseif (strpos($ua, 'Macintosh') !== false) $device = "Mac";
            elseif (strpos($ua, 'Linux') !== false) $device = "Linux";
            elseif (strpos($ua, 'Android') !== false) $device = "Android";
            elseif (strpos($ua, 'iPhone') !== false) $device = "iPhone";

            $browser = "Unknown Browser";
            if (strpos($ua, 'Chrome') !== false) $browser = "Chrome";
            elseif (strpos($ua, 'Firefox') !== false) $browser = "Firefox";
            elseif (strpos($ua, 'Safari') !== false) $browser = "Safari";
            elseif (strpos($ua, 'Edg') !== false) $browser = "Edge";

            // --- FIX 1: Format IP Address ---
            $ip = $s['IP'];
            if ($ip === '::1') {
                $ip = '127.0.0.1 (Localhost)';
            }
            // --------------------------------

            // --- FIX 2: Identify Current Session ---
            // We compare the token in DB with the user's cookie
            $isCurrent = ($s['Token'] === $currentToken);
            // ---------------------------------------

            $formatted[] = [
                'id' => Encrypt($s['id'], "Positioned", ["Timestamp" => time()]),
                'Device' => "$browser on $device",
                'IP' => $ip,
                'LastActive' => date("M d, H:i", $s['UpdatedOn']),
                'IsCurrent' => $isCurrent
            ];
        }

        echo json_encode(['success' => true, 'sessions' => $formatted]);
        die();
    }
    // [ReqType 4] REVOKE SESSION
    if ($ReqType == 4) {
        $EncID = $_POST['SessionID'];
        $SessionID = (int)Decrypt($EncID, "Positioned");

        $sql = "DELETE FROM tokens WHERE id = ? AND UID = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$SessionID, $UID])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error revoking session.']);
        }
        die();
    }

    // [ReqType 5] FETCH BLOCKED USERS
    if ($ReqType == 5) {
        $sql = "SELECT b.id as BlockID, u.id as UID, u.Fname, u.Lname, u.Username, u.ProfilePic 
                FROM blocked_users b
                INNER JOIN users u ON b.BlockedUID = u.id
                WHERE b.BlockerUID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$UID]);
        $blocked = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = [];
        foreach ($blocked as $b) {
            $ProfilePic = $b['ProfilePic'] ? 'MediaFolders/profile_pictures/' . $b['ProfilePic'] : 'Imgs/Icons/unknown.png';
            $formatted[] = [
                'BlockID' => Encrypt($b['BlockID'], "Positioned", ["Timestamp" => time()]),
                'Name' => $b['Fname'] . ' ' . $b['Lname'],
                'Username' => $b['Username'],
                'ProfilePic' => $ProfilePic
            ];
        }
        echo json_encode(['success' => true, 'users' => $formatted]);
        die();
    }

    // [ReqType 6] UNBLOCK USER
    if ($ReqType == 6) {
        $EncID = $_POST['BlockID'];
        $BlockID = (int)Decrypt($EncID, "Positioned");

        $sql = "DELETE FROM blocked_users WHERE id = ? AND BlockerUID = ?";
        $pdo->prepare($sql)->execute([$BlockID, $UID]);
        echo json_encode(['success' => true]);
        die();
    }

    // [ReqType 7] DELETE ACCOUNT (THE NUCLEAR OPTION)
    if ($ReqType == 7) {
        // Because of ON DELETE RESTRICT, we must clean up manually in specific order
        try {
            $pdo->beginTransaction();

            // 1. Delete Dependencies
            // Comments & Replies
            $pdo->prepare("DELETE FROM comments_replies_likes WHERE UID = ?")->execute([$UID]);
            $pdo->prepare("DELETE FROM comments_replies WHERE UID = ?")->execute([$UID]);
            $pdo->prepare("DELETE FROM comments_likes WHERE UID = ?")->execute([$UID]);
            $pdo->prepare("DELETE FROM comments WHERE UID = ?")->execute([$UID]);
            
            // Likes & Follows
            $pdo->prepare("DELETE FROM likes WHERE UID = ?")->execute([$UID]);
            $pdo->prepare("DELETE FROM followers WHERE UserID = ? OR FollowerID = ?")->execute([$UID, $UID]);
            $pdo->prepare("DELETE FROM saved_posts WHERE UID = ?")->execute([$UID]);
            $pdo->prepare("DELETE FROM blocked_users WHERE BlockerUID = ? OR BlockedUID = ?")->execute([$UID, $UID]);
            
            // Sessions
            $pdo->prepare("DELETE FROM tokens WHERE UID = ?")->execute([$UID]);

            // 2. Delete Posts (And their files)
            $stmt = $pdo->prepare("SELECT MediaFolder FROM posts WHERE UID = ?");
            $stmt->execute([$UID]);
            while ($row = $stmt->fetch()) {
                // Recursive delete function would go here for folders
                // For now, we rely on the fact that we store full paths
                // Ideally, delete the actual files from disk here
            }
            $pdo->prepare("DELETE FROM posts WHERE UID = ?")->execute([$UID]);

            // 3. Delete User
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$UID]);

            $pdo->commit();
            
            // Log out
            setcookie("user_token", "", time() - 3600, "/", "localhost", false, true);
            setcookie("user_token2", "", time() - 3600, "/", "localhost", false, true);
            
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Delete Account Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Could not delete account. Database error.']);
        }
        die();
    }
}