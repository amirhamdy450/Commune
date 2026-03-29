<?php

//get profiles he followes

$sql="SELECT F.UserID AS FollowingID, U.Fname, U.Lname, U.ProfilePic, U.Username
FROM followers F
INNER JOIN users U ON F.UserID = U.id
WHERE F.FollowerID = ?";
$stmt=$pdo->prepare($sql);
$stmt->execute([$ProfileUserID]);
$Followings=$stmt->fetchAll(PDO::FETCH_ASSOC);



if($Followings){


    foreach($Followings as $Following){

        $EncFollowingID = urlencode(Encrypt($Following['FollowingID'], "Positioned", ["Timestamp" => time()]));
        $FollowingProfilePic = (!empty($Following['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($Following['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        echo '<a class="UserCard Follower" href="index.php?target=profile&uid='.$EncFollowingID.'">
        <div class="Info">
            <div class="ProfilePictureContainer">
                <img src="'.$FollowingProfilePic.'" alt="Profile Picture">
            </div>
            <div class="ProfileInfo">
                <p class="UserName">'.htmlspecialchars($Following['Fname'].' '.$Following['Lname']).'</p>
                <p class="UserUsername">@'.htmlspecialchars($Following['Username']).'</p>
            </div>
        </div>
        <div class=" BrandBtn Unfollow ">Unfollow</div>
    </a>';
    }
}else{
    //no followings
    echo '<p style="font-style:italic; color:gray; text-align:center; margin-top:20px;">Not following anyone yet</p>';
}




?>