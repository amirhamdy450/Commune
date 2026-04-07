<?php
$PATH = "../../";
require_once $PATH . "Includes/Config.php";
require_once $PATH . "Includes/DB.php";
require_once $PATH . "Includes/UserAuth.php";
require_once $PATH . "Includes/Encryption.php";
require_once $PATH . "Origin/Validation.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); die(); }
ValidateCsrf();

$UID     = (int)$User['id'];
$ReqType = (int)($_POST['ReqType'] ?? 0);

// ── [1] Create page ───────────────────────────────────────────────────────
if ($ReqType === 1) {
    $Name     = trim($_POST['Name']     ?? '');
    $Handle   = trim($_POST['Handle']   ?? '');
    $Category = trim($_POST['Category'] ?? '');
    $Website  = trim($_POST['Website']  ?? '');
    $Bio      = trim($_POST['Bio']      ?? '');

    if (strlen($Name) < 2) {
        echo json_encode(['success' => false, 'message' => 'Page name must be at least 2 characters.']);
        die();
    }
    if (!preg_match('/^[a-zA-Z0-9_]{2,50}$/', $Handle)) {
        echo json_encode(['success' => false, 'message' => 'Handle may only contain letters, numbers and underscores (2–50 characters).']);
        die();
    }
    if ($Website !== '' && !filter_var($Website, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid website URL.']);
        die();
    }

    $check = $pdo->prepare("SELECT id FROM pages WHERE Handle = ?");
    $check->execute([strtolower($Handle)]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'That handle is already taken.']);
        die();
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        INSERT INTO pages (OwnerUID, Name, Handle, Bio, Category, Website)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $UID,
        $Name,
        strtolower($Handle),
        $Bio      ?: null,
        $Category ?: null,
        $Website  ?: null,
    ]);
    $PageID = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO page_members (PageID, UID, Role) VALUES (?, ?, 'owner')")
        ->execute([$PageID, $UID]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'handle'  => strtolower($Handle),
    ]);
    die();
}

// ── [2] Check handle availability ────────────────────────────────────────
if ($ReqType === 2) {
    $Handle = strtolower(trim($_POST['Handle'] ?? ''));
    if (!preg_match('/^[a-zA-Z0-9_]{2,50}$/', $Handle)) {
        echo json_encode(['available' => false]);
        die();
    }
    $check = $pdo->prepare("SELECT id FROM pages WHERE Handle = ?");
    $check->execute([$Handle]);
    echo json_encode(['available' => !$check->fetch()]);
    die();
}

// ── [4] Follow / unfollow a page ─────────────────────────────────────────
if ($ReqType === 4) {
    $EncPageID = $_POST['PageID'] ?? '';
    $Action    = (int)($_POST['Action'] ?? 0); // 1 = follow, 0 = unfollow
    $PageID    = (int)Decrypt($EncPageID, "Positioned");
    if ($PageID <= 0) { echo json_encode(['success' => false]); die(); }

    if ($Action === 1) {
        $ins = $pdo->prepare("INSERT IGNORE INTO page_followers (PageID, UID) VALUES (?, ?)");
        $ins->execute([$PageID, $UID]);
        if ($ins->rowCount()) {
            $pdo->prepare("UPDATE pages SET Followers = Followers + 1 WHERE id = ?")->execute([$PageID]);
        }
    } else {
        $del = $pdo->prepare("DELETE FROM page_followers WHERE PageID = ? AND UID = ?");
        $del->execute([$PageID, $UID]);
        if ($del->rowCount()) {
            $pdo->prepare("UPDATE pages SET Followers = GREATEST(Followers - 1, 0) WHERE id = ?")->execute([$PageID]);
        }
    }

    $Followers = (int)$pdo->prepare("SELECT Followers FROM pages WHERE id = ?")
        ->execute([$PageID]) ? $pdo->query("SELECT Followers FROM pages WHERE id = $PageID")->fetchColumn() : 0;
    $FollowersStmt = $pdo->prepare("SELECT Followers FROM pages WHERE id = ?");
    $FollowersStmt->execute([$PageID]);
    $Followers = (int)$FollowersStmt->fetchColumn();

    echo json_encode(['success' => true, 'followers' => $Followers]);
    die();
}

// ── [3] Get pages the current user owns or is a member of ─────────────────
if ($ReqType === 3) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.Name, p.Handle, p.Logo, p.IsVerified, pm.Role
        FROM page_members pm
        INNER JOIN pages p ON pm.PageID = p.id
        WHERE pm.UID = ?
        ORDER BY pm.Role = 'owner' DESC, p.Name ASC
    ");
    $stmt->execute([$UID]);
    $Pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($Pages as &$Page) {
        $Page['Logo'] = $Page['Logo']
            ? 'MediaFolders/page_logos/' . $Page['Logo']
            : null;
        $Page['EncID'] = Encrypt($Page['id'], "Positioned", ["Timestamp" => time()]);
    }
    echo json_encode(['success' => true, 'pages' => $Pages]);
    die();
}
