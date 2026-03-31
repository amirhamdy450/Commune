<?php   
?>

<div class="Modal CommentSection hidden">
    <div class="ModalContent">
        <div class="ModalCancel"></div>
        <h2>Comments</h2>

        <div class="CommentsArea">
            <div class="ModalCommentsContainer">

                <!-- Show If no comments are found -->
                <div class="NoComments">
                    <img src="Imgs/Icons/comment.svg" alt="">
                    <h4>No comments yet</h4>
                    <p>Be the first to share your thoughts.</p>
                </div>
            </div>
        </div>


        <form class="CreateModalComment" id="CreateNewComment">
            <div contenteditable="true" class="CommentInput" placeholder="Add a comment"></div>
            <input type="submit" value="" class="BrandBtn CommentSubmitBtn">

        </form>

    </div>
</div>
