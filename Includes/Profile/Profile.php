<?php
 
 //check if path not set !
    if(!isset($PATH)){
        die("Path not set");
    }



    include $PATH.'Includes/UserValidation.php';


    $hasCoverPhoto = false; 

    if($User['CoverPhoto']){
        $hasCoverPhoto = true;
    }




    //fetching stats
        $sql = "SELECT 
                COUNT(*) AS TotalPosts
                FROM posts 
                INNER JOIN users ON posts.UID=users.id 
                LEFT JOIN likes ON posts.id=likes.PostID AND likes.UID=users.id
                WHERE posts.Status=1 AND posts.UID=?
                ORDER BY posts.Date ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$User['id']]);
        $Stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $PostsTotal=$Stats['TotalPosts'];

    //fetch following and followers count from users table
    $sql = "SELECT
            U.Followers AS FollowersCount,
            U.Following AS FollowingCount
            FROM users AS U
            WHERE U.id=?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$User['id']]);
    $Stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $FollowersCount=$Stats['FollowersCount'];
    $FollowingCount=$Stats['FollowingCount'];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Feed.css">
    <link rel="stylesheet" href="Styles/profile.css">
    <title>My Profile</title>
</head>

<body>

    <?php include 'Includes/NavBar.php'; ?>

    <div class="ProfileContainer">
        <div class="ProfileHeader">
            <div class="CoverPhotoContainer">
                <div class="CoverPhoto <?php echo !$hasCoverPhoto ? 'Default' : ''; ?>">
                    <?php if ($hasCoverPhoto): ?>
                        <img src="https://via.placeholder.com/900x300/e0e0e0/ffffff?text=Cover+Photo" alt="Cover Photo">
                    <?php endif; ?>
                    <div class="Overlay">
                        <span>Edit Cover Photo</span>
                    </div>
                </div>
            </div>
            <div class="ProfileBottomHeader">
                <div class="Initial">
                    <div class="ProfilePictureContainer">
                        <img src="Imgs/Icons/unknown.png" alt="Profile Picture">
                        <div class="Overlay">
                            <span>Edit </span>
                        </div>

                    </div>


                    <div class="ProfileInfo">
                        <p class="UserName"><?php echo $User['name']; ?></p>
                        <p class="UserUsername">@<?php echo $User['Username']; ?></p>
                    </div>
                </div>

                <div class="ProfileInfoStats">

                    <div class="ProfileStats">
                        <div class="Stat">
                            <p class="StatNumber"><?php echo $PostsTotal; ?></p>
                            <p class="StatTitle">Posts</p>
                        </div>
                        <div class="Stat">
                            <p class="StatNumber"><?php echo $FollowersCount; ?></p>
                            <p class="StatTitle">Followers</p>
                        </div>
                        <div class="Stat">
                            <p class="StatNumber"><?php echo $FollowingCount; ?></p>
                            <p class="StatTitle">Following</p>
                        </div>
                    </div>
                        <button class="BrandBtn EditProfileButton">Edit Profile</button>

                </div>
            

            </div>

                        <div class="TabsNav ProfileNav">
                <a href="#" class="NavItem Active" tab-content="ProfilePostsTab" >Posts</a>
                <a href="#" class="NavItem" tab-content="ProfileFollowersTab">Followers</a>
                <a href="#" class="NavItem" tab-content="ProfileFollowingTab">Following</a>
                <a href="#" class="NavItem" tab-content="ProfileAboutTab">About</a>
            </div>

        </div>

        <?php

            //get filter
/*             if(isset($_GET['filter'])){
                $Filter = strtolower($_GET['filter']);
            }else{
                $Filter ="posts";
            }


            if($Filter == "posts"){
                include 'Includes/Profile/Posts.php';
            }else if($Filter == "followers"){
                include 'Includes/Profile/Followers.php';
            }else if($Filter == "following"){
                include 'Includes/Profile/Following.php';
            }else if($Filter == "about"){
                include 'Includes/Profile/About.php';
            } */


            




        
        
        ?>


        <div class="ProfileContent">
<!--             <div class="TabsNav ProfileNav">
                <a href="#" class="NavItem Active" tab-content="ProfilePostsTab" >Posts</a>
                <a href="#" class="NavItem" tab-content="ProfileFollowersTab">Followers</a>
                <a href="#" class="NavItem" tab-content="ProfileFollowingTab">Following</a>
                <a href="#" class="NavItem" tab-content="ProfileAboutTab">About</a>
            </div>
 -->
            
            <div class="TabContent Posts" id="ProfilePostsTab">
                <?php include 'Includes/Profile/Posts.php'; ?>
            </div>
            <div class="TabContent hidden" id="ProfileFollowersTab">
                <?php include 'Includes/Profile/Followers.php'; ?>
            </div>
            <div class="TabContent hidden" id="ProfileFollowingTab">
                <?php include 'Includes/Profile/Following.php'; ?>
            </div>
            <div class="TabContent hidden" id="ProfileAboutTab">
                <?php include 'Includes/Profile/About.php';?>
            </div>
        </div>

    </div>



    <?php include 'Includes/Modals/CreatePost.php'; ?>
    <?php include 'Includes/Modals/CommentSection.php'; ?>
    <?php include 'Includes/Modals/Confirmation.php'; ?>
</body>

<script src="Scripts/Feed.js" type="module"></script>
<script src="Scripts/Profile.js" type="module"></script>
</html>