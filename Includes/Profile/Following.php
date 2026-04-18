<?php
include_once $PATH . 'Includes/Components/ProfileUserCard.php';
include_once $PATH . 'Includes/Components/EmptyState.php';

$sql = "SELECT F.UserID AS FollowingID, U.Fname, U.Lname, U.ProfilePic, U.Username,
        CASE WHEN F2.UserID IS NOT NULL THEN 1 ELSE 0 END AS IFollowThem
        FROM followers F
        INNER JOIN users U ON F.UserID = U.id
        LEFT JOIN followers F2 ON F2.UserID = F.UserID AND F2.FollowerID = ?
        WHERE F.FollowerID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$UID, $ProfileUserID]);
$Followings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($Followings) {
    foreach ($Followings as $Following) {
        $FollowingCard = BuildProfileUserCardViewModel($Following, $UID, 'FollowingID');
        RenderProfileUserCard($FollowingCard);
    }
} else {
    RenderEmptyState('Imgs/Icons/no-following.svg', 'Not following anyone yet', 'Accounts this person follows will appear here.');
}
?>
