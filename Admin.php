<?php
$PATH = '';
include $PATH.'Includes/UserAuth.php';
include_once $PATH.'Includes/Encryption.php';

// Only admins allowed
if ((int)$User['Privilege'] < PRIV_ADMIN) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $CsrfToken; ?>">
    <title>Admin Dashboard — Commune</title>
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Admin.css">
</head>
<body>
    <div class="AdminLayout">

        <!-- Sidebar -->
        <aside class="AdminSidebar">
            <div class="AdminBrand">
                <span class="AdminBrandLetter">C</span>
                <span class="AdminBrandName">Commune</span>
                <span class="AdminBadge">Admin</span>
            </div>

            <nav class="AdminNav">
                <a class="AdminNavItem Active" data-tab="OverviewTab">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Overview
                </a>
                <a class="AdminNavItem" data-tab="VerificationTab">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Verification Queue
                    <span class="AdminNavBadge" id="VerifCount"></span>
                </a>
                <a class="AdminNavItem" data-tab="UsersTab">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    User Management
                </a>
                <a class="AdminNavItem" data-tab="ContentTab">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    Content Moderation
                </a>
            </nav>

            <div class="AdminSidebarFooter">
                <img src="<?php echo $User['ProfilePic'] ? 'MediaFolders/profile_pictures/'.htmlspecialchars($User['ProfilePic']) : 'Imgs/Icons/unknown.png'; ?>" class="AdminAvatarSmall" alt="">
                <div class="AdminSidebarFooterInfo">
                    <span class="AdminSidebarName"><?php echo htmlspecialchars($User['Fname'].' '.$User['Lname']); ?></span>
                    <span class="AdminSidebarRole">Administrator</span>
                </div>
                <a href="index.php?redirect=logout" class="AdminLogoutBtn" title="Logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="AdminMain">

            <!-- ── Overview Tab ── -->
            <div class="AdminTab Active" id="OverviewTab">
                <div class="AdminTabHeader">
                    <h1>Platform Overview</h1>
                    <p>Live stats across the entire Commune platform.</p>
                </div>

                <div class="StatGrid" id="StatGrid">
                    <div class="StatCard Skeleton"></div>
                    <div class="StatCard Skeleton"></div>
                    <div class="StatCard Skeleton"></div>
                    <div class="StatCard Skeleton"></div>
                    <div class="StatCard Skeleton"></div>
                    <div class="StatCard Skeleton"></div>
                </div>

                <div class="AdminCard TrendCard">
                    <div class="AdminCardHeader">
                        <h3>Posts — last 14 days</h3>
                    </div>
                    <div class="TrendChart" id="TrendChart">
                        <div class="TrendLoading">Loading chart…</div>
                    </div>
                </div>
            </div>

            <!-- ── Verification Tab ── -->
            <div class="AdminTab hidden" id="VerificationTab">
                <div class="AdminTabHeader">
                    <h1>Verification Queue</h1>
                    <p>Review and act on pending blue-tick requests.</p>
                </div>
                <div id="VerifList" class="VerifList">
                    <div class="AdminLoading">Loading requests…</div>
                </div>
            </div>

            <!-- ── Users Tab ── -->
            <div class="AdminTab hidden" id="UsersTab">
                <div class="AdminTabHeader">
                    <h1>User Management</h1>
                    <p>Search, inspect, ban, or verify any user.</p>
                </div>
                <div class="UserSearchBar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="UserSearchInput" placeholder="Search by name, username or email…">
                </div>
                <div id="UserResults" class="UserResults"></div>
            </div>

            <!-- ── Content Moderation Tab ── -->
            <div class="AdminTab hidden" id="ContentTab">
                <div class="AdminTabHeader">
                    <h1>Content Moderation</h1>
                    <p>Delete any post or comment from the platform.</p>
                </div>

                <div class="ContentSearchRow">
                    <div class="UserSearchBar ContentSearchBar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="ContentSearchInput" placeholder="Search posts by keyword or username…">
                    </div>
                    <div class="ContentTypeToggle">
                        <button class="AdminBtn Primary" id="ContentTypePosts">Posts</button>
                        <button class="AdminBtn Ghost" id="ContentTypeComments">Comments</button>
                    </div>
                </div>

                <div id="ContentResults" class="ContentResults"></div>
            </div>

        </main>
    </div>

    <!-- Sanction Modal -->
    <div class="AdminModal hidden" id="BanModal">
        <div class="AdminModalCard">
            <h3 class="AdminModalTitle" id="BanModalTitle">Take Action</h3>
            <p class="AdminModalSub" id="BanModalSub"></p>

            <div class="SanctionTypeRow">
                <label class="SanctionTypeOption">
                    <input type="radio" name="SanctionType" value="0"> Warning
                </label>
                <label class="SanctionTypeOption">
                    <input type="radio" name="SanctionType" value="1" checked> Temporary Ban
                </label>
                <label class="SanctionTypeOption">
                    <input type="radio" name="SanctionType" value="2"> Permanent Ban
                </label>
            </div>

            <div id="BanEndDateWrap">
                <label class="AdminModalLabel">Ban end date</label>
                <input type="datetime-local" class="AdminModalInput" id="BanEndDateInput">
            </div>

            <div class="BanPermWarning hidden" id="BanPermWarning">
                <span class="BanPermWarningTitle">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Permanent ban — no expiry
                </span>
                The user will be blocked from Commune indefinitely. They will not be able to log in until this ban is manually lifted by an administrator.
            </div>

            <textarea class="AdminModalTextarea" id="BanReasonInput" placeholder="Reason (required)…" maxlength="500"></textarea>

            <!-- Referenced content -->
            <div class="RefSummarySection">
                <div class="RefSummaryRow">
                    <span class="AdminModalLabel">Referenced posts</span>
                    <button class="RefBrowseBtn" id="BrowsePostsBtn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Browse posts
                    </button>
                </div>
                <div class="RefSelectedList" id="RefPostSelected"></div>
            </div>
            <div class="RefSummarySection">
                <div class="RefSummaryRow">
                    <span class="AdminModalLabel">Referenced comments</span>
                    <button class="RefBrowseBtn" id="BrowseCommentsBtn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Browse comments
                    </button>
                </div>
                <div class="RefSelectedList" id="RefCommentSelected"></div>
            </div>

            <!-- Option to also delete referenced content -->
            <label class="RefDeleteToggle">
                <input type="checkbox" id="BanAlsoDelete">
                <span>Also delete all referenced posts &amp; comments</span>
            </label>

            <div class="AdminModalActions">
                <button class="AdminBtn Ghost" id="BanModalCancel">Cancel</button>
                <button class="AdminBtn Danger" id="BanModalConfirm">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Extend Ban Modal -->
    <div class="AdminModal hidden" id="ExtendBanModal">
        <div class="AdminModalCard">
            <h3 class="AdminModalTitle">Extend Ban</h3>
            <p class="AdminModalSub" id="ExtendBanSub"></p>
            <label class="AdminModalLabel">New end date</label>
            <input type="datetime-local" class="AdminModalInput" id="ExtendBanDateInput">
            <div class="AdminModalActions">
                <button class="AdminBtn Ghost" id="ExtendBanCancel">Cancel</button>
                <button class="AdminBtn Danger" id="ExtendBanConfirm">Extend Ban</button>
            </div>
        </div>
    </div>

    <!-- Content Picker Panel (full-screen overlay) -->
    <div class="PickerPanel hidden" id="PickerPanel">
        <div class="PickerPanelInner">
            <div class="PickerPanelHeader">
                <div class="PickerPanelTitle" id="PickerPanelTitle">Select Posts</div>
                <div class="PickerPanelSearch">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="PickerSearchInput" placeholder="Filter…">
                </div>
                <div class="PickerPanelActions">
                    <span class="PickerSelectedCount" id="PickerSelectedCount">0 selected</span>
                    <button class="AdminBtn Primary Small" id="PickerDoneBtn">Done</button>
                    <button class="AdminBtn Ghost Small" id="PickerCancelBtn">Cancel</button>
                </div>
            </div>
            <div class="PickerPanelBody" id="PickerPanelBody">
                <div class="AdminLoading">Loading…</div>
            </div>
        </div>
    </div>

    <!-- Delete Reason Modal -->
    <div class="AdminModal hidden" id="DeleteReasonModal">
        <div class="AdminModalCard">
            <h3 class="AdminModalTitle">Delete Content</h3>
            <p class="AdminModalSub" id="DeleteReasonSub"></p>
            <label class="AdminModalLabel">Reason for deletion (required)</label>
            <textarea class="AdminModalTextarea" id="DeleteReasonInput" placeholder="e.g. Violates community guidelines — hate speech…" maxlength="500"></textarea>
            <div class="AdminModalActions">
                <button class="AdminBtn Ghost" id="DeleteReasonCancel">Cancel</button>
                <button class="AdminBtn Danger" id="DeleteReasonConfirm">Delete</button>
            </div>
        </div>
    </div>

    <script type="module" src="Scripts/Admin.js"></script>
</body>
</html>
