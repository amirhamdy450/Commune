import { TimeAgo } from "./Forms.js";
import * as FeedApi from "./Api/FeedApi.js";
import { createCommentHTML } from "./Components/CommentRenderer.js";
import { initPostEditorModal, openEditPostModal } from "./Components/PostEditorModal.js";
import { attachMentionDropdown, collectMentions } from "./Components/MentionDropdown.js";
import { attachCommentInteractions, attachPlainTextPaste } from "./Components/CommentThread.js";
import { createPostHTML, attachPostInteractions, FollowHandler, AttachFollowHover } from "./Components/PostCard.js";
import { showInfoBox } from "./Utilities.js";

// Page-local state
let currentPostID = null;

// Check if pid is set in the URL (single-post deep-link open)
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('pid')) {
  currentPostID = urlParams.get('pid');
}

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
  let feedOffset = document.getElementsByClassName('FeedPost').length;
  const loader = document.getElementsByClassName('FeedLoader')[0];
  const feedContainer = document.getElementsByClassName('FeedContainer')[0];

  return () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100 && !isFetching && !noMorePosts) {
      isFetching = true;
      loader.classList.remove('hidden');

      setTimeout(async () => {
        const allFeedPosts = document.getElementsByClassName('FeedPost');
        if (allFeedPosts.length === 0) {
          loader.classList.add('hidden');
          isFetching = false;
          return;
        }
        try {
          const data = await FeedApi.fetchFeedPage(feedOffset);
          if (data.length > 0) {
            data.forEach(post => {
              feedContainer.insertAdjacentHTML('beforeend', createPostHTML(post));
              attachFeedPostInteractions(feedContainer.lastElementChild);
            });
            feedOffset += data.length;
            feedContainer.appendChild(loader);
          } else {
            noMorePosts = true;
            loader.classList.add('hidden');
            const msg = document.createElement('div');
            msg.className = 'NoMorePosts';
            msg.textContent = 'No more posts to load.';
            feedContainer.appendChild(msg);
          }
        } catch (error) {
          console.error('Error fetching feed page:', error);
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
    onCommentClick: (postId) => {
      FeedApi.fetchComments(postId).then(data => {
        const commentsContainer = document.getElementsByClassName('ModalCommentsContainer')[0];
        commentsContainer.innerHTML = '';
        if (data && data.length !== 0) {
          data.forEach(comment => commentsContainer.insertAdjacentHTML('beforeend', createCommentHTML(comment)));
          attachCommentInteractions(document.getElementsByClassName('CommentSection')[0]);
        } else {
          commentsContainer.insertAdjacentHTML('beforeend', `
            <div class="NoComments">
              <img src="Imgs/Icons/comment.svg" alt="">
              <h4>No comments yet</h4>
              <p>Be the first to share your thoughts.</p>
            </div>
          `);
        }
        toggleModal(document.getElementsByClassName('CommentSection')[0], true);
      }).catch(error => console.error('Error:', error));
      currentPostID = postId;
    }
  });
}


document.addEventListener('DOMContentLoaded', () => {
  // Modal cancel buttons
  const modals = document.getElementsByClassName('Modal');
  [...modals].forEach(modal => {
    if (modal.classList.contains('Confirm')) return;
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

  // Comment submission
  const commentForm = document.getElementById('CreateNewComment');
  commentForm.addEventListener('submit', e => {
    e.preventDefault();
    const commentInputEl = commentForm.getElementsByClassName('CommentInput')[0];
    const commentContent = commentInputEl.innerHTML.replace(/<[^>]*>/g, '').trim();
    if (!commentContent) return;

    const Mentions = collectMentions(commentInputEl);

    FeedApi.createComment(currentPostID, commentContent, Mentions).then(data => {
      if (data.success) {
        commentInputEl.innerHTML = '';
        commentInputEl.classList.remove('has-content');

        const commentsContainer = document.getElementsByClassName('ModalCommentsContainer')[0];
        const noComments = commentsContainer.getElementsByClassName('NoComments')[0];
        if (noComments) noComments.remove();

        commentsContainer.insertAdjacentHTML('afterbegin', createCommentHTML(data.comment));
        attachCommentInteractions(
          commentsContainer.getElementsByClassName('CommentContainer')[0].closest('.CommentSection')
          || document.getElementsByClassName('CommentSection')[0]
        );

        const activePosts = document.querySelectorAll(`.FeedPost[PID="${currentPostID}"]`);
        activePosts.forEach(post => {
          const counter = post.getElementsByClassName('CommentCounter')[0];
          if (counter) counter.textContent = parseInt(counter.textContent) + 1;
        });

        showInfoBox('Comment posted!', 1);
      } else {
        showInfoBox(data.message || 'Failed to post comment.', 2);
      }
    });
  });

  // Attach interactions to server-rendered posts
  const feedPosts = document.getElementsByClassName('FeedPost');
  [...feedPosts].forEach(post => attachFeedPostInteractions(post));

  // Hydrate server-rendered post timestamps
  document.querySelectorAll('.FeedPostTime[data-date]').forEach(el => {
    el.textContent = TimeAgo(parseInt(el.getAttribute('data-date'), 10));
  });

  // Plain-text paste + @mention dropdown on post modal and comment section
  const CommentSectionModal = document.getElementsByClassName('CommentSection')[0];
  attachPlainTextPaste(createPostModal);
  attachPlainTextPaste(CommentSectionModal);
  attachMentionDropdown(CommentSectionModal);

  // has-content class for CSS placeholder toggling
  CommentSectionModal.addEventListener('input', event => {
    const el = event.target;
    if (!el.classList.contains('CommentInput')) return;
    const cleanedText = el.textContent.replace(/\u00A0/g, '').trim();
    if (cleanedText === '') {
      el.textContent = '';
      el.classList.remove('has-content');
    } else {
      el.classList.add('has-content');
    }
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
