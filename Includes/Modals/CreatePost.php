<?php
?>

<!-- Create_Post -->
<div class="Modal CPostContainer hidden">
    <form class="CPost" id="CreatePostForm" enctype="multipart/form-data">

        <input type="hidden" id="CPostEditID" name="PostID" value="">
        <input type="hidden" id="CPostFilesToDelete" name="files_to_delete" value="[]">
        <input type="hidden" id="CPostAsPageID" name="PostAsPageID" value="">
        <input type="hidden" id="CPostVisibility" name="Visibility" value="0">

        <!-- ── Header ─────────────────────────────────────────────── -->
        <div class="CPostHeader">
            <h1>Create Post</h1>
            <div class="ModalCancel"></div>
        </div>

        <!-- ── Post as ────────────────────────────────────────────── -->
        <div class="PostAsRow">
            <div class="PostAsSelector" id="PostAsSelector">
                <img id="PostAsAvatar" src="<?php echo isset($PostProfilePic) ? $PostProfilePic : 'Imgs/Icons/unknown.png'; ?>" alt="" class="PostAsImg">
                <span id="PostAsLabel">Posting as myself</span>
                <span class="PostAsArrow"></span>
            </div>
            <div class="PostAsDropdown hidden" id="PostAsDropdown">
                <div class="PostAsOption PostAsOptionSelf Active" data-pageid="" data-label="Posting as myself">
                    <img src="<?php echo isset($PostProfilePic) ? $PostProfilePic : 'Imgs/Icons/unknown.png'; ?>" class="PostAsImg" alt="">
                    <span>Myself</span>
                </div>
            </div>
        </div>

        <!-- ── Body ───────────────────────────────────────────────── -->
        <div contenteditable="true" id="CPostContent" class="PostContentInput" placeholder="What's on your mind?"></div>

        <!-- ── Upload preview ─────────────────────────────────────── -->
        <div class="UploadOverview hidden">
            <div class="UploadedFiles"></div>
        </div>

        <!-- ── Upload error ───────────────────────────────────────── -->
        <div class="UploadError hidden" id="CPostUploadError"></div>

        <!-- ── Footer toolbar ─────────────────────────────────────── -->
        <div class="CPostFooter">
            <div class="CPostTools">

                <!-- Visibility -->
                <div class="VisibilitySelector" id="VisibilitySelector">
                    <svg id="VisibilitySelectorIcon" class="VisibilityIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <span id="VisibilityLabel">Everyone</span>
                    <span class="PostAsArrow"></span>
                </div>
                <div class="VisibilityDropdown hidden" id="VisibilityDropdown">
                    <div class="VisibilityOption Active" data-value="0">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        <span>Everyone</span>
                    </div>
                    <div class="VisibilityOption" data-value="1">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Followers only</span>
                    </div>
                    <div class="VisibilityOption" data-value="2">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>People I follow</span>
                    </div>
                    <div class="VisibilityOption" data-value="3">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="11"/><line x1="20" y1="8" x2="20" y2="14"/></svg>
                        <span>Mutual followers</span>
                    </div>
                    <div class="VisibilityOption" data-value="4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span>Only me</span>
                    </div>
                </div>

                <!-- Upload buttons -->
                <div class="FileUpload">
                    <label id="DocumentPostUploadLabel" for="DocumentPostUpload" title="Attach document">
                        <img src="Imgs/Icons/DocumentUpload.svg" alt="Document">
                    </label>
                    <input type="file" id="DocumentPostUpload" accept="<?php echo $DocumentExtensions ?>" class="DocumentPostUpload" multiple>

                    <label id="ImagePostUploadLabel" for="ImagePostUpload" title="Attach image">
                        <img src="Imgs/Icons/ImageUpload.svg" alt="Image">
                    </label>
                    <input type="file" id="ImagePostUpload" accept="image/*" class="ImagePostUpload" multiple>
                </div>

            </div>

            <div class="CPostActions">
                <input type="submit" class="BrandBtn PostSubmitBtn" value="Post">
                <div class="Loader hidden"></div>
            </div>
        </div>

    </form>
</div>
