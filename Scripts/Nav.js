// At the top of Scripts/Nav.js
let searchDebounceTimer;
const NavCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';


function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function preg_quote(str, delimiter) {
  // Escapes regex special characters
  return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}

function getNotifText(type) {
    switch(type) {
        case 1: return "liked your post.";
        case 2: return "commented on your post.";
        case 3: return "replied to a comment.";
        case 4: return "started following you.";
        case 5: return "liked your comment.";
        case 6: return "liked your reply.";
        case 7: return "mentioned you.";
        case 11: return "Security Alert.";
        default: return "New interaction.";
    }
}

// Helper to build Redirect URL
function getNotifLink(n) {
    // Post Related (Likes, Comments, Replies, Mentions)
    if ([1, 2, 3, 5, 6, 7].includes(n.Type)) {
        return `index.php?target=post&pid=${encodeURIComponent(n.RefID)}`;
    }
    // User Related (Follow)
    if (n.Type === 4) {
        return `index.php?target=profile&uid=${encodeURIComponent(n.FromUID)}`;
    }
    return '#';
}

document.addEventListener('DOMContentLoaded', () => {
	const NavMenuDropBtn = document.getElementById('NavMenuDropBtn');		
	const NavMenuDrop = document.getElementById('NavMenuDrop');
	
    let MyPagesLoaded = false;
    const NavMyPagesList = document.getElementById('NavMyPagesList');

	NavMenuDropBtn.addEventListener("click", () => {
		NavMenuDrop.classList.toggle("hidden");
        if (!NavMenuDrop.classList.contains('hidden') && !MyPagesLoaded && NavMyPagesList) {
            LoadMyPages();
        }
	});

    function LoadMyPages() {
        MyPagesLoaded = true;
        const Fd = new FormData();
        Fd.append('ReqType', 3);
        fetch('Origin/Operations/Org.php', { method: 'POST', headers: { 'X-CSRF-Token': NavCsrfToken }, body: Fd })
            .then(r => r.json())
            .then(data => {
                if (!data.success || data.pages.length === 0) {
                    NavMyPagesList.innerHTML = '<div class="NavPagesEmpty">No pages yet</div>';
                    return;
                }
                NavMyPagesList.innerHTML = data.pages.map(p => {
                    const Logo = p.Logo
                        ? `<img src="${p.Logo}" class="NavPageLogo" alt="">`
                        : `<div class="NavPageLogoPlaceholder">${p.Name.charAt(0).toUpperCase()}</div>`;
                    return `<a class="DropdownItem NavPageItem" href="index.php?target=page&handle=${encodeURIComponent(p.Handle)}">
                        ${Logo}
                        <span>${htmlspecialchars(p.Name)}</span>
                    </a>`;
                }).join('');
            })
            .catch(() => { NavMyPagesList.innerHTML = ''; });
    }

    const NavCreatePageBtn = document.getElementById('NavCreatePageBtn');
    if (NavCreatePageBtn) {
        NavCreatePageBtn.addEventListener('click', () => {
            NavMenuDrop.classList.add('hidden');
            if (typeof OpenCreatePageModal === 'function') OpenCreatePageModal();
        });
    }
    
    // --- ADD ALL THE CODE BELOW ---

    const searchInput = document.getElementById('NavSearchInput');
    const suggestionsBox = document.getElementById('SearchSuggestions');

    if (searchInput && suggestionsBox) {
        
        // Listen for user typing
        searchInput.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            
            const query = searchInput.value.trim();
            
            if (query.length < 2) {
                suggestionsBox.classList.add('hidden');
                return;
            }
            
            // Debounce: Wait 300ms after user stops typing
            searchDebounceTimer = setTimeout(() => {
                fetchSearchResults(query, suggestionsBox);
            }, 300);
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target)) {
                suggestionsBox.classList.add('hidden');
            }
        });

        // Show suggestions when focusing the input (if it has text)
        searchInput.addEventListener('focus', () => {
             if (searchInput.value.trim().length >= 2) {
                fetchSearchResults(searchInput.value.trim(), suggestionsBox);
             }
        });

        // Navigate on Enter
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                const Q = searchInput.value.trim();
                if (Q.length >= 2) window.location.href = 'index.php?target=search&query=' + encodeURIComponent(Q);
            }
        });
    }




    // --- 2. NOTIFICATION SYSTEM ---
    const notifBtn       = document.getElementById('NotifBtn');
    const notifDrop      = document.getElementById('NotifDrop');
    const notifBadge     = document.getElementById('NotifBadge');
    const MobileNotifBadgeEl = document.getElementById('MobileNotifBadge');
    const notifList      = document.getElementById('NotifList');
    const notifLoader    = notifDrop ? notifDrop.querySelector('.NotifLoader') : null;

    function OpenNotifDrop() {
        notifDrop.classList.toggle('hidden');
        document.getElementById('NavMenuDrop')?.classList.add('hidden');
        if (!notifDrop.classList.contains('hidden')) {
            if (notifBadge) notifBadge.classList.add('hidden');
            if (MobileNotifBadgeEl) MobileNotifBadgeEl.classList.add('hidden');
            fetchNotifications();
        }
    }

    if (notifDrop) {
        fetchUnreadCount();

        if (notifBtn) {
            notifBtn.addEventListener('click', (e) => { e.stopPropagation(); OpenNotifDrop(); });
        }

        document.addEventListener('click', (e) => {
            const MobileNotifBtn = document.getElementById('MobileNotifBtn');
            const clickedInsideDrop = notifDrop.contains(e.target);
            const clickedNotifBtn   = notifBtn?.contains(e.target);
            const clickedMobileBtn  = MobileNotifBtn?.contains(e.target);
            if (!clickedInsideDrop && !clickedNotifBtn && !clickedMobileBtn) {
                notifDrop.classList.add('hidden');
            }
            if (NavMenuDrop && !NavMenuDropBtn.contains(e.target) && !NavMenuDrop.contains(e.target)) {
                NavMenuDrop.classList.add('hidden');
            }
        });
    }

    function fetchUnreadCount() {
        const formData = new FormData();
        formData.append('ReqType', 6);

        fetch('Origin/Operations/User.php', { method: 'POST', headers: { 'X-CSRF-Token': NavCsrfToken }, body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.count > 0) {
                    const Label = data.count > 99 ? '99+' : data.count;
                    if (notifBadge) { notifBadge.textContent = Label; notifBadge.classList.remove('hidden'); }
                    if (MobileNotifBadgeEl) MobileNotifBadgeEl.classList.remove('hidden');
                } else {
                    if (notifBadge) notifBadge.classList.add('hidden');
                    if (MobileNotifBadgeEl) MobileNotifBadgeEl.classList.add('hidden');
                }
            })
            .catch(err => console.error("Notif count error", err));
    }

    // ── Mobile search overlay ─────────────────────────────────────────────
    const MobileSearchBtn     = document.getElementById('MobileSearchBtn');
    const MobileSearchOverlay = document.getElementById('MobileSearchOverlay');
    const MobileSearchBack    = document.getElementById('MobileSearchBack');
    const MobileSearchInput   = document.getElementById('MobileNavSearchInput');
    const MobileSearchSuggs   = document.getElementById('MobileSearchSuggestions');

    if (MobileSearchBtn && MobileSearchOverlay) {
        MobileSearchBtn.addEventListener('click', () => {
            MobileSearchOverlay.classList.remove('hidden');
            setTimeout(() => MobileSearchInput && MobileSearchInput.focus(), 80);
        });
        MobileSearchBack.addEventListener('click', () => {
            MobileSearchOverlay.classList.add('hidden');
            if (MobileSearchInput) MobileSearchInput.value = '';
            if (MobileSearchSuggs) MobileSearchSuggs.classList.add('hidden');
        });
    }

    if (MobileSearchInput && MobileSearchSuggs) {
        let MobileDebounce = null;
        MobileSearchInput.addEventListener('input', () => {
            clearTimeout(MobileDebounce);
            const Q = MobileSearchInput.value.trim();
            if (Q.length < 2) { MobileSearchSuggs.classList.add('hidden'); return; }
            MobileDebounce = setTimeout(() => fetchSearchResults(Q, MobileSearchSuggs), 300);
        });
        MobileSearchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                const Q = MobileSearchInput.value.trim();
                if (Q.length >= 2) window.location.href = 'index.php?target=search&query=' + encodeURIComponent(Q);
            }
        });
    }

    // ── Mobile notif button ───────────────────────────────────────────────
    const MobileNotifBtn = document.getElementById('MobileNotifBtn');
    if (MobileNotifBtn && notifDrop) {
        MobileNotifBtn.addEventListener('click', (e) => { e.stopPropagation(); OpenNotifDrop(); });
    }

    // Notification close button (mobile full-screen)
    const NotifCloseBtn = document.getElementById('NotifCloseBtn');
    if (NotifCloseBtn && notifDrop) {
        NotifCloseBtn.addEventListener('click', () => notifDrop.classList.add('hidden'));
    }

    // ── Mobile menu drawer ────────────────────────────────────────────────
    const MobileMenuDrawer   = document.getElementById('MobileMenuDrawer');
    const MobileMenuBackdrop = document.getElementById('MobileMenuBackdrop');
    const MobileMyPagesList  = document.getElementById('MobileMyPagesList');
    let MobileMenuPagesLoaded = false;

    function OpenMobileMenu() {
        if (!MobileMenuDrawer) return;
        MobileMenuDrawer.classList.remove('hidden');
        if (!MobileMenuPagesLoaded) LoadMobilePages();
    }
    function CloseMobileMenu() {
        if (!MobileMenuDrawer) return;
        MobileMenuDrawer.classList.add('hidden');
    }

    function LoadMobilePages() {
        MobileMenuPagesLoaded = true;
        const Fd = new FormData();
        Fd.append('ReqType', 3);
        fetch('Origin/Operations/Org.php', { method: 'POST', headers: { 'X-CSRF-Token': NavCsrfToken }, body: Fd })
            .then(r => r.json())
            .then(data => {
                if (!MobileMyPagesList) return;
                if (!data.success || data.pages.length === 0) {
                    MobileMyPagesList.innerHTML = '<div style="padding:6px 20px;font-size:13px;color:#b0b8c4;">No pages yet</div>';
                    return;
                }
                MobileMyPagesList.innerHTML = data.pages.map(p => {
                    const Logo = p.Logo
                        ? `<img src="${p.Logo}" class="MobileMenuPageLogo" alt="">`
                        : `<div class="MobileMenuPageLogoPlaceholder">${p.Name.charAt(0).toUpperCase()}</div>`;
                    return `<a class="MobileMenuPageItem" href="index.php?target=page&handle=${encodeURIComponent(p.Handle)}">${Logo}<span>${htmlspecialchars(p.Name)}</span></a>`;
                }).join('');
            })
            .catch(() => {});
    }

    if (MobileMenuBackdrop) MobileMenuBackdrop.addEventListener('click', CloseMobileMenu);

    const MobileCreatePageBtn = document.getElementById('MobileCreatePageBtn');
    if (MobileCreatePageBtn) {
        MobileCreatePageBtn.addEventListener('click', () => {
            CloseMobileMenu();
            if (typeof OpenCreatePageModal === 'function') OpenCreatePageModal();
        });
    }

    // The profile avatar tab on mobile opens the menu drawer instead of navigating
    const MobileProfileTab = document.querySelector('.MobileTab .MobileTabAvatar')?.closest('.MobileTab');
    if (MobileProfileTab && MobileMenuDrawer) {
        MobileProfileTab.addEventListener('click', e => {
            e.preventDefault();
            OpenMobileMenu();
        });
    }

    function fetchNotifications() {
        notifList.innerHTML = '';
        notifLoader.classList.remove('hidden');

        const formData = new FormData();
        formData.append('ReqType', 7);

        fetch('Origin/Operations/User.php', { method: 'POST', headers: { 'X-CSRF-Token': NavCsrfToken }, body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.notifications.length === 0) {
                        notifList.innerHTML = '<div style="padding:20px; text-align:center; color:#888;">No notifications yet.</div>';
                        return;
                    }

                    let html = '';
                    data.notifications.forEach(n => {
                        const text = n.MetaInfo ? n.MetaInfo : `<b>${n.ActorName}</b> ${getNotifText(n.Type)}`;
                        const link = getNotifLink(n);
                        
                        html += `
                        <a href="${link}" class="NotifItem">
                            <img src="${n.ActorPic}" alt="">
                            <div class="NotifContent">
                                <span>${text}</span>
                                <span class="NotifDate">${n.Date}</span>
                            </div>
                        </a>`;
                    });
                    notifList.innerHTML = html;
                }
            })
            .catch(err => console.error("Fetch notif error", err))
            .finally(() => notifLoader.classList.add('hidden'));
    }
});

async function fetchSearchResults(query, suggestionsBox) {
    if (!suggestionsBox) return;
    const formData = new FormData();
    formData.append('query', query);
	formData.append('ReqType', 1); // This is correct

    try {
        const response = await fetch('Origin/Operations/Search.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': NavCsrfToken },
            body: formData
        });
        const data = await response.json();

        suggestionsBox.innerHTML = ''; // Clear previous

        // --- Populate Users (Unchanged) ---
        if (data.users && data.users.length > 0) {
            suggestionsBox.innerHTML += `<div class="SuggestionSectionTitle">People</div>`;
            data.users.forEach(user => {
                const userHtml = `
                    <a class="SearchSuggestionItem UserSearch" href="index.php?target=profile&uid=${encodeURIComponent(user.uid)}">
                        <img src="${user.ProfilePic}" alt="${user.Username}">
                        <div class="UserInfo">
                            <span class="Name">${htmlspecialchars(user.Fname)} ${htmlspecialchars(user.Lname)}</span>
                            <span class="Username">@${htmlspecialchars(user.Username)}</span>
                        </div>
                    </a>
                `;
                suggestionsBox.innerHTML += userHtml;
            });
        }

        // --- Populate Topics (FIXED) ---
        if (data.topics && data.topics.length > 0) {
            suggestionsBox.innerHTML += `<div class="SuggestionSectionTitle">Topics</div>`;
            
            // This loop now renders ALL topics from the server
            data.topics.forEach(topic => {
                
                // 1. Use the 'type' to show a different icon
                let icon = (topic.type === 'full_search') ? 'search.svg' : 'trending.svg';
                let queryHTML = htmlspecialchars(topic.query);

                // 2. Bold the part of the suggestion that matches the user's query
                // This makes the smart suggestions easier to understand
                if (topic.type === 'suggestion') {
                    const regex = new RegExp(`(${preg_quote(query)})`, 'gi');
                    queryHTML = queryHTML.replace(regex, '<strong>$1</strong>');
                } else {
                    // For the "full_search" type, bold the whole thing
                    queryHTML = `<strong>${queryHTML}</strong>`;
                }

                const topicHtml = `
                    <a class="SearchSuggestionItem TopicSearch" href="${topic.url}">
                        <img src="Imgs/Icons/${icon}" alt="Topic">
                        <span>${queryHTML}</span>
                    </a>
                `;
                suggestionsBox.innerHTML += topicHtml;
            });
        }
        
        // Show box if we have *any* results
        if ((data.users && data.users.length > 0) || (data.topics && data.topics.length > 0)) {
            suggestionsBox.classList.remove('hidden');
        } else {
            suggestionsBox.classList.add('hidden');
        }

    } catch (error) {
        console.error('Error fetching search results:', error);
        suggestionsBox.classList.add('hidden');
    }
}