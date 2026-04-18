<?php

if (!function_exists('BuildProfileUserCardViewModel')) {
    function BuildProfileUserCardViewModel(array $UserRow, int $ViewerUID, string $IdKey): array
    {
        $TargetUserID = (int)$UserRow[$IdKey];
        $EncryptedUserID = urlencode(Encrypt($TargetUserID, 'Positioned', ['Timestamp' => time()]));
        $ProfilePic = !empty($UserRow['ProfilePic'])
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($UserRow['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        return [
            'TargetUserID' => $TargetUserID,
            'EncryptedUserID' => $EncryptedUserID,
            'ProfilePic' => $ProfilePic,
            'IsViewer' => ($TargetUserID === $ViewerUID),
            'IFollowThem' => !empty($UserRow['IFollowThem']),
            'Name' => htmlspecialchars($UserRow['Fname'] . ' ' . $UserRow['Lname']),
            'Username' => htmlspecialchars($UserRow['Username']),
        ];
    }
}

if (!function_exists('RenderProfileUserCard')) {
    function RenderProfileUserCard(array $ViewModel): void
    {
        if ($ViewModel['IsViewer']) {
            $ActionBtn = '<span class="YouBadge">You</span>';
        } elseif ($ViewModel['IFollowThem']) {
            $ActionBtn = '<div class="BrandBtn FollowBtn Followed" uid="' . $ViewModel['EncryptedUserID'] . '">Following</div>';
        } else {
            $ActionBtn = '<div class="BrandBtn FollowBtn" uid="' . $ViewModel['EncryptedUserID'] . '">Follow</div>';
        }

        echo '<a class="UserCard Follower" href="index.php?target=profile&uid=' . $ViewModel['EncryptedUserID'] . '">
            <div class="Info">
                <div class="ProfilePictureContainer">
                    <img src="' . $ViewModel['ProfilePic'] . '" alt="Profile Picture">
                </div>
                <div class="ProfileInfo">
                    <p class="UserName">' . $ViewModel['Name'] . '</p>
                    <p class="UserUsername">@' . $ViewModel['Username'] . '</p>
                </div>
            </div>
            ' . $ActionBtn . '
        </a>';
    }
}
