<?php


$sql="SELECT F.FollowerID,U.Fname,U.Lname, U.ProfilePic ,U.Username,
 CASE WHEN F2.UserID IS NULL THEN 0 ELSE 1 END AS IsFollowing
 FROM followers F
 INNER JOIN users U ON F.FollowerID=U.id
 LEFT JOIN followers F2 ON F.FollowerID=F2.UserID AND F.UserID=F2.FollowerID
 WHERE F.UserID=?";
$stmt=$pdo->prepare($sql);
$stmt->execute([$UID]);
$Followers=$stmt->fetchAll(PDO::FETCH_ASSOC);

if($Followers){


    foreach($Followers as $Follower){
        $FollowStatus="";
        if($Follower['IsFollowing']){
            $FollowStatus="FollowingBtn";
        }

        echo '<a class="UserCard Follower" href='.$PATH.'"index.php?target=profile&uid='.$Follower['FollowerID'].'">
        <div class="Info">
            <div class="ProfilePictureContainer">
                <img src="Imgs/Icons/unknown.png" alt="Profile Picture">
            </div>
            <div class="ProfileInfo">
                <p class="UserName">'.$Follower['Fname'].' '.$Follower['Lname'].'</p>
                <p class="UserUsername">@'.$Follower['Username'].'</p>
            </div>
        </div>
        <div class=" BrandBtn FollowBtn '.$FollowStatus.'">Follow Back</div>
    </a>';
    }
}




?>