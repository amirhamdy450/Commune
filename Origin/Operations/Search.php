<?php
$PATH = "../../";

require_once $PATH . "Includes/Config.php";
require_once $PATH . 'Includes/UserAuth.php';
require_once $PATH . 'Includes/Encryption.php';

header('Content-Type: application/json');
$response = ['success' => false];
$query = isset($_POST['query']) ? trim($_POST['query']) : '';
$searchTerm = '%' . $query . '%';

if (!isset($_POST['ReqType'])) {
    echo json_encode(['success' => false, 'message' => 'Request type not specified.']);
    exit;
}

$ReqType = $_POST['ReqType'];




// --- REQTYPE 1: Dropdown Suggestions (V4 + Relevance Fix) ---
if ($ReqType == 1) {
    $response = ['users' => [], 'topics' => []];
    if (strlen($query) >= 2) {

        $sql_check = "SELECT Results FROM topic_cache 
              WHERE LOWER(Query) = LOWER(?) AND LastCalculated > (NOW() - INTERVAL 1 HOUR)";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$query]);
        $cached_result = $stmt_check->fetch();
/* if ($cached_result) {

            // --- 2. CACHE HIT (FAST PATH) ---
            // The result exists and is fresh. Just return it.
            $suggestions_json = $cached_result['Results'];

            // We need to add the "full_search" option
            $response = json_decode($suggestions_json, true); // ['topics' => [...], 'users' => [...]]

            // (Add the user's exact query to the response, like before)
            // ...

            echo json_encode($response);
            exit;

        } */





        
        // --- 1. User Search (Unchanged) ---
        $sqlUsers = "SELECT id, Fname, Lname, Username, ProfilePic 
                     FROM users 
                     WHERE ((CONCAT(TRIM(Fname), ' ', TRIM(Lname)) LIKE ?) OR (Username LIKE ?)) AND id != ?
                     LIMIT 4";
        $stmtUsers = $pdo->prepare($sqlUsers);
        $stmtUsers->execute([$searchTerm, $searchTerm, $UID]);
        while ($user = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
            $params = ["Timestamp" => time()];
            $user['uid'] = urlencode(Encrypt($user['id'], "Positioned", $params));
            $user['ProfilePic'] = $user['ProfilePic'] ? 'MediaFolders/profile_pictures/' . $user['ProfilePic'] : 'Imgs/Icons/unknown.png';
            $response['users'][] = $user;
        }
        
        // --- 2. "Smart" Topic Search ---
        
        // Step A: Always add the user's exact query first
        $response['topics'][] = [
            'type' => 'full_search',
            'query' => htmlspecialchars($query),
            'url' => 'index.php?target=search&query=' . urlencode($query)
        ];





        // Step B: Use FTS to find the TOP 100 *relevant* posts.
/* $sqlTopics = "SELECT Content, MATCH(Content) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
            FROM posts
            WHERE MATCH(Content) AGAINST(? IN NATURAL LANGUAGE MODE)
            AND Status = 1
            ORDER BY relevance DESC  -- Use the alias!
            LIMIT 100";

        $stmtTopics = $pdo->prepare($sqlTopics);

        $stmtTopics->execute([$query, $query]);  */

        $sqlTopics_LIKE = "SELECT Content 
                FROM posts
                WHERE Content LIKE ?
                AND Status = 1
                ORDER BY Date DESC
                LIMIT 100"; // Get the 100 most recent posts that match
            
            $stmtTopics = $pdo->prepare($sqlTopics_LIKE);
            $stmtTopics->execute([$searchTerm]); // $searchTerm is '%query%'


           /* $stmtTopics = $stmtTopics_LIKE->fetchAll(PDO::FETCH_ASSOC) */

        $regex = '/(?:#[\w]+|(?:"[^"]+")|\b(?:[A-Z][a-z]*(?:\s+[A-Z][a-z]*){1,3}|[A-Z]{2,}(?:\s+[A-Z]{2,})*)\b)/';
        
        $stopWords = [
            'the', 'is', 'a', 'an', 'and', 'or', 'but', 'for', 'of', 'at', 'by', 'to', 'in', 'on',
            'with', 'as', 'new', 'my', 'his', 'her', 'it', 'i', 'you', 'he', 'she', 'we', 'they',
            'what', 'which', 'who', 'so', 'that', 'this', 'must', 'be', 'beyond', 'like', 'from',
            'just', 'got', 'really', 'good', 'very', 'nice', 'check', 'out', 'have', 'want', 'need',
            'was', 'were', 'had', 'its', 'not',    
            // Action verbs that appear in casual posts
            'can', 'cant', 'cannot', 'could', 'would', 'should', 'will', 'wont',
            'do', 'does', 'did', 'dont', 'doesnt', 'didnt',
            'get', 'gets', 'getting',
            'make', 'makes', 'making',
            'go', 'goes', 'going', 'went',
    
            // Common adjectives/adverbs
            'more', 'most', 'much', 'many', 'some', 'any', 'all', 'every',
            'here', 'there', 'when', 'where', 'why', 'how',
            'then', 'than', 'now', 'today', 'yesterday',
    
            // Social media specific
            'lol', 'lmao', 'omg', 'tbh', 'imo', 'imho'
        ];
        $queryLower = strtolower($query); 

                $prioritySuggestions = [];
        $otherSuggestions = [];
        
        $allMatches = [];
        while ($row = $stmtTopics->fetch(PDO::FETCH_ASSOC)) {
            if (preg_match_all($regex, $row['Content'], $matches)) {
                $allMatches = array_merge($allMatches, $matches[0]);
            }
        }

       
        // --- START OF FINAL (v5) FILTER LOGIC ---
        $allSuggestions = [];
        foreach ($allMatches as $match) {
            $suggestion = trim($match, ' ".,'); 
            $key = strtolower($suggestion);

            // Filter 1: Basic length
            if (strlen($key) <= 3) { continue; }

            // Filter 2: Exact query match (e.g., "spacex")
            if ($key == $queryLower) { continue; }

            // Filter 3: General stop words (e.g., "the mars", "a new car", "lol")
            $isJunk = false;
            foreach ($stopWords as $stopWord) {
                if ($key == $stopWord) { $isJunk = true; break; } // "lol"
                if (strpos($key, $stopWord . ' ') === 0) { $isJunk = true; break; } // "the mars"
                if (strpos($key, ' ' . $stopWord . ' ') !== false) { $isJunk = true; break; } // "mars the planet"
                if (substr($key, -(strlen($stopWord) + 1)) === ' ' . $stopWord) { $isJunk = true; break; } // "mars the"
            }
            if ($isJunk) { continue; }

            // We REMOVED the context-aware filter (Filter 4).
            // "Starship" and "Mars" will now be kept.

            // 3. If it passes all filters, add it to the bucket
            if (!isset($allSuggestions[$key])) { $allSuggestions[$key] = ['count' => 0, 'original' => $suggestion]; }
            $allSuggestions[$key]['count']++;
        }
        // --- END OF FINAL (v5) FILTER LOGIC ---


        // Step D: Sort the one bucket by frequency
        uasort($allSuggestions, function($a, $b) { return $b['count'] <=> $a['count']; });


        // Step E: Build the final list (Top 3)
        $finalSuggestions = [];
        foreach ($allSuggestions as $s) { // Use the correct variable
            if (count($finalSuggestions) >= 3) break;
            $finalSuggestions[] = $s;
        }
        

        // Step F: Add to the response
        foreach ($finalSuggestions as $suggestion) {
            $sQuery = $suggestion['original'];
            $response['topics'][] = [
                'type' => 'suggestion',
                'query' => htmlspecialchars($sQuery),
                'url' => 'index.php?target=search&query=' . urlencode($sQuery)
            ];
        }
    }

    // --- Step G: Cache the result for next time ---
    // 4. SAVE TO CACHE
    // Before we send it, we save this result for next time.
    $sql_save = "INSERT INTO topic_cache (Query, Results, LastCalculated)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE Results = ?, LastCalculated = NOW()";

    $pdo->prepare($sql_save)->execute([
        $query, 
        json_encode($response), // Save the final JSON
        json_encode($response)
    ]);

    echo json_encode($response);
    exit;
    
}





// --- REQTYPE 2: Initial Page Load (NOW USES FTS) ---
if ($ReqType == 2) {
    $response = ['success' => true, 'posts' => [], 'users' => []];

// --- START OF FIX ---
    $baseParams = [$UID, $UID, $UID, $UID];
    $sqlSearchSelect = "";
    $sqlSearchWhere = " ";
    $sqlSearchOrder = " ORDER BY posts.Date DESC "; 

    if (!empty($query)) {
        // 1. Create the Boolean Mode query
        // "Vision Pro" becomes "+Vision +Pro"
        $booleanQuery = '+' . str_replace(' ', ' +', trim($query)); 

        // 2. Add relevance to the SELECT
        $sqlSearchSelect = ", MATCH(posts.Content) AGAINST(? IN BOOLEAN MODE) AS relevance ";
        // 3. Add the query to the WHERE clause
        $sqlSearchWhere = " AND MATCH(posts.Content) AGAINST(? IN BOOLEAN MODE) ";
        // 4. Order by relevance
        $sqlSearchOrder = " ORDER BY relevance DESC "; 
        
        // Add the *new* booleanQuery twice
        array_push($baseParams, $booleanQuery, $booleanQuery); 
    }
    // --- END OF FIX ---

    // 1. Fetch Posts (Limit 6)
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
                 $sqlSearchOrder LIMIT 6"; 
    
    $stmtPosts = $pdo->prepare($sqlPosts);
    $stmtPosts->execute($baseParams);
    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

    $response['hasMorePosts'] = count($posts) > 5;
    $posts = array_slice($posts, 0, 5);
    foreach ($posts as $post) {
        $response['posts'][] = FormatPostForClient($post, $UID);
    }

    // 2. Fetch Users (Unchanged)
    $searchTerm = '%' . $query . '%';
    $sqlUsers = "SELECT id, Fname, Lname, Username, ProfilePic 
                 FROM users 
                 WHERE ((CONCAT(TRIM(Fname), ' ', TRIM(Lname)) LIKE ?) OR (Username LIKE ?)) AND id != ?
                 LIMIT 6";
    $stmtUsers = $pdo->prepare($sqlUsers);
    $stmtUsers->execute([$searchTerm, $searchTerm, $UID]);
    // ... (rest of user logic is unchanged) ...
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    $response['hasMoreUsers'] = count($users) > 5;
    $users = array_slice($users, 0, 5);
    foreach ($users as $user) {
        $response['users'][] = FormatUserForClient($user);
    }

    echo json_encode($response);
    exit;
}

// --- REQTYPE 3: Fetch More Posts (NOW USES FTS) ---
if ($ReqType == 3) {
    $lastPostID = (int)Decrypt($_POST['lastPostID'], "Positioned");

// --- START OF FIX ---
    $baseParams = [$UID, $UID, $UID, $UID];
    $sqlSearchSelect = "";
    $sqlSearchWhere = " ";
    $sqlSearchOrder = " ORDER BY posts.Date DESC "; 

    if (!empty($query)) {
        // 1. Create the Boolean Mode query
        $booleanQuery = '+' . str_replace(' ', ' +', trim($query)); 

        // 2. Add relevance to the SELECT
        $sqlSearchSelect = ", MATCH(posts.Content) AGAINST(? IN BOOLEAN MODE) AS relevance ";
        // 3. Add the query to the WHERE clause
        $sqlSearchWhere = " AND MATCH(posts.Content) AGAINST(? IN BOOLEAN MODE) ";
        // 4. Order by relevance
        $sqlSearchOrder = " ORDER BY relevance DESC ";

        // Add the *new* booleanQuery twice
        array_push($baseParams, $booleanQuery, $booleanQuery);
    }
    // --- END OF FIX ---
    
    $baseParams[] = $lastPostID; // Add lastPostID for pagination

    $sqlPosts = "SELECT posts.id AS PID, posts.*, users.Fname, users.Lname, users.Username, users.ProfilePic,
                 CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                 CASE WHEN f.UserID IS NOT...
                 $sqlSearchSelect
                 FROM posts 
                 INNER JOIN users ON posts.UID = users.id
                 LEFT JOIN blocked_users b ON posts.UID = b.BlockedUID AND b.BlockerUID = ?
                 LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                 LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                 LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                 WHERE posts.Status = 1 AND b.id IS NULL $sqlSearchWhere AND posts.id < ?
                 $sqlSearchOrder LIMIT 6";

    $stmtPosts = $pdo->prepare($sqlPosts);
    $stmtPosts->execute($baseParams);

    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
    // ... (rest of logic is unchanged) ...
    $response['hasMorePosts'] = count($posts) > 5;
    $posts = array_slice($posts, 0, 5);
    foreach ($posts as $post) {
        $response['posts'][] = FormatPostForClient($post, $UID);
    }
    $response['success'] = true;
    echo json_encode($response);
    exit;
}


// --- REQTYPE 4: Fetch More Users ---
if ($ReqType == 4) {
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

    $sqlUsers = "SELECT id, Fname, Lname, Username, ProfilePic 
                 FROM users 
                 WHERE ((CONCAT(TRIM(Fname), ' ', TRIM(Lname)) LIKE ?) OR (Username LIKE ?)) AND id != ?
                 LIMIT 6 OFFSET ?";
    $stmtUsers = $pdo->prepare($sqlUsers);
    // PDO::PARAM_INT is important for LIMIT/OFFSET
    $stmtUsers->bindValue(1, $searchTerm);
    $stmtUsers->bindValue(2, $searchTerm);
    $stmtUsers->bindValue(3, $UID, PDO::PARAM_INT);
    $stmtUsers->bindValue(4, $offset, PDO::PARAM_INT);
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


/* --- HELPER FUNCTIONS --- */
// (These can be at the end of the file)

function FormatPostForClient($post, $UID)
{
    global $PATH; // We need the $PATH variable from the top of the file

    $params = ["Timestamp" => strtotime($post['Date'])];
    $encryptedFeedPostID = Encrypt($post['PID'], "Positioned", $params);
    $encryptedUserID = Encrypt($post['UID'], "Positioned", $params);

    $post['ProfilePic'] = $post['ProfilePic'] ? 'MediaFolders/profile_pictures/' . $post['ProfilePic'] : 'Imgs/Icons/unknown.png';

    // --- START OF FIX ---
    // This logic is copied from Origin/Operations/Feed.php
    $MediaFolder = $PATH . $post['MediaFolder']; // Use $PATH to build the server path for checking
    $media = [];
    if (!empty($post['MediaFolder']) && is_dir($MediaFolder)) {
        $MediaFiles = scandir($MediaFolder);
        foreach ($MediaFiles as $file) {
            if (in_array(strtolower($file), ['.', '..'])) {
                continue;  //skip this iteration
            }

            // This is the relative path for the client (no $PATH)
            $filePath = $post['MediaFolder'] . '/' . $file;

            $media[] = [
                'name' => $file,
                'path' => $filePath,
            ];
        }
    }
    // --- END OF FIX ---

    return [
        'PID' => $encryptedFeedPostID,
        'UID' => $encryptedUserID,
        'name' => htmlspecialchars($post['Fname'] . ' ' . $post['Lname']),
        'Username' => htmlspecialchars($post['Username']),
        'ProfilePic' => $post['ProfilePic'],
        'Content' => htmlspecialchars($post['Content']),
        'LikeCounter' => $post['LikeCounter'],
        'CommentCounter' => $post['CommentCounter'],
        'MediaFolder' => $media, // This is now a populated array
        'MediaType' => (int)$post['Type'],
        'liked' => (bool)$post['liked'],
        'following' => (bool)$post['following'],
        'Self' => (int)($post['UID'] == $UID),
        'saved' => (int)$post['saved']
    ];
}

function FormatUserForClient($user)
{
    $params = ["Timestamp" => time()];
    $user['uid_encrypted'] = urlencode(Encrypt($user['id'], "Positioned", $params));
    $user['ProfilePic'] = $user['ProfilePic'] ? 'MediaFolders/profile_pictures/' . $user['ProfilePic'] : 'Imgs/Icons/unknown.png';
    $user['Fname'] = htmlspecialchars($user['Fname']);
    $user['Lname'] = htmlspecialchars($user['Lname']);
    $user['Username'] = htmlspecialchars($user['Username']);

    return $user;
}
