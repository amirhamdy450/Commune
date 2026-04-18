import { Submit } from "./Forms.js";
import { createPostHTML, attachPostInteractions } from "./Components/PostCard.js";

document.addEventListener('DOMContentLoaded', () => {
    const searchQuery = document.body.dataset.searchQuery;

    // --- 1. TAB NAVIGATION LOGIC ---
    const tabsNav = document.querySelector('.TabsNav.SearchNav');
    if (tabsNav) {
        const tabs = tabsNav.querySelectorAll('.NavItem');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                tabs.forEach(t => t.classList.remove('Active'));
                tab.classList.add('Active');
                document.querySelectorAll('.TabContent').forEach(c => {
                    c.classList.remove('Active');
                    c.classList.add('hidden');
                });
                const targetId = tab.getAttribute('tab-content');
                const targetContent = document.getElementById(targetId);
                targetContent.classList.remove('hidden');
                targetContent.classList.add('Active');
            });
        });
    }

    // --- 2. "SEE ALL" BUTTON HANDLERS ---
    const seeAllPeopleBtn = document.getElementById('SeeAllPeopleBtn');
    const seeAllPostsBtn = document.getElementById('SeeAllPostsBtn');

    if (seeAllPeopleBtn) {
        seeAllPeopleBtn.addEventListener('click', () => {
            document.querySelector('[tab-content="SearchUsersTab"]').click();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    if (seeAllPostsBtn) {
        seeAllPostsBtn.addEventListener('click', () => {
            document.querySelector('[tab-content="SearchPostsTab"]').click();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // --- 3. GLOBAL STATE FOR OFFSETS ---
    const state = {
        postsOffset: 0,
        usersOffset: 0
    };

    // --- 4. INITIAL FETCH ---
    async function performInitialSearch() {
        const formData = new FormData();
        formData.append('ReqType', 2); 
        formData.append('query', searchQuery);

        try {
            const data = await Submit("POST", "Origin/Operations/Search.php", formData);
            
            if(data.success) {
                // A. Populate Users
                if (data.users.length > 0) {
                    const allUsersContainer = document.getElementById('AllPeopleList');
                    const mainUsersContainer = document.getElementById('SearchUsersList');

                    data.users.slice(0, 3).forEach(u => allUsersContainer.insertAdjacentHTML('beforeend', createUserCardHTML(u)));
                    document.getElementById('AllPeopleSection').classList.remove('hidden');

                    data.users.forEach(u => mainUsersContainer.insertAdjacentHTML('beforeend', createUserCardHTML(u)));
                    state.usersOffset += data.users.length; 
                    
                    if (!data.hasMoreUsers) {
                        document.getElementById('NoMoreUsers').classList.remove('hidden');
                    }
                } else {
                    document.getElementById('NoMoreUsers').classList.remove('hidden');
                    document.getElementById('NoMoreUsers').textContent = "No users found.";
                }

                // B. Populate Posts
                if (data.posts.length > 0) {
                    const allPostsContainer = document.getElementById('AllPostsList');
                    const mainPostsContainer = document.getElementById('SearchPostsList');

                    data.posts.slice(0, 3).forEach(p => {
                        allPostsContainer.insertAdjacentHTML('beforeend', createPostHTML(p));
                        attachPostInteractions(allPostsContainer.lastElementChild);
                    });
                    document.getElementById('AllPostsSection').classList.remove('hidden');

                    data.posts.forEach(p => {
                        mainPostsContainer.insertAdjacentHTML('beforeend', createPostHTML(p));
                        attachPostInteractions(mainPostsContainer.lastElementChild);
                    });
                    state.postsOffset += data.posts.length; 

                    if (!data.hasMorePosts) {
                        document.getElementById('NoMorePosts').classList.remove('hidden');
                    }

                } else {
                    document.getElementById('NoMorePosts').classList.remove('hidden');
                    document.getElementById('NoMorePosts').textContent = "No posts found.";
                }

                // C. Empty State
                if (data.users.length === 0 && data.posts.length === 0) {
                    document.getElementById('AllEmptyState').classList.remove('hidden');
                }
            }
        } catch (err) {
            console.error(err);
        }
    }

    // --- 5. INFINITE SCROLL CLOSURE ---
    function fetchMoreResults() {
        let isFetching = false;
        let noMorePosts = false;
        let noMoreUsers = false;

        const postsLoader = document.getElementById('PostsLoader');
        const usersLoader = document.getElementById('UsersLoader');
        const postsContainer = document.getElementById('SearchPostsList');
        const usersContainer = document.getElementById('SearchUsersList');

        return () => {
            const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
            if (scrollTop + clientHeight < scrollHeight - 150) return;
            if (isFetching) return;

            const activeTab = document.querySelector('.TabContent.Active');
            if (!activeTab) return;

            // --- SCENARIO A: SEARCH POSTS TAB ---
            if (activeTab.id === 'SearchPostsTab' && !noMorePosts) {
                isFetching = true;
                postsLoader.classList.remove('hidden');

                setTimeout(() => {
                    const formData = new FormData();
                    formData.append('ReqType', 3);
                    formData.append('query', searchQuery);
                    formData.append('offset', state.postsOffset);

                    Submit("POST", "Origin/Operations/Search.php", formData)
                        .then(data => {
                            if (data.success) {
                                if (data.posts.length > 0) {
                                    data.posts.forEach(post => {
                                        const postHTML = createPostHTML(post);
                                        postsContainer.insertAdjacentHTML('beforeend', postHTML);
                                        attachPostInteractions(postsContainer.lastElementChild);
                                    });
                                    state.postsOffset += data.posts.length;
                                }
                                if (!data.hasMorePosts || data.posts.length === 0) {
                                    noMorePosts = true;
                                    document.getElementById('NoMorePosts').classList.remove('hidden');
                                }
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            noMorePosts = true;
                        })
                        .finally(() => {
                            isFetching = false;
                            postsLoader.classList.add('hidden');
                        });
                }, 500);
            }

            // --- SCENARIO B: SEARCH USERS TAB ---
            if (activeTab.id === 'SearchUsersTab' && !noMoreUsers) {
                isFetching = true;
                usersLoader.classList.remove('hidden');

                setTimeout(() => {
                    const formData = new FormData();
                    formData.append('ReqType', 4);
                    formData.append('query', searchQuery);
                    formData.append('offset', state.usersOffset);

                    Submit("POST", "Origin/Operations/Search.php", formData)
                        .then(data => {
                            if (data.success) {
                                if (data.users.length > 0) {
                                    data.users.forEach(user => {
                                        usersContainer.insertAdjacentHTML('beforeend', createUserCardHTML(user));
                                    });
                                    state.usersOffset += data.users.length;
                                }
                                if (!data.hasMoreUsers || data.users.length === 0) {
                                    noMoreUsers = true;
                                    document.getElementById('NoMoreUsers').classList.remove('hidden');
                                }
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            noMoreUsers = true;
                        })
                        .finally(() => {
                            isFetching = false;
                            usersLoader.classList.add('hidden');
                        });
                }, 500);
            }
        };
    }

    function createUserCardHTML(user) {
        const verifiedBadge = user.IsBlueTick ? '<span class="BlueTick" title="Verified"></span>' : '';
        const mutualBadge   = user.mutual_follow
            ? '<span class="SearchMutualBadge">Follows you</span>'
            : '';
        return `
        <a class="SearchUserCard" href="index.php?target=profile&uid=${user.uid_encrypted}">
            <img class="SearchUserAvatar" src="${user.ProfilePic}" alt="">
            <div class="SearchUserInfo">
                <div class="SearchUserNameRow">
                    <span class="SearchUserName">${user.Fname} ${user.Lname}</span>
                    ${verifiedBadge}
                    ${mutualBadge}
                </div>
                <span class="SearchUserHandle">@${user.Username}</span>
                ${user.Bio ? `<span class="SearchUserBio">${user.Bio}</span>` : ''}
            </div>
        </a>`;
    }

    window.addEventListener('scroll', fetchMoreResults());
    performInitialSearch();
});