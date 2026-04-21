<?php

if (!function_exists('FeedBuildPostMedia')) {
    function FeedBuildPostMedia(string $BasePath, ?string $MediaFolder): array
    {
        if (empty($MediaFolder)) {
            return [];
        }

        $FolderPath = $BasePath . $MediaFolder;
        if (!is_dir($FolderPath)) {
            return [];
        }

        $Media = [];
        foreach (scandir($FolderPath) as $File) {
            if ($File === '.' || $File === '..') {
                continue;
            }

            $Media[] = [
                'name' => $File,
                'path' => $MediaFolder . '/' . $File
            ];
        }

        return $Media;
    }
}

if (!function_exists('FeedBuildPostResponse')) {
    function FeedBuildPostResponse(array $PostRow, int $ViewerUID, string $BasePath, int $CurrentUserPrivilege = 0): array
    {
        $Timestamp = strtotime($PostRow['Date']);
        $PostID = (int)($PostRow['PID'] ?? $PostRow['id'] ?? 0);
        $AuthorID = (int)($PostRow['UID'] ?? 0);
        $MediaFolder = $PostRow['MediaFolder'] ?? $PostRow['Mediafolder'] ?? '';
        $PageLogo = $PostRow['PageLogo'] ?? null;
        $ProfilePic = $PostRow['ProfilePic'] ?? null;

        return [
            'PID' => Encrypt($PostID, 'Positioned', ['Timestamp' => $Timestamp]),
            'UID' => Encrypt($AuthorID, 'Positioned', ['Timestamp' => $Timestamp]),
            'name' => trim(($PostRow['Fname'] ?? '') . ' ' . ($PostRow['Lname'] ?? '')),
            'Username' => $PostRow['Username'] ?? null,
            'Date' => $Timestamp,
            'Content' => $PostRow['Content'] ?? '',
            'LikeCounter' => (int)($PostRow['LikeCounter'] ?? 0),
            'CommentCounter' => (int)($PostRow['CommentCounter'] ?? 0),
            'MediaFolder' => FeedBuildPostMedia($BasePath, $MediaFolder),
            'MediaType' => (int)($PostRow['Type'] ?? 1),
            'CurrentUserPrivilege' => $CurrentUserPrivilege,
            'liked' => (bool)($PostRow['liked'] ?? false),
            'following' => (bool)($PostRow['following'] ?? false),
            'Self' => (int)($AuthorID === $ViewerUID),
            'saved' => (int)($PostRow['saved'] ?? 0),
            'ProfilePic' => (!empty($ProfilePic))
                ? 'MediaFolders/profile_pictures/' . htmlspecialchars($ProfilePic)
                : 'Imgs/Icons/unknown.png',
            'IsBlueTick' => (int)($PostRow['IsBlueTick'] ?? 0),
            'PageName' => $PostRow['PageName'] ?? null,
            'PageHandle' => $PostRow['PageHandle'] ?? null,
            'PageLogo' => !empty($PageLogo) ? 'MediaFolders/page_logos/' . $PageLogo : null,
            'PageIsVerified' => (int)($PostRow['PageIsVerified'] ?? 0),
            'Visibility' => (int)($PostRow['Visibility'] ?? 0)
        ];
    }
}

if (!function_exists('FeedBuildFeedResponseList')) {
    function FeedBuildFeedResponseList(array $Posts, int $ViewerUID, string $BasePath, int $CurrentUserPrivilege = 0): array
    {
        $Response = [];
        foreach ($Posts as $FeedPost) {
            $Response[] = FeedBuildPostResponse($FeedPost, $ViewerUID, $BasePath, $CurrentUserPrivilege);
        }
        return $Response;
    }
}

if (!function_exists('FeedTogglePostLike')) {
    function FeedTogglePostLike(PDO $pdo, int $FeedPostID, int $ViewerUID): array
    {
        $stmt = $pdo->prepare('SELECT UID FROM posts WHERE id = ?');
        $stmt->execute([$FeedPostID]);
        $PostOwnerUID = $stmt->fetchColumn();

        if (!$PostOwnerUID) {
            return ['success' => false, 'message' => 'Post not found.'];
        }

        $stmt = $pdo->prepare('SELECT 1 FROM likes WHERE PostID = ? AND UID = ?');
        $stmt->execute([$FeedPostID, $ViewerUID]);

        if (!$stmt->fetchColumn()) {
            $stmt = $pdo->prepare('INSERT INTO likes(PostID, UID) VALUES (?, ?)');
            if ($stmt->execute([$FeedPostID, $ViewerUID])) {
                $pdo->prepare('UPDATE posts SET LikeCounter = LikeCounter + 1 WHERE id = ?')->execute([$FeedPostID]);
                CreateNotification((int)$PostOwnerUID, $ViewerUID, 1, $FeedPostID);
                InvalidateFeedCache($pdo, [$ViewerUID]);

                return [
                    'success' => true,
                    'message' => 'Like Added',
                    'liked' => true,
                    'Insertion' => 1
                ];
            }
        }

        $stmt = $pdo->prepare('DELETE FROM likes WHERE PostID = ? AND UID = ?');
        if ($stmt->execute([$FeedPostID, $ViewerUID])) {
            $pdo->prepare('UPDATE posts SET LikeCounter = LikeCounter - 1 WHERE id = ?')->execute([$FeedPostID]);
            InvalidateFeedCache($pdo, [$ViewerUID]);

            return [
                'success' => true,
                'message' => 'Like Removed',
                'liked' => false,
                'Insertion' => -1
            ];
        }

        return ['success' => false, 'message' => 'Failed to update like state.'];
    }
}

if (!function_exists('FeedDeletePost')) {
    function FeedDeletePost(PDO $pdo, int $FeedPostID, int $ViewerUID): array
    {
        $stmt = $pdo->prepare('UPDATE posts SET Status = 0 WHERE id = ? AND UID = ?');
        if ($stmt->execute([$FeedPostID, $ViewerUID]) && $stmt->rowCount() > 0) {
            InvalidateFeedCache($pdo, [$ViewerUID]);
            return [
                'success' => true,
                'message' => 'Post Deleted'
            ];
        }

        return [
            'success' => false,
            'message' => 'Post not found or you do not have permission to delete it.'
        ];
    }
}

if (!function_exists('FeedToggleFollowUser')) {
    function FeedToggleFollowUser(PDO $pdo, int $ViewerUID, int $TargetUserID): array
    {
        $stmt = $pdo->prepare('SELECT 1 FROM followers WHERE FollowerID = ? AND UserID = ?');
        $stmt->execute([$ViewerUID, $TargetUserID]);

        if ($stmt->fetchColumn()) {
            $stmt = $pdo->prepare('DELETE FROM followers WHERE FollowerID = ? AND UserID = ?');
            if ($stmt->execute([$ViewerUID, $TargetUserID])) {
                $pdo->prepare('UPDATE users SET Followers = Followers - 1 WHERE id = ?')->execute([$TargetUserID]);
                $pdo->prepare('UPDATE users SET Following = Following - 1 WHERE id = ?')->execute([$ViewerUID]);
                InvalidateFeedCache($pdo, [$ViewerUID, $TargetUserID]);

                return [
                    'success' => true,
                    'message' => 'Unfollowed',
                    'Followed' => false
                ];
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO followers (FollowerID, UserID) VALUES (?, ?)');
            if ($stmt->execute([$ViewerUID, $TargetUserID])) {
                $pdo->prepare('UPDATE users SET Followers = Followers + 1 WHERE id = ?')->execute([$TargetUserID]);
                $pdo->prepare('UPDATE users SET Following = Following + 1 WHERE id = ?')->execute([$ViewerUID]);
                CreateNotification($TargetUserID, $ViewerUID, 4);
                InvalidateFeedCache($pdo, [$ViewerUID, $TargetUserID]);

                return [
                    'success' => true,
                    'message' => 'Followed',
                    'Followed' => true
                ];
            }
        }

        return ['success' => false, 'message' => 'Failed to update follow state.'];
    }
}

if (!function_exists('FeedToggleSavedPost')) {
    function FeedToggleSavedPost(PDO $pdo, int $ViewerUID, int $PostID): array
    {
        if (!RowExists('posts', 'id', $PostID)) {
            return ['success' => false, 'message' => 'Post not found.'];
        }

        $stmt = $pdo->prepare('SELECT id FROM saved_posts WHERE UID = ? AND PostID = ?');
        $stmt->execute([$ViewerUID, $PostID]);

        if ($stmt->fetch()) {
            $pdo->prepare('DELETE FROM saved_posts WHERE UID = ? AND PostID = ?')->execute([$ViewerUID, $PostID]);
            return ['success' => true, 'message' => 'Post unsaved!', 'Saved' => false];
        }

        $pdo->prepare('INSERT INTO saved_posts (UID, PostID) VALUES (?, ?)')->execute([$ViewerUID, $PostID]);
        return ['success' => true, 'message' => 'Post saved!', 'Saved' => true];
    }
}

if (!function_exists('FeedBlockUser')) {
    function FeedBlockUser(PDO $pdo, int $ViewerUID, int $BlockedUID): array
    {
        if ($BlockedUID === $ViewerUID) {
            return ['success' => false, 'message' => 'You cannot block yourself.'];
        }

        if (!RowExists('users', 'id', $BlockedUID)) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $stmt = $pdo->prepare('SELECT id FROM blocked_users WHERE BlockerUID = ? AND BlockedUID = ?');
        $stmt->execute([$ViewerUID, $BlockedUID]);

        if ($stmt->fetch()) {
            InvalidateFeedCache($pdo, [$ViewerUID]);
            return ['success' => true, 'message' => 'User is already blocked.'];
        }

        $pdo->prepare('INSERT INTO blocked_users (BlockerUID, BlockedUID) VALUES (?, ?)')->execute([$ViewerUID, $BlockedUID]);
        InvalidateFeedCache($pdo, [$ViewerUID]);
        return ['success' => true, 'message' => 'User blocked.'];
    }
}

if (!function_exists('FeedFetchPostForEditing')) {
    function FeedFetchPostForEditing(PDO $pdo, int $FeedPostID, int $ViewerUID, string $BasePath): array
    {
        $stmt = $pdo->prepare('SELECT Content, Type, MediaFolder, Visibility FROM posts WHERE id = ? AND UID = ?');
        $stmt->execute([$FeedPostID, $ViewerUID]);
        $Post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$Post) {
            return ['success' => false, 'message' => 'Post not found or you do not have permission to edit it.'];
        }

        $MentionMap = [];
        preg_match_all('/@([\w]+)/', $Post['Content'], $Matches);
        if (!empty($Matches[1])) {
            $UniqueUsernames = array_unique($Matches[1]);
            $Placeholders = implode(',', array_fill(0, count($UniqueUsernames), '?'));
            $stmtMentions = $pdo->prepare("SELECT id, Username FROM users WHERE Username IN ($Placeholders)");
            $stmtMentions->execute($UniqueUsernames);
            while ($Row = $stmtMentions->fetch(PDO::FETCH_ASSOC)) {
                $MentionMap[$Row['Username']] = Encrypt((int)$Row['id'], 'Positioned', ['Timestamp' => time()]);
            }
        }

        return [
            'success' => true,
            'Content' => $Post['Content'],
            'MediaType' => (int)$Post['Type'],
            'MediaFiles' => FeedBuildPostMedia($BasePath, $Post['MediaFolder'] ?? ''),
            'MentionMap' => $MentionMap,
            'Visibility' => (int)($Post['Visibility'] ?? 0)
        ];
    }
}

if (!function_exists('FeedRecordPostView')) {
    function FeedRecordPostView(PDO $pdo, int $PostID, int $ViewerUID): array
    {
        if ($PostID > 0) {
            $pdo->prepare('INSERT IGNORE INTO post_views (PostID, UID, ViewedAt) VALUES (?, ?, NOW())')->execute([$PostID, $ViewerUID]);
        }

        return ['success' => true];
    }
}

if (!function_exists('FeedNotifyPostMentions')) {
    function FeedNotifyPostMentions(int $ViewerUID, array $Mentions, int $ReferencePostID): void
    {
        if (empty($Mentions) || !is_array($Mentions)) {
            return;
        }

        $SeenMentions = [];
        foreach ($Mentions as $EncMentionUID) {
            $MentionUID = (int)Decrypt($EncMentionUID, 'Positioned');
            if ($MentionUID <= 0 || $MentionUID === $ViewerUID || in_array($MentionUID, $SeenMentions, true)) {
                continue;
            }
            if (!RowExists('users', 'id', $MentionUID)) {
                continue;
            }

            $SeenMentions[] = $MentionUID;
            CreateNotification($MentionUID, $ViewerUID, 7, $ReferencePostID);
        }
    }
}

if (!function_exists('FeedMoveUploadedDocuments')) {
    function FeedMoveUploadedDocuments(array $DocumentFiles, string $FolderPath, int $ViewerUID, array $AllowedDocumentExtensions): void
    {
        $CreationTime = strtotime('now');
        for ($i = 0; $i < count($DocumentFiles['name']); $i++) {
            $FileExtension = pathinfo($DocumentFiles['name'][$i], PATHINFO_EXTENSION);
            $NewFilename = $CreationTime . $ViewerUID . '_file' . ($i + 1) . '.' . $FileExtension;
            $TargetPath = $FolderPath . '/' . $NewFilename;

            if (!in_array(strtolower($FileExtension), $AllowedDocumentExtensions, true)) {
                throw new RuntimeException('Error: The File Extension Of ' . $DocumentFiles['name'][$i] . ' is not allowed !');
            }

            if (!move_uploaded_file($DocumentFiles['tmp_name'][$i], $TargetPath)) {
                throw new RuntimeException('Error: Failed to move ' . $DocumentFiles['name'][$i] . ' to the specified directory');
            }
        }
    }
}

if (!function_exists('FeedMoveUploadedImages')) {
    function FeedMoveUploadedImages(array $ImageFiles, string $FolderPath, int $ViewerUID, array $AllowedImagesExtensions): void
    {
        $CreationTime = strtotime('now');

        for ($i = 0; $i < count($ImageFiles['name']); $i++) {
            $FileExtension = strtolower(pathinfo($ImageFiles['name'][$i], PATHINFO_EXTENSION));
            $NewFilename = $CreationTime . $ViewerUID . '_file' . ($i + 1) . '.' . $FileExtension;
            $TargetPath = $FolderPath . '/' . $NewFilename;

            if (!in_array($FileExtension, $AllowedImagesExtensions, true)) {
                throw new RuntimeException('Error: The File Extension Of ' . $ImageFiles['name'][$i] . ' is not allowed !');
            }

            $ScaleSupportedExtension = false;
            $Image = null;

            if ($FileExtension === 'jpeg' || $FileExtension === 'jpg') {
                $Image = imagecreatefromjpeg($ImageFiles['tmp_name'][$i]);
                $ScaleSupportedExtension = true;
            } else if ($FileExtension === 'png') {
                $Image = imagecreatefrompng($ImageFiles['tmp_name'][$i]);
                $ScaleSupportedExtension = true;
            }

            $Width = $Image ? imagesx($Image) : 0;
            $Height = $Image ? imagesy($Image) : 0;

            if ($ScaleSupportedExtension && $Width >= 1920 && $Height >= 900) {
                if ($Width > $Height) {
                    $NewHeight = 900;
                    $NewWidth = (int) round(($NewHeight * $Width) / $Height);
                    $Image = imagescale($Image, $NewWidth, $NewHeight);
                } else {
                    $NewWidth = 900;
                    $NewHeight = (int) round(($NewWidth * $Height) / $Width);
                    $Image = imagescale($Image, $NewWidth, $NewHeight);
                }

                $Saved = false;
                if ($FileExtension === 'jpeg' || $FileExtension === 'jpg') {
                    $Saved = imagejpeg($Image, $TargetPath);
                } else if ($FileExtension === 'png') {
                    $Saved = imagepng($Image, $TargetPath);
                }

                if ($Image) {
                    imagedestroy($Image);
                }

                if (!$Saved) {
                    throw new RuntimeException('Error: Failed to move ' . $ImageFiles['name'][$i] . ' to the specified directory');
                }

                continue;
            }

            if (!move_uploaded_file($ImageFiles['tmp_name'][$i], $TargetPath)) {
                throw new RuntimeException('Error:' . ($i + 1) . ' Failed to move ' . $ImageFiles['name'][$i] . ' to the specified directory');
            }
        }
    }
}

if (!function_exists('FeedCreatePost')) {
    function FeedCreatePost(
        PDO $pdo,
        int $ViewerUID,
        array $Request,
        array $Files,
        string $BasePath,
        int $CurrentUserPrivilege,
        array $AllowedDocumentExtensions,
        array $AllowedImagesExtensions
    ): array {
        $PostContent = $Request['content'] ?? '';
        $FolderPath = '';
        $RootMediaFolderPath = '';
        $ImagesFound = false;
        $DocumentFound = false;

        if (!empty($Files)) {
            if (isset($Files['document']) && isset($Files['images'])) {
                return ['success' => false, 'message' => 'Error: Multiple File Types Detected !'];
            }

            $CreationTime = strtotime('now');
            $FolderName = $CreationTime . $ViewerUID . uniqid();
            $FolderPath = $BasePath . 'MediaFolders/posts/' . $FolderName;
            $RootMediaFolderPath = 'MediaFolders/posts/' . $FolderName;

            if (!mkdir($FolderPath, 0777, true) && !is_dir($FolderPath)) {
                return ['success' => false, 'message' => 'Error: Could not create media directory.'];
            }

            try {
                if (isset($Files['document'])) {
                    $DocumentFound = true;
                    FeedMoveUploadedDocuments($Files['document'], $FolderPath, $ViewerUID, $AllowedDocumentExtensions);
                }

                if (isset($Files['images'])) {
                    $ImagesFound = true;
                    FeedMoveUploadedImages($Files['images'], $FolderPath, $ViewerUID, $AllowedImagesExtensions);
                }
            } catch (RuntimeException $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        $Type = $DocumentFound ? 3 : ($ImagesFound ? 2 : 1);

        $OrgID = null;
        if (!empty($Request['PostAsPageID'])) {
            $PostAsPageID = (int)Decrypt($Request['PostAsPageID'], 'Positioned');
            if ($PostAsPageID > 0) {
                $MemberCheck = $pdo->prepare('SELECT Role FROM page_members WHERE PageID = ? AND UID = ?');
                $MemberCheck->execute([$PostAsPageID, $ViewerUID]);
                if ($MemberCheck->fetchColumn() !== false) {
                    $OrgID = $PostAsPageID;
                }
            }
        }

        $Visibility = (int)($Request['Visibility'] ?? 0);
        if (!in_array($Visibility, [0, 1, 2, 3, 4], true)) {
            $Visibility = 0;
        }
        if ($OrgID !== null) {
            $Visibility = 0;
        }

        $stmt = $pdo->prepare('INSERT INTO posts (Content, Type, Mediafolder, Date, Status, UID, OrgID, Visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt->execute([$PostContent, $Type, $RootMediaFolderPath, date('Y-m-d H:i:s'), 1, $ViewerUID, $OrgID, $Visibility])) {
            return ['success' => false, 'message' => 'Error: Failed to insert data into the database'];
        }

        $LastInsertId = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT posts.id AS PID, posts.*, users.*, FALSE AS liked,
                pg.Name AS PageName, pg.Handle AS PageHandle, pg.Logo AS PageLogo, pg.IsVerified AS PageIsVerified
                FROM posts
                INNER JOIN users ON posts.UID = users.id
                LEFT JOIN pages pg ON posts.OrgID = pg.id
                WHERE posts.id = ?');
        $stmt->execute([$LastInsertId]);
        $NewPost = $stmt->fetch(PDO::FETCH_ASSOC);

        FeedNotifyPostMentions($ViewerUID, $Request['Mentions'] ?? [], $LastInsertId);

        return [
            'success' => true,
            'message' => 'Post added successfully',
            'post' => FeedBuildPostResponse($NewPost, $ViewerUID, $BasePath, $CurrentUserPrivilege)
        ];
    }
}

if (!function_exists('FeedUpdatePost')) {
    function FeedUpdatePost(
        PDO $pdo,
        int $ViewerUID,
        array $Request,
        array $Files,
        string $BasePath,
        int $CurrentUserPrivilege,
        array $AllowedDocumentExtensions,
        array $AllowedImagesExtensions
    ): array {
        $FeedPostID = (int)Decrypt($Request['PostID'] ?? '', 'Positioned');
        $PostContent = $Request['content'] ?? '';
        $FilesToDelete = isset($Request['files_to_delete']) ? json_decode($Request['files_to_delete']) : [];

        $stmt = $pdo->prepare('SELECT MediaFolder, Type FROM posts WHERE id = ? AND UID = ?');
        $stmt->execute([$FeedPostID, $ViewerUID]);
        $Post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$Post) {
            return ['success' => false, 'message' => 'Post not found or you do not have permission to edit it.'];
        }

        $FolderPath = $BasePath . $Post['MediaFolder'];
        $CurrentType = (int)$Post['Type'];
        $ImagesFound = false;
        $DocumentFound = false;

        if (!empty($Post['MediaFolder']) && !is_dir($FolderPath)) {
            if (!mkdir($FolderPath, 0777, true) && !is_dir($FolderPath)) {
                return ['success' => false, 'message' => 'Error: Could not create media directory.'];
            }
        }

        if (!empty($FilesToDelete)) {
            foreach ($FilesToDelete as $Filename) {
                $FilePath = $FolderPath . '/' . basename($Filename);
                if (file_exists($FilePath)) {
                    unlink($FilePath);
                }
            }
        }

        if (isset($Files['document']) && isset($Files['images'])) {
            return ['success' => false, 'message' => 'Error: Multiple File Types Detected !'];
        }

        try {
            if (isset($Files['document'])) {
                $DocumentFound = true;
                FeedMoveUploadedDocuments($Files['document'], $FolderPath, $ViewerUID, $AllowedDocumentExtensions);
            }

            if (isset($Files['images'])) {
                $ImagesFound = true;
                FeedMoveUploadedImages($Files['images'], $FolderPath, $ViewerUID, $AllowedImagesExtensions);
            }
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $RemainingFiles = glob($FolderPath . '/*');
        $FileCount = $RemainingFiles ? count($RemainingFiles) : 0;

        $NewType = $CurrentType;
        if ($FileCount === 0) {
            $NewType = 1;
        } else if ($DocumentFound) {
            $NewType = 3;
        } else if ($ImagesFound) {
            $NewType = 2;
        } else if ($CurrentType === 3 && !$DocumentFound) {
            $NewType = 3;
        } else if ($CurrentType === 2 && !$ImagesFound) {
            $NewType = 2;
        }

        $stmt = $pdo->prepare('UPDATE posts SET Content = ?, Type = ? WHERE id = ? AND UID = ?');
        if (!$stmt->execute([$PostContent, $NewType, $FeedPostID, $ViewerUID])) {
            return ['success' => false, 'message' => 'Error: Failed to update post in database.'];
        }

        $stmt = $pdo->prepare('SELECT posts.id AS PID, posts.*, users.Fname, users.Lname, users.Username, users.ProfilePic,
                CASE WHEN likes.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked,
                CASE WHEN f.UserID IS NOT NULL THEN TRUE ELSE FALSE END AS following,
                CASE WHEN sp.PostID IS NOT NULL THEN TRUE ELSE FALSE END AS saved
                FROM posts
                INNER JOIN users ON posts.UID = users.id
                LEFT JOIN likes ON posts.id = likes.PostID AND likes.UID = ?
                LEFT JOIN followers f ON f.UserID = users.id AND f.FollowerID = ?
                LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
                WHERE posts.id = ?');
        $stmt->execute([$ViewerUID, $ViewerUID, $ViewerUID, $FeedPostID]);
        $UpdatedPost = $stmt->fetch(PDO::FETCH_ASSOC);

        FeedNotifyPostMentions($ViewerUID, $Request['Mentions'] ?? [], $FeedPostID);

        return [
            'success' => true,
            'message' => 'Post updated successfully',
            'post' => FeedBuildPostResponse($UpdatedPost, $ViewerUID, $BasePath, $CurrentUserPrivilege)
        ];
    }
}
