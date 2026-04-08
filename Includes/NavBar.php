  
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
            <div class="SearchSuggestions hidden" id="SearchSuggestions"></div>
        </div>

        <div class="ActionBar">
                <button class="BrandBtn CreatePostBtn">Create Post</button>
               <!--  <a class="BrandBtn Round"><img src="Imgs/Icons/notification.svg" alt=""></a> -->

               <div class="ProfileMenuContainer" id="NotifContainer">
                    <a class="BrandBtn Round" id="NotifBtn">
                        <img src="Imgs/Icons/notification.svg" alt="">
                        <span class="NotifBadge hidden" id="NotifBadge">0</span>
                    </a>
                </div>

                <div class="ProfileMenuContainer">
                        <a class="BrandBtn Round" id="NavMenuDropBtn"><img src="<?php echo $NavProfilePic; ?>" alt="Profile"></a>
                        <div class="DropdownMenu hidden" id="NavMenuDrop">
                            <div class="NavDropHeader">
                                <img src="<?php echo $NavProfilePic; ?>" class="NavDropAvatar" alt="">
                                <div class="NavDropInfo">
                                    <span class="NavDropName"><?php echo htmlspecialchars($User['Fname'] . ' ' . $User['Lname']); ?></span>
                                    <span class="NavDropHandle">@<?php echo htmlspecialchars($User['Username']); ?></span>
                                </div>
                            </div>
                            <div class="DropdownDivider"></div>
                            <a href="index.php?redirect=self" class="DropdownItem">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                Profile
                            </a>
                            <a href="index.php?target=settings" class="DropdownItem">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                Settings
                            </a>
                            <a href="index.php?redirect=saved" class="DropdownItem">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                Saved Posts
                            </a>
                            <div class="DropdownDivider"></div>
                            <div class="DropdownSectionLabel">Pages</div>
                            <div id="NavMyPagesList"></div>
                            <a class="DropdownItem DropdownItemPage" id="NavCreatePageBtn">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Create a Page
                            </a>
                            <div class="DropdownDivider"></div>
                            <a href="index.php?redirect=logout" class="DropdownItem Logout">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Logout
                            </a>
                        </div>
                </div>


        </div>

        <script src="<?php echo $PATH ?>Scripts/Nav.js"></script>

</div>

<!-- Notification panel -->
<div class="DropdownMenu Notifications hidden" id="NotifDrop">
    <div class="NotifHeader">
        Notifications
        <button class="NotifCloseBtn" id="NotifCloseBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="NotifList" id="NotifList"></div>
    <div class="NotifLoader hidden"><div class="Loader"></div></div>
</div>

<!-- Mobile menu drawer -->
<div class="MobileMenuDrawer hidden" id="MobileMenuDrawer">
    <div class="MobileMenuBackdrop" id="MobileMenuBackdrop"></div>
    <div class="MobileMenuSheet">
        <div class="MobileMenuHeader">
            <img src="<?php echo $NavProfilePic; ?>" class="MobileMenuAvatar" alt="">
            <div class="MobileMenuInfo">
                <span class="MobileMenuName"><?php echo htmlspecialchars($User['Fname'] . ' ' . $User['Lname']); ?></span>
                <span class="MobileMenuHandle">@<?php echo htmlspecialchars($User['Username']); ?></span>
            </div>
        </div>
        <div class="MobileMenuDivider"></div>
        <a href="index.php?redirect=self" class="MobileMenuItem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            Profile
        </a>
        <a href="index.php?target=settings" class="MobileMenuItem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Settings
        </a>
        <a href="index.php?redirect=saved" class="MobileMenuItem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            Saved Posts
        </a>
        <div class="MobileMenuDivider"></div>
        <div class="MobileMenuLabel">Pages</div>
        <div id="MobileMyPagesList"></div>
        <button class="MobileMenuItem MobileMenuPageCreate" id="MobileCreatePageBtn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create a Page
        </button>
        <div class="MobileMenuDivider"></div>
        <a href="index.php?redirect=logout" class="MobileMenuItem MobileMenuLogout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</div>

<!-- Mobile search overlay -->
<div class="MobileSearchOverlay hidden" id="MobileSearchOverlay">
    <div class="MobileSearchBar">
        <button class="MobileSearchBack" id="MobileSearchBack">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </button>
        <input type="text" placeholder="Search Commune…" id="MobileNavSearchInput" autocomplete="off">
        <div class="SearchSuggestions hidden" id="MobileSearchSuggestions"></div>
    </div>
</div>

<?php
    $MobTarget   = $_GET['target']   ?? '';
    $MobRedirect = $_GET['redirect'] ?? '';
    $MobIsHome    = (!$MobTarget && !$MobRedirect);
    $MobIsSearch  = ($MobTarget === 'search');
    $MobIsProfile = ($MobRedirect === 'self' || $MobTarget === 'profile');
?>
<!-- Mobile bottom tab bar -->
<nav class="MobileTabBar">
    <a class="MobileTab <?php echo $MobIsHome ? 'Active' : ''; ?>" href="index.php">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    </a>
    <button class="MobileTab <?php echo $MobIsSearch ? 'Active' : ''; ?>" id="MobileSearchBtn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    </button>
    <button class="MobileTab MobileTabCreate CreatePostBtn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>
    <button class="MobileTab" id="MobileNotifBtn">
        <span class="MobileNotifBadge hidden" id="MobileNotifBadge"></span>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    </button>
    <a class="MobileTab <?php echo $MobIsProfile ? 'Active' : ''; ?>" href="index.php?redirect=self">
        <img src="<?php echo $NavProfilePic; ?>" class="MobileTabAvatar <?php echo $MobIsProfile ? 'ActiveAvatar' : ''; ?>" alt="">
    </a>
</nav>




