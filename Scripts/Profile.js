import { Submit, ValidateName, ValidateDate, PopulateFieldError } from "./Forms.js";
import * as FeedApi from "./Api/FeedApi.js";
import { createPostHTML, attachPostInteractions, FollowHandler, AttachFollowHover } from "./Components/PostCard.js";
import { mountActionMenu } from "./Components/ActionMenu.js";
import { confirmBlock } from "./Components/ConfirmActions.js";
import { initPostEditorModal, openEditPostModal, resetPostEditorModal } from "./Components/PostEditorModal.js";
import { attachPlainTextPaste } from "./Components/CommentThread.js";
import { showInfoBox } from "./Utilities.js";
import { initCommentSection, onCommentClick } from "./Components/CommentSection.js";
document.addEventListener('DOMContentLoaded', () => {

if (document.body.id === 'VProfile') {
    const followButton = document.getElementsByClassName('FollowBtn')[0];
    if (followButton) {
        const uid = followButton.getAttribute('uid');
        followButton.addEventListener('click', () => FollowHandler(followButton, uid));
        AttachFollowHover(followButton);
    }
}

// ── Post editor modal (edit + create) ────────────────────────────────────
const createPostModal = document.getElementsByClassName('CPostContainer')[0];
if (createPostModal) {
    initPostEditorModal({
        modal: createPostModal,
        onToggleModal: (modal, open) => {
            modal.classList.toggle('hidden', !open);
            document.body.classList.toggle('ModalOpen', open);
        },
        attachPlainTextPaste,
        showInfoBox,
        createPostHTML,
        attachPostInteractions: (postEl) => attachProfilePostInteractions(postEl),
    });
}

initCommentSection();

function attachProfilePostInteractions(postEl) {
    attachPostInteractions(postEl, {
        onCommentClick,
        onEditPost: (pid) => openEditPostModal(pid),
        onDeletePost: async (pid) => {
            const data = await FeedApi.deletePost(pid);
            if (data.success) {
                postEl.remove();
                showInfoBox('Post deleted.', 1);
            } else {
                showInfoBox(data.message || 'Failed to delete post.', 2);
            }
        },
    });
}

//TABS

const TabsNav=document.getElementsByClassName("TabsNav ProfileNav")[0];
const Tabs=TabsNav.getElementsByClassName("NavItem");

[...Tabs].forEach(tab => {

    tab.addEventListener("click",()=>{
        let ActiveTab=TabsNav.getElementsByClassName("Active")[0];
        ActiveTab.classList.remove("Active");
        tab.classList.add("Active");
        //set all other tabs content to hidden
        let TabsContent=document.getElementsByClassName("TabContent");
        [...TabsContent].forEach(TabContent => {

            TabContent.classList.add("hidden");
        });

        //get tab content id from attribute
        let TabContentId=tab.getAttribute("tab-content");
        let TabContent=document.getElementById(TabContentId);
        TabContent.classList.remove("hidden");

        TabContent.classList.remove("hidden");



    });

});



    // --- EDIT PROFILE MODAL (REVISED SIMPLIFIED LOGIC) ---
    const editProfileBtn = document.getElementsByClassName("EditProfileBtn")[0];
    const editProfileModal = document.getElementById("EditProfileModal");
    const editProfileForm = document.getElementById("EditProfileForm");
    
    // Find the new Cancel button

    if (editProfileBtn && editProfileModal && editProfileForm ) {
        const editProfileCancelBtn = editProfileModal.querySelector(".ModalCancelBtn");

        // 1. Click listener to open the modal
        editProfileBtn.addEventListener("click", () => {
            // Clear any old validation errors
            clearFormErrors();
            
            // Just open the modal. PHP has already populated the data.
            toggleModal(editProfileModal, true);
        });

        // 2. Add listener for the new Cancel button
        editProfileCancelBtn.addEventListener("click", () => {
            toggleModal(editProfileModal, false);
        });


        // 3. Form submission listener (This logic remains the same as before)
        editProfileForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            
            clearFormErrors();
            
            const loader = editProfileForm.querySelector(".Loader");
            const submitBtn = editProfileForm.querySelector("input[type='submit']");
            const formResponse = editProfileForm.querySelector(".FormResponse");

            // --- Client-side validation ---
            let errors = 0;
            const fnameField = document.getElementById("Edit_Fname").parentElement;
            const lnameField = document.getElementById("Edit_Lname").parentElement;
            const bdayField = document.getElementById("Edit_Bday").parentElement;

            const fnameVal = document.getElementById("Edit_Fname").value;
            const lnameVal = document.getElementById("Edit_Lname").value;
            const bdayVal = document.getElementById("Edit_Bday").value;

            const fnameRes = ValidateName(fnameVal);
            if (!fnameRes.IsValid) {
                PopulateFieldError(fnameField, fnameRes.Errors);
                errors++;
            }
            
            const lnameRes = ValidateName(lnameVal);
            if (!lnameRes.IsValid) {
                PopulateFieldError(lnameField, lnameRes.Errors);
                errors++;
            }
            
            const bdayRes = ValidateDate(bdayVal); // Using the imported validator
            if (!bdayRes.IsValid) {
                PopulateFieldError(bdayField, bdayRes.Errors);
                errors++;
            }
            
            if (errors > 0) return; // Stop if validation fails
            // --- End of validation ---

            loader.classList.remove("hidden");
            submitBtn.disabled = true;

            const formData = new FormData(editProfileForm);
            formData.append("ReqType", 3); // 3 = Update Personal Info

            try {
                const data = await Submit("POST", "Origin/Operations/User.php", formData);

                if (data.success) {
                    // Update page with new datao97p
                    const UserNameEl = document.querySelector(".ProfileInfo .UserName");
                    const TextNode = Array.from(UserNameEl.childNodes).find(n => n.nodeType === Node.TEXT_NODE);
                    if (TextNode) {
                        TextNode.textContent = data.newData.Fname + ' ' + data.newData.Lname;
                    } else {
                        UserNameEl.prepend(document.createTextNode(data.newData.Fname + ' ' + data.newData.Lname));
                    }
                    document.querySelector(".ProfileInfo .UserUsername").textContent = '@' + data.newData.Username;
                    
                    formResponse.textContent = data.message;
                    formResponse.className = "FormResponse Success";
                    
                    setTimeout(() => {
                        toggleModal(editProfileModal, false);
                    }, 1500);

                } else {
                    formResponse.textContent = data.message;
                    formResponse.className = "FormResponse Error";
                }
            } catch (error) {
                console.error("Error updating profile:", error);
                formResponse.textContent = "An unexpected error occurred. Please try again.";
                formResponse.className = "FormResponse Error";
            } finally {
                loader.classList.add("hidden");
                submitBtn.disabled = false;
            }
        });
    }

    // --- EDIT PROFILE PICTURE ---
    const profilePicContainer = document.querySelector(".ProfileHeader .ProfilePictureContainer");
    const profilePicModal = document.getElementById("EditProfilePicModal");
    const profilePicForm = document.getElementById("EditProfilePicForm");
    const profilePicInput = document.getElementById("ProfilePicInput");
    const profilePicPreview = document.getElementById("ProfilePicPreviewImage");
    const saveProfilePicBtn = document.getElementById("SaveProfilePicBtn");
    
    let selectedProfilePicFile = null;

    if (profilePicContainer && profilePicModal && profilePicForm) {
        
        // 1. Open the modal
        profilePicContainer.addEventListener("click", () => {
            // Reset form
            profilePicForm.reset();
            selectedProfilePicFile = null;
            saveProfilePicBtn.disabled = true;
            
            // Reset preview to current user image
            const currentImg = profilePicContainer.querySelector("img").src;
            profilePicPreview.src = currentImg;

            clearFormErrors(profilePicForm);
            toggleModal(profilePicModal, true);
        });

        // 2. Handle file selection and show preview
        profilePicInput.addEventListener("change", () => {
            const file = profilePicInput.files[0];
            if (file) {
                // Check file type (client-side)
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert("Invalid file type. Please select an image (jpg, png, gif, webp).");
                    return;
                }
                
                selectedProfilePicFile = file;
                
                // Use FileReader to show a live preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    profilePicPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                saveProfilePicBtn.disabled = false;
            }
        });

        // 3. Handle form submission
        profilePicForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (!selectedProfilePicFile) return;

            const loader = profilePicForm.querySelector(".Loader");
            const formResponse = profilePicForm.querySelector(".FormResponse");
            
            loader.classList.remove("hidden");
            saveProfilePicBtn.disabled = true;
            clearFormErrors(profilePicForm);

            const formData = new FormData();
            formData.append("ReqType", 2);
            formData.append("profile_pic", selectedProfilePicFile);

            try {
                const data = await Submit("POST", "Origin/Operations/User.php", formData);

                if (data.success) {
                    formResponse.textContent = data.message;
                    formResponse.className = "FormResponse Success";
                    
                    // Update the image on the page
                    const newPath = "MediaFolders/profile_pictures/" + data.newImagePath + '?t=' + new Date().getTime(); // Cache buster
                    profilePicContainer.querySelector("img").src = newPath;
                    
                    // Update navbar image
                    // Note: This ID might be different, check NavBar.php
                    // From NavBar.php, the ID is NavMenuDropBtn
                    const navBarImg = document.querySelector("#NavMenuDropBtn img");
                    if (navBarImg) {
                        navBarImg.src = newPath;
                    }

                    setTimeout(() => {
                        toggleModal(profilePicModal, false);
                    }, 1500);

                } else {
                    formResponse.textContent = data.message;
                    formResponse.className = "FormResponse Error";
                }

            } catch (error) {
                console.error("Error uploading profile picture:", error);
                formResponse.textContent = "An unexpected error occurred.";
                formResponse.className = "FormResponse Error";
            } finally {
                loader.classList.add("hidden");
                saveProfilePicBtn.disabled = false;
            }
        });
    }


    // --- EDIT COVER PHOTO ---
    const coverPhotoContainer = document.querySelector(".ProfileHeader .CoverPhotoContainer");
    const coverPhotoModal = document.getElementById("EditCoverPhotoModal");
    const coverPhotoForm = document.getElementById("EditCoverPhotoForm");
    const coverPhotoInput = document.getElementById("CoverPhotoInput");
    const coverPhotoPreview = document.getElementById("CoverPhotoPreviewImage");
    const saveCoverPhotoBtn = document.getElementById("SaveCoverPhotoBtn");

    let selectedCoverPhotoFile = null;

    if (coverPhotoContainer && coverPhotoModal && coverPhotoForm) {
        
        // 1. Open the modal
        coverPhotoContainer.addEventListener("click", () => {
            // Reset form
            coverPhotoForm.reset();
            selectedCoverPhotoFile = null;
            saveCoverPhotoBtn.disabled = true;
            
            // Reset preview to current user image
            const mainCoverImg = document.querySelector(".CoverPhotoContainer .CoverPhoto img");
            if (mainCoverImg) {
                coverPhotoPreview.src = mainCoverImg.src;
                coverPhotoPreview.classList.remove("DefaultImage");
            } else {
                // If no cover photo exists, use the placeholder
                coverPhotoPreview.src = "Imgs/Icons/unknown.png"; // or a specific default cover
                coverPhotoPreview.classList.add("DefaultImage");
            }

            clearFormErrors(coverPhotoForm); // Assuming clearFormErrors is available
            toggleModal(coverPhotoModal, true);
        });

        // 2. Handle file selection and show preview
        coverPhotoInput.addEventListener("change", () => {
            const file = coverPhotoInput.files[0];
            if (file) {
                // Client-side type check
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert("Invalid file type. Please select an image (jpg, png, gif, webp).");
                    return;
                }
                
                selectedCoverPhotoFile = file;
                
                // Show live preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    coverPhotoPreview.src = e.target.result;
                    coverPhotoPreview.classList.remove("DefaultImage");
                };
                reader.readAsDataURL(file);
                
                saveCoverPhotoBtn.disabled = false;
            }
        });

        // 3. Handle form submission
        coverPhotoForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (!selectedCoverPhotoFile) return;

            const loader = coverPhotoForm.querySelector(".Loader");
            const formResponse = coverPhotoForm.querySelector(".FormResponse");
            
            loader.classList.remove("hidden");
            saveCoverPhotoBtn.disabled = true;
            clearFormErrors(coverPhotoForm); // Assuming clearFormErrors is available

            const formData = new FormData();
            formData.append("ReqType", 4); // Use ReqType 4
            formData.append("cover_photo", selectedCoverPhotoFile);

            try {
                const data = await Submit("POST", "Origin/Operations/User.php", formData);

                if (data.success) {
                    formResponse.textContent = data.message;
                    formResponse.className = "FormResponse Success";
                    
                    // Update the image on the page
                    const newPath ="MediaFolders/cover_pictures/" + data.newImagePath + '?t=' + new Date().getTime(); // Cache buster
                    
                    const mainCoverDiv = document.querySelector(".CoverPhotoContainer .CoverPhoto");
                    let mainCoverImg = mainCoverDiv.querySelector("img");
                    
                    if (!mainCoverImg) {
                        // If no image existed, create one
                        mainCoverDiv.innerHTML = ''; // Clear default state
                        mainCoverImg = document.createElement("img");
                        mainCoverDiv.appendChild(mainCoverImg);
                        mainCoverDiv.classList.remove("Default"); //
                    }
                    
                    mainCoverImg.src = newPath;

                    setTimeout(() => {
                        toggleModal(coverPhotoModal, false);
                    }, 1500);

                } else {
                    formResponse.textContent = data.message;
                    formResponse.className = "FormResponse Error";
                }

            } catch (error) {
                console.error("Error uploading cover photo:", error);
                formResponse.textContent = "An unexpected error occurred.";
                formResponse.className = "FormResponse Error";
            } finally {
                loader.classList.add("hidden");
                saveCoverPhotoBtn.disabled = false;
            }
        });
    }

    let isFetchingProfilePosts = false;
    let noMoreProfilePosts = false;
    const profilePostsContainer = document.getElementById('ProfilePostsTab');
    const profileLoader = document.getElementById('ProfilePostLoader');
    const targetUIDInput = document.getElementById('UserProfileID');

    // Attach interactions to server-rendered posts
    [...(profilePostsContainer ? profilePostsContainer.getElementsByClassName('FeedPost') : [])].forEach(post => {
        attachProfilePostInteractions(post);
    });

    if (profilePostsContainer && profileLoader && targetUIDInput) {

        // If no posts were server-rendered, mark as done immediately — don't try to fetch
        if (profilePostsContainer.getElementsByClassName('FeedPost').length === 0) {
            noMoreProfilePosts = true;
        }

        const checkAndLoadPosts = () => {
            if (profilePostsContainer.classList.contains('hidden')) return;
            if (isFetchingProfilePosts || noMoreProfilePosts) return;

            const { scrollTop, scrollHeight, clientHeight } = document.documentElement;

            if (scrollTop + clientHeight >= scrollHeight - 150) {
                
                // 3. Show Loader IMMEDIATELY
                isFetchingProfilePosts = true;
                profileLoader.classList.remove('hidden');

                // 4. START THE ARTIFICIAL DELAY HERE
                // This ensures the loader is visible for at least 500ms
                setTimeout(() => {
                    const posts = profilePostsContainer.getElementsByClassName('FeedPost');
                    if (posts.length === 0) {
                        // Safety check: if for some reason posts are gone
                        isFetchingProfilePosts = false;
                        profileLoader.classList.add('hidden');
                        return;
                    }
                    
                    const lastPost = posts[posts.length - 1];
                    const lastPostID = lastPost.getAttribute('PID');
                    const targetUID = targetUIDInput.value;

                    const formData = new FormData();
                    formData.append('ReqType', 5);
                    formData.append('TargetUID', targetUID);
                    formData.append('LastPostID', lastPostID);

                    Submit('POST', 'Origin/Operations/User.php', formData)
                        .then(data => {
                            if (data && data.length > 0) {
                                data.forEach(post => {
                                    const postHTML = createPostHTML(post);
                                    profileLoader.insertAdjacentHTML('beforebegin', postHTML);

                                    const newPostElement = profileLoader.previousElementSibling;
                                    attachProfilePostInteractions(newPostElement);
                                });
                                
                                setTimeout(checkAndLoadPosts, 200);
                                
                            } else {
                                noMoreProfilePosts = true;
                                if (!profilePostsContainer.querySelector('.NoMorePosts')) {
                                    profileLoader.insertAdjacentHTML('beforebegin', '<p class="NoMorePosts">No more posts to show.</p>');
                                }
                            }
                        })
                        .catch(err => console.error("Error loading profile posts:", err))
                        .finally(() => {
                            isFetchingProfilePosts = false;
                            profileLoader.classList.add('hidden');
                        });
                }, 500); // <--- The 500ms Delay
            }
        };

        window.addEventListener('scroll', checkAndLoadPosts);
        setTimeout(checkAndLoadPosts, 500); // Initial load delay
    }

    const profileActionBtn = document.querySelector('.ProfileActionBtn');
    const profileActions   = document.querySelector('.ProfileActions');
    let profileActionMenu  = null;

    function closeProfileActionMenu() {
        if (profileActionMenu) {
            profileActionMenu.remove();
            profileActionMenu = null;
        }
    }

    async function copyCurrentProfileLink() {
        const shareLink = window.location.href;
        try {
            await navigator.clipboard.writeText(shareLink);
            return true;
        } catch (_error) {
            window.prompt('Copy this profile link:', shareLink);
            return false;
        }
    }

    function markCopySuccess(copyOption) {
        if (!copyOption) return;

        copyOption.classList.add('Success');
        copyOption.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 6 9 17l-5-5"></path>
            </svg>
            Link Copied
        `;

        profileActionBtn.classList.add('Copied');
        setTimeout(() => profileActionBtn.classList.remove('Copied'), 650);
        setTimeout(() => closeProfileActionMenu(), 950);
    }

    if (profileActionBtn && profileActions) {
        profileActionBtn.addEventListener('click', (e) => {
            e.stopPropagation();

            if (profileActionMenu) {
                closeProfileActionMenu();
                return;
            }

            const menuMarkup = `
                <div class="ActionOption" data-action="copy-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 12.5 15.5 5a3 3 0 1 1 4.2 4.2l-8.3 8.3a5 5 0 0 1-7.1-7.1l7.9-7.9"></path>
                    </svg>
                    Copy Profile Link
                </div>
                <div class="ActionOption Delete" data-action="block-user">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="8"></circle>
                        <path d="M8.5 8.5 15.5 15.5"></path>
                    </svg>
                    Block User
                </div>
            `;

            ({ menu: profileActionMenu } = mountActionMenu({
                selector: '.ProfileContext',
                className: 'ActionMenu ProfileContext',
                html: menuMarkup,
                parent: profileActions,
                onClose: () => { profileActionMenu = null; },
                onClick: async (evt) => {
                evt.stopPropagation();

                const option = evt.target.closest('.ActionOption');
                if (!option) return;

                const action = option.dataset.action;
                const profileUID = profileActionBtn.dataset.uid;
                const profileName = profileActionBtn.dataset.name || 'this user';

                if (action === 'copy-link') {
                    const copied = await copyCurrentProfileLink();
                    if (copied) {
                        markCopySuccess(option);
                    } else {
                        closeProfileActionMenu();
                    }
                    return;
                }

                if (action === 'block-user') {
                    closeProfileActionMenu();
                    confirmBlock({
                        Name: profileName,
                        onConfirm: async () => {
                            const data = await FeedApi.blockUser(profileUID);
                            if (data.success) {
                                window.location.href = 'index.php';
                            } else {
                                alert(data.message || 'Could not block this user.');
                            }
                        }
                    });
                }
                }
            }));
        });

        document.addEventListener('click', (e) => {
            if (!profileActions.contains(e.target)) {
                closeProfileActionMenu();
            }
        });
    }

    function clearFormErrors(form) {
        const target = form || editProfileForm;
        target.querySelectorAll(".TextField.Error").forEach(field => {
            field.classList.remove("Error");
            const errorMsg = field.querySelector(".FieldError");
            if (errorMsg) errorMsg.innerHTML = "";
        });
        const formResponse = target.querySelector(".FormResponse");
        if (formResponse) {
            formResponse.textContent = "";
            formResponse.className = "FormResponse";
        }
    }



});



