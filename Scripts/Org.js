import { Submit } from './Forms.js';

function Post(ReqType, Extra = {}) {
    const Fd = new FormData();
    Fd.append('ReqType', ReqType);
    Object.entries(Extra).forEach(([K, V]) => Fd.append(K, V));
    return Submit('POST', 'Origin/Operations/Org.php', Fd);
}

// ── Elements ──────────────────────────────────────────────────────────────
const CreatePageModal  = document.getElementById('CreatePageModal');
const CreatePageClose  = document.getElementById('CreatePageClose');
const CreatePageSubmit = document.getElementById('CreatePageSubmit');
const CreatePageLoader = document.getElementById('CreatePageLoader');
const CreatePageError  = document.getElementById('CreatePageError');
const PageNameInput    = document.getElementById('PageNameInput');
const PageHandleInput  = document.getElementById('PageHandleInput');
const PageHandleHint   = document.getElementById('PageHandleHint');

// ── Open / close ──────────────────────────────────────────────────────────
function OpenCreatePageModal() {
    PageNameInput.value   = '';
    PageHandleInput.value = '';
    document.getElementById('PageCategoryInput').value = '';
    document.getElementById('PageWebsiteInput').value  = '';
    document.getElementById('PageBioInput').value      = '';
    CreatePageError.classList.add('hidden');
    CreatePageError.textContent = '';
    PageHandleHint.textContent  = 'Only letters, numbers and underscores.';
    PageHandleHint.className    = 'CreatePageHint';
    HandleManuallyEdited        = false;
    CreatePageModal.classList.remove('hidden');
}

window.OpenCreatePageModal = OpenCreatePageModal;

CreatePageClose.addEventListener('click', () => CreatePageModal.classList.add('hidden'));
CreatePageModal.addEventListener('click', e => {
    if (e.target === CreatePageModal) CreatePageModal.classList.add('hidden');
});

// ── Auto-generate handle from name ────────────────────────────────────────
let HandleManuallyEdited = false;

PageNameInput.addEventListener('input', () => {
    if (!HandleManuallyEdited) {
        PageHandleInput.value = PageNameInput.value
            .toLowerCase()
            .replace(/\s+/g, '_')
            .replace(/[^a-z0-9_]/g, '')
            .slice(0, 50);
        CheckHandle();
    }
});

PageHandleInput.addEventListener('input', () => {
    HandleManuallyEdited = true;
    CheckHandle();
});

// ── Handle availability check ─────────────────────────────────────────────
let HandleDebounce = null;

function CheckHandle() {
    const Val = PageHandleInput.value.trim();
    clearTimeout(HandleDebounce);
    if (Val.length < 2) {
        PageHandleHint.textContent = 'Only letters, numbers and underscores.';
        PageHandleHint.className   = 'CreatePageHint';
        return;
    }
    PageHandleHint.textContent = 'Checking…';
    PageHandleHint.className   = 'CreatePageHint';
    HandleDebounce = setTimeout(async () => {
        const Data = await Post(2, { Handle: Val });
        if (Data.available) {
            PageHandleHint.textContent = '@' + Val + ' is available';
            PageHandleHint.className   = 'CreatePageHint Available';
        } else {
            PageHandleHint.textContent = '@' + Val + ' is already taken';
            PageHandleHint.className   = 'CreatePageHint Taken';
        }
    }, 400);
}

// ── Submit ────────────────────────────────────────────────────────────────
CreatePageSubmit.addEventListener('click', async () => {
    const Name     = PageNameInput.value.trim();
    const Handle   = PageHandleInput.value.trim();
    const Category = document.getElementById('PageCategoryInput').value;
    const Website  = document.getElementById('PageWebsiteInput').value.trim();
    const Bio      = document.getElementById('PageBioInput').value.trim();

    CreatePageError.classList.add('hidden');

    if (!Name)   { ShowError('Page name is required.');  return; }
    if (!Handle) { ShowError('Handle is required.');     return; }

    CreatePageSubmit.classList.add('hidden');
    CreatePageLoader.classList.remove('hidden');

    const Data = await Post(1, { Name, Handle, Category, Website, Bio });

    CreatePageSubmit.classList.remove('hidden');
    CreatePageLoader.classList.add('hidden');

    if (Data.success) {
        CreatePageModal.classList.add('hidden');
        window.location.href = 'index.php?target=page&handle=' + Data.handle;
    } else {
        ShowError(Data.message || 'Something went wrong. Please try again.');
    }
});

function ShowError(Msg) {
    CreatePageError.textContent = Msg;
    CreatePageError.classList.remove('hidden');
}

// ── Post-as switcher ──────────────────────────────────────────────────────
const PostAsSelector = document.getElementById('PostAsSelector');
const PostAsDropdown = document.getElementById('PostAsDropdown');
const PostAsPageID   = document.getElementById('CPostAsPageID');
const PostAsLabel    = document.getElementById('PostAsLabel');
const PostAsAvatar   = document.getElementById('PostAsAvatar');

let PostAsPagesLoaded = false;

// On mobile, move PostAsDropdown to body to escape overflow:auto clipping (iOS WebKit bug)
if (PostAsDropdown && window.innerWidth <= 750) {
    document.body.appendChild(PostAsDropdown);
}

if (PostAsSelector) {
    PostAsSelector.addEventListener('click', async () => {
        const IsOpen = !PostAsDropdown.classList.contains('hidden');
        if (IsOpen) { PostAsDropdown.classList.add('hidden'); return; }

        if (!PostAsPagesLoaded) {
            const Data = await Post(3);
            if (Data.success && Data.pages.length > 0) {
                Data.pages.forEach(Page => {
                    const Opt = document.createElement('div');
                    Opt.className = 'PostAsOption';
                    Opt.dataset.pageid = Page.EncID;
                    Opt.dataset.label  = 'Posting as ' + Page.Name;
                    Opt.innerHTML = Page.Logo
                        ? `<img src="${Page.Logo}" class="PostAsImg" alt=""><span>${Page.Name}</span>`
                        : `<div class="PostAsLogoPlaceholder">${Page.Name.charAt(0).toUpperCase()}</div><span>${Page.Name}</span>`;
                    PostAsDropdown.appendChild(Opt);
                });
            }
            PostAsPagesLoaded = true;
        }

        // Position fixed below the selector (works after body move on mobile)
        const Rect = PostAsSelector.getBoundingClientRect();
        if (window.innerWidth <= 750) {
            PostAsDropdown.style.top  = (Rect.bottom + 6) + 'px';
            PostAsDropdown.style.left = '16px';
            PostAsDropdown.style.right = '16px';
        } else {
            PostAsDropdown.style.top = PostAsDropdown.style.left = PostAsDropdown.style.right = '';
        }

        PostAsDropdown.classList.remove('hidden');
    });

    document.addEventListener('click', e => {
        if (PostAsSelector && !PostAsSelector.contains(e.target) && PostAsDropdown && !PostAsDropdown.contains(e.target)) {
            PostAsDropdown.classList.add('hidden');
        }
    });
}

if (PostAsDropdown) {
    PostAsDropdown.addEventListener('click', e => {
        const Opt = e.target.closest('.PostAsOption');
        if (!Opt) return;
        SelectPostAsOption(Opt);
        PostAsDropdown.classList.add('hidden');
    });
}

function SelectPostAsOption(Opt) {
    [...document.getElementsByClassName('PostAsOption')].forEach(O => O.classList.remove('Active'));
    Opt.classList.add('Active');
    PostAsPageID.value = Opt.dataset.pageid || '';
    PostAsLabel.textContent = Opt.dataset.label;
    const Img = Opt.querySelector('img');
    const Placeholder = Opt.querySelector('.PostAsLogoPlaceholder');
    if (Img) {
        PostAsAvatar.src = Img.src;
        PostAsAvatar.style.display = '';
    } else if (Placeholder) {
        PostAsAvatar.style.display = 'none';
    }
}

window.SelectPostAsOption = SelectPostAsOption;
