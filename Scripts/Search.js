// Scripts/Search.js
import { Submit } from "./Forms.js";
import { createPostHTML, attachPostInteractions } from "./Feed.js";

// We need createPostHTML from Feed.js.
// Since it's not exported, I'll copy a simplified version here.
// In a real refactor, we'd move createPostHTML to a shared 'Utils.js' module.

// --- START HELPER FUNCTIONS ---

/* function createPostHTML(post) {
  const likeIcon = post.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';
  let followBtn = '';
  
  if (post.Self == 0) {
    const following = post.following ? 'Followed' : '';
    const follow_text = post.following ? 'Following' : 'Follow';
    followBtn = `<button class="BrandBtn FollowBtn ${following}" uid="${post.UID}"> ${follow_text}</button>`;
  }

  // Note: Media content logic is simplified here.
  // We're assuming FormatPostForClient in PHP formats 'Content'
  // and we're not handling images/docs in this snippet.
  const mediaContent = ''; // TODO: Add media rendering if needed

  return `
    <div class="FeedPost" PID="${post.PID}" UID="${post.UID}" Self="${post.Self}" Saved="${post.saved}">
      <div class="FeedPostHeader">
        <div class="FeedPostAuthorContainer">
          <a class="FeedPostAuthor" href="index.php?target=profile&uid=${encodeURIComponent(post.UID)}">
            <img src="${post.ProfilePic}" alt="">
            <p>${post.name}</p>
          </a>
          ${followBtn}
        </div>
        <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>
      </div>
      <div class="FeedPostContent">
        <p>${post.Content}</p>
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
 */
// This function creates the HTML for a user card, based on Following.php
function createUserCardHTML(user) {
    // We can't reuse the follow button logic from Feed.js easily here,
    // so we'll just display the card.
    // A "Follow" button here would require more logic.
    return `
    <a class="UserCard Follower" href="index.php?target=profile&uid=${user.uid_encrypted}">
        <div class="Info">
            <div class="ProfilePictureContainer">
                <img src="${user.ProfilePic}" alt="Profile Picture">
            </div>
            <div class="ProfileInfo">
                <p class="UserName">${user.Fname} ${user.Lname}</p>
                <p class="UserUsername">@${user.Username}</p>
            </div>
        </div>
        </a>`;
}

// --- END HELPER FUNCTIONS ---


document.addEventListener('DOMContentLoaded', () => {
    const searchQuery = document.body.dataset.searchQuery;
    
    // Containers
    const postsContainer = document.getElementById('SearchPostsContainer');
    const usersContainer = document.getElementById('SearchUsersContainer');
    
    // Loaders
    const postsLoader = document.getElementById('PostsLoader');
    const usersLoader = document.getElementById('UsersLoader');

    // "See More" Buttons
    const seeMorePostsBtn = document.getElementById('SeeMorePostsBtn');
    const seeMoreUsersBtn = document.getElementById('SeeMoreUsersBtn');
    
    let userOffset = 0; // For user pagination

    // 1. Fetch Initial Results (ReqType 2)
    async function fetchInitialResults() {
        const formData = new FormData();
        formData.append('ReqType', 2);
        formData.append('query', searchQuery);

        try {
            const data = await Submit("POST", "Origin/Operations/Search.php", formData);
            if (!data.success) throw new Error("Failed to fetch initial results.");

            // --- Populate Posts ---
            postsLoader.classList.add('hidden');
            if (data.posts.length > 0) {
                data.posts.forEach(post => {
                    let newPostHTML = createPostHTML(post);
                    // 1. Insert the HTML string
                    postsContainer.insertAdjacentHTML('beforeend', newPostHTML);
                    // 2. Get the element you JUST added
                    const newPostElement = postsContainer.lastElementChild;
                    // 3. Pass the DOM ELEMENT to the function
                    attachPostInteractions(newPostElement);

                });
                if (data.hasMorePosts) seeMorePostsBtn.classList.remove('hidden');
            } else {
                postsContainer.innerHTML = '<p class="NoResults">No posts found.</p>';
            }

            // --- Populate Users ---
            if (data.users.length > 0) {
                data.users.forEach(user => {
                    usersContainer.innerHTML += createUserCardHTML(user);
                });
                userOffset = data.users.length;
                if (data.hasMoreUsers) seeMoreUsersBtn.classList.remove('hidden');
            } else {
                usersContainer.innerHTML = '<p class="NoResults">No users found.</p>';
            }

            // Note: We need to re-attach post interactions from Feed.js
            // This is tricky. A better architecture would be to have
            // attachPostInteractions as an exported function.
            // For now, post interactions *will not work* on this page
            // without modifying Feed.js to export its functions.

        } catch (error) {
            console.error(error);
            postsLoader.classList.add('hidden');
            postsContainer.innerHTML = '<p class="NoResults Error">Could not load search results.</p>';
        }
    }

    // 2. "See More Posts" (ReqType 3)
    seeMorePostsBtn.addEventListener('click', async () => {
        postsLoader.classList.remove('hidden');
        seeMorePostsBtn.classList.add('hidden');

        // Find the last post ID
        const lastPost = postsContainer.querySelector('.FeedPost:last-child');
        if (!lastPost) return;
        const lastPostID = lastPost.getAttribute('PID');

        const formData = new FormData();
        formData.append('ReqType', 3);
        formData.append('query', searchQuery);
        formData.append('lastPostID', lastPostID);

        try {
            const data = await Submit("POST", "Origin/Operations/Search.php", formData);
            if (data.success && data.posts.length > 0) {
                data.posts.forEach(post => {
                   let newPostHTML = createPostHTML(post);
                    // 1. Insert the HTML string
                    postsContainer.insertAdjacentHTML('beforeend', newPostHTML);
                    // 2. Get the element you JUST added
                    const newPostElement = postsContainer.lastElementChild;
                    // 3. Pass the DOM ELEMENT to the function
                    attachPostInteractions(newPostElement);
                });
                if (data.hasMorePosts) seeMorePostsBtn.classList.remove('hidden');
            } else {
                seeMorePostsBtn.classList.add('hidden');
            }
        } catch (error) {
            console.error(error);
        } finally {
            postsLoader.classList.add('hidden');
        }
    });

    // 3. "See More Users" (ReqType 4)
    seeMoreUsersBtn.addEventListener('click', async () => {
        usersLoader.classList.remove('hidden');
        seeMoreUsersBtn.classList.add('hidden');

        const formData = new FormData();
        formData.append('ReqType', 4);
        formData.append('query', searchQuery);
        formData.append('offset', userOffset);

        try {
            const data = await Submit("POST", "Origin/Operations/Search.php", formData);
            if (data.success && data.users.length > 0) {
                data.users.forEach(user => {
                    usersContainer.innerHTML += createUserCardHTML(user);
                });
                userOffset += data.users.length;
                if (data.hasMoreUsers) seeMoreUsersBtn.classList.remove('hidden');
            } else {
                seeMoreUsersBtn.classList.add('hidden');
            }
        } catch (error) {
            console.error(error);
        } finally {
            usersLoader.classList.add('hidden');
        }
    });

    // --- Initial Call ---
    fetchInitialResults();
});