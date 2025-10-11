  
<?php
    if(!isset($PATH)){
        die(' Path is not set in NavBar !');
    }
?>
<div class="NavBar">
        <a class="Logo" href="index.php">
            <img src="Imgs/Logo/logo.svg" alt="Logo">
            OMMUNE
        </a>

        <div class="WideSearch">
            <input type="text" placeholder="Search">
        </div>

        <div class="ActionBar">
                <button class="BrandBtn CreatePostBtn">Create Post</button>
                <a class="BrandBtn Round"><img src="Imgs/Icons/notification.svg" alt=""></a>

              <!--   <a class="BrandBtn Round" id="NavMenuDropBtn"><img src="Imgs/Icons/anonymous.svg" alt=""></a> -->
                <div class="ProfileMenuContainer">
                    <a class="BrandBtn Round" id="NavMenuDropBtn"><img src="Imgs/Icons/anonymous.svg" alt=""></a>
                    <div class="DropdownMenu hidden" id="NavMenuDrop">
                        <a href="index.php?redirect=self" class="DropdownItem">Profile</a>
                        <a href="index.php?redirect=logout" class="DropdownItem">Logout</a>
                    </div>
                </div>


        </div>

        <script src="<?php echo $PATH ?>Scripts/Nav.js"></script>

</div>




