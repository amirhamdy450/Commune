<?php   
?>

<!-- Create_Post -->
<div class="Modal CPostContainer hidden">
    <form class="CPost"  id="CreatePostForm" enctype="multipart/form-data">
        <h1>Create Post</h1>

        <div class="ModalCancel"></div>


        <input type="hidden" id="CPostEditID" name="PostID" value="">
        <input type="hidden" id="CPostFilesToDelete" name="files_to_delete" value="[]">

        <div class="PostArea">
            <textarea id="CPostContent" name="content" rows="6" required="" placeholder="What's on your mind?"></textarea>


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