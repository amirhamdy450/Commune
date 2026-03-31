<?php

// Fetch accounts ProfileUser follows, with follow status from the logged-in user's ($UID) perspective
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
        $IsMe = ((int)$Following['FollowingID'] === (int)$UID);
        $EncFollowingID = urlencode(Encrypt($Following['FollowingID'], "Positioned", ["Timestamp" => time()]));
        $FollowingProfilePic = (!empty($Following['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($Following['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        if ($IsMe) {
            $ActionBtn = '<span class="YouBadge">You</span>';
        } elseif ($Following['IFollowThem']) {
            $ActionBtn = '<div class="BrandBtn FollowBtn Followed" uid="' . $EncFollowingID . '">Following</div>';
        } else {
            $ActionBtn = '<div class="BrandBtn FollowBtn" uid="' . $EncFollowingID . '">Follow</div>';
        }

        echo '<a class="UserCard Follower" href="index.php?target=profile&uid=' . $EncFollowingID . '">
            <div class="Info">
                <div class="ProfilePictureContainer">
                    <img src="' . $FollowingProfilePic . '" alt="Profile Picture">
                </div>
                <div class="ProfileInfo">
                    <p class="UserName">' . htmlspecialchars($Following['Fname'] . ' ' . $Following['Lname']) . '</p>
                    <p class="UserUsername">@' . htmlspecialchars($Following['Username']) . '</p>
                </div>
            </div>
            ' . $ActionBtn . '
        </a>';
    }
} else {
    echo "
        <div class='ProfileEmptyState'>
            <img src='Imgs/Icons/no-following.svg' alt=''>
            <h3>Not following anyone yet</h3>
            <p>Accounts this person follows will appear here.</p>
        </div>
    ";
}
?>
