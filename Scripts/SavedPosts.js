import { Submit } from "./Forms.js";
import { createPostHTML, attachPostInteractions } from "./Components/PostCard.js";

function HasReachedBottom(Threshold = 100) {
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    return scrollTop + clientHeight >= scrollHeight - Threshold;
}

document.addEventListener('DOMContentLoaded', () => {
    let IsFetching = false;
    let NoMorePosts = false;
    const Container = document.getElementById('SavedPostsContainer');
    const Loader = document.getElementById('SavedPostsLoader');

    // If the page loaded with no posts, nothing more to fetch
    if (Container.getElementsByClassName('FeedPost').length === 0) {
        NoMorePosts = true;
    }

    const LoadMoreSavedPosts = () => {
        if (IsFetching || NoMorePosts || !HasReachedBottom()) return;

        const Posts = Container.getElementsByClassName('FeedPost');
        if (Posts.length === 0) return;

        IsFetching = true;
        Loader.classList.remove('hidden');

        const LastPostID = Posts[Posts.length - 1].getAttribute('PID');
        const PostFormData = new FormData();
        PostFormData.append('ReqType', 8);
        PostFormData.append('LastPostID', LastPostID);

        Submit('POST', 'Origin/Operations/User.php', PostFormData)
            .then(Data => {
                if (Data && Data.length > 0) {
                    Data.forEach(Post => {
                        Loader.insertAdjacentHTML('beforebegin', createPostHTML(Post));
                        attachPostInteractions(Loader.previousElementSibling);
                    });
                } else {
                    NoMorePosts = true;
                    const Msg = document.createElement('div');
                    Msg.className = 'NoMorePosts';
                    Msg.innerHTML = '<img src="Imgs/Icons/no-saved.svg" alt=""><p>You\'ve seen all your saved posts!</p>';
                    Loader.insertAdjacentElement('beforebegin', Msg);
                }
            })
            .catch(Err => console.error('Fetch Error:', Err))
            .finally(() => {
                IsFetching = false;
                Loader.classList.add('hidden');
            });
    };

    window.addEventListener('scroll', LoadMoreSavedPosts);
});