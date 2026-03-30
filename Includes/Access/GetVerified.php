<?php
if (!isset($PATH)) { $PATH = ''; }
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
    <link rel="stylesheet" href="Styles/GetVerified.css">
    <title>Get Verified / Commune</title>
</head>
<body class="GetVerifiedPage">

    <?php include $PATH . 'Includes/NavBar.php'; ?>

    <div class="VerifiedPageContainer">

        <div class="VerifiedPageCard" id="VerificationCard">

            <!-- STATE: Already verified -->
            <?php if ((int)$User['IsBlueTick'] === 1): ?>
            <div class="VerifState VerifStateActive">
                <div class="VerifBadgeLarge">✓</div>
                <h2>You're Verified</h2>
                <p>Your account has been verified. The blue tick badge is displayed on your profile and posts.</p>
            </div>

            <?php else: ?>
            <!-- STATE: Not verified — check for pending/rejected request -->
            <?php
                $HasPending = false;
                $WasRejected = false;
                $stmt = $pdo->prepare("SELECT Status, SubmittedAt FROM verification_requests WHERE UID = ? ORDER BY SubmittedAt DESC LIMIT 1");
                $stmt->execute([$UID]);
                $LastRequest = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($LastRequest) {
                    if ((int)$LastRequest['Status'] === 0) $HasPending = true;
                    if ((int)$LastRequest['Status'] === 2) $WasRejected = true;
                }
            ?>

            <?php if ($HasPending): ?>
            <!-- STATE: Pending -->
            <div class="VerifState VerifStatePending">
                <div class="VerifBadgeLarge Pending">⏳</div>
                <h2>Request Under Review</h2>
                <p>Your verification request is being reviewed. This typically takes 1–3 business days. We'll notify you when a decision is made.</p>
                <span class="VerifSubmittedDate">Submitted on <?php echo date('M j, Y', strtotime($LastRequest['SubmittedAt'])); ?></span>
            </div>

            <?php else: ?>
            <!-- STATE: Form (new or re-apply after rejection) -->
            <div class="VerifPageHeader">
                <div class="VerifBadgeLarge">✓</div>
                <h2>Apply for Verification</h2>
                <p class="VerifSubtitle">Verified accounts get a blue badge that shows the community your account is authentic and notable.</p>
            </div>

            <?php if ($WasRejected): ?>
            <div class="VerifRejectedNotice">
                Your previous request was not approved. You may apply again below.
            </div>
            <?php endif; ?>

            <div class="VerifCriteria">
                <h3>Who can get verified?</h3>
                <ul>
                    <li>Public figures, celebrities, and influencers</li>
                    <li>Brands, companies, and organizations</li>
                    <li>Journalists and media personalities</li>
                    <li>Accounts with significant community presence</li>
                </ul>
            </div>

            <form class="VerifForm" id="VerificationRequestForm">
                <div class="VerifFormSection">
                    <label for="VerifReason">Why should your account be verified?</label>
                    <textarea
                        id="VerifReason"
                        name="reason"
                        placeholder="Describe who you are, your public presence, and why verification would benefit the community..."
                        rows="5"
                        maxlength="1000"
                    ></textarea>
                    <span class="VerifCharCount"><span id="VerifCharCounter">0</span> / 1000</span>
                </div>

                <div class="VerifFeeBox">
                    <div class="VerifFeeInfo">
                        <div class="VerifFeeAmount">$9.99 <span>one-time fee</span></div>
                        <p>A non-refundable processing fee is required to submit a verification request. This helps us maintain a fair and spam-free review process.</p>
                    </div>
                    <label class="VerifFeeCheck">
                        <input type="checkbox" id="FeeConfirmed" name="fee_confirmed">
                        <span>I understand and agree to pay the $9.99 verification fee</span>
                    </label>
                </div>

                <div class="FormResponse" id="VerifFormResponse"></div>

                <button type="submit" class="BrandBtn VerifSubmitBtn" id="VerifSubmitBtn" disabled>
                    Submit Request
                </button>
            </form>
            <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>

    <script type="module" src="Scripts/GetVerified.js"></script>
</body>
</html>
