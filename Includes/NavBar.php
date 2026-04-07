  
<?php
    if(!isset($PATH)){
        die(' Path is not set in NavBar !');
    }


    if (isset($User['ProfilePic']) && !empty($User['ProfilePic'])) {
        $NavProfilePic = $PATH . 'MediaFolders/profile_pictures/' . htmlspecialchars($User['ProfilePic']);
    } else {
        $NavProfilePic = 'Imgs/Icons/unknown.png'; // Fallback
    }
?>
<div class="NavBar">
        <a class="Logo" href="index.php">
            <img src="Imgs/Logo/logo.svg" alt="Logo">
            OMMUNE
        </a>

        <div class="WideSearch">
            <input type="text" placeholder="Search" id="NavSearchInput" autocomplete="off">
            
            <div class="SearchSuggestions hidden" id="SearchSuggestions">
                </div>
        </div>

        <div class="ActionBar">
                <button class="BrandBtn CreatePostBtn">Create Post</button>
               <!--  <a class="BrandBtn Round"><img src="Imgs/Icons/notification.svg" alt=""></a> -->

               <div class="ProfileMenuContainer" id="NotifContainer">
                    <a class="BrandBtn Round" id="NotifBtn">
                        <img src="Imgs/Icons/notification.svg" alt="">
                        <span class="NotifBadge hidden" id="NotifBadge">0</span>
                    </a>
                    
                    <div class="DropdownMenu Notifications hidden" id="NotifDrop">
                        <div class="NotifHeader">Notifications</div>
                        <div class="NotifList" id="NotifList">
                            </div>
                        <div class="NotifLoader hidden"><div class="Loader"></div></div>
                    </div>
                </div>

                <div class="ProfileMenuContainer">
                        <a class="BrandBtn Round" id="NavMenuDropBtn"><img src="<?php echo $NavProfilePic; ?>" alt="Profile"></a>                    <div class="DropdownMenu hidden" id="NavMenuDrop">
                        <a href="index.php?redirect=self" class="DropdownItem">Profile</a>
                        <a href="index.php?target=settings" class="DropdownItem">Settings</a>
                        <a href="index.php?redirect=saved" class="DropdownItem">Saved Posts</a>
                        <div class="DropdownDivider"></div>
                        <div class="DropdownSectionLabel">Pages</div>
                        <div id="NavMyPagesList"></div>
                        <a class="DropdownItem DropdownItemPage" id="NavCreatePageBtn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                            Create a Page
                        </a>
                        <div class="DropdownDivider"></div>
                        <a href="index.php?redirect=logout" class="DropdownItem">Logout</a>
                    </div>
                </div>


        </div>

        <script src="<?php echo $PATH ?>Scripts/Nav.js"></script>

</div>




