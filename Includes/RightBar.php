<?php
    // Smart Who to Follow — friends-of-friends + liked authors, fallback to random
    $suggestions = GetSmartWhoToFollow($pdo, $UID, 5);
?>

<div class="RightSidebar">
    <div class="SidebarSection">
        <h3>Who to Follow</h3>
        <div class="SuggestionList">
            <?php if ($suggestions): ?>
                <?php foreach ($suggestions as $sug): ?>
                    <?php 
                        $sugPic = $sug['ProfilePic'] ? 'MediaFolders/profile_pictures/' . $sug['ProfilePic'] : 'Imgs/Icons/unknown.png'; 
                        $sugEncID = Encrypt($sug['id'], "Positioned", ["Timestamp" => time()]);
                    ?>
                    <div class="SuggestionItem">
                        <a href="index.php?target=profile&uid=<?php echo urlencode($sugEncID); ?>" class="SugUser">
                            <img src="<?php echo $sugPic; ?>" alt="">
                            <div class="SugInfo">
                                <div class="Name">
                                    <?php echo htmlspecialchars($sug['Fname'] . ' ' . $sug['Lname']); ?>
                                    <?php if (!empty($sug['IsBlueTick'])): ?>
                                        <span class="BlueTick" title="Verified"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="Handle">@<?php echo htmlspecialchars($sug['Username']); ?></div>
                            </div>
                        </a>
                        <button class="BrandBtn FollowBtn Small" uid="<?php echo $sugEncID; ?>">Follow</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#888; font-size:13px; padding:10px;">No new suggestions.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="SidebarFooter">
        <p>&copy; 2025 Commune. All rights reserved.</p>
        <a href="#">Privacy</a> • <a href="#">Terms</a>
    </div>
</div>