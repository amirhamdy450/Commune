import { Submit } from './Forms.js';

function Post(ReqType, Extra = {}) {
    const Fd = new FormData();
    Fd.append('ReqType', ReqType);
    Object.entries(Extra).forEach(([K, V]) => Fd.append(K, V));
    return Submit('POST', 'Origin/Operations/Org.php', Fd);
}

// ── Tab navigation (reuses same pattern as Profile.js) ───────────────────
const NavItems   = document.getElementsByClassName('NavItem');
const TabContents = document.getElementsByClassName('TabContent');

[...NavItems].forEach(Item => {
    Item.addEventListener('click', e => {
        e.preventDefault();
        [...NavItems].forEach(N => N.classList.remove('Active'));
        [...TabContents].forEach(T => T.classList.add('hidden'));
        Item.classList.add('Active');
        const Target = Item.getAttribute('tab-content');
        document.getElementById(Target)?.classList.remove('hidden');
    });
});

// ── Follow / Unfollow ─────────────────────────────────────────────────────
const FollowBtns = document.getElementsByClassName('PageFollowBtn');

[...FollowBtns].forEach(Btn => {
    Btn.addEventListener('click', async () => {
        const PageID    = Btn.dataset.pageid;
        const Following = Btn.classList.contains('Followed');
        Btn.disabled    = true;

        const Data = await Post(4, { PageID, Action: Following ? 0 : 1 });

        if (Data.success) {
            if (Following) {
                Btn.classList.remove('Followed');
                Btn.textContent = 'Follow';
            } else {
                Btn.classList.add('Followed');
                Btn.textContent = 'Following';
            }
            const CountEl = document.querySelector('.ProfileStats .Stat:nth-child(2) .StatNumber');
            if (CountEl) CountEl.textContent = Data.followers;
        }

        Btn.disabled = false;
    });
});
