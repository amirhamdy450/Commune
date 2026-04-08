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

// ── [5] Update page settings ──────────────────────────────────────────────
if ($ReqType === 5) {
    $PageID  = (int)Decrypt($_POST['PageID'] ?? '', "Positioned");
    if ($PageID <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid page.']); die(); }

    // Must be owner or admin
    $RoleStmt = $pdo->prepare("SELECT Role FROM page_members WHERE PageID = ? AND UID = ?");
    $RoleStmt->execute([$PageID, $UID]);
    $Role = $RoleStmt->fetchColumn();
    if (!in_array($Role, ['owner', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        die();
    }

    $Name     = trim($_POST['Name']     ?? '');
    $Handle   = strtolower(trim($_POST['Handle'] ?? ''));
    $Category = trim($_POST['Category'] ?? '');
    $Website  = trim($_POST['Website']  ?? '');
    $Bio      = trim($_POST['Bio']      ?? '');

    if (strlen($Name) < 2) { echo json_encode(['success' => false, 'message' => 'Page name must be at least 2 characters.']); die(); }
    if (!preg_match('/^[a-zA-Z0-9_]{2,50}$/', $Handle)) { echo json_encode(['success' => false, 'message' => 'Handle may only contain letters, numbers and underscores.']); die(); }
    if ($Website !== '' && !filter_var($Website, FILTER_VALIDATE_URL)) { echo json_encode(['success' => false, 'message' => 'Please enter a valid website URL.']); die(); }

    // Handle uniqueness (exclude current page)
    $CheckStmt = $pdo->prepare("SELECT id FROM pages WHERE Handle = ? AND id != ?");
    $CheckStmt->execute([$Handle, $PageID]);
    if ($CheckStmt->fetch()) { echo json_encode(['success' => false, 'message' => 'That handle is already taken.']); die(); }

    $LogoPath  = null;
    $CoverPath = null;

    // Handle logo upload
    if (!empty($_FILES['Logo']['tmp_name'])) {
        $Ext = strtolower(pathinfo($_FILES['Logo']['name'], PATHINFO_EXTENSION));
        if (!in_array($Ext, ['jpg','jpeg','png','webp','gif'])) { echo json_encode(['success' => false, 'message' => 'Invalid logo format.']); die(); }
        $Dir = $PATH . 'MediaFolders/page_logos/';
        if (!is_dir($DIR = $PATH . 'MediaFolders/page_logos/')) mkdir($DIR, 0777, true);
        $Filename = 'logo_' . $PageID . '_' . time() . '.' . $Ext;
        move_uploaded_file($_FILES['Logo']['tmp_name'], $Dir . $Filename);
        $LogoPath = $Filename;
    }

    // Handle cover upload
    if (!empty($_FILES['Cover']['tmp_name'])) {
        $Ext = strtolower(pathinfo($_FILES['Cover']['name'], PATHINFO_EXTENSION));
        if (!in_array($Ext, ['jpg','jpeg','png','webp','gif'])) { echo json_encode(['success' => false, 'message' => 'Invalid cover format.']); die(); }
        $Dir = $PATH . 'MediaFolders/page_covers/';
        if (!is_dir($Dir)) mkdir($Dir, 0777, true);
        $Filename = 'cover_' . $PageID . '_' . time() . '.' . $Ext;
        move_uploaded_file($_FILES['Cover']['tmp_name'], $Dir . $Filename);
        $CoverPath = $Filename;
    }

    $SetClauses = "Name = ?, Handle = ?, Category = ?, Website = ?, Bio = ?";
    $Params     = [$Name, $Handle, $Category ?: null, $Website ?: null, $Bio ?: null];

    if ($LogoPath) { $SetClauses .= ", Logo = ?"; $Params[] = $LogoPath; }
    if ($CoverPath) { $SetClauses .= ", CoverPhoto = ?"; $Params[] = $CoverPath; }
    $Params[] = $PageID;

    $pdo->prepare("UPDATE pages SET $SetClauses WHERE id = ?")->execute($Params);

    echo json_encode([
        'success'   => true,
        'handle'    => $Handle,
        'LogoPath'  => $LogoPath  ? 'MediaFolders/page_logos/'  . $LogoPath  : null,
        'CoverPath' => $CoverPath ? 'MediaFolders/page_covers/' . $CoverPath : null,
    ]);
    die();
}

// ── [6] Fetch more page posts (infinite scroll) ───────────────────────────
if ($ReqType === 6) {
    $PageID      = (int)Decrypt($_POST['PageID'] ?? '', "Positioned");
    $LastPostID  = (int)Decrypt($_POST['LastPostID'] ?? '', "Positioned");
    if ($PageID <= 0 || $LastPostID <= 0) { echo json_encode(['success' => false]); die(); }

    $stmt = $pdo->prepare("
        SELECT posts.id AS PID, posts.*, users.Fname, users.Lname, users.Username,
               users.ProfilePic, users.IsBlueTick,
               CASE WHEN l.UID IS NOT NULL THEN 1 ELSE 0 END AS liked,
               CASE WHEN sp.PostID IS NOT NULL THEN 1 ELSE 0 END AS saved
        FROM posts
        INNER JOIN users ON posts.UID = users.id
        LEFT JOIN likes l ON posts.id = l.PostID AND l.UID = ?
        LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
        WHERE posts.OrgID = ? AND posts.Status = 1 AND posts.id < ?
        ORDER BY posts.Date DESC
        LIMIT 10
    ");
    $stmt->execute([$UID, $UID, $PageID, $LastPostID]);
    $Posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $Response = [];
    foreach ($Posts as $Post) {
        $Timestamp  = strtotime($Post['Date']);
        $Params     = ["Timestamp" => $Timestamp];
        $EncPostID  = Encrypt($Post['PID'], "Positioned", $Params);
        $EncUID     = Encrypt($Post['UID'], "Positioned", $Params);
        $ProfilePic = $Post['ProfilePic']
            ? 'MediaFolders/profile_pictures/' . $Post['ProfilePic']
            : 'Imgs/Icons/unknown.png';

        $Media = [];
        $Folder = $PATH . $Post['MediaFolder'];
        if (is_dir($Folder)) {
            foreach (scandir($Folder) as $F) {
                if ($F === '.' || $F === '..') continue;
                $Media[] = ['name' => $F, 'path' => $Post['MediaFolder'] . '/' . $F];
            }
        }

        $Response[] = [
            'PID'         => $EncPostID,
            'UID'         => $EncUID,
            'Self'        => (int)($Post['UID'] == $UID),
            'name'        => $Post['Fname'] . ' ' . $Post['Lname'],
            'Username'    => $Post['Username'],
            'Date'        => $Timestamp,
            'Content'     => $Post['Content'],
            'LikeCounter' => $Post['LikeCounter'],
            'CommentCounter' => $Post['CommentCounter'],
            'MediaFol
            er' => $Media,
            'MediaType'   => (int)$Post['Type'],
            'liked'       => (int)$Post['liked'],
            'saved'       => (int)$Post['saved'],
            'ProfilePic'  => $ProfilePic,
            'IsBlueTick'  => (int)$Post['IsBlueTick'],
            'PageName'    => null,
            'PageHandle'  => null,
            'PageLogo'    => null,
            'PageIsVerified' => 0,
        ];
    }

    echo json_encode(['success' => true, 'posts' => $Response]);
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
