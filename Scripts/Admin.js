import { Submit } from './Forms.js';

// ── SVG icons ─────────────────────────────────────────────────────────────
const SVG = {
    users:   `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
    post:    `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>`,
    comment: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
    check:   `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
    ban:     `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>`,
    clock:   `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
    search:  `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>`,
    verified:`<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
    blocked: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>`,
    empty:   `<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
};

// ── Tab navigation ────────────────────────────────────────────────────────
const NavItems = document.getElementsByClassName('AdminNavItem');
const Tabs     = document.getElementsByClassName('AdminTab');

[...NavItems].forEach(item => {
    item.addEventListener('click', () => {
        [...NavItems].forEach(n => n.classList.remove('Active'));
        [...Tabs].forEach(t => t.classList.add('hidden'));
        item.classList.add('Active');
        document.getElementById(item.dataset.tab).classList.remove('hidden');

        if (item.dataset.tab === 'VerificationTab') LoadVerification();
        if (item.dataset.tab === 'UsersTab' && !UsersLoaded) {
            UsersLoaded = true;
            LoadUsers('');
        }
        if (item.dataset.tab === 'ContentTab' && !ContentLoaded) {
            ContentLoaded = true;
            LoadContent();
        }
    });
});

let ContentLoaded = false;

let UsersLoaded = false;

// ── Helpers ───────────────────────────────────────────────────────────────
function Post(ReqType, extra = {}) {
    const fd = new FormData();
    fd.append('ReqType', ReqType);
    Object.entries(extra).forEach(([k, v]) => fd.append(k, v));
    return Submit('POST', 'Origin/Operations/Admin.php', fd);
}

function TimeAgoShort(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60) return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

function EmptyState(message) {
    return `<div class="AdminEmpty">${SVG.empty}<p>${message}</p></div>`;
}

// ── Overview ─────────────────────────────────────────────────────────────
async function LoadOverview() {
    const data = await Post(7);
    if (!data.success) return;
    const s = data.stats;

    const cards = [
        { label: 'Total Users',           value: s.TotalUsers,           icon: SVG.users,   accent: '#1877f2' },
        { label: 'Total Posts',            value: s.TotalPosts,           icon: SVG.post,    accent: '#27ae60' },
        { label: 'Total Comments',         value: s.TotalComments,        icon: SVG.comment, accent: '#8e44ad' },
        { label: 'Verified Users',         value: s.VerifiedUsers,        icon: SVG.check,   accent: '#2980b9' },
        { label: 'Banned Users',           value: s.BannedUsers,          icon: SVG.ban,     accent: '#e74c3c' },
        { label: 'Pending Verifications',  value: s.PendingVerifications, icon: SVG.clock,   accent: '#f39c12' },
    ];

    document.getElementById('StatGrid').innerHTML = cards.map(c => `
        <div class="StatCard">
            <div class="StatIcon" style="background:${c.accent}18;color:${c.accent}">${c.icon}</div>
            <div class="StatBody">
                <span class="StatValue">${Number(c.value).toLocaleString()}</span>
                <span class="StatLabel">${c.label}</span>
            </div>
        </div>
    `).join('');

    const pendingCount = parseInt(s.PendingVerifications);
    const badge = document.getElementById('VerifCount');
    if (pendingCount > 0) badge.textContent = pendingCount;

    RenderTrend(s.PostTrend);
}

function RenderTrend(trend) {
    const container = document.getElementById('TrendChart');
    if (!trend || trend.length === 0) {
        container.innerHTML = '<p class="TrendEmpty">No data yet.</p>';
        return;
    }
    const max = Math.max(...trend.map(d => parseInt(d.Count)), 1);
    container.innerHTML = `
        <div class="TrendBars">
            ${trend.map(d => {
                const pct = Math.round((parseInt(d.Count) / max) * 100);
                return `
                    <div class="TrendBarWrap" title="${d.Day}: ${d.Count} posts">
                        <span class="TrendCount">${d.Count}</span>
                        <div class="TrendBar" style="height:${Math.max(pct, 4)}%"></div>
                        <span class="TrendLabel">${d.Day.slice(5)}</span>
                    </div>`;
            }).join('')}
        </div>
    `;
}

// ── Verification ─────────────────────────────────────────────────────────
async function LoadVerification() {
    const list = document.getElementById('VerifList');
    list.innerHTML = '<div class="AdminLoading">Loading requests…</div>';
    const data = await Post(1);

    if (!data.success || data.requests.length === 0) {
        list.innerHTML = EmptyState('No pending verification requests.');
        return;
    }

    list.innerHTML = data.requests.map(r => {
        const isPage = !!r.PageID;
        const avatar = isPage
            ? (r.PageLogo
                ? `<img class="VerifAvatar" src="${r.PageLogo}" alt="">`
                : `<div class="VerifAvatar VerifAvatarPlaceholder">${r.PageName.charAt(0).toUpperCase()}</div>`)
            : `<img class="VerifAvatar" src="${r.ProfilePic}" alt="">`;
        const nameRow = isPage
            ? `<span class="VerifName">${r.PageName}</span>
               <span class="VerifUsername">@${r.PageHandle}</span>
               <span class="VerifTypePill Page">Page</span>
               <span class="VerifTime">${TimeAgoShort(r.SubmittedAt)}</span>
               <span class="VerifSubName">Requested by ${r.Name}</span>`
            : `<span class="VerifName">${r.Name}</span>
               <span class="VerifUsername">@${r.Username}</span>
               <span class="VerifTypePill User">User</span>
               <span class="VerifTime">${TimeAgoShort(r.SubmittedAt)}</span>`;
        return `
        <div class="VerifCard" id="VerifCard-${r.id}">
            ${avatar}
            <div class="VerifInfo">
                <div class="VerifNameRow">${nameRow}</div>
                <p class="VerifReason">${r.Reason}</p>
            </div>
            <div class="VerifActions">
                <button class="AdminBtn Success" onclick="ApproveVerif(${r.id})">Approve</button>
                <button class="AdminBtn Danger"  onclick="RejectVerif(${r.id})">Reject</button>
            </div>
        </div>`;
    }).join('');
}

window.ApproveVerif = async function(id) {
    const fd = new FormData();
    fd.append('ReqType', 3);
    fd.append('request_id', id);
    const data = await Submit('POST', 'Origin/Operations/Verification.php', fd);
    if (data.success) {
        document.getElementById('VerifCard-' + id)?.remove();
        CheckVerifEmpty();
    }
};

window.RejectVerif = async function(id) {
    const fd = new FormData();
    fd.append('ReqType', 4);
    fd.append('request_id', id);
    const data = await Submit('POST', 'Origin/Operations/Verification.php', fd);
    if (data.success) {
        document.getElementById('VerifCard-' + id)?.remove();
        CheckVerifEmpty();
    }
};

function CheckVerifEmpty() {
    const list = document.getElementById('VerifList');
    if (list.children.length === 0) {
        list.innerHTML = EmptyState('No pending verification requests.');
    }
}

// ── User Management ──────────────────────────────────────────────────────
async function LoadUsers(query) {
    const results = document.getElementById('UserResults');
    results.innerHTML = '<div class="AdminLoading">Searching…</div>';
    const data = await Post(2, { query });
    if (!data.success || data.users.length === 0) {
        results.innerHTML = EmptyState('No users found.');
        return;
    }
    results.innerHTML = data.users.map(u => UserCardHTML(u)).join('');
}

function BanTypeLabel(type) {
    return type === 0 ? 'Warning' : type === 2 ? 'Permanent Ban' : 'Temporary Ban';
}

function BanTypePillClass(type) {
    return type === 0 ? 'warning' : type === 2 ? 'permanent' : 'temp';
}

function UserCardHTML(u) {
    const banned   = parseInt(u.IsBanned) === 1;
    const verified = parseInt(u.IsBlueTick) === 1;
    const ban      = u.ActiveBan;

    let banInfoHtml = '';
    if (banned && ban) {
        const banType = parseInt(ban.Type);
        const label   = BanTypeLabel(banType);
        const pillCls = BanTypePillClass(banType);
        let endText = '';
        if (banType === 1 && ban.EndDate) {
            const d = new Date(ban.EndDate);
            endText = ` &middot; until ${d.toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' })}`;
        } else if (banType === 2) {
            endText = ' &middot; indefinite';
        }
        banInfoHtml = `
            <div class="UserBanInfo">
                <span class="UserBanTypePill ${pillCls}">${label}</span>
                <span>${ban.Reason}${endText}</span>
                ${banType === 1 ? `<button class="AdminBtn Ghost Small" onclick="OpenExtendBanModal(${u.id}, '${u.Fname} ${u.Lname}')">Extend</button>` : ''}
            </div>`;
    }

    return `
    <div class="UserMgmtCard" id="UCard-${u.id}">
        <a href="index.php?target=profile&uid=${encodeURIComponent(u.EncUID)}" target="_blank" class="UserMgmtLeft">
            <img class="UserMgmtAvatar" src="${u.ProfilePic}" alt="">
            <div class="UserMgmtInfo">
                <div class="UserMgmtNameRow">
                    <span class="UserMgmtName">${u.Fname} ${u.Lname}</span>
                    ${verified ? `<span class="AdminVerifBadge">${SVG.verified} Verified</span>` : ''}
                    ${banned   ? `<span class="AdminBanBadge">${SVG.blocked} Banned</span>`    : ''}
                </div>
                <span class="UserMgmtUsername">@${u.Username}</span>
                <span class="UserMgmtMeta">${u.Email} &middot; ${u.PostCount} posts &middot; ${u.Followers} followers</span>
                ${banInfoHtml}
            </div>
        </a>
        <div class="UserMgmtActions">
            ${verified
                ? `<button class="AdminBtn Ghost Small" onclick="ToggleTick(${u.id}, 0, this)">Revoke Tick</button>`
                : `<button class="AdminBtn Primary Small" onclick="ToggleTick(${u.id}, 1, this)">Grant Tick</button>`
            }
            ${banned
                ? `<button class="AdminBtn Success Small" onclick="ToggleBan(${u.id}, 0, this)">Unban</button>`
                : `<button class="AdminBtn Danger Small" onclick="OpenBanModal(${u.id}, '${u.Fname} ${u.Lname}')">Warn / Ban</button>`
            }
        </div>
    </div>`;
}

window.ToggleTick = async function(uid, action, btn) {
    btn.disabled = true;
    const data = await Post(4, { TargetUID: uid, Action: action });
    if (data.success) {
        LoadUsers(document.getElementById('UserSearchInput').value);
    } else {
        btn.disabled = false;
    }
};

// ── Sanction Modal ────────────────────────────────────────────────────────
let BanTargetUID = null;
const BanModal        = document.getElementById('BanModal');
const BanEndDateWrap  = document.getElementById('BanEndDateWrap');
const BanPermWarning  = document.getElementById('BanPermWarning');
const BanTypeRadios   = document.getElementsByName('SanctionType');

function GetSelectedBanType() {
    for (const r of BanTypeRadios) { if (r.checked) return parseInt(r.value); }
    return 1;
}

function UpdateBanModalUI() {
    const type = GetSelectedBanType();
    BanEndDateWrap.classList.toggle('hidden', type !== 1);
    BanPermWarning.classList.toggle('hidden', type !== 2);
    const confirmBtn = document.getElementById('BanModalConfirm');
    confirmBtn.className = type === 0
        ? 'AdminBtn Ghost'
        : 'AdminBtn Danger';
    confirmBtn.textContent = type === 0 ? 'Issue Warning' : type === 2 ? 'Permanently Ban' : 'Temporarily Ban';
}

[...BanTypeRadios].forEach(r => r.addEventListener('change', UpdateBanModalUI));

// ── Reference picker panel ────────────────────────────────────────────────
let RefPostsPicked    = []; // { id, encID, preview, date }
let RefCommentsPicked = []; // { id, encID, preview, postPreview, date }

// Current picker session state
let PickerMode       = 'posts'; // 'posts' | 'comments'
let PickerTempPicked = [];      // working copy while panel is open (ban flow)
let PickerDebounce   = null;
let PickerReturnUID  = null; // UID to filter picker results by

const PickerPanel      = document.getElementById('PickerPanel');
const PickerBody       = document.getElementById('PickerPanelBody');
const PickerSearchInput = document.getElementById('PickerSearchInput');

function UpdateAlsoDeleteVisibility() {
    const hasAny = RefPostsPicked.length > 0 || RefCommentsPicked.length > 0;
    document.getElementById('BanAlsoDelete').closest('.RefDeleteToggle').classList.toggle('hidden', !hasAny);
}

function RenderSelectedList(arr, containerId, isPost) {
    const el = document.getElementById(containerId);
    if (!arr.length) { el.innerHTML = ''; return; }
    el.innerHTML = arr.map((item, i) => `
        <div class="RefSelectedCard" data-idx="${i}">
            <div class="RefSelectedCardBody">
                ${isPost ? '' : `<span class="RefSelectedCardSub">On: ${item.postPreview}</span>`}
                <span class="RefSelectedCardText">${item.preview}</span>
                <span class="RefSelectedCardDate">${TimeAgoShort(item.date)}</span>
            </div>
            <button class="RefSelectedRemove" data-idx="${i}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>`).join('');
    [...el.getElementsByClassName('RefSelectedRemove')].forEach(btn => {
        btn.addEventListener('click', () => {
            arr.splice(parseInt(btn.dataset.idx), 1);
            RenderSelectedList(arr, containerId, isPost);
            UpdateAlsoDeleteVisibility();
        });
    });
}

async function PickerLoad(query = '') {
    PickerBody.innerHTML = '<div class="AdminLoading">Loading…</div>';
    // Picker is ban-only: always filter by user (ReqType 11 for posts, 12 for comments)
    const reqType = PickerMode === 'posts' ? 11 : 12;
    const data    = await Post(reqType, { TargetUID: PickerReturnUID, query });
    const items   = PickerMode === 'posts' ? data.posts : data.comments;

    if (!data.success || !items || !items.length) {
        PickerBody.innerHTML = EmptyState('No content found.');
        return;
    }

    PickerBody.innerHTML = items.map(item => {
        const id       = item.id;
        const isChosen = PickerTempPicked.find(p => p.id === id);
        const chosen   = isChosen ? 'PickerCardChosen' : '';

        const toggleLabel = isChosen ? 'Selected' : 'Select';
        const toggleClass = isChosen ? 'PickerToggleChosen' : '';

        if (PickerMode === 'posts') {
            return PostCardHTML(item, id, chosen, toggleLabel, toggleClass, 'picker');
        } else {
            return CommentCardHTML(item, id, chosen, toggleLabel, toggleClass, 'picker');
        }
    }).join('');

    UpdatePickerCount();

    // "Show more" expand for long post context in comment picker
    [...PickerBody.getElementsByClassName('PickerExpandBtn')].forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const more = btn.previousElementSibling;
            if (more.classList.contains('hidden')) {
                more.classList.remove('hidden');
                btn.textContent = 'Show less';
            } else {
                more.classList.add('hidden');
                btn.textContent = 'Show more';
            }
        });
    });

    [...PickerBody.getElementsByClassName('PickerCard')].forEach(card => {
        card.getElementsByClassName('PickerToggleBtn')[0].addEventListener('click', () => {
            const id          = parseInt(card.dataset.id);
            const encID       = card.dataset.enc;
            const preview     = card.dataset.preview;
            const postPreview = card.dataset.postpreview || '';
            const date        = card.dataset.date;
            const existingIdx = PickerTempPicked.findIndex(p => p.id === id);
            const toggleBtn   = card.getElementsByClassName('PickerToggleBtn')[0];

            if (existingIdx >= 0) {
                PickerTempPicked.splice(existingIdx, 1);
                card.classList.remove('PickerCardChosen');
                toggleBtn.textContent = 'Select';
                toggleBtn.classList.remove('PickerToggleChosen');
            } else {
                PickerTempPicked.push({ id, encID, preview, postPreview, date });
                card.classList.add('PickerCardChosen');
                toggleBtn.textContent = 'Selected';
                toggleBtn.classList.add('PickerToggleChosen');
            }
            UpdatePickerCount();
        });
    });
}

function UpdatePickerCount() {
    document.getElementById('PickerSelectedCount').textContent =
        PickerTempPicked.length + ' selected';
}

function OpenPicker(mode) {
    PickerMode      = mode;
    PickerReturnUID = BanTargetUID;
    PickerTempPicked = mode === 'posts' ? [...RefPostsPicked] : [...RefCommentsPicked];
    document.getElementById('PickerPanelTitle').textContent = mode === 'posts' ? 'Select Posts' : 'Select Comments';
    PickerSearchInput.value = '';
    PickerPanel.classList.remove('hidden');
    BanModal.classList.add('hidden');
    PickerLoad('');
}

document.getElementById('BrowsePostsBtn').addEventListener('click',    () => OpenPicker('posts'));
document.getElementById('BrowseCommentsBtn').addEventListener('click', () => OpenPicker('comments'));

PickerSearchInput.addEventListener('input', e => {
    clearTimeout(PickerDebounce);
    PickerDebounce = setTimeout(() => PickerLoad(e.target.value.trim()), 300);
});

document.getElementById('PickerDoneBtn').addEventListener('click', () => {
    if (PickerMode === 'posts') {
        RefPostsPicked = [...PickerTempPicked];
        RenderSelectedList(RefPostsPicked, 'RefPostSelected', true);
    } else {
        RefCommentsPicked = [...PickerTempPicked];
        RenderSelectedList(RefCommentsPicked, 'RefCommentSelected', false);
    }
    UpdateAlsoDeleteVisibility();
    PickerPanel.classList.add('hidden');
    BanModal.classList.remove('hidden');
});

document.getElementById('PickerCancelBtn').addEventListener('click', () => {
    PickerPanel.classList.add('hidden');
    BanModal.classList.remove('hidden');
});

window.OpenBanModal = function(uid, name) {
    BanTargetUID = uid;
    document.getElementById('BanModalSub').textContent = `You are taking action against ${name}.`;
    document.getElementById('BanReasonInput').value = '';
    document.getElementById('BanEndDateInput').value = '';
    document.getElementById('BanAlsoDelete').checked = false;
    RefPostsPicked    = [];
    RefCommentsPicked = [];
    UpdateAlsoDeleteVisibility();
    RenderSelectedList(RefPostsPicked,    'RefPostSelected',    true);
    RenderSelectedList(RefCommentsPicked, 'RefCommentSelected', false);
    BanTypeRadios[1].checked = true;
    UpdateBanModalUI();
    BanModal.classList.remove('hidden');
};

document.getElementById('BanModalCancel').addEventListener('click', () => BanModal.classList.add('hidden'));

document.getElementById('BanModalConfirm').addEventListener('click', async () => {
    const reason = document.getElementById('BanReasonInput').value.trim();
    if (!reason) { document.getElementById('BanReasonInput').focus(); return; }

    const type = GetSelectedBanType();
    const extra = { TargetUID: BanTargetUID, Action: 1, Type: type, Reason: reason };

    if (type === 1) {
        const endDate = document.getElementById('BanEndDateInput').value;
        if (!endDate) { document.getElementById('BanEndDateInput').focus(); return; }
        extra.EndDate = endDate;
    }

    if (RefPostsPicked.length)    extra.RefPosts    = RefPostsPicked.map(p => p.id).join(',');
    if (RefCommentsPicked.length) extra.RefComments = RefCommentsPicked.map(p => p.id).join(',');
    extra.AlsoDelete = document.getElementById('BanAlsoDelete').checked ? 1 : 0;

    const data = await Post(3, extra);
    if (data.success) {
        BanModal.classList.add('hidden');
        LoadUsers(document.getElementById('UserSearchInput').value);
    }
});

window.ToggleBan = async function(uid, action, btn) {
    btn.disabled = true;
    const data = await Post(3, { TargetUID: uid, Action: action });
    if (data.success) {
        LoadUsers(document.getElementById('UserSearchInput').value);
    } else {
        btn.disabled = false;
    }
};

// ── Extend Ban Modal ──────────────────────────────────────────────────────
let ExtendBanUID = null;
const ExtendBanModal = document.getElementById('ExtendBanModal');

window.OpenExtendBanModal = function(uid, name) {
    ExtendBanUID = uid;
    document.getElementById('ExtendBanSub').textContent = `Set a new end date for ${name}'s ban.`;
    document.getElementById('ExtendBanDateInput').value = '';
    ExtendBanModal.classList.remove('hidden');
};

document.getElementById('ExtendBanCancel').addEventListener('click', () => ExtendBanModal.classList.add('hidden'));

document.getElementById('ExtendBanConfirm').addEventListener('click', async () => {
    const endDate = document.getElementById('ExtendBanDateInput').value;
    if (!endDate) { document.getElementById('ExtendBanDateInput').focus(); return; }
    const data = await Post(9, { TargetUID: ExtendBanUID, EndDate: endDate });
    if (data.success) {
        ExtendBanModal.classList.add('hidden');
        LoadUsers(document.getElementById('UserSearchInput').value);
    }
});

// ── User search debounce ──────────────────────────────────────────────────
let SearchDebounce = null;
document.getElementById('UserSearchInput').addEventListener('input', (e) => {
    clearTimeout(SearchDebounce);
    SearchDebounce = setTimeout(() => LoadUsers(e.target.value.trim()), 300);
});

// ── Shared card renderers ─────────────────────────────────────────────────
// Used by both the ban picker panel and the content moderation tab.

function PostCardHTML(item, id, chosenClass, toggleLabel, toggleClass, variant) {
    const typeInt   = parseInt(item.Type);
    const typeBadge = typeInt === 2
        ? `<span class="PickerTypeBadge Image">Images</span>`
        : typeInt === 3
            ? `<span class="PickerTypeBadge Doc">Document</span>`
            : `<span class="PickerTypeBadge Text">Text</span>`;
    const mediaHtml = item.Thumbnail
        ? `<img class="PickerCardThumb" src="${item.Thumbnail}" alt="">`
        : item.DocName
            ? `<div class="PickerCardDoc"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>${item.DocName}</div>`
            : '';
    const authorHtml = item.AuthorName
        ? `<span class="PickerCardAuthor">${item.AuthorName} &middot; @${item.Username}</span>`
        : '';
    const encAttr = variant === 'moderation' ? `data-enc="${item.EncID}"` : `data-enc="${item.EncID}" data-preview="${(item.Preview||'').replace(/"/g,'&quot;').slice(0,80)}" data-date="${item.Date}"`;

    return `
    <div class="PickerCard ${chosenClass}" data-id="${id}" ${encAttr}>
        ${mediaHtml}
        <div class="PickerCardBody">
            <div class="PickerCardMeta">${typeBadge}${authorHtml}<span class="PickerCardDate">${TimeAgoShort(item.Date)}</span></div>
            <p class="PickerCardText">${item.Preview}</p>
        </div>
        <button class="PickerToggleBtn ${toggleClass}">${toggleLabel}</button>
    </div>`;
}

function CommentCardHTML(item, id, chosenClass, toggleLabel, toggleClass, variant) {
    const hasLongPost = item.PostContent && item.PostContent.length > 120;
    const shortPost   = item.PostContent ? item.PostContent.slice(0, 120) : '';
    const thumbHtml   = item.PostThumbnail
        ? `<img class="PickerPostThumb" src="${item.PostThumbnail}" alt="">`
        : '';
    const authorHtml  = item.AuthorName
        ? `<div class="PickerCardMeta"><span class="PickerCardAuthor">${item.AuthorName} &middot; @${item.Username}</span></div>`
        : '';
    const encAttr = variant === 'moderation' ? `data-enc="${item.EncID}"` : `data-enc="${item.EncID}" data-preview="${(item.Preview||'').replace(/"/g,'&quot;').slice(0,80)}" data-postpreview="${(item.PostContent||'').replace(/"/g,'&quot;').slice(0,60)}" data-date="${item.Date}"`;

    return `
    <div class="PickerCard ${chosenClass}" data-id="${id}" ${encAttr}>
        <div class="PickerCardBody">
            <div class="PickerCommentMain">
                ${authorHtml}
                <p class="PickerCardText">${item.Preview}</p>
                <span class="PickerCardDate">${TimeAgoShort(item.Date)}</span>
            </div>
            <div class="PickerPostContext">
                ${thumbHtml}
                <div class="PickerPostContextText">
                    <span class="PickerContextLabel">Posted on:</span>
                    <p class="PickerPostSnippet">${shortPost}${hasLongPost ? `<span class="PickerPostMore hidden"> ${item.PostContent.slice(120)}</span><button class="PickerExpandBtn">Show more</button>` : ''}</p>
                </div>
            </div>
        </div>
        <button class="PickerToggleBtn ${toggleClass}">${toggleLabel}</button>
    </div>`;
}

// ── Content Moderation ────────────────────────────────────────────────────
let ContentMode = 'posts'; // 'posts' or 'comments'
const BtnPosts    = document.getElementById('ContentTypePosts');
const BtnComments = document.getElementById('ContentTypeComments');

BtnPosts.addEventListener('click', () => {
    ContentMode = 'posts';
    BtnPosts.className    = 'AdminBtn Primary';
    BtnComments.className = 'AdminBtn Ghost';
    LoadContent();
});

BtnComments.addEventListener('click', () => {
    ContentMode = 'comments';
    BtnComments.className = 'AdminBtn Primary';
    BtnPosts.className    = 'AdminBtn Ghost';
    LoadContent();
});

let ContentDebounce = null;
document.getElementById('ContentSearchInput').addEventListener('input', (e) => {
    clearTimeout(ContentDebounce);
    ContentDebounce = setTimeout(() => LoadContent(e.target.value.trim()), 300);
});

async function LoadContent(query = '') {
    const container = document.getElementById('ContentResults');
    container.innerHTML = '<div class="AdminLoading">Loading…</div>';

    const isPost  = ContentMode === 'posts';
    const reqType = isPost ? 8 : 10;
    const data    = await Post(reqType, { query });

    if (!data.success || !data.items || !data.items.length) {
        container.innerHTML = EmptyState('No content found.');
        return;
    }

    container.innerHTML = data.items.map(item => {
        const id = item.id;
        return isPost
            ? PostCardHTML(item, id, '', 'Delete', 'PickerToggleDelete', 'moderation')
            : CommentCardHTML(item, id, '', 'Delete', 'PickerToggleDelete', 'moderation');
    }).join('');

    // Bind "Show more" expand buttons for comment cards
    [...container.getElementsByClassName('PickerExpandBtn')].forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const more = btn.previousElementSibling;
            if (more.classList.contains('hidden')) {
                more.classList.remove('hidden');
                btn.textContent = 'Show less';
            } else {
                more.classList.add('hidden');
                btn.textContent = 'Show more';
            }
        });
    });

    // Bind Delete buttons
    [...container.getElementsByClassName('PickerToggleBtn')].forEach(btn => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.PickerCard');
            OpenDeleteModal(card.dataset.enc, isPost ? 1 : 0);
        });
    });
}

// ── Delete Reason Modal ───────────────────────────────────────────────────
let DeleteTargetEncID = null;
let DeleteTargetIsPost = true;
const DeleteReasonModal = document.getElementById('DeleteReasonModal');

window.OpenDeleteModal = function(encID, isPost) {
    DeleteTargetEncID  = encID;
    DeleteTargetIsPost = isPost === 1;
    document.getElementById('DeleteReasonSub').textContent =
        `You are about to delete this ${DeleteTargetIsPost ? 'post' : 'comment'}. This cannot be undone.`;
    document.getElementById('DeleteReasonInput').value = '';
    DeleteReasonModal.classList.remove('hidden');
};

document.getElementById('DeleteReasonCancel').addEventListener('click', () => DeleteReasonModal.classList.add('hidden'));

document.getElementById('DeleteReasonConfirm').addEventListener('click', async () => {
    const reason = document.getElementById('DeleteReasonInput').value.trim();
    if (!reason) { document.getElementById('DeleteReasonInput').focus(); return; }

    const reqType = DeleteTargetIsPost ? 5 : 6;
    const payload = DeleteTargetIsPost
        ? { PostID: DeleteTargetEncID, Reason: reason }
        : { CommentID: DeleteTargetEncID, Reason: reason };

    const data = await Post(reqType, payload);
    if (data.success) {
        DeleteReasonModal.classList.add('hidden');
        LoadContent(document.getElementById('ContentSearchInput').value.trim());
    }
});

// ── Init ──────────────────────────────────────────────────────────────────
LoadOverview();
