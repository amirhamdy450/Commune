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

// [ReqType 1] SUBMIT VERIFICATION REQUEST
if ($ReqType === 1) {
    // Block if already blue-ticked
    if ((int)$User['IsBlueTick'] === 1) {
        echo json_encode(['success' => false, 'message' => 'Your account is already verified.']);
        die();
    }

    // Block if a pending request already exists
    $stmt = $pdo->prepare("SELECT id FROM verification_requests WHERE UID = ? AND Status = 0");
    $stmt->execute([$UID]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending verification request.']);
        die();
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

    $stmt = $pdo->prepare("INSERT INTO verification_requests (UID, Reason) VALUES (?, ?)");
    if ($stmt->execute([$UID, $Reason])) {
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
    $stmt = $pdo->prepare("SELECT UID FROM verification_requests WHERE id = ? AND Status = 0");
    $stmt->execute([$RequestID]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        echo json_encode(['success' => false, 'message' => 'Request not found or already reviewed.']);
        die();
    }

    $TargetUID = (int)$req['UID'];

    // Mark request approved + set IsBlueTick
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE verification_requests SET Status = 1, ReviewedAt = NOW() WHERE id = ?")->execute([$RequestID]);
        $pdo->prepare("UPDATE users SET IsBlueTick = 1 WHERE id = ?")->execute([$TargetUID]);
        // Notify the user
        $pdo->prepare("INSERT INTO notifications (ToUID, FromUID, Type, MetaInfo) VALUES (?, ?, 10, 'Your account has been verified!')")->execute([$TargetUID, $UID]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'User verified successfully.']);
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
