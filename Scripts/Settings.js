import { Submit } from "./Forms.js";

document.addEventListener('DOMContentLoaded', () => {

    // 1. Tab Switching Logic
    const tabs = document.querySelectorAll('.SettingsNavItem');
    const sections = document.querySelectorAll('.SettingsSection');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Active State
            tabs.forEach(t => t.classList.remove('Active'));
            tab.classList.add('Active');

            // Show Section
            const targetId = tab.getAttribute('data-tab');
            sections.forEach(s => s.classList.remove('Active'));
            document.getElementById(targetId).classList.add('Active');

            // Load Data if needed
            if (targetId === 'SecurityTab') loadActiveSessions();
            if (targetId === 'PrivacyTab') loadBlockedUsers();
            if (targetId === 'ActivityTab') loadActivity('liked');
        });
    });

    // 2. Update Account Info
    const accountForm = document.getElementById('UpdateAccountForm');
    if (accountForm) {
        accountForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(accountForm);
            formData.append('ReqType', 1);
            
            const btn = accountForm.querySelector('.BrandBtn');
            const resp = accountForm.querySelector('.FormResponse');
            
            btn.disabled = true;
            const data = await Submit('POST', 'Origin/Operations/Settings.php', formData);
            
            resp.textContent = data.message;
            resp.className = `FormResponse ${data.success ? 'Success' : 'Error'}`;
            btn.disabled = false;
        });
    }

    // 3. Change Password
    const passForm = document.getElementById('ChangePasswordForm');
    if (passForm) {
        passForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            // Clear previous errors
            passForm.querySelectorAll('.TextField').forEach(f => {
                f.classList.remove('Error');
                const err = f.querySelector('.FieldError');
                if(err) err.innerHTML = '';
            });

            const formData = new FormData(passForm);
            formData.append('ReqType', 2);
            
            const btn = passForm.querySelector('.BrandBtn');
            const resp = passForm.querySelector('.FormResponse');
            
            btn.disabled = true;
            const data = await Submit('POST', 'Origin/Operations/Settings.php', formData);
            
            resp.textContent = data.message;
            resp.className = `FormResponse ${data.success ? 'Success' : 'Error'}`;
            btn.disabled = false;
            
            if (data.success) passForm.reset();
        });
    }

    // 4. Active Sessions
async function loadActiveSessions() {
        const container = document.getElementById('SessionsList');
        container.innerHTML = '<div class="Loader"></div>';

        const formData = new FormData();
        formData.append('ReqType', 3);
        const data = await Submit('POST', 'Origin/Operations/Settings.php', formData);

        if (data.success) {
            let html = '';
            data.sessions.forEach(s => {
                // --- LOGIC: HANDLE CURRENT SESSION ---
                let actionBtn;
                let badgeClass = '';
                
                if (s.IsCurrent) {
                    // Show "Active Now" badge instead of Revoke button
                    actionBtn = `<span class="CurrentSessionBadge">Active Now</span>`;
                    badgeClass = 'Current'; // CSS class for highlighting
                } else {
                    actionBtn = `<button class="RevokeBtn" data-id="${s.id}">Revoke</button>`;
                }
                // -----------------------------------------

                html += `
                <div class="SessionItem ${badgeClass}">
                    <div class="SessionInfo">
                        <div class="SessionIcon"><img src="Imgs/Icons/notification.svg"></div>
                        <div class="SessionDetails">
                            <p>${s.Device}</p>
                            <span>${s.IP} • ${s.LastActive}</span>
                        </div>
                    </div>
                    ${actionBtn}
                </div>`;
            });
            
            // 1. Inject HTML
            container.innerHTML = html;

            // 2. --- THIS WAS MISSING: Attach Revoke Listeners ---
            container.querySelectorAll('.RevokeBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    ShowConfirmModal({
                        Title: "Revoke this session?",
                        ConfirmText: "Revoke",
                        Action: "Close",
                        onConfirm: async () => {
                            btn.textContent = '...';
                            btn.disabled = true;

                            const formData = new FormData();
                            formData.append('ReqType', 4);
                            formData.append('SessionID', btn.dataset.id);

                            await Submit('POST', 'Origin/Operations/Settings.php', formData);
                            loadActiveSessions();
                        }
                    });
                });
            });
            // ----------------------------------------------------
        }
    }

    // 5. Blocked Users
    async function loadBlockedUsers() {
        const container = document.getElementById('BlockedUsersList');
        container.innerHTML = '<div class="Loader"></div>';

        const formData = new FormData();
        formData.append('ReqType', 5);
        const data = await Submit('POST', 'Origin/Operations/Settings.php', formData);

        if (data.success) {
            if (data.users.length === 0) {
                container.innerHTML = '<p style="color:#888; text-align:center; padding:20px;">You haven\'t blocked anyone.</p>';
                return;
            }

            let html = '';
            data.users.forEach(u => {
                html += `
                <div class="BlockedUserItem">
                    <div class="BlockedUserInfo">
                        <img src="${u.ProfilePic}">
                        <div>
                            <div style="font-weight:bold">${u.Name}</div>
                            <div style="color:#666; font-size:13px">@${u.Username}</div>
                        </div>
                    </div>
                    <button class="UnblockBtn" data-id="${u.BlockID}">Unblock</button>
                </div>`;
            });
            container.innerHTML = html;

            // Attach Unblock Listeners
            container.querySelectorAll('.UnblockBtn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    const formData = new FormData();
                    formData.append('ReqType', 6);
                    formData.append('BlockID', btn.dataset.id);
                    const data = await Submit('POST', 'Origin/Operations/Settings.php', formData);
                    if (data.success) {
                        loadBlockedUsers();
                    } else {
                        btn.disabled = false;
                    }
                });
            });
        }
    }

    // 6. Activity Tab
    const ActivityBtns = document.getElementsByClassName('ActivityTabBtn');
    const ActivityPanels = document.getElementsByClassName('ActivityPanel');
    const ActivityLoaded = {};

    for (let i = 0; i < ActivityBtns.length; i++) {
        ActivityBtns[i].addEventListener('click', function() {
            for (let j = 0; j < ActivityBtns.length; j++) ActivityBtns[j].classList.remove('Active');
            for (let j = 0; j < ActivityPanels.length; j++) ActivityPanels[j].classList.remove('Active');
            this.classList.add('Active');
            const ActivityType = this.getAttribute('data-activity').replace('Tab', '').toLowerCase();
            document.getElementById(this.getAttribute('data-activity')).classList.add('Active');
            if (!ActivityLoaded[ActivityType]) loadActivity(ActivityType);
        });
    }

    async function loadActivity(Type) {
        const PanelMap = { liked: 'LikedTab', commented: 'CommentedTab', saved: 'SavedTab' };
        const Panel = document.getElementById(PanelMap[Type]);
        Panel.innerHTML = '<div class="Loader"></div>';

        const formData = new FormData();
        formData.append('ReqType', 8);
        formData.append('ActivityType', Type);
        const data = await Submit('POST', 'Origin/Operations/Settings.php', formData);

        if (!data.success || data.posts.length === 0) {
            const Labels = { liked: 'liked', commented: 'commented on', saved: 'saved' };
            Panel.innerHTML = `
                <div class="ActivityEmpty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    No posts ${Labels[Type]} yet.
                </div>`;
            ActivityLoaded[Type] = true;
            return;
        }

        let html = '';
        data.posts.forEach(post => {
            const snippet = post.content.length > 80 ? post.content.substring(0, 80) + '…' : post.content;
            const date = new Date(post.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            html += `
                <a href="index.php?target=post&pid=${encodeURIComponent(post.pid)}" class="ActivityPostItem">
                    <img src="${post.pic}" class="ActivityPostAvatar" alt="">
                    <div class="ActivityPostMeta">
                        <div class="ActivityPostAuthor">${post.fname} ${post.lname} <span style="color:#94a3b8;font-weight:400">@${post.username}</span></div>
                        <div class="ActivityPostText">${snippet}</div>
                        <div class="ActivityPostDate">${date}</div>
                    </div>
                </a>`;
        });

        Panel.innerHTML = html;
        ActivityLoaded[Type] = true;
    }

    // 7. Delete Account
    const deleteBtn = document.getElementById('DeleteAccountBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            // Assuming ShowConfirmModal is globally available from modal.js
            if (typeof ShowConfirmModal === 'function') {
                ShowConfirmModal({
                    Title: "Delete your account?",
                    Hint: "Your account will be permanently deleted along with your posts, saved items, and profile data.",
                    ConfirmText: "Permanently Delete",
                    Action: "Close", // We handle redirect manually
                    onConfirm: async () => {
                        const formData = new FormData();
                        formData.append('ReqType', 7);
                        const data = await Submit('POST', 'Origin/Operations/Settings.php', formData);
                        if (data.success) {
                            window.location.href = 'index.php';
                        } else {
                            alert(data.message);
                        }
                    }
                });
            } else {
                // Fallback if modal.js isn't loaded properly
                if(confirm("Are you sure you want to delete your account? This cannot be undone.")) {
                    const formData = new FormData();
                    formData.append('ReqType', 7);
                    Submit('POST', 'Origin/Operations/Settings.php', formData).then(data => {
                        if(data.success) window.location.href = 'index.php';
                    });
                }
            }
        });
    }

});
