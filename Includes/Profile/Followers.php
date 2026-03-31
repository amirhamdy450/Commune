<?php

// Fetch followers of ProfileUser, with follow status from the logged-in user's ($UID) perspective
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
        $IsMe = ((int)$Follower['FollowerID'] === (int)$UID);
        $EncFollowerID = urlencode(Encrypt($Follower['FollowerID'], "Positioned", ["Timestamp" => time()]));
        $FollowerProfilePic = (!empty($Follower['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($Follower['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        if ($IsMe) {
            $ActionBtn = '<span class="YouBadge">You</span>';
        } elseif ($Follower['IFollowThem']) {
            $ActionBtn = '<div class="BrandBtn FollowBtn Followed" uid="' . $EncFollowerID . '">Following</div>';
        } else {
            $ActionBtn = '<div class="BrandBtn FollowBtn" uid="' . $EncFollowerID . '">Follow</div>';
        }

        echo '<a class="UserCard Follower" href="index.php?target=profile&uid=' . $EncFollowerID . '">
            <div class="Info">
                <div class="ProfilePictureContainer">
                    <img src="' . $FollowerProfilePic . '" alt="Profile Picture">
                </div>
                <div class="ProfileInfo">
                    <p class="UserName">' . htmlspecialchars($Follower['Fname'] . ' ' . $Follower['Lname']) . '</p>
                    <p class="UserUsername">@' . htmlspecialchars($Follower['Username']) . '</p>
                </div>
            </div>
            ' . $ActionBtn . '
        </a>';
    }
} else {
    echo "
        <div class='ProfileEmptyState'>
            <img src='Imgs/Icons/no-followers.svg' alt=''>
            <h3>No followers yet</h3>
            <p>When someone follows this account, they'll show up here.</p>
        </div>
    ";
}
?>
