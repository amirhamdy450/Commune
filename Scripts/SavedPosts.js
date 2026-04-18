import { Submit } from "./Forms.js";
import { createPostHTML, attachPostInteractions } from "./Components/PostCard.js";

function hasReachedBottom(threshold = 150) {
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    return scrollTop + clientHeight >= scrollHeight - threshold;
}

document.addEventListener('DOMContentLoaded', () => {
    let isFetching = false;
    let noMorePosts = false;
    const container = document.getElementById('SavedPostsContainer');
    const loader = document.getElementById('SavedPostsLoader');

    const loadMoreSavedPosts = () => {
        if (isFetching || noMorePosts) return;

        if (hasReachedBottom(100)) {
            isFetching = true;
            loader.classList.remove('hidden');

            setTimeout(() => {
                const posts = container.getElementsByClassName('FeedPost');
                if (posts.length === 0) {
                    isFetching = false;
                    return;
                }

                const lastPost = posts[posts.length - 1];
                const lastPostID = lastPost.getAttribute('PID');

                const formData = new FormData();
                formData.append('ReqType', 8); // Assuming ReqType 8 in User.php
                formData.append('LastPostID', lastPostID);

                Submit('POST', 'Origin/Operations/User.php', formData)
                    .then(data => {
                        if (data && data.length > 0) {
                            data.forEach(post => {
                                const postHTML = createPostHTML(post);
                                loader.insertAdjacentHTML('beforebegin', postHTML);
                                const newPostElement = loader.previousElementSibling;
                                attachPostInteractions(newPostElement);
                            });

                            setTimeout(loadMoreSavedPosts, 100);
                        } else {
                            noMorePosts = true;
                            if (!container.querySelector('.NoMorePosts')) {
                                loader.insertAdjacentHTML('beforebegin', '<p class="NoMorePosts">No more saved posts.</p>');
                            }
                        }
                    })
                    .catch(err => console.error("Fetch Error:", err))
                    .finally(() => {
                        isFetching = false;
                        loader.classList.add('hidden');
                    });
            }, 500);
        }
    };

    window.addEventListener('scroll', loadMoreSavedPosts);
    
    // Initial check
    setTimeout(loadMoreSavedPosts, 500);
});