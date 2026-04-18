// Shared post card renderer and interaction module.
// All surfaces that need to display or interact with feed posts should import from here.

import { TimeAgo } from "../Forms.js";
import * as FeedApi from "../Api/FeedApi.js";
import { confirmBlock, confirmDelete } from "./ConfirmActions.js";
import { mountActionMenu } from "./ActionMenu.js";
import { showInfoBox } from "../Utilities.js";
import { VisibilityIcons, VisibilityTitles } from "../Constants.js";

// Converts @username mentions in plain text to clickable profile links
function renderMentions(text) {
  if (!text) return '';
  return text.replace(/@([\w]+)/g, '<a class="MentionLink" href="index.php?target=profile&username=$1">@$1</a>');
}

// Generates media content HTML based on post type
function generateMediaContent(post) {
  let mediaContent = '';
  if (post.MediaFolder && post.MediaFolder.length > 0) {
    if (parseInt(post.MediaType) === 2) {
      post.MediaFolder.forEach(image => {
        mediaContent += `<img src="${image.path}" alt="">`;
      });
    } else if (parseInt(post.MediaType) === 3) {
      post.MediaFolder.forEach(doc => {
        const ext = doc.name.split('.').pop().toUpperCase();
        const nameNoExt = doc.name.replace(/\.[^/.]+$/, '');
        mediaContent += `
          <a href="${doc.path}" class="FeedPostLink" target="_blank" rel="noopener">
            <div class="UploadedFile">
              <div class="UploadedFileIcon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
              </div>
              <div class="UploadedFileBody">
                <div class="UploadedFileName">${nameNoExt}</div>
                <div class="UploadedFileExt">${ext} Document</div>
              </div>
              <svg class="UploadedFileArrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </div>
          </a>`;
      });
    }
  }
  return mediaContent;
}

// Attach hover text swap on a .Followed button
export function AttachFollowHover(btn) {
  btn.addEventListener('mouseenter', () => {
    if (btn.classList.contains('Followed')) btn.textContent = 'Unfollow';
  });
  btn.addEventListener('mouseleave', () => {
    if (btn.classList.contains('Followed')) btn.textContent = 'Following';
  });
}

export async function FollowHandler(followButton, uid) {
  const data = await FeedApi.toggleFollowUser(uid);
  if (data.success) {
    if (!data.Followed) {
      followButton.classList.remove('Followed');
      followButton.classList.add('FollowBtn');
      followButton.textContent = 'Follow';
    } else {
      followButton.classList.remove('FollowBtn');
      followButton.classList.add('Followed');
      followButton.textContent = 'Following';
    }
  }
}

async function blockUser(uid, postElement) {
  const data = await FeedApi.blockUser(uid);
  if (data.success) {
    postElement.remove();
  }
}

async function savePost(post, postID) {
  const data = await FeedApi.toggleSavePost(postID);
  if (data.success) {
    showInfoBox(data.message, 1);
    post.setAttribute('Saved', data.Saved ? '1' : '0');
  }
}

async function deletePostElement(post, postID) {
  const data = await FeedApi.deletePost(postID);
  if (data.success) {
    post.remove();
    showInfoBox('Post deleted.', 1);
  } else {
    showInfoBox(data.message || 'Failed to delete post.', 2);
  }
}

// Creates HTML for a single post card
export function createPostHTML(post) {
  const mediaContent = generateMediaContent(post);
  const likeIcon = post.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';

  let followbtn = '';
  if (post.Self == 0) {
    const following = post.following ? 'Followed' : '';
    const follow_text = post.following ? 'Following' : 'Follow';
    followbtn = `<button class="BrandBtn FollowBtn ${following}"> ${follow_text}</button>`;
  }

  const PageIconSVG = `<svg class="FeedPageIcon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Page"><path d="M3 2v12M3 2h8.5l-2 3.5 2 3.5H3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

  const authorHeader = post.PageName
    ? `<a class="FeedPageBadge" href="index.php?target=page&handle=${encodeURIComponent(post.PageHandle)}">
        ${post.PageLogo
          ? `<img class="FeedPageLogo" src="${post.PageLogo}" alt="">`
          : `<div class="FeedPageLogoPlaceholder">${post.PageName.charAt(0).toUpperCase()}</div>`}
        <div class="FeedPostAuthorInfo">
          <div class="FeedPostNameRow">
            <p class="FeedPostAuthorName">${post.PageName}</p>
            ${post.PageIsVerified ? '<span class="BlueTick" title="Verified"></span>' : ''}
            ${PageIconSVG}
            ${post.Date ? `<span class="FeedPostTime" data-date="${post.Date}"></span>` : ''}
          </div>
          <span class="FeedPostUsername">@${post.PageHandle}</span>
        </div>
      </a>`
    : `<a class="FeedPostAuthor" href="index.php?redirected_from=profile&target=profile&uid=${encodeURIComponent(post.UID)}">
        <img src="${post.ProfilePic}" alt="">
        <div class="FeedPostAuthorInfo">
          <div class="FeedPostNameRow">
            <p class="FeedPostAuthorName">${post.name}</p>
            ${post.IsBlueTick ? '<span class="BlueTick" title="Verified"></span>' : ''}
            ${post.Date ? `<span class="FeedPostTime">&middot; ${TimeAgo(post.Date)}</span>` : ''}
          </div>
          ${post.Username ? `<span class="FeedPostUsername">@${post.Username}</span>` : ''}
        </div>
      </a>
      ${followbtn}`;

  return `
    <div class="FeedPost${post.PageName ? ' PageFeedPost' : ''}" PID="${post.PID}" UID="${post.UID}" Self="${post.Self}" Saved="${post.saved ?? post.Saved ?? 0}">
      <div class="FeedPostHeader">
        <div class="FeedPostAuthorContainer">
          ${authorHeader}
        </div>
        <div class="FeedPostHeaderRight">
          ${post.Self && post.Visibility > 0 ? `<span class="VisibilityBadge" title="${VisibilityTitles[post.Visibility] || ''}">${VisibilityIcons[post.Visibility] || ''}${VisibilityTitles[post.Visibility] || ''}</span>` : ''}
          <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>
        </div>
      </div>
      <div class="FeedPostContent">
        <p>${renderMentions(post.Content)}</p>
        ${mediaContent}
      </div>
      <div class="FeedPostInteractionCounters">
        <p><span class="PostLikesCNT">${post.LikeCounter}</span> likes</p>
        <p>${post.CommentCounter} Comments</p>
      </div>
      <div class="FeedPostInteractions">
        <div class="Interaction FeedPostLike">
          <img src="${likeIcon}"> Like
        </div>
        <div class="Interaction FeedPostComment">
          <img src="Imgs/Icons/comment.svg"> Comment
        </div>
        <div class="Interaction FeedPostShare">
          <img src="Imgs/Icons/share.svg"> Share
        </div>
      </div>
    </div>`;
}

// Attaches interaction event listeners to a post element.
// onCommentClick: optional callback invoked when the comment button is clicked (receives postId).
// onDeletePost: optional callback invoked when the user confirms a post deletion (receives postId).
// onEditPost: optional callback invoked when the user chooses to edit a post (receives postId). Falls back to window.openEditPostModal if not provided.
export function attachPostInteractions(post, { onCommentClick, onDeletePost, onEditPost } = {}) {
  const postId = post.getAttribute('PID');
  const uid = post.getAttribute('UID');
  const followButton = post.getElementsByClassName('FollowBtn')[0];
  const likeButton = post.getElementsByClassName('FeedPostLike')[0];
  const commentButton = post.getElementsByClassName('FeedPostComment')[0];
  const shareButton = post.getElementsByClassName('FeedPostShare')[0];
  const actionButton = post.getElementsByClassName('ActionBtn')[0];

  if (followButton) {
    followButton.addEventListener('click', () => FollowHandler(followButton, uid));
    AttachFollowHover(followButton);
  }

  likeButton.addEventListener('click', () => {
    FeedApi.likePost(postId)
      .then(data => {
        if (data.success) {
          const likesCountElement = post.getElementsByClassName('PostLikesCNT')[0];
          let likesCount = parseInt(likesCountElement.innerHTML);
          likesCount += parseInt(data.Insertion);
          likesCountElement.innerHTML = likesCount;
          const likeIcon = likeButton.getElementsByTagName('img')[0];
          likeIcon.src = data.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';
        }
      })
      .catch(error => console.error('Error:', error));
  });

  commentButton.addEventListener('click', () => {
    if (typeof onCommentClick === 'function') {
      onCommentClick(postId);
    }
  });

  shareButton.addEventListener('click', async () => {
    const Url = window.location.href.split('?')[0];
    const ShareLink = `${Url}?target=post&pid=${encodeURIComponent(postId)}`;
    try {
      await navigator.clipboard.writeText(ShareLink);
      shareButton.innerHTML = `<img src="Imgs/Icons/Checkmark.svg" alt=""> Copied !`;
      setTimeout(() => {
        shareButton.innerHTML = `<img src="Imgs/Icons/share.svg" alt=""> Share`;
      }, 2000);
    } catch (error) {
      showInfoBox('Failed to copy link. Please try again.', 2);
    }
  });

  if (actionButton) {
    actionButton.addEventListener('click', (e) => {
      e.stopPropagation();

      const existingMenu = document.querySelector('.ActionMenu.PostContext');
      if (existingMenu) {
        existingMenu.remove();
        return;
      }

      const postAuthorUID = post.getAttribute('UID');
      const postPID = post.getAttribute('PID');
      const IsSaved = post.getAttribute('Saved') == 1;
      const isSelfPost = post.getAttribute('Self') == 1;
      const isSinglePostView = document.body.classList.contains('PostView');

      let menuOptions = '';
      if (!isSinglePostView) {
        menuOptions += `<div class="ActionOption" data-action="hide"><img src="Imgs/Icons/EyeOff.svg">Hide Post</div>`;
      }
      if (!IsSaved) {
        menuOptions += `<div class="ActionOption" data-action="save" data-pid="${postPID}"><img src="Imgs/Icons/save.svg">Save Post</div>`;
      } else {
        menuOptions += `<div class="ActionOption" data-action="save" data-pid="${postPID}"><img src="Imgs/Icons/unsave.svg">Unsave Post</div>`;
      }
      if (!isSelfPost) {
        menuOptions += `<div class="ActionOption" data-action="block" data-uid="${postAuthorUID}"><img src="Imgs/Icons/block.svg">Block User</div>`;
      }
      if (isSelfPost) {
        menuOptions += `<div class="ActionOption Delete" data-action="delete" data-pid="${postPID}"><img src="Imgs/Icons/trash.svg">Delete Post</div>`;
        menuOptions += `<div class="ActionOption" data-action="edit" data-pid="${postPID}"><img src="Imgs/Icons/edit.svg">Edit Post</div>`;
      }

      mountActionMenu({
        selector: '.ActionMenu.PostContext',
        className: 'ActionMenu PostContext',
        html: menuOptions,
        parent: post.getElementsByClassName('FeedPostHeader')[0],
        onClick: (evt) => {
          evt.stopPropagation();
          const target = evt.target.closest('.ActionOption');
          if (!target) return;
          const action = target.dataset.action;

          if (action === 'hide') {
            post.style.display = 'none';
          } else if (action === 'save') {
            savePost(post, postId);
          } else if (action === 'block') {
            const PostAuthor = post.getElementsByClassName('FeedPostAuthor')[0];
            const Name = PostAuthor ? PostAuthor.getElementsByTagName('p')[0].innerText : '';
            confirmBlock({
              Name,
              Action: 'refresh',
              onConfirm: async () => await blockUser(uid, post)
            });
          } else if (action === 'edit') {
            const editFn = onEditPost || (typeof window.openEditPostModal === 'function' ? window.openEditPostModal : null);
            if (editFn) editFn(target.dataset.pid);
          } else if (action === 'delete') {
            const pid = target.dataset.pid;
            confirmDelete({
              Title: 'Delete This Post?',
              onConfirm: async () => {
                if (typeof onDeletePost === 'function') {
                  await onDeletePost(pid);
                } else {
                  await deletePostElement(post, pid);
                }
              }
            });
          }
        }
      });
    });
  }
}
