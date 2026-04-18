<?php
$PATH="../../";

require_once $PATH."Includes/Config.php";
require_once $PATH.'Includes/UserAuth.php';
require_once $PATH.'Includes/Encryption.php';
require_once $PATH.'Includes/FeedAlgorithm.php';
include_once $PATH.'Origin/Validation.php';
require_once $PATH.'Origin/Services/FeedCommentService.php';
require_once $PATH.'Origin/Services/FeedPostService.php';


function CreateNotification($ToUID, $FromUID, $Type, $ReferenceID = null, $MetaInfo = null) {
    global $pdo;

    if ($ToUID == $FromUID && $FromUID !== null) {
        return;
    }

    $sql = "INSERT INTO notifications (ToUID, FromUID, Type, ReferenceID, MetaInfo, Date)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ToUID, $FromUID, $Type, $ReferenceID, $MetaInfo]);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    ValidateCsrf();
    $UID = $User['id'];

    if ($_POST['ReqType'] == 1) { // Create post
        echo json_encode(FeedCreatePost(
            $pdo, $UID,
            $_POST,
            $_FILES,
            $PATH,
            (int)($User['Privilege'] ?? 0),
            $AllowedDocumentExtensions,
            $AllowedImagesExtensions
        ));
        die();

    } else if ($_POST['ReqType'] == 2) { // Like/unlike post
        $FeedPostID = (int)Decrypt($_POST['FeedPostID'], 'Positioned');
        echo json_encode(FeedTogglePostLike($pdo, $FeedPostID, $UID));
        die();

    } else if ($_POST['ReqType'] == 3) { // Add comment
        $PostID = (int)Decrypt($_POST['FeedPostID'], 'Positioned');
        echo json_encode(FeedCreateComment($pdo, $PostID, $UID, $_POST['CommentContent'] ?? '', $_POST['Mentions'] ?? []));
        die();

    } else if ($_POST['ReqType'] == 4) { // Fetch comments
        $PostID = (int)Decrypt($_POST['FeedPostID'], 'Positioned');
        echo json_encode(FeedFetchComments($pdo, $PostID, $UID));
        die();

    } else if ($_POST['ReqType'] == 5) { // Fetch next feed page
        $FeedOffset = isset($_POST['FeedOffset']) ? max(0, (int)$_POST['FeedOffset']) : 0;
        $FeedResult = GetPersonalizedFeed($pdo, $UID, $FeedOffset);
        $Posts = $FeedResult['posts'] ?? [];
        echo json_encode(FeedBuildFeedResponseList($Posts, $UID, $PATH, (int)($User['Privilege'] ?? 0)));
        die();

    } else if ($_POST['ReqType'] == 6) { // Delete post
        $FeedPostID = (int)Decrypt($_POST['FeedPostID'], 'Positioned');
        echo json_encode(FeedDeletePost($pdo, $FeedPostID, $UID));
        die();

    } else if ($_POST['ReqType'] == 7) { // Like/unlike comment
        $CommentID = (int)Decrypt($_POST['CommentID'], 'Positioned');
        echo json_encode(FeedToggleCommentLike($pdo, $CommentID, $UID));
        die();

    } else if ($_POST['ReqType'] == 8) { // Reply to comment
        $CommentID = (int)Decrypt($_POST['CommentID'], 'Positioned');
        $TaggedUser = isset($_POST['ReplyTo']) ? (int)Decrypt($_POST['ReplyTo'], 'Positioned') : null;
        echo json_encode(FeedCreateReply($pdo, $CommentID, $UID, $_POST['Reply'] ?? '', $TaggedUser, $_POST['Mentions'] ?? []));
        die();

    } else if ($_POST['ReqType'] == 9) { // Like/unlike reply
        $ReplyID = (int)Decrypt($_POST['ReplyID'], 'Positioned');
        echo json_encode(FeedToggleReplyLike($pdo, $ReplyID, $UID));
        die();

    } else if ($_POST['ReqType'] == 10) { // Fetch replies
        $CommentID = (int)Decrypt($_POST['CommentID'], 'Positioned');
        echo json_encode(FeedFetchReplies($pdo, $CommentID, $UID));
        die();

    } else if ($_POST['ReqType'] == 11) { // Follow/unfollow user
        $TargetUserID = (int)Decrypt($_POST['UserID'], 'Positioned');
        echo json_encode(FeedToggleFollowUser($pdo, $UID, $TargetUserID));
        die();

    } else if ($_POST['ReqType'] == 12) { // Save/unsave post
        $PostID = (int)Decrypt($_POST['PostID'], 'Positioned');
        echo json_encode(FeedToggleSavedPost($pdo, $UID, $PostID));
        die();

    } else if ($_POST['ReqType'] == 13) { // Block user
        $BlockedUID = (int)Decrypt($_POST['BlockedUID'], 'Positioned');
        echo json_encode(FeedBlockUser($pdo, $UID, $BlockedUID));
        die();

    } else if ($_POST['ReqType'] == 14) { // Fetch post for editing
        $FeedPostID = (int)Decrypt($_POST['FeedPostID'], 'Positioned');
        echo json_encode(FeedFetchPostForEditing($pdo, $FeedPostID, $UID, $PATH));
        die();

    } else if ($_POST['ReqType'] == 15) { // Submit post edit
        echo json_encode(FeedUpdatePost(
            $pdo, $UID,
            $_POST,
            $_FILES,
            $PATH,
            (int)($User['Privilege'] ?? 0),
            $AllowedDocumentExtensions,
            $AllowedImagesExtensions
        ));
        die();

    } else if ($_POST['ReqType'] == 16) { // Delete comment
        $CommentID = (int)Decrypt($_POST['CommentID'], 'Positioned');
        echo json_encode(FeedDeleteComment($pdo, $CommentID, $UID));
        die();

    } else if ($_POST['ReqType'] == 17) { // Delete reply
        $ReplyID = (int)Decrypt($_POST['ReplyID'], 'Positioned');
        echo json_encode(FeedDeleteReply($pdo, $ReplyID, $UID));
        die();

    } else if ($_POST['ReqType'] == 18) { // Record post view
        $PostID = (int)Decrypt($_POST['FeedPostID'] ?? '', 'Positioned');
        echo json_encode(FeedRecordPostView($pdo, $PostID, $UID));
        die();
    }
}
