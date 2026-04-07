<?php
$PATH = '';
include $PATH . 'Includes/UserAuth.php';
include_once $PATH . 'Includes/Encryption.php';

$DocumentExtensions = '.pdf, .doc, .docx, .txt ,.xls,.xlsx,.ppt,.pptx';

// Resolve page by handle
$Handle = strtolower(trim($PageHandle));
$stmt = $pdo->prepare("SELECT * FROM pages WHERE Handle = ? LIMIT 1");
$stmt->execute([$Handle]);
$Page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$Page) {
    header("Location: 404.php");
    exit();
}

$PageID = (int)$Page['id'];

// Is the logged-in user a member of this page?
$MemberRow = $pdo->prepare("SELECT Role FROM page_members WHERE PageID = ? AND UID = ?");
$MemberRow->execute([$PageID, $UID]);
$MemberRole = $MemberRow->fetchColumn(); // false if not a member, otherwise 'owner'/'admin'/etc.

$IsOwner  = $MemberRole === 'owner';
$IsMember = $MemberRole !== false;

// Is the logged-in user following this page?
$FollowRow = $pdo->prepare("SELECT id FROM page_followers WHERE PageID = ? AND UID = ?");
$FollowRow->execute([$PageID, $UID]);
$IsFollowing = (bool)$FollowRow->fetchColumn();

// Post count
$PostCount = (int)$pdo->prepare("SELECT COUNT(*) FROM posts WHERE OrgID = ? AND Status = 1")
    ->execute([$PageID]) ? $pdo->query("SELECT COUNT(*) FROM posts WHERE OrgID = $PageID AND Status = 1")->fetchColumn() : 0;

$PostCountStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE OrgID = ? AND Status = 1");
$PostCountStmt->execute([$PageID]);
$PostCount = (int)$PostCountStmt->fetchColumn();

// Media paths
$LogoSrc = $Page['Logo']
    ? $PATH . 'MediaFolders/page_logos/' . htmlspecialchars($Page['Logo'])
    : null;
$CoverSrc = $Page['CoverPhoto']
    ? $PATH . 'MediaFolders/page_covers/' . htmlspecialchars($Page['CoverPhoto'])
    : null;

// Fetch page posts for initial render
$PostStmt = $pdo->prepare("
    SELECT posts.id AS PID, posts.*, users.Fname, users.Lname, users.Username, users.ProfilePic, users.IsBlueTick,
           CASE WHEN l.UID IS NOT NULL THEN 1 ELSE 0 END AS liked,
           CASE WHEN sp.PostID IS NOT NULL THEN 1 ELSE 0 END AS saved
    FROM posts
    INNER JOIN users ON posts.UID = users.id
    LEFT JOIN likes l ON posts.id = l.PostID AND l.UID = ?
    LEFT JOIN saved_posts sp ON posts.id = sp.PostID AND sp.UID = ?
    WHERE posts.OrgID = ? AND posts.Status = 1
    ORDER BY posts.Date DESC
    LIMIT 10
");
$PostStmt->execute([$UID, $UID, $PageID]);
$PagePosts = $PostStmt->fetchAll(PDO::FETCH_ASSOC);

$EncPageID = Encrypt($PageID, "Positioned", ["Timestamp" => time()]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $CsrfToken; ?>">
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Feed.css">
    <link rel="stylesheet" href="Styles/Profile.css">
    <link rel="stylesheet" href="Styles/PageProfile.css">
    <title><?php echo htmlspecialchars($Page['Name']); ?> — Commune</title>
</head>
<body id="PageProfileBody">

    <?php include 'Includes/NavBar.php'; ?>

    <div class="ProfileContainer">
        <div class="ProfileHeader">

            <!-- Cover -->
            <div class="CoverPhotoContainer">
                <div class="CoverPhoto <?php echo !$CoverSrc ? 'Default PageCoverDefault' : ''; ?>">
                    <?php if ($CoverSrc): ?>
                        <img src="<?php echo $CoverSrc; ?>" alt="Cover">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Header bottom row -->
            <div class="ProfileBottomHeader">
                <div class="Initial">
                    <!-- Logo -->
                    <div class="ProfilePictureContainer PageLogoContainer">
                        <?php if ($LogoSrc): ?>
                            <img src="<?php echo $LogoSrc; ?>" alt="<?php echo htmlspecialchars($Page['Name']); ?>">
                        <?php else: ?>
                            <div class="PageLogoPlaceholder">
                                <?php echo mb_strtoupper(mb_substr($Page['Name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="ProfileInfo">
                        <p class="UserName">
                            <?php echo htmlspecialchars($Page['Name']); ?>
                            <?php if ($Page['IsVerified']): ?>
                                <span class="BlueTick Large" title="Verified Page"></span>
                            <?php endif; ?>
                            <span class="PageTypeBadge">Page</span>
                        </p>
                        <p class="UserUsername">@<?php echo htmlspecialchars($Page['Handle']); ?></p>
                        <?php if ($Page['Category']): ?>
                            <p class="PageCategory"><?php echo htmlspecialchars($Page['Category']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ProfileInfoStats">
                    <div class="ProfileStats">
                        <div class="Stat">
                            <p class="StatNumber"><?php echo number_format($PostCount); ?></p>
                            <p class="StatTitle">Posts</p>
                        </div>
                        <div class="Stat">
                            <p class="StatNumber"><?php echo number_format((int)$Page['Followers']); ?></p>
                            <p class="StatTitle">Followers</p>
                        </div>
                    </div>

                    <div class="ProfileActions">
                        <?php if ($IsOwner): ?>
                            <button class="BrandBtn PageManageBtn" onclick="window.location.href='index.php?target=page-dashboard&handle=<?php echo htmlspecialchars($Handle); ?>'">
                                Manage Page
                            </button>
                        <?php elseif ($IsFollowing): ?>
                            <button class="BrandBtn PageFollowBtn Followed" data-pageid="<?php echo $EncPageID; ?>">Following</button>
                        <?php else: ?>
                            <button class="BrandBtn PageFollowBtn" data-pageid="<?php echo $EncPageID; ?>">Follow</button>
                        <?php endif; ?>

                        <?php if ($Page['Website']): ?>
                            <a class="BrandBtn Dark PageWebsiteBtn" href="<?php echo htmlspecialchars($Page['Website']); ?>" target="_blank" rel="noopener noreferrer">Website</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="TabsNav ProfileNav">
                <a href="#" class="NavItem Active" tab-content="PagePostsTab">Posts</a>
                <a href="#" class="NavItem" tab-content="PageAboutTab">About</a>
                <?php if ($IsMember): ?>
                    <a href="#" class="NavItem" tab-content="PageTeamTab">Team</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="ProfileContent">

            <!-- Posts tab -->
            <div class="TabContent Posts" id="PagePostsTab">
                <?php if (empty($PagePosts)): ?>
                    <div class="EmptyFeed">
                        <p>No posts yet.</p>
                        <?php if ($IsMember): ?>
                            <button class="BrandBtn CreatePostBtn">Create the first post</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($PagePosts as $FeedPost):
                        $PostTimestamp   = strtotime($FeedPost['Date']);
                        $Params          = ["Timestamp" => $PostTimestamp];
                        $EncPostID       = Encrypt($FeedPost['PID'], "Positioned", $Params);
                        $EncAuthorUID    = Encrypt($FeedPost['UID'], "Positioned", $Params);
                        $PostProfilePic  = $FeedPost['ProfilePic']
                            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($FeedPost['ProfilePic'])
                            : 'Imgs/Icons/unknown.png';
                        $IsSelfPost = (int)($FeedPost['UID'] == $UID);
                        $IsSavedPost = (int)$FeedPost['saved'];
                    ?>
                        <div class="FeedPost PageFeedPost" PID="<?php echo $EncPostID; ?>" UID="<?php echo $EncAuthorUID; ?>" Self="<?php echo $IsSelfPost; ?>" Saved="<?php echo $IsSavedPost; ?>">
                            <div class="FeedPostHeader">
                                <div class="FeedPostAuthorContainer">
                                    <!-- Page identity (who the post is from) -->
                                    <div class="PagePostAuthorBadge">
                                        <?php if ($LogoSrc): ?>
                                            <img class="PagePostLogo" src="<?php echo $LogoSrc; ?>" alt="">
                                        <?php else: ?>
                                            <div class="PagePostLogoPlaceholder"><?php echo mb_strtoupper(mb_substr($Page['Name'], 0, 1)); ?></div>
                                        <?php endif; ?>
                                        <div class="PagePostAuthorInfo">
                                            <span class="PagePostName"><?php echo htmlspecialchars($Page['Name']); ?></span>
                                            <span class="PagePostMeta">@<?php echo htmlspecialchars($Page['Handle']); ?> &middot; <span class="FeedPostTime" data-date="<?php echo $PostTimestamp; ?>"></span></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>
                            </div>

                            <div class="FeedPostContent">
                                <p><?php echo preg_replace('/@([\w]+)/', '<a class="MentionLink" href="index.php?target=profile&username=$1">@$1</a>', htmlspecialchars($FeedPost['Content'])); ?></p>
                                <?php
                                $MediaFolder = $FeedPost['MediaFolder'];
                                if (is_dir($MediaFolder)) {
                                    $Media = scandir($MediaFolder);
                                    $MediaType = (int)$FeedPost['Type'];
                                    if ($MediaType === 2) {
                                        foreach ($Media as $Image) {
                                            if (in_array(strtolower($Image), ['.', '..'])) continue;
                                            echo '<img src="' . $MediaFolder . '/' . $Image . '" alt="">';
                                        }
                                    } elseif ($MediaType === 3) {
                                        foreach ($Media as $Doc) {
                                            if (in_array(strtolower($Doc), ['.', '..'])) continue;
                                            echo '<a class="FeedPostLink" href="' . APP_URL . '/' . $MediaFolder . '/' . $Doc . '">
                                                <div class="UploadedFile">
                                                    <img src="Imgs/Icons/Document.svg">
                                                    <p>' . $Doc . '</p>
                                                </div>
                                            </a>';
                                        }
                                    }
                                }
                                ?>
                            </div>

                            <div class="FeedPostInteractionCounters">
                                <p><span class="PostLikesCNT"><?php echo $FeedPost['LikeCounter']; ?></span> likes</p>
                                <p><?php echo $FeedPost['CommentCounter']; ?> Comments</p>
                            </div>

                            <div class="FeedPostInteractions">
                                <div class="Interaction FeedPostLike">
                                    <img src="<?php echo $FeedPost['liked'] ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg'; ?>">
                                    Like
                                </div>
                                <div class="Interaction FeedPostComment">
                                    <img src="Imgs/Icons/comment.svg">
                                    Comment
                                </div>
                                <div class="Interaction FeedPostShare">
                                    <img src="Imgs/Icons/share.svg">
                                    Share
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- About tab -->
            <div class="TabContent hidden" id="PageAboutTab">
                <div class="PageAboutCard">
                    <?php if ($Page['Bio']): ?>
                        <div class="PageAboutSection">
                            <h4 class="PageAboutLabel">About</h4>
                            <p class="PageAboutText"><?php echo nl2br(htmlspecialchars($Page['Bio'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($Page['Category']): ?>
                        <div class="PageAboutSection">
                            <h4 class="PageAboutLabel">Category</h4>
                            <p class="PageAboutText"><?php echo htmlspecialchars($Page['Category']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($Page['Website']): ?>
                        <div class="PageAboutSection">
                            <h4 class="PageAboutLabel">Website</h4>
                            <a class="PageAboutLink" href="<?php echo htmlspecialchars($Page['Website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($Page['Website']); ?></a>
                        </div>
                    <?php endif; ?>
                    <div class="PageAboutSection">
                        <h4 class="PageAboutLabel">Created</h4>
                        <p class="PageAboutText"><?php echo date('F j, Y', strtotime($Page['CreatedAt'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Team tab (members only) -->
            <?php if ($IsMember): ?>
            <div class="TabContent hidden" id="PageTeamTab">
                <div class="PageAboutCard">
                    <p class="PageAboutText PageTeamNote">Team management coming soon.</p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include 'Includes/Modals/CreatePost.php'; ?>
    <?php include 'Includes/Modals/CommentSection.php'; ?>
    <?php include 'Includes/Modals/Confirmation.php'; ?>
    <?php include 'Includes/Modals/CreateOrg.php'; ?>

    <script src="Scripts/modal.js"></script>
    <script type="module" src="Scripts/Feed.js"></script>
    <script type="module" src="Scripts/PageProfile.js"></script>
    <script type="module" src="Scripts/Org.js"></script>
</body>
</html>
