<?php   
?>

<!-- Create_Post -->
<div class="Modal CPostContainer hidden">
    <form class="CPost" enctype="multipart/form-data">
        <h1>Create Post</h1>

        <div class="ModalCancel"></div>

        <div class="PostArea">
            <textarea id="CPostContent" name="content" rows="6" required=""></textarea>


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

    </form>


</div>