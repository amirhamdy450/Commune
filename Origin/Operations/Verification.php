<?php
$PATH = "../../";

require_once $PATH . "Includes/Config.php";
require_once $PATH . 'Includes/DB.php';
require_once $PATH . 'Includes/UserAuth.php';
require_once $PATH . 'Includes/Encryption.php';
require_once $PATH . 'Origin/Validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { die(); }

ValidateCsrf();
$ReqType = (int)($_POST['ReqType'] ?? 0);

// [ReqType 1] SUBMIT VERIFICATION REQUEST (user or page)
if ($ReqType === 1) {
    $EncPageID = trim($_POST['PageID'] ?? '');
    $PageID    = $EncPageID ? (int)Decrypt($EncPageID, "Positioned") : null;

    if ($PageID) {
        // Page verification — must be owner or admin
        $RoleStmt = $pdo->prepare("SELECT Role FROM page_members WHERE PageID = ? AND UID = ?");
        $RoleStmt->execute([$PageID, $UID]);
        $Role = $RoleStmt->fetchColumn();
        if (!in_array($Role, ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to request verification for this page.']);
            die();
        }
        // Block if page already verified
        $PageStmt = $pdo->prepare("SELECT IsVerified FROM pages WHERE id = ?");
        $PageStmt->execute([$PageID]);
        $PageRow = $PageStmt->fetch(PDO::FETCH_ASSOC);
        if (!$PageRow) { echo json_encode(['success' => false, 'message' => 'Page not found.']); die(); }
        if ((int)$PageRow['IsVerified'] === 1) {
            echo json_encode(['success' => false, 'message' => 'This page is already verified.']);
            die();
        }
        // Block if pending request exists for this page
        $PendingStmt = $pdo->prepare("SELECT id FROM verification_requests WHERE PageID = ? AND Status = 0");
        $PendingStmt->execute([$PageID]);
        if ($PendingStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This page already has a pending verification request.']);
            die();
        }
    } else {
        // User verification
        if ((int)$User['IsBlueTick'] === 1) {
            echo json_encode(['success' => false, 'message' => 'Your account is already verified.']);
            die();
        }
        $PendingStmt = $pdo->prepare("SELECT id FROM verification_requests WHERE UID = ? AND PageID IS NULL AND Status = 0");
        $PendingStmt->execute([$UID]);
        if ($PendingStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You already have a pending verification request.']);
            die();
        }
    }

    $Reason = trim($_POST['reason'] ?? '');
    if (strlen($Reason) < 20) {
        echo json_encode(['success' => false, 'message' => 'Please provide a reason of at least 20 characters.']);
        die();
    }
    if (strlen($Reason) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Reason must be under 1000 characters.']);
        die();
    }

    $FeePaid = (int)($_POST['fee_confirmed'] ?? 0);
    if ($FeePaid !== 1) {
        echo json_encode(['success' => false, 'message' => 'You must confirm the verification fee to proceed.']);
        die();
    }

    $stmt = $pdo->prepare("INSERT INTO verification_requests (UID, PageID, Reason) VALUES (?, ?, ?)");
    if ($stmt->execute([$UID, $PageID, $Reason])) {
        echo json_encode(['success' => true, 'message' => 'Your verification request has been submitted. We will review it shortly.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit request. Please try again.']);
    }
    die();
}

// [ReqType 2] GET CURRENT VERIFICATION STATUS (for the page to load state)
if ($ReqType === 2) {
    $status = [
        'isBlueTick' => (int)$User['IsBlueTick'] === 1,
        'pendingRequest' => false,
        'rejectedRequest' => false,
    ];

    $stmt = $pdo->prepare("SELECT Status, SubmittedAt FROM verification_requests WHERE UID = ? ORDER BY SubmittedAt DESC LIMIT 1");
    $stmt->execute([$UID]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($req) {
        if ((int)$req['Status'] === 0) {
            $status['pendingRequest'] = true;
            $status['submittedAt'] = $req['SubmittedAt'];
        } elseif ((int)$req['Status'] === 2) {
            $status['rejectedRequest'] = true;
        }
    }

    echo json_encode(['success' => true, 'data' => $status]);
    die();
}

// [ReqType 3] ADMIN: AUTO-ACCEPT — approves a request by request ID
// Protected: requires Privilege >= PRIV_ADMIN (5)
if ($ReqType === 3) {
    if ((int)$User['Privilege'] < PRIV_ADMIN) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        die();
    }

    $RequestID = (int)($_POST['request_id'] ?? 0);
    if ($RequestID <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        die();
    }

    // Fetch request
    $stmt = $pdo->prepare("SELECT UID, PageID FROM verification_requests WHERE id = ? AND Status = 0");
    $stmt->execute([$RequestID]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        echo json_encode(['success' => false, 'message' => 'Request not found or already reviewed.']);
        die();
    }

    $TargetUID = (int)$req['UID'];
    $TargetPageID = $req['PageID'] ? (int)$req['PageID'] : null;

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE verification_requests SET Status = 1, ReviewedAt = NOW() WHERE id = ?")->execute([$RequestID]);
        if ($TargetPageID) {
            $pdo->prepare("UPDATE pages SET IsVerified = 1 WHERE id = ?")->execute([$TargetPageID]);
            $pdo->prepare("INSERT INTO notifications (ToUID, FromUID, Type, MetaInfo) VALUES (?, ?, 10, 'Your page has been verified!')")->execute([$TargetUID, $UID]);
        } else {
            $pdo->prepare("UPDATE users SET IsBlueTick = 1 WHERE id = ?")->execute([$TargetUID]);
            $pdo->prepare("INSERT INTO notifications (ToUID, FromUID, Type, MetaInfo) VALUES (?, ?, 10, 'Your account has been verified!')")->execute([$TargetUID, $UID]);
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $TargetPageID ? 'Page verified successfully.' : 'User verified successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Transaction failed.']);
    }
    die();
}

// [ReqType 4] ADMIN: REJECT a request
if ($ReqType === 4) {
    if ((int)$User['Privilege'] < PRIV_ADMIN) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        die();
    }

    $RequestID = (int)($_POST['request_id'] ?? 0);
    if ($RequestID <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        die();
    }

    $stmt = $pdo->prepare("SELECT UID FROM verification_requests WHERE id = ? AND Status = 0");
    $stmt->execute([$RequestID]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        echo json_encode(['success' => false, 'message' => 'Request not found or already reviewed.']);
        die();
    }

    $pdo->prepare("UPDATE verification_requests SET Status = 2, ReviewedAt = NOW() WHERE id = ?")->execute([$RequestID]);
    echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    die();
}
