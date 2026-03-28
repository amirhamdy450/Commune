import { Submit } from "./Forms.js"; 
import { createPostHTML, createCommentHTML, attachPostInteractions, attachCommentInteractions } from "./Feed.js";

document.addEventListener('DOMContentLoaded', () => {
    const dataDiv = document.getElementById('PageData');
    const container = document.getElementById('SinglePostContainer');

    if (dataDiv && container) {
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
                    commentsHTML = `<div class="NoComments"><p>No comments yet. Be the first!</p></div>`;
                }

                // Build the Inline Container
                // We reuse classes like 'CreateModalComment' to inherit styles, but wrap in 'PostCommentsInline'
                const inlineHTML = `
                    <div class="PostCommentsInline">
                        <div class="ModalCommentsContainer">
                            ${commentsHTML}
                        </div>
                        <form class="CreateModalComment" id="CreateNewComment">
                            <textarea class="CommentInput" placeholder="Write a comment..." rows="1"></textarea>
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

                // D. Handle New Comment Submission (Inline)
                const form = postElement.querySelector('#CreateNewComment');
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const input = form.querySelector('.CommentInput');
                    const content = input.value.trim();
                    if (!content) return;

                    const formData = new FormData();
                    formData.append('ReqType', 3);
                    formData.append('FeedPostID', postData.PID); // PID is encrypted
                    formData.append('CommentContent', content);

                    try {
                        const res = await Submit('POST', 'Origin/Operations/Feed.php', formData);
                        if (res.success) {
                            window.location.reload(); // Simple reload to show new comment state
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