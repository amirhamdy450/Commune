<?php
 
 //check if path not set !


    if(!isset($PATH)){
        die("Path not set");
    }



    include_once $PATH.'Includes/UserAuth.php';
    include_once $PATH.'Includes/Encryption.php';



    $hasCoverPhoto = false; 

    //get user data (ignore data in session to get fresh data)
    $sql="SELECT id, Fname, Lname, Username, Email , Bio, BirthDay, Gender, ProfilePic, Privilege, CoverPhoto  FROM users WHERE id=?";
    $stmt=$pdo->prepare($sql);
    $stmt->execute([$UID]);
    $User=$stmt->fetch(PDO::FETCH_ASSOC);
    


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



    $ProfileUserID = $User['id'];
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
                        <p class="UserName"><?php echo $User['Fname'].' '.$User['Lname']; ?></p>
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

                </div>

                <div class="ProfileActions">
                    <button class="BrandBtn EditProfileBtn">Edit Profile</button>

                    <?php
                        $encryptedUserID=Encrypt($User['id'],"Positioned",["Timestamp"=>time()]);
                    ?>
                    <a class="BrandBtn PreviewProfileBtn" href="index.php?redirected_from=profile&target=profile&uid=<?php echo urlencode($encryptedUserID); ?>"><img src="Imgs/Icons/profile-preview.svg" alt=""></a>
            

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
            <div class="TabContent FollowList Followers hidden" id="ProfileFollowersTab">
                <?php include 'Includes/Profile/Followers.php'; ?>
            </div>
            <div class="TabContent FollowList Following hidden" id="ProfileFollowingTab">
                <?php include 'Includes/Profile/Following.php'; ?>
            </div>
            <div class="TabContent hidden" id="ProfileAboutTab">
                <?php include 'Includes/Profile/About.php';?>
            </div>
        </div>

    </div>


<div class="Modal EditProfileModal hidden" id="EditProfileModal">
    <div class="ModalContent">
        <div class="ModalCancel"></div>
        <h2>Edit Your Profile</h2>
        
        <form class="EditProfileForm" id="EditProfileForm" novalidate>
            
            <div class="TextField">
                <label for="Edit_Fname">First Name</label>
                <input type="text" name="fname" id="Edit_Fname" placeholder="Your first name" value="<?php echo htmlspecialchars($User['Fname'] ?? ''); ?>">
            </div>
            <div class="TextField">
                <label for="Edit_Lname">Last Name</label>
                <input type="text" name="lname" id="Edit_Lname" placeholder="Your last name" value="<?php echo htmlspecialchars($User['Lname'] ?? ''); ?>">
            </div>
            <div class="TextField">
                <label for="Edit_Username">Username</label>
                <input type="text" name="username" id="Edit_Username" placeholder="Your unique username" value="<?php echo htmlspecialchars($User['Username'] ?? ''); ?>">
            </div>
            <div class="TextField">
                <label for="Edit_Bio">Bio</label>
                <textarea name="bio" id="Edit_Bio" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($User['Bio'] ?? ''); ?></textarea>
            </div>
            <div class="TextField">
                <label for="Edit_Bday">Birthday</label>
                <input type="date" name="bday" id="Edit_Bday" value="<?php echo htmlspecialchars($User['BirthDay'] ?? ''); ?>">
            </div>
            <div class="TextField">
                <label for="Edit_Country">Country</label>
                <select name="country" id="Edit_Country">
                    <option value="">Select a country...</option>
                    <?php
                    // This query is from Includes/Access/Register.php
                    $sql = "SELECT id, Name FROM countries ORDER BY Name ASC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Check if this is the user's current country and set 'selected'
                        $selected = ($row['id'] == $User['CountryID']) ? 'selected' : '';
                        echo '<option value="' . $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['Name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="TextField">
                <label>Gender</label>
                <div class="RadioGroup" id="Edit_Gender">
                    <label><input type="radio" name="gender" value="0" <?php echo ($User['Gender'] == 0) ? 'checked' : ''; ?>> Male</label>
                    <label><input type="radio" name="gender" value="1" <?php echo ($User['Gender'] == 1) ? 'checked' : ''; ?>> Female</label>
                </div>
            </div>

            <div class="FormResponse"></div>
            
            <div class="FormNavigation">
                <div class="BrandBtn Dark ModalCancelBtn">Cancel</div>
                <input type="submit" value="Save Changes" class="BrandBtn">
                <div class="Loader hidden"></div>
            </div>
        </form>
        
    </div>
</div>

    <?php include 'Includes/Modals/CreatePost.php'; ?>
    <?php include 'Includes/Modals/CommentSection.php'; ?>
    <?php include 'Includes/Modals/Confirmation.php'; ?>
</body>

<script src="Scripts/Feed.js" type="module"></script>
<script src="Scripts/Profile.js" type="module"></script>
</html>