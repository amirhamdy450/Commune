import { attachPostInteractions, createPostHTML } from './Components/PostCard.js';
import { attachCommentInteractions } from './Components/CommentThread.js';
import { toggleFollowPage, checkPageHandleAvailability, updatePage, fetchMorePagePosts } from './Api/OrgApi.js';

// ── Attach interactions to all server-rendered posts ─────────────────────
[...document.getElementsByClassName('FeedPost')].forEach(Post => attachPostInteractions(Post));
attachCommentInteractions();

// ── Tab navigation ────────────────────────────────────────────────────────
const NavItems    = document.getElementsByClassName('NavItem');
const TabContents = document.getElementsByClassName('TabContent');

[...NavItems].forEach(Item => {
    Item.addEventListener('click', e => {
        e.preventDefault();
        [...NavItems].forEach(N => N.classList.remove('Active'));
        [...TabContents].forEach(T => T.classList.add('hidden'));
        Item.classList.add('Active');
        document.getElementById(Item.getAttribute('tab-content'))?.classList.remove('hidden');
    });
});

// ── Follow / Unfollow ─────────────────────────────────────────────────────
[...document.getElementsByClassName('PageFollowBtn')].forEach(Btn => {
    Btn.addEventListener('click', async () => {
        const PageID    = Btn.dataset.pageid;
        const Following = Btn.classList.contains('Followed');
        Btn.disabled    = true;

        const Data = await toggleFollowPage(PageID, Following ? 0 : 1);

        if (Data.success) {
            Btn.classList.toggle('Followed', !Following);
            Btn.textContent = Following ? 'Follow' : 'Following';
            const CountEl = document.querySelector('.ProfileStats .Stat:nth-child(2) .StatNumber');
            if (CountEl) CountEl.textContent = Data.followers;
        }
        Btn.disabled = false;
    });
    // Hover: show "Unfollow" when already following
    Btn.addEventListener('mouseenter', () => { if (Btn.classList.contains('Followed')) Btn.textContent = 'Unfollow'; });
    Btn.addEventListener('mouseleave', () => { if (Btn.classList.contains('Followed')) Btn.textContent = 'Following'; });
});

// ── Manage Page / Edit Page modal ─────────────────────────────────────────
const ManageBtn      = document.getElementById('PageManageBtn');
const EditPageModal  = document.getElementById('EditPageModal');
const EditPageClose  = document.getElementById('EditPageClose');
const EditPageSubmit = document.getElementById('EditPageSubmit');
const EditPageLoader = document.getElementById('EditPageLoader');
const EditPageError  = document.getElementById('EditPageError');

let PendingLogo  = null;
let PendingCover = null;

const PageOwnerMoreBtn = document.getElementById('PageOwnerMoreBtn');
const PageOwnerMenu    = document.getElementById('PageOwnerMenu');
if (PageOwnerMoreBtn && PageOwnerMenu) {
    PageOwnerMoreBtn.addEventListener('click', e => {
        e.stopPropagation();
        PageOwnerMenu.classList.toggle('Open');
    });
    document.addEventListener('click', e => {
        if (!PageOwnerMoreBtn.contains(e.target) && !PageOwnerMenu.contains(e.target)) {
            PageOwnerMenu.classList.remove('Open');
        }
    });
}

if (ManageBtn && EditPageModal) {
    ManageBtn.addEventListener('click', () => EditPageModal.classList.remove('hidden'));
    EditPageClose.addEventListener('click', () => EditPageModal.classList.add('hidden'));
    EditPageModal.addEventListener('click', e => { if (e.target === EditPageModal) EditPageModal.classList.add('hidden'); });

    // Logo preview
    document.getElementById('EditPageLogoUpload').addEventListener('change', function () {
        const File = this.files[0];
        if (!File) return;
        PendingLogo = File;
        const Url = URL.createObjectURL(File);
        const Img = document.getElementById('EditPageLogoImg');
        const Placeholder = document.getElementById('EditPageLogoPlaceholder');
        Img.src = Url;
        Img.classList.remove('hidden');
        if (Placeholder) Placeholder.classList.add('hidden');
    });

    // Cover preview
    document.getElementById('EditPageCoverUpload').addEventListener('change', function () {
        const File = this.files[0];
        if (!File) return;
        PendingCover = File;
        const Url = URL.createObjectURL(File);
        const Img = document.getElementById('EditPageCoverImg');
        const Wrap = document.getElementById('EditPageCoverPreview');
        Img.src = Url;
        Img.classList.remove('hidden');
        Wrap.classList.remove('PageCoverDefault');
    });

    // Handle availability check
    let HandleDebounce = null;
    document.getElementById('EditPageHandleInput').addEventListener('input', function () {
        const Val = this.value.trim();
        const Hint = document.getElementById('EditPageHandleHint');
        clearTimeout(HandleDebounce);
        if (Val.length < 2) { Hint.textContent = 'Only letters, numbers and underscores.'; Hint.className = 'CreatePageHint'; return; }
        Hint.textContent = 'Checking…'; Hint.className = 'CreatePageHint';
        HandleDebounce = setTimeout(async () => {
            const Data = await checkPageHandleAvailability(Val);
            if (Data.available) { Hint.textContent = '@' + Val + ' is available'; Hint.className = 'CreatePageHint Available'; }
            else { Hint.textContent = '@' + Val + ' is taken'; Hint.className = 'CreatePageHint Taken'; }
        }, 400);
    });

    // Submit
    EditPageSubmit.addEventListener('click', async () => {
        EditPageError.classList.add('hidden');
        const PageID   = window.PageContext.PageID;
        const Name     = document.getElementById('EditPageNameInput').value.trim();
        const Handle   = document.getElementById('EditPageHandleInput').value.trim();
        const Category = document.getElementById('EditPageCategoryInput').value;
        const Website  = document.getElementById('EditPageWebsiteInput').value.trim();
        const Bio      = document.getElementById('EditPageBioInput').value.trim();

        if (!Name) { ShowEditError('Page name is required.'); return; }
        if (!Handle) { ShowEditError('Handle is required.'); return; }

        EditPageSubmit.classList.add('hidden');
        EditPageLoader.classList.remove('hidden');

        const Data = await updatePage(PageID, { Name, Handle, Category, Website, Bio }, { Logo: PendingLogo, Cover: PendingCover });

        EditPageSubmit.classList.remove('hidden');
        EditPageLoader.classList.add('hidden');

        if (Data.success) {
            EditPageModal.classList.add('hidden');
            // If handle changed, redirect to new URL
            if (Data.handle !== window.PageContext.Handle) {
                window.location.href = 'index.php?target=page&handle=' + encodeURIComponent(Data.handle);
            } else {
                window.location.reload();
            }
        } else {
            ShowEditError(Data.message || 'Something went wrong.');
        }
    });
}

function ShowEditError(Msg) {
    EditPageError.textContent = Msg;
    EditPageError.classList.remove('hidden');
}

// ── Infinite scroll ───────────────────────────────────────────────────────
const PagePostsContainer = document.getElementById('PagePostsTab');
let PageScrollLoading    = false;
let PageScrollExhausted  = false;

function GetLastPostID() {
    const Posts = PagePostsContainer ? PagePostsContainer.getElementsByClassName('FeedPost') : [];
    return Posts.length ? Posts[Posts.length - 1].getAttribute('PID') : null;
}

if (PagePostsContainer && window.PageContext) {
    window.addEventListener('scroll', async () => {
        if (PageScrollLoading || PageScrollExhausted) return;
        const DistanceFromBottom = document.documentElement.scrollHeight - window.scrollY - window.innerHeight;
        if (DistanceFromBottom > 300) return;

        const LastPID = GetLastPostID();
        if (!LastPID) return;

        PageScrollLoading = true;
        const Loader = document.querySelector('.FeedLoader');
        if (Loader) Loader.classList.remove('hidden');

        const Data = await fetchMorePagePosts(window.PageContext.PageID, LastPID);

        if (Loader) Loader.classList.add('hidden');
        PageScrollLoading = false;

        if (!Data.success || !Data.posts.length) { PageScrollExhausted = true; return; }

        Data.posts.forEach(P => {
            PagePostsContainer.insertAdjacentHTML('beforeend', createPostHTML(P));
            attachPostInteractions(PagePostsContainer.lastElementChild);
        });

        if (Data.posts.length < 10) PageScrollExhausted = true;
    });
}

// ── CreatePostBtn: open modal pre-selected as this page ───────────────────
[...document.getElementsByClassName('CreatePostBtn')].forEach(Btn => {
    Btn.addEventListener('click', async () => {
        // Wait for PostAs dropdown to be populated (Org.js lazy-loads it)
        const Selector = document.getElementById('PostAsSelector');
        if (!Selector) return;

        // Trigger the selector click to load pages if not yet loaded
        Selector.click();
        // Give it a tick to populate, then find and select this page
        await new Promise(R => setTimeout(R, 50));
        Selector.click(); // close dropdown again

        const PageID = window.PageContext.PageID;
        const Opts   = document.getElementsByClassName('PostAsOption');
        let Found    = false;
        for (const Opt of Opts) {
            if (Opt.dataset.pageid === PageID) {
                if (window.SelectPostAsOption) window.SelectPostAsOption(Opt);
                Found = true;
                break;
            }
        }
        // If pages not loaded yet, retry once after a short delay
        if (!Found) {
            await new Promise(R => setTimeout(R, 400));
            for (const Opt of document.getElementsByClassName('PostAsOption')) {
                if (Opt.dataset.pageid === PageID) {
                    if (window.SelectPostAsOption) window.SelectPostAsOption(Opt);
                    break;
                }
            }
        }
    });
});
