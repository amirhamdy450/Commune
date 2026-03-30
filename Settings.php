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
                <img src="Imgs/Icons/anonymous.svg" alt=""> Account
            </div>
            <div class="SettingsNavItem" data-tab="SecurityTab">
                <img src="Imgs/Icons/lock.svg" alt=""> Security
            </div>
            <div class="SettingsNavItem" data-tab="PrivacyTab">
                <img src="Imgs/Icons/block.svg" alt=""> Privacy
            </div>
            <div class="SettingsNavItem" data-tab="VerificationTab">
                <img src="Imgs/Icons/Checkmark.svg" alt=""> Verification
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
                    <h3>Delete Account</h3>
                    <p class="SubText">This will permanently delete your account and all your content.</p>
                    <button class="BrandBtn Delete" id="DeleteAccountBtn">Delete My Account</button>
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
                            <div class="VerifPendingIcon">⏳</div>
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
    <script type="module" src="Scripts/Settings.js"></script> </body>
</html>