import * as FeedApi from "./Api/FeedApi.js";
import { createCommentHTML } from "./Components/CommentRenderer.js";
import { createPostHTML, attachPostInteractions } from "./Components/PostCard.js";
import { attachCommentInteractions, attachPlainTextPaste } from "./Components/CommentThread.js";
import { attachMentionDropdown } from "./Components/MentionDropdown.js";


document.addEventListener('DOMContentLoaded', () => {
    const dataDiv = document.getElementById('PageData');
    const container = document.getElementById('SinglePostContainer');

    if (dataDiv && container) {
        const isRestricted = dataDiv.getAttribute('data-restricted') === '1';

        if (isRestricted) {
            container.innerHTML = `
                <div class="PostRestricted">
                    <div class="PostRestrictedIcon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <h2 class="PostRestrictedTitle">This post is private</h2>
                    <p class="PostRestrictedMsg">The author has limited who can see this post. You don't have permission to view it.</p>
                    <a href="index.php" class="BrandBtn PostRestrictedBtn">Back to Feed</a>
                </div>`;
            return;
        }

        const rawData = dataDiv.getAttribute('data-payload');

        if (rawData) {
            try {
                const pageData = JSON.parse(rawData);
                const postData = pageData.post;
                const commentsData = pageData.comments;

                // 1. Render the Post Card
                container.innerHTML = createPostHTML(postData);
                const postElement = container.firstElementChild;
                
                // Add the "Opened" class for specific styling (white space, width, etc.)
                postElement.classList.add('Opened');

                // 2. Disable the "Comment" button functionality visually and logically
                const commentBtn = postElement.querySelector('.FeedPostComment');
                if(commentBtn) {
                    // Remove hover/pointer events via class
                    // We will also intercept the click below in attachPostInteractions logic if needed,
                    // but since we are attaching interactions manually, we can override it here.
                }

                // 3. Render Comments Inline (The "Cont")
                let commentsHTML = '';
                if (commentsData && commentsData.length > 0) {
                    commentsData.forEach(c => {
                        commentsHTML += createCommentHTML(c);
                    });
                } else {
                    commentsHTML = `<div class="NoComments"><img src="Imgs/Icons/comment.svg" alt=""><h4>No comments yet</h4><p>Be the first to share your thoughts.</p></div>`;
                }

                // Build the Inline Container
                // We reuse classes like 'CreateModalComment' to inherit styles, but wrap in 'PostCommentsInline'
                const inlineHTML = `
                    <div class="PostCommentsInline">
                        <div class="ModalCommentsContainer">
                            ${commentsHTML}
                        </div>
                        <form class="CreateModalComment" id="CreateNewComment">
                            <div contenteditable="true" class="CommentInput" placeholder="Write a comment..."></div>
                            <input type="submit" value="" class="BrandBtn CommentSubmitBtn">
                        </form>
                    </div>
                `;

                // Append Inline Container to the Post
                postElement.insertAdjacentHTML('beforeend', inlineHTML);

                // 4. Attach Listeners
                
                // A. Post Interactions (Like, Share, etc.)
                attachPostInteractions(postElement);
                
                // B. Override the Comment Button on the card to focus the input instead of opening modal
                if(commentBtn) {
                    // Clone to strip existing listeners if any were attached
                    const newBtn = commentBtn.cloneNode(true);
                    commentBtn.parentNode.replaceChild(newBtn, commentBtn);
                    
                    newBtn.addEventListener('click', () => {
                        const input = postElement.querySelector('.CommentInput');
                        if(input) input.focus();
                    });
                }

                // C. Comment Interactions (Like Comment, Reply)
                // We pass the SPECIFIC container so we don't accidentally target hidden modals
                const inlineCommentsContainer = postElement.querySelector('.PostCommentsInline');
                attachCommentInteractions(inlineCommentsContainer);

                // Intercept paste in reply inputs to strip rich HTML and insert plain text only
                attachPlainTextPaste(inlineCommentsContainer);

                // Enable @mention dropdown for all reply inputs inside the inline comment section
                attachMentionDropdown(inlineCommentsContainer);

                // D. Handle New Comment Submission (Inline) — live DOM insert, no reload
                const form = postElement.querySelector('#CreateNewComment');

                // Toggle has-content so the CSS placeholder shows/hides correctly
                form.addEventListener('input', (e) => {
                    const el = e.target;
                    if (el.getAttribute('contenteditable') !== 'true') return;
                    if (el.innerHTML.replace(/<[^>]*>/g, '').trim()) {
                        el.classList.add('has-content');
                    } else {
                        el.classList.remove('has-content');
                    }
                });

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const input = form.querySelector('.CommentInput');
                    // Read from contenteditable div — strip HTML tags to get plain text
                    const content = input.innerHTML.replace(/<[^>]*>/g, '').trim();
                    if (!content) return;

                    try {
                        const res = await FeedApi.createComment(postData.PID, content);
                        if (res.success && res.comment) {
                            input.innerHTML = '';
                            input.classList.remove('has-content');

                            // Remove empty state if present
                            const commentsContainer = postElement.querySelector('.ModalCommentsContainer');
                            const noComments = commentsContainer.querySelector('.NoComments');
                            if (noComments) noComments.remove();

                            // Live-insert the new comment at the top
                            commentsContainer.insertAdjacentHTML('afterbegin', createCommentHTML(res.comment));

                            // Attach interactions to the newly inserted comment
                            attachCommentInteractions(inlineCommentsContainer);
                        } else if (res.success) {
                            // Fallback: fetch failed but comment was saved — reload as last resort
                            window.location.reload();
                        }
                    } catch (err) {
                        console.error(err);
                    }
                });

            } catch (e) {
                console.error("Error parsing post data", e);
                container.innerHTML = "<p class='Error'>Failed to load post.</p>";
            }
        } else {
            container.innerHTML = "<p style='text-align:center; padding:20px'>Post not found.</p>";
        }
    }
});