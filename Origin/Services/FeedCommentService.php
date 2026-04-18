<?php

if (!function_exists('FeedProfilePicturePath')) {
    function FeedProfilePicturePath(?string $ProfilePic): string
    {
        return (!empty($ProfilePic))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($ProfilePic)
            : 'Imgs/Icons/unknown.png';
    }
}

if (!function_exists('FeedBuildCommentResponse')) {
    function FeedBuildCommentResponse(PDO $pdo, int $CommentID, int $ViewerUID, ?int $Timestamp = null): ?array
    {
        $Timestamp = $Timestamp ?? time();

        $sql = "SELECT comments.id as CID, comments.*, users.Fname, users.Lname, users.Username, users.ProfilePic, users.IsBlueTick
                FROM comments INNER JOIN users ON comments.UID = users.id
                WHERE comments.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$CommentID]);
        $Comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$Comment) {
            return null;
        }

        $AuthorID = (int)$Comment['UID'];
        $Comment['CID'] = Encrypt($CommentID, 'Positioned', ['Timestamp' => $Timestamp]);
        $Comment['UID'] = Encrypt($AuthorID, 'Positioned', ['Timestamp' => $Timestamp]);
        $Comment['ProfilePic'] = FeedProfilePicturePath($Comment['ProfilePic'] ?? null);
        $Comment['IsSelf'] = ($AuthorID === $ViewerUID);
        $Comment['liked'] = false;
        $Comment['LikeCounter'] = (int)($Comment['LikeCounter'] ?? 0);
        $Comment['ReplyCounter'] = (int)($Comment['ReplyCounter'] ?? 0);
        $Comment['Date'] = $Timestamp;

        return $Comment;
    }
}

if (!function_exists('FeedBuildReplyResponse')) {
    function FeedBuildReplyResponse(PDO $pdo, int $ReplyID, int $ViewerUID, ?int $Timestamp = null): ?array
    {
        $Timestamp = $Timestamp ?? time();

        $sql = "SELECT CR.id AS CRID, CR.UID, CR.Reply, CR.LikeCounter, CR.Date,
                CONCAT(U.Fname,' ',U.Lname) AS Sender,
                U.Username AS SenderUsername, U.ProfilePic AS SenderProfilePic, U.IsBlueTick,
                Tagged.Username AS TaggedUser
                FROM comments_replies CR
                INNER JOIN users U ON CR.UID = U.id
                LEFT JOIN users Tagged ON CR.Tagged = Tagged.id
                WHERE CR.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ReplyID]);
        $Reply = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$Reply) {
            return null;
        }

        $AuthorID = (int)$Reply['UID'];
        $Reply['CRID'] = Encrypt($ReplyID, 'Positioned', ['Timestamp' => $Timestamp]);
        $Reply['UID'] = Encrypt($AuthorID, 'Positioned', ['Timestamp' => $Timestamp]);
        $Reply['SenderProfilePic'] = FeedProfilePicturePath($Reply['SenderProfilePic'] ?? null);
        $Reply['IsSelf'] = ($AuthorID === $ViewerUID);
        $Reply['liked'] = false;
        $Reply['LikeCounter'] = (int)($Reply['LikeCounter'] ?? 0);
        $Reply['Date'] = $Timestamp;

        return $Reply;
    }
}

if (!function_exists('FeedDeleteComment')) {
    function FeedDeleteComment(PDO $pdo, int $CommentID, int $ViewerUID): array
    {
        if ($CommentID <= 0) {
            return ['success' => false, 'message' => 'Invalid Comment ID.'];
        }

        $sql = 'SELECT UID, PostID FROM comments WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$CommentID]);
        $CommentData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$CommentData) {
            return ['success' => false, 'message' => 'Comment not found.'];
        }

        if ((int)$CommentData['UID'] !== $ViewerUID) {
            return ['success' => false, 'message' => 'Permission denied. You do not own this comment.'];
        }

        $PostID = (int)$CommentData['PostID'];

        try {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM comments_replies_likes WHERE ReplyID IN (SELECT id FROM comments_replies WHERE CommentID = ?)')->execute([$CommentID]);
            $pdo->prepare('DELETE FROM comments_replies WHERE CommentID = ?')->execute([$CommentID]);
            $pdo->prepare('DELETE FROM comments_likes WHERE CommentID = ?')->execute([$CommentID]);
            $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$CommentID]);
            $pdo->prepare('UPDATE posts SET CommentCounter = GREATEST(0, CommentCounter - 1) WHERE id = ?')->execute([$PostID]);
            $pdo->commit();

            return ['success' => true, 'message' => 'Comment and its replies deleted successfully'];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Delete Comment Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred while deleting.'];
        }
    }
}

if (!function_exists('FeedDeleteReply')) {
    function FeedDeleteReply(PDO $pdo, int $ReplyID, int $ViewerUID): array
    {
        if ($ReplyID <= 0) {
            return ['success' => false, 'message' => 'Invalid Reply ID.'];
        }

        $sql = 'SELECT CommentID FROM comments_replies WHERE id = ? AND UID = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ReplyID, $ViewerUID]);
        $ReplyData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ReplyData) {
            return ['success' => false, 'message' => 'Reply not found or permission denied.'];
        }

        $CommentID = (int)$ReplyData['CommentID'];

        try {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM comments_replies_likes WHERE ReplyID = ?')->execute([$ReplyID]);
            $pdo->prepare('DELETE FROM comments_replies WHERE id = ?')->execute([$ReplyID]);
            $pdo->prepare('UPDATE comments SET ReplyCounter = GREATEST(0, ReplyCounter - 1) WHERE id = ?')->execute([$CommentID]);
            $pdo->commit();

            return ['success' => true, 'message' => 'Reply deleted'];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Delete Reply Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error deleting reply'];
        }
    }
}

if (!function_exists('FeedCreateComment')) {
    function FeedCreateComment(PDO $pdo, int $FeedPostID, int $ViewerUID, string $CommentContent, array $EncryptedMentions = []): array
    {
        if (!RowExists('posts', 'id', $FeedPostID)) {
            return ['success' => false, 'message' => 'Error: Post Not Found'];
        }

        $stmt = $pdo->prepare('INSERT INTO comments(comment,PostID,UID) VALUES (?,?,?)');
        if (!$stmt->execute([$CommentContent, $FeedPostID, $ViewerUID])) {
            return ['success' => false, 'message' => 'Error: Failed To insert comment'];
        }

        $newCID = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE posts SET CommentCounter=CommentCounter+1 WHERE id=?')->execute([$FeedPostID]);

        $stmtOwner = $pdo->prepare('SELECT UID FROM posts WHERE id = ?');
        $stmtOwner->execute([$FeedPostID]);
        $PostOwnerUID = (int)$stmtOwner->fetchColumn();
        CreateNotification($PostOwnerUID, $ViewerUID, 2, $FeedPostID);

        if (!empty($EncryptedMentions)) {
            $seen = [];
            foreach ($EncryptedMentions as $EncMentionUID) {
                $MentionUID = (int)Decrypt($EncMentionUID, "Positioned");
                if ($MentionUID <= 0 || $MentionUID === $ViewerUID || in_array($MentionUID, $seen, true)) continue;
                if (!RowExists('users', 'id', $MentionUID)) continue;
                $seen[] = $MentionUID;
                CreateNotification($MentionUID, $ViewerUID, 7, $FeedPostID);
            }
        }

        $newComment = FeedBuildCommentResponse($pdo, $newCID, $ViewerUID, time());
        if (!$newComment) {
            return ['success' => true, 'message' => 'Comment added successfully'];
        }

        return [
            'success' => true,
            'message' => 'Comment added successfully',
            'comment' => $newComment
        ];
    }
}

if (!function_exists('FeedFetchComments')) {
    function FeedFetchComments(PDO $pdo, int $FeedPostID, int $ViewerUID): array
    {
        if (!RowExists('posts', 'id', $FeedPostID)) {
            return ['success' => false, 'message' => 'Error: Post Not Found'];
        }

        $sql = "SELECT comments.id as CID,comments.*,users.Fname,users.Lname,users.Username,users.ProfilePic,users.IsBlueTick,
                CASE WHEN CL.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
                FROM comments
                INNER JOIN users ON comments.UID=users.id
                LEFT JOIN comments_likes CL ON comments.id=CL.CommentID AND CL.UID=?
                WHERE comments.PostID=?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ViewerUID, $FeedPostID]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comments as &$Comment) {
            $timestamp = strtotime($Comment['Date']);
            $Comment['Date'] = $timestamp;
            $Comment['CID'] = Encrypt($Comment['CID'], "Positioned", ["Timestamp" => $timestamp]);
            $Comment['ProfilePic'] = FeedProfilePicturePath($Comment['ProfilePic'] ?? null);
            $Comment['IsSelf'] = ((int)$Comment['UID'] === $ViewerUID);
            $Comment['UID'] = Encrypt($Comment['UID'], "Positioned", ["Timestamp" => $timestamp]);
        }
        unset($Comment);

        return $comments;
    }
}

if (!function_exists('FeedToggleCommentLike')) {
    function FeedToggleCommentLike(PDO $pdo, int $CommentID, int $ViewerUID): array
    {
        if (!RowExists('comments', 'id', $CommentID)) {
            return ['success' => false, 'message' => 'Comment not found'];
        }

        $stmt = $pdo->prepare("SELECT Count(*) FROM comments_likes WHERE CommentID=? AND UID=?");
        $stmt->execute([$CommentID, $ViewerUID]);

        if ((int)$stmt->fetchColumn() === 0) {
            $stmt = $pdo->prepare('INSERT INTO comments_likes(CommentID,UID) VALUES (?,?)');
            if ($stmt->execute([$CommentID, $ViewerUID])) {
                $pdo->prepare("UPDATE comments SET LikeCounter=LikeCounter+1 WHERE id=?")->execute([$CommentID]);

                $stmtDetails = $pdo->prepare("SELECT UID, PostID FROM comments WHERE id = ?");
                $stmtDetails->execute([$CommentID]);
                $details = $stmtDetails->fetch(PDO::FETCH_ASSOC);

                CreateNotification((int)$details['UID'], $ViewerUID, 5, (int)$details['PostID']);

                return [
                    'success' => true,
                    'message' => 'Comment Liked',
                    'liked' => true,
                    'Insertion' => 1
                ];
            }
        }

        $stmt = $pdo->prepare("DELETE FROM comments_likes WHERE CommentID = ? AND UID = ?");
        if ($stmt->execute([$CommentID, $ViewerUID])) {
            $pdo->prepare("UPDATE comments SET LikeCounter=LikeCounter-1 WHERE id=?")->execute([$CommentID]);
            return [
                'success' => true,
                'message' => 'Comment Unliked',
                'liked' => false,
                'Insertion' => -1
            ];
        }

        return ['success' => false, 'message' => 'Failed to update comment like state.'];
    }
}

if (!function_exists('FeedCreateReply')) {
    function FeedCreateReply(PDO $pdo, int $CommentID, int $ViewerUID, string $Reply, ?int $TaggedUser, array $EncryptedMentions = []): array
    {
        if (!RowExists('comments', 'id', $CommentID)) {
            return ['success' => false, 'message' => 'Comment not found'];
        }

        if ($TaggedUser !== null && !RowExists('users', 'id', $TaggedUser)) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $stmt = $pdo->prepare("INSERT INTO comments_replies (CommentID,UID,Reply,Tagged,`Date`) VALUES (?,?,?,?,?)");
        if (!$stmt->execute([$CommentID, $ViewerUID, $Reply, $TaggedUser, date('Y-m-d H:i:s')])) {
            return ['success' => false, 'message' => 'Error inserting reply'];
        }

        $newRID = (int)$pdo->lastInsertId();
        if (!$pdo->prepare("UPDATE comments SET ReplyCounter=ReplyCounter+1 WHERE id=?")->execute([$CommentID])) {
            return ['success' => false, 'message' => 'Error incrementing reply counter'];
        }

        $stmtDetails = $pdo->prepare("SELECT UID, PostID FROM comments WHERE id = ?");
        $stmtDetails->execute([$CommentID]);
        $CommentDetails = $stmtDetails->fetch(PDO::FETCH_ASSOC);

        $CommentOwnerUID = (int)$CommentDetails['UID'];
        $ReferencePostID = (int)$CommentDetails['PostID'];
        $TargetUID = ($TaggedUser !== null) ? $TaggedUser : $CommentOwnerUID;

        CreateNotification($TargetUID, $ViewerUID, 3, $ReferencePostID);

        if (!empty($EncryptedMentions)) {
            $seenMentions = [];
            foreach ($EncryptedMentions as $EncMentionUID) {
                $MentionUID = (int)Decrypt($EncMentionUID, "Positioned");
                if ($MentionUID <= 0 || $MentionUID === $ViewerUID || $MentionUID === $TargetUID || in_array($MentionUID, $seenMentions, true)) continue;
                if (!RowExists('users', 'id', $MentionUID)) continue;
                $seenMentions[] = $MentionUID;
                CreateNotification($MentionUID, $ViewerUID, 7, $ReferencePostID);
            }
        }

        $newReply = FeedBuildReplyResponse($pdo, $newRID, $ViewerUID, time());
        if (!$newReply) {
            return ['success' => true, 'message' => 'Reply inserted'];
        }

        return [
            'success' => true,
            'message' => 'Reply inserted',
            'reply' => $newReply,
        ];
    }
}

if (!function_exists('FeedToggleReplyLike')) {
    function FeedToggleReplyLike(PDO $pdo, int $ReplyID, int $ViewerUID): array
    {
        if (!RowExists('comments_replies', 'id', $ReplyID)) {
            return ['success' => false, 'message' => 'Comment not found'];
        }

        $stmt = $pdo->prepare("SELECT Count(*) FROM comments_replies_likes WHERE ReplyID=? AND UID=?");
        $stmt->execute([$ReplyID, $ViewerUID]);

        if ((int)$stmt->fetchColumn() === 0) {
            $stmt = $pdo->prepare('INSERT INTO comments_replies_likes(ReplyID,UID) VALUES (?,?)');
            if ($stmt->execute([$ReplyID, $ViewerUID])) {
                $pdo->prepare("UPDATE comments_replies SET LikeCounter=LikeCounter+1 WHERE id=?")->execute([$ReplyID]);

                $stmtReply = $pdo->prepare("SELECT UID, CommentID FROM comments_replies WHERE id = ?");
                $stmtReply->execute([$ReplyID]);
                $replyData = $stmtReply->fetch(PDO::FETCH_ASSOC);

                $stmtComment = $pdo->prepare("SELECT PostID FROM comments WHERE id = ?");
                $stmtComment->execute([(int)$replyData['CommentID']]);
                $ReferencePostID = (int)$stmtComment->fetchColumn();

                CreateNotification((int)$replyData['UID'], $ViewerUID, 6, $ReferencePostID);

                return [
                    'success' => true,
                    'message' => 'Comment Liked',
                    'liked' => true,
                    'Insertion' => 1
                ];
            }
        }

        $stmt = $pdo->prepare("DELETE FROM comments_replies_likes WHERE ReplyID = ? AND UID = ?");
        if ($stmt->execute([$ReplyID, $ViewerUID])) {
            $pdo->prepare("UPDATE comments_replies SET LikeCounter=LikeCounter-1 WHERE id=?")->execute([$ReplyID]);
            return [
                'success' => true,
                'message' => 'Comment Unliked',
                'liked' => false,
                'Insertion' => -1
            ];
        }

        return ['success' => false, 'message' => 'Failed to update reply like state.'];
    }
}

if (!function_exists('FeedFetchReplies')) {
    function FeedFetchReplies(PDO $pdo, int $CommentID, int $ViewerUID): array
    {
        if (!RowExists('comments', 'id', $CommentID)) {
            return ['success' => false, 'message' => 'Comment not found'];
        }

        $sql = "SELECT CR.id AS CRID, CR.UID,CR.Reply,CR.LikeCounter, CR.Date,CONCAT(Sender.Fname,' ',Sender.Lname) AS Sender,
                Sender.Username AS SenderUsername, Sender.ProfilePic AS SenderProfilePic, Sender.IsBlueTick,
                Tagged.Username AS TaggedUser,
                CASE WHEN CRL.UID IS NOT NULL THEN TRUE ELSE FALSE END AS liked
                FROM comments_replies CR
                INNER JOIN users Sender ON CR.UID=Sender.id
                LEFT JOIN users Tagged ON CR.Tagged=Tagged.id
                LEFT JOIN comments_replies_likes CRL ON CRL.ReplyID=CR.id AND CRL.UID=?
                WHERE CommentID=? ORDER BY CR.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ViewerUID, $CommentID]);
        $Replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($Replies as &$Reply) {
            $timestamp = strtotime($Reply['Date']);
            $Reply['Date'] = $timestamp;
            $Reply['CRID'] = Encrypt($Reply['CRID'], "Positioned", ["Timestamp" => $timestamp]);
            $Reply['SenderProfilePic'] = FeedProfilePicturePath($Reply['SenderProfilePic'] ?? null);
            $Reply['IsSelf'] = ((int)$Reply['UID'] === $ViewerUID);
            $Reply['UID'] = Encrypt($Reply['UID'], "Positioned", ["Timestamp" => $timestamp]);
        }
        unset($Reply);

        return $Replies;
    }
}
