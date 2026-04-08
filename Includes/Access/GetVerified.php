<?php
if (!isset($PATH)) { $PATH = ''; }
include_once $PATH . 'Includes/UserAuth.php';
include_once $PATH . 'Includes/Encryption.php';

// Resolve optional page context
$VerifPage    = null;
$VerifPageEncID = null;
$VerifPageHandle = trim($_GET['page'] ?? '');
if ($VerifPageHandle !== '') {
    $PageStmt = $pdo->prepare("SELECT p.*, pm.Role FROM pages p INNER JOIN page_members pm ON p.id = pm.PageID WHERE p.Handle = ? AND pm.UID = ?");
    $PageStmt->execute([$VerifPageHandle, $UID]);
    $VerifPage = $PageStmt->fetch(PDO::FETCH_ASSOC);
    if ($VerifPage && in_array($VerifPage['Role'], ['owner', 'admin'])) {
        $VerifPageEncID = Encrypt($VerifPage['id'], "Positioned", ["Timestamp" => time()]);
    } else {
        $VerifPage = null; // Not a member with permission — fall back to user flow
    }
}

// Check state
$IsAlreadyVerified = $VerifPage ? (int)$VerifPage['IsVerified'] === 1 : (int)$User['IsBlueTick'] === 1;
$HasPending = false;
$WasRejected = false;
if (!$IsAlreadyVerified) {
    if ($VerifPage) {
        $stmt = $pdo->prepare("SELECT Status, SubmittedAt FROM verification_requests WHERE PageID = ? AND Status != 1 ORDER BY SubmittedAt DESC LIMIT 1");
        $stmt->execute([$VerifPage['id']]);
    } else {
        $stmt = $pdo->prepare("SELECT Status, SubmittedAt FROM verification_requests WHERE UID = ? AND PageID IS NULL ORDER BY SubmittedAt DESC LIMIT 1");
        $stmt->execute([$UID]);
    }
    $LastRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($LastRequest) {
        if ((int)$LastRequest['Status'] === 0) $HasPending = true;
        if ((int)$LastRequest['Status'] === 2) $WasRejected = true;
    }
}

$SubjectLabel = $VerifPage ? htmlspecialchars($VerifPage['Name']) : 'your account';
$SubjectType  = $VerifPage ? 'page' : 'account';
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

            <?php if ($IsAlreadyVerified): ?>
            <div class="VerifState VerifStateActive">
                <div class="VerifBadgeLarge">✓</div>
                <h2><?php echo $VerifPage ? htmlspecialchars($VerifPage['Name']) . ' is Verified' : "You're Verified"; ?></h2>
                <p>The blue tick badge is displayed on <?php echo $VerifPage ? 'this page' : 'your profile'; ?> and its posts.</p>
            </div>

            <?php elseif ($HasPending): ?>
            <div class="VerifState VerifStatePending">
                <div class="VerifBadgeLarge Pending">⏳</div>
                <h2>Request Under Review</h2>
                <p>The verification request for <?php echo $SubjectLabel; ?> is being reviewed. This typically takes 1–3 business days.</p>
                <span class="VerifSubmittedDate">Submitted on <?php echo date('M j, Y', strtotime($LastRequest['SubmittedAt'])); ?></span>
            </div>

            <?php else: ?>
            <div class="VerifPageHeader">
                <div class="VerifBadgeLarge">✓</div>
                <h2>Apply for Verification<?php echo $VerifPage ? ' — ' . htmlspecialchars($VerifPage['Name']) : ''; ?></h2>
                <p class="VerifSubtitle">A blue badge shows the community that <?php echo $SubjectLabel; ?> is authentic and notable.</p>
            </div>

            <?php if ($WasRejected): ?>
            <div class="VerifRejectedNotice">Your previous request was not approved. You may apply again below.</div>
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
                <?php if ($VerifPageEncID): ?>
                <input type="hidden" name="PageID" value="<?php echo $VerifPageEncID; ?>">
                <?php endif; ?>

                <div class="VerifFormSection">
                    <label for="VerifReason">Why should <?php echo $SubjectLabel; ?> be verified?</label>
                    <textarea id="VerifReason" name="reason" placeholder="Describe <?php echo $SubjectType === 'page' ? 'this page, its reach, and why verification would benefit the community…' : 'who you are, your public presence, and why verification would benefit the community…'; ?>" rows="5" maxlength="1000"></textarea>
                    <span class="VerifCharCount"><span id="VerifCharCounter">0</span> / 1000</span>
                </div>

                <div class="VerifFeeBox">
                    <div class="VerifFeeInfo">
                        <div class="VerifFeeAmount">$9.99 <span>one-time fee</span></div>
                        <p>A non-refundable processing fee is required to submit a verification request.</p>
                    </div>
                    <label class="VerifFeeCheck">
                        <input type="checkbox" id="FeeConfirmed" name="fee_confirmed">
                        <span>I understand and agree to pay the $9.99 verification fee</span>
                    </label>
                </div>

                <div class="FormResponse" id="VerifFormResponse"></div>
                <button type="submit" class="BrandBtn VerifSubmitBtn" id="VerifSubmitBtn" disabled>Submit Request</button>
            </form>
            <?php endif; ?>

        </div>
    </div>

    <script type="module" src="Scripts/GetVerified.js"></script>
</body>
</html>
