<?php
$PATH = "../../";

require_once $PATH . "Includes/Config.php";
require_once $PATH . 'Includes/UserAuth.php';
require_once $PATH . 'Includes/Encryption.php';
require_once $PATH . 'Origin/Validation.php';

header('Content-Type: application/json');
ValidateCsrf();
$response = ['success' => false];
$query = isset($_POST['query']) ? trim($_POST['query']) : '';

// 1. Search Term for User Names (LIKE)
$searchTermLike = '%' . $query . '%';

// 2. Search Term for Dropdown (Strict Boolean)
$words = explode(' ', $query);
$searchTermFTS = '';
foreach($words as $word) {
    if(strlen($word) > 0) {
        $searchTermFTS .= '+' . $word . '* ';
    }
}
$searchTermFTS = trim($searchTermFTS);

$limit = 5;
$fetchLimit = $limit + 1; // Always fetch one extra to check for "Next Page"

if (!isset($_POST['ReqType'])) {
    echo json_encode(['success' => false, 'message' => 'Request type not specified.']);
    exit;
}

$ReqType = $_POST['ReqType'];


// --- REQTYPE 1: Dropdown Suggestions ---
if ($ReqType == 1) {
    $response = ['users' => [], 'topics' => []];
    if (strlen($query) >= 2) {
        // 1. User Search
        $sqlUsers = "SELECT id, Fname, Lname, Username, ProfilePic 
                     FROM users 
                     WHERE ((CONCAT(TRIM(Fname), ' ', TRIM(Lname)) LIKE ?) OR (Username LIKE ?)) AND id != ?
                     LIMIT 4";
        $stmtUsers = $pdo->prepare($sqlUsers);
        $stmtUsers->execute([$searchTermLike, $searchTermLike, $UID]);
        while ($user = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
            $params = ["Timestamp" => time()];
            $user['uid'] = urlencode(Encrypt($user['id'], "Positioned", $params));
            $user['ProfilePic'] = $user['ProfilePic'] ? 'MediaFolders/profile_pictures/' . $user['ProfilePic'] : 'Imgs/Icons/unknown.png';
            $response['users'][] = $user;
        }
        
        // 2. Topic Suggestions
        $response['topics'][] = [
            'type' => 'full_search',
            'query' => htmlspecialchars($query),
            'url' => 'index.php?target=search&query=' . urlencode($query)
        ];

        $sqlTopics = "SELECT Content FROM posts WHERE MATCH(Content) AGAINST(? IN BOOLEAN MODE) AND Status = 1 LIMIT 100"; 
        $stmtTopics = $pdo->prepare($sqlTopics);
        $stmtTopics->execute([$searchTermFTS]); 

        // (Suggestion logic remains unchanged for brevity)
        $regex = '/(?:#[\w]+|(?:"[^"]+")|\b(?:[A-Z][a-z]*(?:\s+[A-Z][a-z]*){1,3}|[A-Z]{2,}(?:\s+[A-Z]{2,})*)\b)/';
        $stopWords = ['the', 'is', 'a', 'an', 'and', 'or', 'but', 'for', 'of', 'at', 'by', 'to', 'in', 'on', 'with', 'as', 'new', 'my', 'his', 'her', 'it', 'i', 'you', 'he', 'she', 'we', 'they', 'what', 'which', 'who', 'so', 'that', 'this', 'must', 'be', 'beyond', 'like', 'from', 'just', 'got', 'really', 'good', 'very', 'nice', 'check', 'out', 'have', 'want', 'need', 'was', 'were', 'had', 'its', 'not', 'can', 'cant', 'cannot', 'could', 'would', 'should', 'will', 'wont', 'do', 'does', 'did', 'dont', 'doesnt', 'didnt', 'get', 'gets', 'getting', 'make', 'makes', 'making', 'go', 'goes', 'going', 'went', 'more', 'most', 'much', 'many', 'some', 'any', 'all', 'every', 'here', 'there', 'when', 'where', 'why', 'how', 'then', 'than', 'now', 'today', 'yesterday', 'lol', 'lmao', 'omg', 'tbh', 'imo', 'imho'];
        $queryLower = strtolower($query); 
       
        $allSuggestions = [];
        while ($row = $stmtTopics->fetch(PDO::FETCH_ASSOC)) {
            if (preg_match_all($regex, $row['Content'], $matches)) {
                foreach ($matches[0] as $match) {
                    $suggestion = trim($match, ' ".,'); 
                    $key = strtolower($suggestion);
                    if (strlen($key) <= 3) continue;
                    if ($key == $queryLower) continue;
                    $isJunk = false;
                    foreach ($stopWords as $stopWord) {
                        if ($key == $stopWord || strpos($key, $stopWord . ' ') === 0 || strpos($key, ' ' . $stopWord . ' ') !== false || substr($key, -(strlen($stopWord) + 1)) === ' ' . $stopWord) { $isJunk = true; break; }
                    }
                    if ($isJunk) continue;
                    if (!isset($allSuggestions[$key])) { $allSuggestions[$key] = ['count' => 0, 'original' => $suggestion]; }
                    $allSuggestions[$key]['count']++;
                }
            }
        }
        uasort($allSuggestions, function($a, $b) { return $b['count'] <=> $a['count']; });
        $finalSuggestions = array_slice($allSuggestions, 0, 3);
        foreach ($finalSuggestions as $suggestion) {
            $sQuery = $suggestion['original'];
            $response['topics'][] = [
                'type' => 'suggestion',
                'query' => htmlspecialchars($sQuery),
                'url' => 'index.php?target=search&query=' . urlencode($sQuery)
            ];
        }
    }
    echo json_encode($response);
    exit;
}


// --- REQTYPE 2: Initial Page Load ---
if ($ReqType == 2) {
    $response = ['success' => true, 'posts' => [], 'users' => []];

    $searchParams = [];
    $sqlSearchSelect = "";
    $sqlSearchWhere = " ";
    $sqlSearchOrder = " ORDER BY posts.Date DESC "; 
    
    if (!empty($query)) {
        $searchParams[] = $query; 
        $sqlSearchSelect = ", MATCH(posts.Content) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance ";
        $sqlSearchWhere = " AND MATCH(posts.Content) AGAINST(? IN NATURAL LANGUAGE MODE) ";
        $sqlSearchOrder = " ORDER BY relevance DESC, posts.id DESC "; 
    }

    $searchParams[] = $UID; 
    $searchParams[] = $UID; 
    $searchParams[] = $UID; 
    $searchParams[] = $UID; 

    if (!empty($query)) {
        $searchParams[] = $query; 
    }

    // Using $fetchLimit (Variable) instead of Hardcoded 1 or 6
    $sqlPosts = "SELECT posts.id AS PID, posts.*, users.Fname, users.Lname, users.Username, users.ProfilePic,
                 CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                 CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                 CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved
                 $sqlSearchSelect
                 FROM posts 
                 INNER JOIN users ON posts.UID = users.id
                 LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?
                 LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                 LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                 LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                 WHERE posts.Status = 1 AND b.id IS NULL $sqlSearchWhere 
                 $sqlSearchOrder LIMIT $fetchLimit"; 
    
    $stmtPosts = $pdo->prepare($sqlPosts);
    $stmtPosts->execute($searchParams);
    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

    // Dynamic check against $limit
    $response['hasMorePosts'] = count($posts) > $limit;
    
    // Slice to the actual $limit for display
    $posts = array_slice($posts, 0, $limit);
    foreach ($posts as $post) {
        $response['posts'][] = FormatPostForClient($post, $UID);
    }

    // 2. Fetch Users
    $sqlUsers = "SELECT u.id, u.Fname, u.Lname, u.Username, u.ProfilePic, u.Bio, u.IsBlueTick,
                 CASE WHEN f.FollowerID IS NOT NULL THEN 1 ELSE 0 END AS mutual_follow
                 FROM users u
                 LEFT JOIN followers f ON f.UserID = ? AND f.FollowerID = u.id
                 WHERE ((CONCAT(TRIM(u.Fname), ' ', TRIM(u.Lname)) LIKE ?) OR (u.Fname LIKE ?) OR (u.Lname LIKE ?) OR (u.Username LIKE ?)) AND u.id != ?
                 LIMIT 6";
    $stmtUsers = $pdo->prepare($sqlUsers);
    $stmtUsers->execute([$UID, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $UID]);
    
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    $response['hasMoreUsers'] = count($users) > 5;
    $users = array_slice($users, 0, 5);
    foreach ($users as $user) {
        $response['users'][] = FormatUserForClient($user);
    }

    echo json_encode($response);
    exit;
}

// --- REQTYPE 3: Fetch More Posts ---
if ($ReqType == 3) {
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

    $searchParams = [];
    $sqlSearchSelect = "";
    $sqlSearchWhere = " ";
    $sqlSearchOrder = " ORDER BY posts.Date DESC "; 

    if (!empty($query)) {
        $searchParams[] = $query; 
        $sqlSearchSelect = ", MATCH(posts.Content) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance ";
        $sqlSearchWhere = " AND MATCH(posts.Content) AGAINST(? IN NATURAL LANGUAGE MODE) ";
        $sqlSearchOrder = " ORDER BY relevance DESC, posts.id DESC "; 
    }

    $searchParams[] = $UID; 
    $searchParams[] = $UID; 
    $searchParams[] = $UID; 
    $searchParams[] = $UID; 

    if (!empty($query)) {
        $searchParams[] = $query; 
    }

    // Using $fetchLimit here as well
    $sqlPosts = "SELECT posts.id AS PID, posts.*, users.Fname, users.Lname, users.Username, users.ProfilePic,
                 CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                 CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                 CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved
                 $sqlSearchSelect
                 FROM posts 
                 INNER JOIN users ON posts.UID = users.id
                 LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?
                 LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                 LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                 LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                 WHERE posts.Status = 1 AND b.id IS NULL $sqlSearchWhere 
                 $sqlSearchOrder 
                 LIMIT $fetchLimit OFFSET ?";

    $stmtPosts = $pdo->prepare($sqlPosts);
    
    $i = 1;
    foreach ($searchParams as $param) {
        $stmtPosts->bindValue($i++, $param);
    }
    $stmtPosts->bindValue($i, $offset, PDO::PARAM_INT); 
    
    $stmtPosts->execute();
    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
    
    // Dynamic check
    $response['hasMorePosts'] = count($posts) > $limit;
    
    // Slice
    $posts = array_slice($posts, 0, $limit);
    foreach ($posts as $post) {
        $response['posts'][] = FormatPostForClient($post, $UID);
    }
    $response['success'] = true;
    echo json_encode($response);
    exit;
}



// --- REQTYPE 4: Fetch More Users (Unchanged) ---
if ($ReqType == 4) {
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

    $sqlUsers = "SELECT u.id, u.Fname, u.Lname, u.Username, u.ProfilePic, u.Bio, u.IsBlueTick,
                 CASE WHEN f.FollowerID IS NOT NULL THEN 1 ELSE 0 END AS mutual_follow
                 FROM users u
                 LEFT JOIN followers f ON f.UserID = ? AND f.FollowerID = u.id
                 WHERE ((CONCAT(TRIM(u.Fname), ' ', TRIM(u.Lname)) LIKE ?) OR (u.Fname LIKE ?) OR (u.Lname LIKE ?) OR (u.Username LIKE ?)) AND u.id != ?
                 LIMIT 6 OFFSET ?";
    $stmtUsers = $pdo->prepare($sqlUsers);
    $stmtUsers->bindValue(1, $UID, PDO::PARAM_INT);
    $stmtUsers->bindValue(2, $searchTermLike);
    $stmtUsers->bindValue(3, $searchTermLike);
    $stmtUsers->bindValue(4, $searchTermLike);
    $stmtUsers->bindValue(5, $searchTermLike);
    $stmtUsers->bindValue(6, $UID, PDO::PARAM_INT);
    $stmtUsers->bindValue(7, $offset, PDO::PARAM_INT);
    $stmtUsers->execute();
    
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    $response['hasMoreUsers'] = count($users) > 5;
    $users = array_slice($users, 0, 5);
    foreach ($users as $user) {
        $response['users'][] = FormatUserForClient($user);
    }
    $response['success'] = true;
    echo json_encode($response);
    exit;
}


// --- REQTYPE 5: Mention Autocomplete (@mention dropdown in comment/reply inputs) ---
// Returns up to 6 users matching the query, with IFollowThem flag for the follow indicator
if ($ReqType == 5) {
    $response = ['users' => []];
    if (true) { // empty query returns top users ordered by follow status; non-empty filters by name/username
        $sqlUsers = "SELECT u.id, u.Fname, u.Lname, u.Username, u.ProfilePic, u.IsBlueTick,
                     CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS IFollowThem
                     FROM users u
                     LEFT JOIN followers f ON f.UserID = u.id AND f.FollowerID = ?
                     WHERE ((CONCAT(TRIM(u.Fname), ' ', TRIM(u.Lname)) LIKE ?) OR (u.Username LIKE ?)) AND u.id != ?
                     ORDER BY IFollowThem DESC, u.Username ASC
                     LIMIT 6";
        $stmtUsers = $pdo->prepare($sqlUsers);
        $stmtUsers->execute([$UID, $searchTermLike, $searchTermLike, $UID]);
        while ($user = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
            $now = time();
            $user['EncUID'] = Encrypt($user['id'], "Positioned", ["Timestamp" => $now]);
            $user['ProfilePic'] = $user['ProfilePic'] ? 'MediaFolders/profile_pictures/' . htmlspecialchars($user['ProfilePic']) : 'Imgs/Icons/unknown.png';
            $response['users'][] = $user;
        }
    }
    echo json_encode($response);
    exit;
}


/* --- HELPER FUNCTIONS --- */
function FormatPostForClient($post, $UID) {
    global $PATH; 
    $params = ["Timestamp" => strtotime($post['Date'])];
    $encryptedFeedPostID = Encrypt($post['PID'], "Positioned", $params);
    $encryptedUserID = Encrypt($post['UID'], "Positioned", $params);

    $post['ProfilePic'] = (isset($post['ProfilePic']) && !empty($post['ProfilePic'])) 
        ? 'MediaFolders/profile_pictures/' . htmlspecialchars($post['ProfilePic']) 
        : 'Imgs/Icons/unknown.png';

    $MediaFolder = $PATH . $post['MediaFolder']; 
    $media = [];
    if (!empty($post['MediaFolder']) && is_dir($MediaFolder)) {
        $MediaFiles = scandir($MediaFolder);
        foreach ($MediaFiles as $file) {
            if (in_array(strtolower($file), ['.', '..'])) continue; 
            $filePath = $post['MediaFolder'] . '/' . $file;
            $media[] = ['name' => $file, 'path' => $filePath];
        }
    }

    return [
        'PID' => $encryptedFeedPostID,
        'UID' => $encryptedUserID,
        'name' => htmlspecialchars($post['Fname'] . ' ' . $post['Lname']),
        'Username' => htmlspecialchars($post['Username']),
        'ProfilePic' => $post['ProfilePic'],
        'Content' => htmlspecialchars($post['Content']),
        'LikeCounter' => $post['LikeCounter'],
        'CommentCounter' => $post['CommentCounter'],
        'MediaFolder' => $media, 
        'MediaType' => (int)$post['Type'],
        'liked' => (bool)$post['liked'],
        'following' => (bool)$post['following'],
        'Self' => (int)($post['UID'] == $UID),
        'saved' => (int)$post['saved']
    ];
}

function FormatUserForClient($user) {
    $params = ["Timestamp" => time()];
    $user['uid_encrypted'] = urlencode(Encrypt($user['id'], "Positioned", $params));
    $user['ProfilePic'] = (isset($user['ProfilePic']) && !empty($user['ProfilePic']))
        ? 'MediaFolders/profile_pictures/' . $user['ProfilePic']
        : 'Imgs/Icons/unknown.png';
    $user['Fname']    = htmlspecialchars($user['Fname']);
    $user['Lname']    = htmlspecialchars($user['Lname']);
    $user['Username'] = htmlspecialchars($user['Username']);
    $user['Bio']      = isset($user['Bio']) ? htmlspecialchars(mb_strimwidth($user['Bio'], 0, 80, '…')) : '';
    $user['IsBlueTick']    = (int)($user['IsBlueTick'] ?? 0);
    $user['mutual_follow'] = (int)($user['mutual_follow'] ?? 0);
    return $user;
}
?>