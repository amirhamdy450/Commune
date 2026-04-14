<?php
// settings.php
$PATH = ''; // Root path
include_once $PATH . 'Includes/UserAuth.php';
include_once $PATH . 'Includes/Encryption.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $CsrfToken; ?>">
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Settings.css">
    <title>Settings / Commune</title>
</head>
<body>

    <?php include 'Includes/NavBar.php'; ?>

    <div class="SettingsContainer">

        <div class="SettingsSidebar">
            <div class="SettingsNavHeader">Settings</div>

            <div class="SettingsNavItem Active" data-tab="AccountTab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Account
            </div>
            <div class="SettingsNavItem" data-tab="SecurityTab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Security
            </div>
            <div class="SettingsNavItem" data-tab="PrivacyTab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Privacy
            </div>
            <div class="SettingsNavItem" data-tab="ActivityTab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Activity
            </div>
            <div class="SettingsNavItem" data-tab="VerificationTab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                Verification
            </div>
        </div>

        <div class="SettingsContent">

            <div class="SettingsSection Active" id="AccountTab">
                <h2 class="SectionTitle">Account Information</h2>
                <form class="SettingsForm" id="UpdateAccountForm">
                    <div class="TextField">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($User['Email']); ?>" class="TextField">
                    </div>

                    <input type="submit" class="BrandBtn" value="Save Changes">
                    <div class="FormResponse"></div>
                </form>

                <div class="DangerZone">
                    <div class="DangerZoneBadge">Danger Zone</div>
                    <h3>Delete Account</h3>
                    <p class="SubText">This will permanently delete your account, posts, saved items, and the rest of your profile data. This action cannot be undone.</p>
                    <div class="DangerZoneActions">
                        <button class="BrandBtn Delete" id="DeleteAccountBtn">Delete My Account</button>
                    </div>
                </div>
            </div>

            <div class="SettingsSection" id="SecurityTab">
                <h2 class="SectionTitle">Security</h2>

                <form class="SettingsForm" id="ChangePasswordForm">
                    <div class="TextField">
                        <label>Current Password</label>
                        <input type="password" name="current_pass" class="TextField">
                    </div>
                    <div class="TextField">
                        <label>New Password</label>
                        <input type="password" name="new_pass" class="TextField">
                    </div>
                    <div class="TextField">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_pass" class="TextField">
                    </div>
                    <input type="submit" class="BrandBtn" value="Update Password">
                    <div class="FormResponse"></div>
                </form>

                <br><hr><br>

                <h2 class="SectionTitle">Active Sessions</h2>
                <p class="SubText" style="margin-bottom:15px;">These represent the devices that have logged into your account.</p>
                <div id="SessionsList">
                    <div class="Loader"></div>
                </div>
            </div>

            <div class="SettingsSection" id="PrivacyTab">
                <h2 class="SectionTitle">Blocked Users</h2>
                <p class="SubText" style="margin-bottom:15px;">People you have blocked cannot see your posts or interact with you.</p>

                <div id="BlockedUsersList">
                    <div class="Loader"></div>
                </div>
            </div>

            <div class="SettingsSection" id="ActivityTab">
                <h2 class="SectionTitle">Activity</h2>

                <div class="ActivityTabBar">
                    <button class="ActivityTabBtn Active" data-activity="LikedTab">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        Liked Posts
                    </button>
                    <button class="ActivityTabBtn" data-activity="CommentedTab">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Commented
                    </button>
                    <button class="ActivityTabBtn" data-activity="SavedTab">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        Saved Posts
                    </button>
                </div>

                <div class="ActivityPanel Active" id="LikedTab">
                    <div class="Loader"></div>
                </div>
                <div class="ActivityPanel" id="CommentedTab">
                    <div class="Loader"></div>
                </div>
                <div class="ActivityPanel" id="SavedTab">
                    <div class="Loader"></div>
                </div>
            </div>

            <div class="SettingsSection" id="VerificationTab">
                <h2 class="SectionTitle">Verification</h2>

                <?php if ((int)$User['IsBlueTick'] === 1): ?>
                    <div class="VerifSettingsState">
                        <span class="BlueTick Large" title="Verified"></span>
                        <div>
                            <p class="VerifSettingsTitle">Your account is verified</p>
                            <p class="SubText">The blue badge is shown on your profile and posts.</p>
                        </div>
                    </div>

                <?php else:
                    $stmt = $pdo->prepare("SELECT Status, SubmittedAt FROM verification_requests WHERE UID = ? ORDER BY SubmittedAt DESC LIMIT 1");
                    $stmt->execute([$UID]);
                    $LastVerifReq = $stmt->fetch(PDO::FETCH_ASSOC);
                    $HasPending = $LastVerifReq && (int)$LastVerifReq['Status'] === 0;
                    $WasRejected = $LastVerifReq && (int)$LastVerifReq['Status'] === 2;
                ?>
                    <?php if ($HasPending): ?>
                        <div class="VerifSettingsState Pending">
                            <div class="VerifPendingIcon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <p class="VerifSettingsTitle">Request under review</p>
                                <p class="SubText">Submitted on <?php echo date('M j, Y', strtotime($LastVerifReq['SubmittedAt'])); ?>. We'll notify you when a decision is made.</p>
                            </div>
                        </div>

                    <?php else: ?>
                        <p class="SubText" style="margin-bottom:20px;">
                            Get a blue badge that shows the community your account is authentic.
                            <?php if ($WasRejected): ?>
                                <span class="VerifRejectedInline">Your previous request was not approved.</span>
                            <?php endif; ?>
                        </p>
                        <a href="index.php?target=get-verified" class="BrandBtn VerifApplyBtn">Apply for Verification</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php include 'Includes/Modals/Confirmation.php'; ?>
    <script src="Scripts/modal.js"></script>
    <script type="module" src="Scripts/Settings.js"></script>
</body>
</html>
