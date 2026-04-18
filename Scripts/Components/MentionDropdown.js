import { searchMentionUsers } from "../Api/SearchApi.js";

// ─── MENTION SYSTEM ──────────────────────────────────────────────────────────
// Attaches @mention dropdown behavior to all contenteditable elements
// inside the given container. Call once per container on init.
export function attachMentionDropdown(container) {
  let mentionState = null; // { input, atOffset, dropdown }
  container._closeMentionDropdown = () => closeDropdown();
  let mentionDebounce = null;

  function getTextBeforeCaret(input) {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return '';
    const caretRange = sel.getRangeAt(0);
    const walker = document.createTreeWalker(input, NodeFilter.SHOW_TEXT);
    let text = '';
    while (walker.nextNode()) {
      const node = walker.currentNode;
      if (node.parentElement && node.parentElement.getAttribute('contenteditable') === 'false') continue;
      if (node === caretRange.endContainer) {
        text += node.textContent.slice(0, caretRange.endOffset);
        break;
      }
      text += node.textContent;
    }
    return text;
  }

  function closeDropdown() {
    if (mentionState && mentionState.dropdown) {
      mentionState.dropdown.remove();
    }
    mentionState = null;
    clearTimeout(mentionDebounce);
  }

  function insertMentionTag(input, atOffset, user) {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;

    const range = sel.getRangeAt(0).cloneRange();
    const walker = document.createTreeWalker(input, NodeFilter.SHOW_TEXT);
    let chars = 0;
    let atNode = null, atNodeOffset = 0;

    while (walker.nextNode()) {
      const node = walker.currentNode;
      if (node.parentElement && node.parentElement.getAttribute('contenteditable') === 'false') continue;
      if (chars + node.length > atOffset) {
        atNode = node;
        atNodeOffset = atOffset - chars;
        break;
      }
      chars += node.length;
    }

    if (!atNode) return;

    const deleteRange = document.createRange();
    deleteRange.setStart(atNode, atNodeOffset);
    deleteRange.setEnd(range.endContainer, range.endOffset);
    deleteRange.deleteContents();

    const tag = document.createElement('span');
    tag.className = 'ReplyTag MentionTag';
    tag.setAttribute('contenteditable', 'false');
    tag.setAttribute('mention-uid', user.EncUID);
    tag.setAttribute('mention-username', user.Username);
    tag.textContent = '@' + user.Username;

    const selAfter = window.getSelection();
    const rangeAfter = selAfter.getRangeAt(0);
    rangeAfter.insertNode(tag);

    rangeAfter.setStartAfter(tag);
    rangeAfter.setEndAfter(tag);

    const space = document.createTextNode('\u00A0');
    rangeAfter.insertNode(space);
    rangeAfter.setStartAfter(space);
    rangeAfter.setEndAfter(space);

    selAfter.removeAllRanges();
    selAfter.addRange(rangeAfter);

    input.dispatchEvent(new Event('input', { bubbles: true }));
    closeDropdown();
  }

  function renderDropdown(input, users, atOffset) {
    if (mentionState && mentionState.dropdown) mentionState.dropdown.remove();
    if (users.length === 0) { closeDropdown(); return; }

    const dropdown = document.createElement('div');
    dropdown.className = 'MentionDropdown';

    users.forEach(user => {
      const item = document.createElement('div');
      item.className = 'MentionItem';

      const followBadge = user.IFollowThem == 1
        ? '<span class="MentionFollowBadge">Follows you</span>'
        : '';
      const blueTick = user.IsBlueTick == 1
        ? '<span class="BlueTick" title="Verified"></span>'
        : '';

      item.innerHTML = `
        <img class="MentionAvatar" src="${user.ProfilePic}" alt="">
        <div class="MentionUserInfo">
          <div class="MentionNameRow">
            <span class="MentionName">${user.Fname} ${user.Lname}</span>
            ${blueTick}
            ${followBadge}
          </div>
          <span class="MentionUsername">@${user.Username}</span>
        </div>
      `;

      item.addEventListener('mousedown', (e) => {
        e.preventDefault();
        insertMentionTag(input, atOffset, user);
      });

      dropdown.appendChild(item);
    });

    const sel = window.getSelection();
    let caretRect = null;
    if (sel && sel.rangeCount > 0) {
      const r = sel.getRangeAt(0).getBoundingClientRect();
      if (r && r.width >= 0 && r.height > 0) caretRect = r;
    }
    const anchorRect = caretRect || input.getBoundingClientRect();
    const spaceBelow = window.innerHeight - anchorRect.bottom;
    const goDown = spaceBelow > 240;

    dropdown.style.width = '272px';
    dropdown.style.left = Math.min(anchorRect.left, window.innerWidth - 280) + 'px';

    if (goDown) {
      dropdown.style.top = (anchorRect.bottom + 6) + 'px';
      dropdown.style.bottom = 'auto';
    } else {
      dropdown.style.bottom = (window.innerHeight - anchorRect.top + 6) + 'px';
      dropdown.style.top = 'auto';
    }

    document.body.appendChild(dropdown);
    if (mentionState) mentionState.dropdown = dropdown;
  }

  async function fetchMentionUsers(query, input, atOffset) {
    try {
      const data = await searchMentionUsers(query);
      if (mentionState && mentionState.input === input) {
        renderDropdown(input, data.users || [], atOffset);
      }
    } catch (e) {
      // Silently ignore network errors in mention lookup
    }
  }

  container.addEventListener('input', (e) => {
    const input = e.target;
    if (input.getAttribute('contenteditable') !== 'true') return;

    const textBefore = getTextBeforeCaret(input);
    const atMatch = textBefore.match(/@([\w]*)$/);

    if (!atMatch) {
      if (mentionState && mentionState.input === input) closeDropdown();
      return;
    }

    const query = atMatch[1];
    const atOffset = textBefore.length - query.length - 1;

    if (mentionState && mentionState.input === input) {
      mentionState.atOffset = atOffset;
    } else {
      closeDropdown();
      mentionState = { input, atOffset, dropdown: null };
    }

    clearTimeout(mentionDebounce);
    const delay = query.length === 0 ? 0 : 250;
    mentionDebounce = setTimeout(() => {
      fetchMentionUsers(query, input, atOffset);
    }, delay);
  });

  container.addEventListener('keydown', (e) => {
    if (!mentionState) return;
    if (e.key === 'Escape') { closeDropdown(); return; }
    if (!mentionState.dropdown) return;

    const items = mentionState.dropdown.getElementsByClassName('MentionItem');
    const focused = mentionState.dropdown.querySelector('.MentionItem.Focused');
    let idx = -1;
    if (focused) idx = [...items].indexOf(focused);

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (focused) focused.classList.remove('Focused');
      const next = items[Math.min(idx + 1, items.length - 1)];
      if (next) next.classList.add('Focused');
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (focused) focused.classList.remove('Focused');
      const prev = items[Math.max(idx - 1, 0)];
      if (prev) prev.classList.add('Focused');
    } else if (e.key === 'Enter' || e.key === 'Tab') {
      const focusedItem = mentionState.dropdown.querySelector('.MentionItem.Focused');
      if (focusedItem) {
        e.preventDefault();
        focusedItem.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
      }
    }
  });

  container.addEventListener('focusout', (e) => {
    if (!mentionState) return;
    if (e.target.getAttribute('contenteditable') !== 'true') return;
    setTimeout(() => {
      if (!mentionState) return;
      const dd = mentionState.dropdown;
      if (dd && dd.contains(document.activeElement)) return;
      closeDropdown();
    }, 150);
  });
}

// Returns an array of encrypted UIDs from all MentionTag spans inside an element
export function collectMentions(inputEl) {
  const tags = inputEl.getElementsByClassName('MentionTag');
  const uids = [];
  const seen = new Set();
  for (const tag of tags) {
    const uid = tag.getAttribute('mention-uid');
    if (uid && !seen.has(uid)) { seen.add(uid); uids.push(uid); }
  }
  return uids;
}
