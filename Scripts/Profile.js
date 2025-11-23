import { Submit, ValidateName, ValidateDate, PopulateFieldError } from "./Forms.js";
import { createPostHTML, attachPostInteractions } from "./Feed.js";
document.addEventListener('DOMContentLoaded', () => {

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
                    document.querySelector(".ProfileInfo .UserName").textContent = data.newData.Fname + ' ' + data.newData.Lname;
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

    if (profilePostsContainer && profileLoader && targetUIDInput) {
        
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
                                    attachPostInteractions(newPostElement);
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
    function clearFormErrors() {
        const errorFields = editProfileForm.querySelectorAll(".TextField.Error");
        errorFields.forEach(field => {
            field.classList.remove("Error");
            const errorMsg = field.querySelector(".FieldError");
            if (errorMsg) errorMsg.innerHTML = "";
        });
        
        const formResponse = editProfileForm.querySelector(".FormResponse");
        formResponse.textContent = "";
        formResponse.className = "FormResponse";
    }



});