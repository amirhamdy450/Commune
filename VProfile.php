<?php
 /* this page is for previwing the profile (without editing) */

    $PATH='';



    include $PATH.'Includes/UserAuth.php';
    include_once $PATH.'Includes/Encryption.php';



    $hasCoverPhoto = false; 


    $IsSelf=false;


    //decrypt the uid
    $ProfileUserID=Decrypt($ProfileUserID,"Positioned");
/*     print_r($ProfileUserID);
    die(); */


    //check if uid is the same of the user logged in 
    if($ProfileUserID == $UID){
        //ALLOW ONLY WHEN REDIRECTed IS SET to profile
        if(!isset($_GET['redirected_from']) || $_GET['redirected_from'] != "profile"){
            //redirect to his profile with edit permissions
            include $PATH.'Includes/Profile/Profile.php';
            die();
        }

        $IsSelf = true;

    }else{

        //see if the current user follows the profile user
        $sql = "SELECT COUNT(*) FROM followers WHERE UserID=? AND FollowerID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ProfileUserID, $UID]);
        $IsFollowing = $stmt->fetchColumn() > 0;



    }




    //fetch user data
    $sql = "SELECT * FROM users WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ProfileUserID]);
    $ProfileUser = $stmt->fetch(PDO::FETCH_ASSOC);


    $params=[
        //convert Date to a Unix timestamp
        "Timestamp"=> time()
    ];
    $EncUserID= Encrypt($ProfileUserID,"Positioned",$params);





    if(!$ProfileUser){
        header("Location: 404.php");
        exit();
    }



    if($ProfileUser['CoverPhoto']){
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
        $stmt->execute([$ProfileUserID]);
        $Stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $PostsTotal=$Stats['TotalPosts'];

    //fetch following and followers count from users table
    $sql = "SELECT
            U.Followers AS FollowersCount,
            U.Following AS FollowingCount
            FROM users AS U
            WHERE U.id=?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ProfileUserID]);
    $Stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $FollowersCount=$Stats['FollowersCount'];
    $FollowingCount=$Stats['FollowingCount'];



?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $CsrfToken; ?>">
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Feed.css">
    <link rel="stylesheet" href="Styles/profile.css">
    <title>My Profile</title>
</head>

<body id="VProfile">

    <?php include 'Includes/NavBar.php'; ?>

    <div class="ProfileContainer">
        <div class="ProfileHeader">
            <div class="CoverPhotoContainer">
                <div class="CoverPhoto <?php echo !$hasCoverPhoto ? 'Default' : ''; ?>">
                    <?php if ($hasCoverPhoto): ?>
                        <img src="<?php echo $PATH . 'MediaFolders/cover_pictures/' . htmlspecialchars($ProfileUser['CoverPhoto']); ?>" alt="Cover Photo">
                    <?php endif; ?>

                </div>
            </div>
            <div class="ProfileBottomHeader">
                <div class="Initial">
                    <div class="ProfilePictureContainer">
                        <?php
                        if ($ProfileUser['ProfilePic']) {
                            echo '<img src="'.$PATH . 'MediaFolders/profile_pictures/' . htmlspecialchars($ProfileUser['ProfilePic']) . '" alt="Profile Picture">';
                        } else {
                            echo '<img src="Imgs/Icons/unknown.png" alt="Profile Picture">';
                        }
                        ?>
<!--                         <div class="Overlay">
                            <span>Edit </span>
                        </div>
 -->
                    </div>


                    <div class="ProfileInfo">
                        <p class="UserName"><?php echo $ProfileUser['Fname'].' '.$ProfileUser['Lname']; ?></p>
                        <p class="UserUsername">@<?php echo $ProfileUser['Username']; ?></p>
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

                    <div class="ProfileActions">

                        <?php if($IsSelf): ?>
                            <a class="BrandBtn EditProfileBtn" href="index.php?redirect=self">Exit Preview</a>
                        <?php  else: ?>
                            <?php if($IsFollowing): ?>
                                <button class="BrandBtn FollowBtn Followed" uid="<?php echo $EncUserID; ?>">Following</button>
                            <?php else: ?>
                                <button class="BrandBtn FollowBtn" uid="<?php echo $EncUserID; ?>">Follow</button>
                            <?php endif; ?>
                         <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>

                        <?php endif; ?>

                    </div>

                </div>
            

            </div>

            <div class="TabsNav ProfileNav">
                <a href="#" class="NavItem" tab-content="ProfileBioTab">Bio</a>
                <a href="#" class="NavItem Active" tab-content="ProfilePostsTab" >Posts</a>
                <a href="#" class="NavItem" tab-content="ProfileFollowersTab">Followers</a>
                <a href="#" class="NavItem" tab-content="ProfileFollowingTab">Following</a>
                <a href="#" class="NavItem" tab-content="ProfileAboutTab">About</a>
            </div>

        </div>

        <?php



            




        
        
        ?>


        <div class="ProfileContent">

            
            <div class="TabContent Posts" id="ProfilePostsTab">
                <?php include 'Includes/Profile/Posts.php'; ?>
            </div>
            <div class="TabContent Bio hidden" id="ProfileBioTab">
                <?php include 'Includes/Profile/Bio.php';?>
            </div>
            <div class="TabContent FollowList Followers hidden" id="ProfileFollowersTab">
                <?php include 'Includes/Profile/Followers.php'; ?>
            </div>
            <div class="TabContent FollowList Following hidden" id="ProfileFollowingTab">
                <?php include 'Includes/Profile/Following.php'; ?>
            </div>
            <div class="TabContent About hidden" id="ProfileAboutTab">
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