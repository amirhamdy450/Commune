<?php
include_once $PATH . 'Includes/Components/ProfileUserCard.php';
include_once $PATH . 'Includes/Components/EmptyState.php';

$sql = "SELECT F.FollowerID, U.Fname, U.Lname, U.ProfilePic, U.Username,
        CASE WHEN F2.UserID IS NOT NULL THEN 1 ELSE 0 END AS IFollowThem
        FROM followers F
        INNER JOIN users U ON F.FollowerID = U.id
        LEFT JOIN followers F2 ON F2.UserID = F.FollowerID AND F2.FollowerID = ?
        WHERE F.UserID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$UID, $ProfileUserID]);
$Followers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($Followers) {
    foreach ($Followers as $Follower) {
        $FollowerCard = BuildProfileUserCardViewModel($Follower, $UID, 'FollowerID');
        RenderProfileUserCard($FollowerCard);
    }
} else {
    RenderEmptyState('Imgs/Icons/no-followers.svg', 'No followers yet', 'When someone follows this account, they\'ll show up here.');
}
?>
