<?php   
?>

<!-- Create_Post -->
<div class="Modal CPostContainer hidden">
    <form class="CPost"  id="CreatePostForm" enctype="multipart/form-data">
        <h1>Create Post</h1>

        <div class="ModalCancel"></div>


        <input type="hidden" id="CPostEditID" name="PostID" value="">
        <input type="hidden" id="CPostFilesToDelete" name="files_to_delete" value="[]">
        <input type="hidden" id="CPostAsPageID" name="PostAsPageID" value="">

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

        <div class="PostArea">
            <div contenteditable="true" id="CPostContent" class="PostContentInput" placeholder="What's on your mind?"></div>


            <div class="FileUpload">
                <label id="DocumentPostUploadLabel" for="DocumentPostUpload" >
                    <img src="Imgs/Icons/DocumentUpload.svg" alt="">
                </label>
                <input type="file" id="DocumentPostUpload" accept="<?php echo $DocumentExtensions ?>" class="DocumentPostUpload" multiple>

                <label  id="ImagePostUploadLabel" for="ImagePostUpload"  >
                    <img src="Imgs/Icons/ImageUpload.svg" alt="">
                </label>
                <input type="file" id="ImagePostUpload" accept="image/*" class="ImagePostUpload" multiple>


            </div>


        </div>


        <div class="UploadOverview hidden">
            <h4>Uploaded Files:</h4>
            <div class="UploadedFiles">

            </div>
        </div>







        <input type="submit" class="BrandBtn PostSubmitBtn">

        <div class="Loader hidden"></div>

    </form>


</div>