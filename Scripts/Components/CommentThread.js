import * as FeedApi from "../Api/FeedApi.js";
import { createCommentHTML } from "./CommentRenderer.js";
import { confirmDelete } from "./ConfirmActions.js";
import { mountActionMenu } from "./ActionMenu.js";
import { showInfoBox } from "../Utilities.js";
import { collectMentions } from "./MentionDropdown.js";

export function attachPlainTextPaste(container) {
  container.addEventListener('paste', (e) => {
    const target = e.target;
    if (target.getAttribute('contenteditable') !== 'true') return;

    e.preventDefault();
    const plainText = e.clipboardData ? e.clipboardData.getData('text/plain') : '';
    if (!plainText) return;

    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return;

    const range = selection.getRangeAt(0);
    range.deleteContents();

    const textNode = document.createTextNode(plainText);
    range.insertNode(textNode);
    range.setStartAfter(textNode);
    range.setEndAfter(textNode);
    selection.removeAllRanges();
    selection.addRange(range);

    target.dispatchEvent(new Event('input', { bubbles: true }));
  });
}

function createReplyForm(replyToUserID = '', replyToUsername = '') {
  const replyTag = replyToUserID && replyToUsername
    ? `<span class="ReplyTag" contenteditable="false" replyto="${replyToUserID}">@${replyToUsername}</span>`
    : '';

  const hasContent = replyTag ? ' has-content' : '';
  const placeholder = replyTag ? '' : 'Reply to comment';
  return `
    <form class="CreateModalComment CreateCommentReply">
      <div contenteditable="true" class="CommentInput${hasContent}" placeholder="${placeholder}" rows="1">${replyTag}</div>
      <input type="submit" value="" class="BrandBtn CommentSubmitBtn">
    </form>
  `;
}

function updateReplyTarget(createReplyFormEl, replyToUserID, replyToUsername) {
  const commentInput = createReplyFormEl.getElementsByClassName("CommentInput")[0];
  commentInput.setAttribute("placeholder", "");
  commentInput.classList.add("has-content");

  const replyTag = commentInput.getElementsByClassName("ReplyTag")[0];
  if (replyTag) {
    replyTag.innerHTML = "@" + replyToUsername;
    replyTag.setAttribute("replyto", replyToUserID);
    return;
  }

  commentInput.insertAdjacentHTML("beforeend", `<span class="ReplyTag" contenteditable="false" replyto="${replyToUserID}">@${replyToUsername}</span>`);
}

function stripReplyText(inputEl) {
  return inputEl.innerHTML
    .replace(/<[^>]+contenteditable="false"[^>]*>.*?<\/[^>]+>/gi, '')
    .replace(/<[^>]*>/g, '')
    .trim();
}

function mountCommentMenu({ type, id, element, isSelf }) {
  const header = element.querySelector('.ModalCommentHeader');
  if (!header) return;

  const existingMenu = document.querySelector('.ActionMenu.CommentContext');
  if (existingMenu) {
    existingMenu.remove();
    return;
  }

  let menuHTML = `
    <div class="ActionOption" data-action="hide">
      <img src="Imgs/Icons/EyeOff.svg"> Hide
    </div>
  `;

  if (isSelf) {
    menuHTML += `
      <div class="ActionOption Delete" data-action="delete">
        <img src="Imgs/Icons/trash.svg"> Delete
      </div>
    `;
  }

  mountActionMenu({
    selector: '.ActionMenu.CommentContext',
    className: 'ActionMenu CommentContext',
    html: menuHTML,
    parent: header,
    onClick: async (evt) => {
      evt.stopPropagation();
      const option = evt.target.closest('.ActionOption');
      if (!option) return;

      const action = option.dataset.action;
      if (action === 'hide') {
        element.style.display = 'none';
        return;
      }

      if (action === 'delete') {
        confirmDelete({
          Title: 'Delete this?',
          onConfirm: async () => {
            const data = type === 1
              ? await FeedApi.deleteComment(id)
              : await FeedApi.deleteReply(id);

            if (data.success) {
              element.remove();
            } else {
              showInfoBox(data.message || 'Failed to delete.', 2);
            }
          }
        });
      }
    }
  });
}

function attachReplyFormSubmission(createReplyFormEl, commentID, parentComment) {
  const commentInputEl = createReplyFormEl.getElementsByClassName('CommentInput')[0];
  commentInputEl.addEventListener('input', () => {
    const hasText = commentInputEl.innerHTML.replace(/<[^>]*>/g, '').trim() !== ''
      || commentInputEl.getElementsByClassName('ReplyTag').length > 0;
    commentInputEl.classList.toggle('has-content', hasText);
  });

  createReplyFormEl.addEventListener('submit', async (e) => {
    e.preventDefault();

    const commentInputEl = createReplyFormEl.getElementsByClassName('CommentInput')[0];
    const reply = stripReplyText(commentInputEl);
    if (!reply) return;

    const mentions = collectMentions(commentInputEl);
    const replyTag = commentInputEl.getElementsByClassName('ReplyTag')[0];
    const replyTo = replyTag ? replyTag.getAttribute('replyto') : null;
    const data = await FeedApi.createReply(commentID, reply, mentions, replyTo);

    if (data.success) {
      createReplyFormEl.remove();

      const repliesContainer = parentComment.getElementsByClassName('RepliesContainer')[0];
      repliesContainer.classList.remove('hidden');

      if (data.reply) {
        repliesContainer.insertAdjacentHTML('beforeend', createCommentHTML(data.reply, 2));
        const replies = repliesContainer.getElementsByClassName('CommentContainer');
        const newReply = replies[replies.length - 1];
        attachReplyInteractions(newReply, parentComment);
      }

      const viewRepliesButton = parentComment.getElementsByClassName('ViewRepliesBtn')[0];
      if (viewRepliesButton) {
        const currentCount = parseInt(viewRepliesButton.textContent) || 0;
        viewRepliesButton.textContent = (currentCount + 1) + ' Replies';
      }

      showInfoBox('Reply posted!', 1);
    } else {
      showInfoBox(data.message || 'Failed to post reply.', 2);
    }
  });
}

function ensureReplyForm(parentComment, commentID, replyToUserID = '', replyToUsername = '') {
  let createReply = parentComment.getElementsByClassName("CreateCommentReply")[0];

  if (!createReply) {
    parentComment.insertAdjacentHTML("beforeend", createReplyForm(replyToUserID, replyToUsername));
    createReply = parentComment.getElementsByClassName("CreateCommentReply")[0];
    attachReplyFormSubmission(createReply, commentID, parentComment);
    return createReply;
  }

  if (replyToUserID && replyToUsername) {
    updateReplyTarget(createReply, replyToUserID, replyToUsername);
  }

  return createReply;
}

export function attachReplyInteractions(reply, parentComment) {
  if (reply.dataset.bound === '1') return;
  reply.dataset.bound = '1';

  const likeButton = reply.getElementsByClassName("FeedPostLike")[0];
  const commentButton = reply.getElementsByClassName("FeedPostComment")[0];
  const attrs = reply.getElementsByClassName('CMDI073')[0];
  const replyID = attrs.getAttribute('crid');
  const replyUserID = attrs.getAttribute('uid');
  const menuBtn = reply.querySelector('.ActionBtn');
  const modalComment = reply.getElementsByClassName('ModalComment')[0];

  if (menuBtn) {
    menuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isSelf = modalComment.getAttribute('Self') === '1';
      mountCommentMenu({ type: 2, id: replyID, element: reply, isSelf });
    });
  }

  likeButton.addEventListener("click", () => {
    FeedApi.likeReply(replyID)
      .then((data) => {
        if (!data.success) return;
        const likesCountElement = reply.getElementsByClassName("CommentLikesCNT")[0];
        let likesCount = parseInt(likesCountElement.innerHTML);
        likesCount += parseInt(data.Insertion);
        likesCountElement.innerHTML = likesCount;
        const likeIcon = likeButton.getElementsByTagName("img")[0];
        likeIcon.src = data.liked ? "Imgs/Icons/liked.svg" : "Imgs/Icons/like.svg";
      })
      .catch((error) => console.error("Error:", error));
  });

  commentButton.addEventListener("click", () => {
    const replyTo = reply.getElementsByClassName("username-readonly")[0]?.innerHTML || '';
    ensureReplyForm(parentComment, parentComment.getElementsByClassName('CMDI073')[0].getAttribute('cid'), replyUserID, replyTo);
  });
}

export function attachCommentInteractions(specificContainer = null) {
  const container = specificContainer || document.getElementsByClassName('CommentSection')[0];
  if (!container) return;

  const comments = container.getElementsByClassName('CommentContainer');
  [...comments].forEach(comment => {
    if (comment.classList.contains('Reply')) return;
    if (comment.dataset.bound === '1') return;
    comment.dataset.bound = '1';

    const likeButton = comment.getElementsByClassName('FeedPostLike')[0];
    const commentButton = comment.getElementsByClassName('FeedPostComment')[0];
    const viewRepliesButton = comment.getElementsByClassName('ViewRepliesBtn')[0];
    const attrs = comment.getElementsByClassName('CMDI073')[0];
    const commentID = attrs.getAttribute('cid');
    const modalComment = comment.getElementsByClassName('ModalComment')[0];
    const menuBtn = comment.querySelector('.ActionBtn');

    if (menuBtn) {
      menuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isSelf = modalComment.getAttribute('Self') === '1';
        mountCommentMenu({ type: 1, id: commentID, element: comment, isSelf });
      });
    }

    likeButton.addEventListener('click', () => {
      FeedApi.likeComment(commentID)
        .then(data => {
          if (!data.success) return;
          const likesCountElement = comment.getElementsByClassName('CommentLikesCNT')[0];
          let likesCount = parseInt(likesCountElement.innerHTML);
          likesCount += parseInt(data.Insertion);
          likesCountElement.innerHTML = likesCount;
          const likeIcon = likeButton.getElementsByTagName('img')[0];
          likeIcon.src = data.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';
        })
        .catch(error => console.error('Error:', error));
    });

    commentButton.addEventListener('click', () => {
      ensureReplyForm(comment, commentID);
    });

    if (viewRepliesButton) {
      viewRepliesButton.addEventListener('click', () => {
        const repliesContainer = comment.getElementsByClassName('RepliesContainer')[0];
        repliesContainer.classList.remove('hidden');
        if (repliesContainer.dataset.loaded === '1') return;

        FeedApi.fetchReplies(commentID).then(data => {
          repliesContainer.classList.remove('hidden');
          repliesContainer.dataset.loaded = '1';

          data.forEach(reply => {
            repliesContainer.insertAdjacentHTML('beforeend', createCommentHTML(reply, 2));
            const replies = repliesContainer.getElementsByClassName('CommentContainer');
            const lastInsertedReply = replies[replies.length - 1];
            attachReplyInteractions(lastInsertedReply, comment);
          });
        });
      });
    }
  });
}
