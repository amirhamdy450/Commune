import { TimeAgo } from "./Forms.js";
import * as FeedApi from "./Api/FeedApi.js";
import { initPostEditorModal, openEditPostModal } from "./Components/PostEditorModal.js";
import { attachPlainTextPaste } from "./Components/CommentThread.js";
import { createPostHTML, attachPostInteractions, FollowHandler, AttachFollowHover } from "./Components/PostCard.js";
import { showInfoBox } from "./Utilities.js";
import { initCommentSection, onCommentClick } from "./Components/CommentSection.js";

// Toggles modal visibility — page-local since it touches body.ModalOpen
function toggleModal(modal, show) {
  if (show) {
    modal.classList.remove('hidden');
    document.body.classList.add('ModalOpen');
  } else {
    modal.classList.add('hidden');
    document.body.classList.remove('ModalOpen');
    if (typeof modal._closeMentionDropdown === 'function') modal._closeMentionDropdown();
  }
}

// Fetches the next page of posts when the user scrolls to the bottom
function fetchMorePosts() {
  let isFetching = false;
  let noMorePosts = false;
  const feedContainer = document.getElementsByClassName('FeedContainer')[0];
  const loader = document.getElementsByClassName('FeedLoader')[0];

  // If the page loaded with zero posts, the feed is already empty — lock immediately
  if (document.getElementsByClassName('FeedPost').length === 0) {
    noMorePosts = true;
  }

  return () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100 && !isFetching && !noMorePosts) {
      isFetching = true;
      loader.classList.remove('hidden');

      setTimeout(async () => {
        const feedOffset = document.getElementsByClassName('FeedPost').length;
        try {
          const data = await FeedApi.fetchFeedPage(feedOffset);
          if (data.length > 0) {
            data.forEach(post => {
              feedContainer.insertAdjacentHTML('beforeend', createPostHTML(post));
              attachFeedPostInteractions(feedContainer.lastElementChild);
            });
            // Keep loader pinned at the bottom so the scroll trigger works
            feedContainer.appendChild(loader);
          } else {
            noMorePosts = true;
            loader.classList.add('hidden');
            const msg = document.createElement('div');
            msg.className = 'NoMorePosts';
            msg.innerHTML = '<img src="Imgs/Icons/no-posts.svg" alt=""><p>You\'ve seen all the posts!</p>';
            feedContainer.appendChild(msg);
          }
        } catch (error) {
          console.error('Error fetching feed page:', error);
          loader.classList.add('hidden');
        } finally {
          isFetching = false;
        }
      }, 500);
    }
  };
}

// Wraps attachPostInteractions with feed-page specific action handlers
function attachFeedPostInteractions(post) {
  attachPostInteractions(post, {
    onDeletePost: async (pid) => {
      const data = await FeedApi.deletePost(pid);
      if (data.success) window.location.reload();
    },
    onEditPost: (pid) => openEditPostModal(pid),
    onCommentClick,
  });
}


document.addEventListener('DOMContentLoaded', () => {
  // Modal cancel buttons (CommentSection cancel is handled by initCommentSection)
  const modals = document.getElementsByClassName('Modal');
  [...modals].forEach(modal => {
    if (modal.classList.contains('Confirm')) return;
    if (modal.classList.contains('CommentSection')) return;
    const cancelBtn = modal.getElementsByClassName('ModalCancel')[0];
    const cancelBtnAlt = modal.getElementsByClassName('ModalCancelBtn')[0];
    if (cancelBtn) cancelBtn.addEventListener('click', () => toggleModal(modal, false));
    if (cancelBtnAlt) cancelBtnAlt.addEventListener('click', () => toggleModal(modal, false));
  });

  // Post editor modal (create + edit)
  const createPostModal = document.getElementsByClassName('CPostContainer')[0];
  initPostEditorModal({
    modal: createPostModal,
    onToggleModal: toggleModal,
    showInfoBox,
    createPostHTML,
    attachPostInteractions: attachFeedPostInteractions,
    attachPlainTextPaste,
  });

  initCommentSection();

  // Attach interactions to server-rendered posts
  const feedPosts = document.getElementsByClassName('FeedPost');
  [...feedPosts].forEach(post => attachFeedPostInteractions(post));

  // Hydrate server-rendered post timestamps
  document.querySelectorAll('.FeedPostTime[data-date]').forEach(el => {
    el.textContent = TimeAgo(parseInt(el.getAttribute('data-date'), 10));
  });

  // Post view tracking (scroll-position based, 50% visible for 1.5s = view)
  if (document.body.classList.contains('FetchPostsOnScroll')) {
    const ViewedPIDs = new Set();
    const ViewTimers = new Map();

    function CheckPostsInView() {
      const VH = window.innerHeight;
      const Posts = document.getElementsByClassName('FeedPost');
      for (let i = 0; i < Posts.length; i++) {
        const post = Posts[i];
        const pid = post.getAttribute('pid');
        if (!pid || ViewedPIDs.has(pid)) continue;

        const rect = post.getBoundingClientRect();
        const visible = Math.min(rect.bottom, VH) - Math.max(rect.top, 0);
        const ratio = visible / rect.height;

        if (ratio >= 0.5) {
          if (!ViewTimers.has(pid)) {
            ViewTimers.set(pid, setTimeout(() => {
              if (ViewedPIDs.has(pid)) return;
              ViewedPIDs.add(pid);
              ViewTimers.delete(pid);
              FeedApi.recordPostView(pid).catch(() => {});
            }, 1500));
          }
        } else {
          if (ViewTimers.has(pid)) {
            clearTimeout(ViewTimers.get(pid));
            ViewTimers.delete(pid);
          }
        }
      }
    }

    const ScrollHandler = fetchMorePosts();
    window.addEventListener('scroll', () => {
      ScrollHandler();
      CheckPostsInView();
    });

    CheckPostsInView();
  }

  // Follow button on the profile preview sidebar (VProfile body id)
  if (document.body.id === 'VProfile') {
    const followButton = document.getElementsByClassName('FollowBtn')[0];
    if (followButton) {
      const uid = followButton.getAttribute('uid');
      followButton.addEventListener('click', () => FollowHandler(followButton, uid));
      AttachFollowHover(followButton);
    }
  }
});
