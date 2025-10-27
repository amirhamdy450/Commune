<?php

//get profiles he followes

$sql="SELECT F.UserID AS FollowingID, U.Fname, U.Lname, U.ProfilePic, U.Username
FROM followers F
INNER JOIN users U ON F.UserID = U.id
WHERE F.FollowerID = ?";
$stmt=$pdo->prepare($sql);
$stmt->execute([$UID]);
$Followings=$stmt->fetchAll(PDO::FETCH_ASSOC);



if($Followings){


    foreach($Followings as $Following){

        echo '<a class="UserCard Follower" href='.$PATH.'"index.php?target=profile&uid='.$Following['FollowingID'].'">
        <div class="Info">
            <div class="ProfilePictureContainer">
                <img src="Imgs/Icons/unknown.png" alt="Profile Picture">
            </div>
            <div class="ProfileInfo">
                <p class="UserName">'.$Following['Fname'].' '.$Following['Lname'].'</p>
                <p class="UserUsername">@'.$Following['Username'].'</p>
            </div>
        </div>
        <div class=" BrandBtn Unfollow ">Unfollow</div>
    </a>';
    }
}




?>