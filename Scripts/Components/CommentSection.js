import * as FeedApi from "../Api/FeedApi.js";
import { createCommentHTML } from "./CommentRenderer.js";
import { attachCommentInteractions, attachPlainTextPaste } from "./CommentThread.js";
import { attachMentionDropdown, collectMentions } from "./MentionDropdown.js";
import { showInfoBox } from "../Utilities.js";

let CurrentPostID = null;

function ToggleCommentModal(show) {
    const Modal = document.getElementsByClassName('CommentSection')[0];
    if (!Modal) return;
    if (show) {
        Modal.classList.remove('hidden');
        document.body.classList.add('ModalOpen');
    } else {
        Modal.classList.add('hidden');
        document.body.classList.remove('ModalOpen');
        if (typeof Modal._closeMentionDropdown === 'function') Modal._closeMentionDropdown();
    }
}

// Call once on DOMContentLoaded on any page that shows the comment modal
export function initCommentSection() {
    const Modal = document.getElementsByClassName('CommentSection')[0];
    if (!Modal) return;

    // Close button
    const CancelBtn = Modal.getElementsByClassName('ModalCancel')[0];
    const CancelBtnAlt = Modal.getElementsByClassName('ModalCancelBtn')[0];
    if (CancelBtn) CancelBtn.addEventListener('click', () => ToggleCommentModal(false));
    if (CancelBtnAlt) CancelBtnAlt.addEventListener('click', () => ToggleCommentModal(false));

    // Plain-text paste + mention dropdown on comment input
    attachPlainTextPaste(Modal);
    attachMentionDropdown(Modal);

    // has-content CSS class for placeholder toggling
    Modal.addEventListener('input', e => {
        const El = e.target;
        if (!El.classList.contains('CommentInput')) return;
        const Cleaned = El.textContent.replace(/\u00A0/g, '').trim();
        if (Cleaned === '') { El.textContent = ''; El.classList.remove('has-content'); }
        else El.classList.add('has-content');
    });

    // Comment submission
    const CommentForm = document.getElementById('CreateNewComment');
    if (!CommentForm) return;

    CommentForm.addEventListener('submit', e => {
        e.preventDefault();
        const InputEl = CommentForm.getElementsByClassName('CommentInput')[0];
        const Content = InputEl.innerHTML.replace(/<[^>]*>/g, '').trim();
        if (!Content) return;

        const Mentions = collectMentions(InputEl);

        FeedApi.createComment(CurrentPostID, Content, Mentions).then(Data => {
            if (!Data.success) { showInfoBox(Data.message || 'Failed to post comment.', 2); return; }

            InputEl.innerHTML = '';
            InputEl.classList.remove('has-content');

            const CommentsContainer = document.getElementsByClassName('ModalCommentsContainer')[0];
            const NoComments = CommentsContainer.getElementsByClassName('NoComments')[0];
            if (NoComments) NoComments.remove();

            CommentsContainer.insertAdjacentHTML('afterbegin', createCommentHTML(Data.comment));
            attachCommentInteractions(
                CommentsContainer.getElementsByClassName('CommentContainer')[0].closest('.CommentSection')
                || Modal
            );

            document.querySelectorAll(`.FeedPost[PID="${CurrentPostID}"]`).forEach(Post => {
                const Counter = Post.getElementsByClassName('CommentCounter')[0];
                if (Counter) Counter.textContent = parseInt(Counter.textContent) + 1;
            });

            showInfoBox('Comment posted!', 1);
        });
    });
}

// Pass this as onCommentClick when calling attachPostInteractions
export function onCommentClick(PostID) {
    CurrentPostID = PostID;

    FeedApi.fetchComments(PostID).then(Data => {
        const CommentsContainer = document.getElementsByClassName('ModalCommentsContainer')[0];
        CommentsContainer.innerHTML = '';

        if (Data && Data.length > 0) {
            Data.forEach(C => CommentsContainer.insertAdjacentHTML('beforeend', createCommentHTML(C)));
            attachCommentInteractions(document.getElementsByClassName('CommentSection')[0]);
        } else {
            CommentsContainer.innerHTML = `
                <div class="NoComments">
                    <img src="Imgs/Icons/comment.svg" alt="">
                    <h4>No comments yet</h4>
                    <p>Be the first to share your thoughts.</p>
                </div>`;
        }

        ToggleCommentModal(true);
    }).catch(Err => console.error('Error fetching comments:', Err));
}
