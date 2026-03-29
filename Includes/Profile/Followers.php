<?php


$sql="SELECT F.FollowerID,U.Fname,U.Lname, U.ProfilePic ,U.Username,
 CASE WHEN F2.UserID IS NULL THEN 0 ELSE 1 END AS IsFollowing
 FROM followers F
 INNER JOIN users U ON F.FollowerID=U.id
 LEFT JOIN followers F2 ON F.FollowerID=F2.UserID AND F.UserID=F2.FollowerID
 WHERE F.UserID=?";
$stmt=$pdo->prepare($sql);
$stmt->execute([$ProfileUserID]);
$Followers=$stmt->fetchAll(PDO::FETCH_ASSOC);

if($Followers){


    foreach($Followers as $Follower){
        $FollowStatus="";
        if($Follower['IsFollowing']){
            $FollowStatus="FollowingBtn";
        }

        $EncFollowerID = urlencode(Encrypt($Follower['FollowerID'], "Positioned", ["Timestamp" => time()]));
        $FollowerProfilePic = (!empty($Follower['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($Follower['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        echo '<a class="UserCard Follower" href="index.php?target=profile&uid='.$EncFollowerID.'">
        <div class="Info">
            <div class="ProfilePictureContainer">
                <img src="'.$FollowerProfilePic.'" alt="Profile Picture">
            </div>
            <div class="ProfileInfo">
                <p class="UserName">'.htmlspecialchars($Follower['Fname'].' '.$Follower['Lname']).'</p>
                <p class="UserUsername">@'.htmlspecialchars($Follower['Username']).'</p>
            </div>
        </div>
        <div class=" BrandBtn FollowBtn '.$FollowStatus.'">Follow Back</div>
    </a>';
    }
}else{
    //no followers
    echo '<p style="font-style:italic; color:gray; text-align:center; margin-top:20px;">No followers yet</p>';
}




?>