
import { Submit } from "./Forms.js";
//GLOBALS
const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 MB in bytes
const MAX_FILE_COUNT = 2;
const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx'];
const ALLOWED_IMAGE_EXTENSIONS = ['xbm', 'tif', 'jfif', 'ico', 'tiff', 'gif', 'svg', 'webp', 'svgz', 'jpg', 'jpeg', 'png', 'bmp', 'pjp', 'apng', 'pjpeg', 'avif'];

let validSelectedFiles = [];
let currentPostID = null;


let CurrentConfirmModalFunction = null;

//check if pid is set in the URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('pid')) {
  currentPostID = urlParams.get('pid');
}



// Creates HTML for a single post
function createPostHTML(post) {
  const mediaContent = generateMediaContent(post);
  const postDeleteButton = post.CurrentUserPrivilege 
    ? `<div class="DeleteBtn PostDeleteBtn"><img src="Imgs/Icons/trash.png" alt=""></div>` 
    : '';
  const likeIcon = post.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';

  return `
    <div class="FeedPost" PID="${post.PID}">
      <div class="FeedPostHeader">
        <img src="Imgs/Icons/unknown.png" alt="">
        <p>${post.name}</p>
        ${postDeleteButton}
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


function createCommentHTML(comment,type=1){
  let likeIcon,ViewRepliesButton;
  //type 1: comment, type 2: reply
  if(comment.liked){
    likeIcon = 'Imgs/Icons/liked.svg';
  }else{
    likeIcon = 'Imgs/Icons/like.svg';
  }

  if(comment.ReplyCounter > 0){
    ViewRepliesButton = `<div class="ViewRepliesBtn">${comment.ReplyCounter} Replies</div>`;
  }else{
    ViewRepliesButton = '';
  }

  if(type==1){
    return `             
      <div class="CommentContainer">

        <div class="CI_2">
          <div class="comment-readonly">
              <div class="meta">
                <span class="CMDI073" cid="${comment.CID}" uid="${comment.UID}"></span>
              </div>
          </div>
        </div>

        <div class="ModalComment">
          <div class="ModalCommentHeader">
            <img src="Imgs/Icons/unknown.png" alt="">
            <p>${comment.name}</p>
          </div>
          <div class="ModalCommentContent">
            <p>${comment.comment}</p>
          </div>
        </div>

        <div class="FeedPostInteractions">
          <div class="Interaction FeedPostLike">
              <img src="${likeIcon}">
              <p class="CommentLikesCNT">${comment.LikeCounter}</p>
          </div>
          
          <div class="Interaction FeedPostComment">
              <img src="Imgs/Icons/comment.svg">
              Reply
          </div>


                  


        </div>

        
        ${ViewRepliesButton}
        
        <span class="username-readonly hidden">${comment.Username}</span>

        <div class="RepliesContainer hidden">



        </div>

      </div>
      `;

  }else if(type ==2){
      let TaggedUser;
      if(comment.TaggedUser){
        TaggedUser = `<span class="ReplyTag">@${comment.TaggedUser}</span>`;
      }else{
        TaggedUser = '';
      }


        return `             
      <div class="CommentContainer Reply">

        <div class="CI_2">
          <div class="comment-readonly">
              <div class="meta">
                <span class="CMDI073" crid="${comment.CRID}" uid="${comment.UID}"></span>
              </div>
          </div>
        </div>

        <div class="ModalComment">
          <div class="ModalCommentHeader">
            <img src="Imgs/Icons/unknown.png" alt="">
            <p>${comment.Sender}</p>
          </div>
          <div class="ModalCommentContent">
             ${TaggedUser} <p>${comment.Reply}</p>
          </div>
        </div>

        <div class="FeedPostInteractions">
          <div class="Interaction FeedPostLike">
              <img src="${likeIcon}">
              <p class="CommentLikesCNT">${comment.LikeCounter}</p>
          </div>
          
          <div class="Interaction FeedPostComment">
              <img src="Imgs/Icons/comment.svg">
             Reply
          </div>


                  


        </div>


        <span class="username-readonly hidden">${comment.SenderUsername}</span>


        

      </div>
      `;

  }
  
}


// Generates media content HTML based on post type
function generateMediaContent(post) {
  let mediaContent = '';
  if (post.MediaFolder && post.MediaFolder.length > 0) {
    if (parseInt(post.MediaType) === 2) {
      post.MediaFolder.forEach(image => {
        mediaContent += `<img src="${image.path}" alt="">`;
      });
    } else if (parseInt(post.MediaType) === 3) {
      post.MediaFolder.forEach(document => {
        mediaContent += `
          <a href="${document.path}" class="FeedPostLink">
            <div class="UploadedFile">
              <img src="Imgs/Icons/Document.svg">
              ${document.name}
            </div>
          </a>`;
      });
    }
  }
  return mediaContent;
}


// Fetches posts when scrolling to bottom
function fetchMorePosts() {
  let isFetching = false;
  let noMorePosts = false;
  const loader = document.getElementsByClassName('FeedLoader')[0];
  const feedContainer = document.getElementsByClassName('FeedContainer')[0];

  return () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100 && !isFetching && !noMorePosts) {
      isFetching = true;
      loader.classList.remove('hidden');

      setTimeout(() => {
        const allFeedPosts = document.getElementsByClassName('FeedPost');
        if (allFeedPosts.length === 0) {
          loader.classList.add('hidden');
          isFetching = false;
          return;
        }
        const lastPostPID = allFeedPosts[allFeedPosts.length - 1].getAttribute('pid');
        const formData = new FormData();
        formData.append('ReqType', 5);
        formData.append('LastFeedPostPID', lastPostPID);

        fetch('Origin/Operations/User.php', { method: 'POST', body: formData })
          .then(response => response.json())
          .then(data => {
            if (data.length > 0) {
              data.forEach(post => {
                const postHTML = createPostHTML(post);
                feedContainer.insertAdjacentHTML('beforeend', postHTML);
                const newPostElement = feedContainer.lastElementChild;
                attachPostInteractions(newPostElement);
              });
              feedContainer.appendChild(loader);
            } else {
              noMorePosts = true;
              loader.classList.add('hidden');
              const noMorePostsMessage = document.createElement('div');
              noMorePostsMessage.className = 'NoMorePosts';
              noMorePostsMessage.textContent = 'No more posts to load.';

              feedContainer.appendChild(noMorePostsMessage);
            }
          })
          .catch(error => {
            console.error('Error:', error);
          })
          .finally(() => {
            isFetching = false;
          });
      }, 500);
    }
  };
}

// Attaches interaction event listeners to a post
function attachPostInteractions(post) {
  const postId = post.getAttribute('PID');
  const likeButton = post.getElementsByClassName('FeedPostLike')[0];
  const commentButton = post.getElementsByClassName('FeedPostComment')[0];
  const shareButton = post.getElementsByClassName('FeedPostShare')[0];
  const deleteButton = post.getElementsByClassName('PostDeleteBtn')[0];

  likeButton.addEventListener('click', () => {
    const formData = new FormData();
    formData.append('ReqType', 2);
    formData.append('FeedPostID', postId);

    fetch('Origin/Operations/User.php', { method: 'POST', body: formData })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const likesCountElement = post.getElementsByClassName('PostLikesCNT')[0];
          let likesCount = parseInt(likesCountElement.innerHTML);
          likesCount += parseInt(data.Insertion);
          likesCountElement.innerHTML = likesCount;
          const likeIcon = likeButton.getElementsByTagName('img')[0];
          likeIcon.src = data.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';
        }
      })
      .catch(error => console.error('Error:', error));
  });

  commentButton.addEventListener('click', () => {
    const formData = new FormData();
    formData.append('ReqType', 4);
    formData.append('FeedPostID', postId);

    let likeIcon = 'Imgs/Icons/like.svg'; 

    fetch('Origin/Operations/User.php', { method: 'POST', body: formData })
      .then(response => response.json())
      .then(data => {
        const commentsContainer = document.getElementsByClassName('ModalCommentsContainer')[0];

        commentsContainer.innerHTML = '';

        if(data && data.length !== 0) {
         // const commentsContainer = document.getElementsByClassName('ModalCommentsContainer')[0];
         // commentsContainer.innerHTML = '';
          data.forEach(comment => {

            commentsContainer.insertAdjacentHTML('beforeend', createCommentHTML(comment));


            
          });
          attachCommentInteractions();

        }else{
          commentsContainer.insertAdjacentHTML('beforeend', `
                <div class="NoComments">
                    <p>No Comments Found !</p>
                </div>
          `);
        }
        let CommentSectionModal = document.getElementsByClassName('CommentSection')[0];
        toggleModal(CommentSectionModal, true);
      })
      .catch(error => console.error('Error:', error));

    currentPostID = postId;
    console.log('Current Post ID:', currentPostID);

  });

  shareButton.addEventListener('click', async() => {
    //get base URL
    const Url = window.location.href.split('?')[0];
    const ShareLink = `${Url}?pid=${encodeURIComponent(postId)}`;



    try{
      await navigator.clipboard.writeText(ShareLink);
      let Img = shareButton.getElementsByTagName('img')[0];
      Img.src = 'Imgs/Icons/Checkmark.svg';
      
      shareButton.innerHTML = `
        <img src="Imgs/Icons/Checkmark.svg" alt="">
         Copied !`;
      setTimeout(() => {

        Img.src = 'Imgs/Icons/share.svg';
        shareButton.innerHTML = `
          <img src="Imgs/Icons/share.svg" alt="">
          Share`;
      }, 2000);

    }catch (error) {
      alert('Failed to copy link. Please try again.');
    }





  });

  if (deleteButton) {
    deleteButton.addEventListener('click', () => {
      //toggleModal('DelPostBox', true);
      currentPostID = postId;

      ShowConfirmModal({
        Title: 'Are You Sure You Want To Delete This Post?',
        ConfirmText: 'Delete',
        onConfirm: () =>DeletePost()
      });
    });
  }
}



function attachCommentInteractions() {
  let CommentSectionModal= document.getElementsByClassName('CommentSection')[0];

  
  let Comments= CommentSectionModal.getElementsByClassName('CommentContainer');


  [...Comments].forEach(comment => {

    if(comment.classList.contains('Reply')){ //skip replies , we handle them through the main comment
      return;
    }

    const likeButton = comment.getElementsByClassName('FeedPostLike')[0];
    const commentButton = comment.getElementsByClassName('FeedPostComment')[0];
    const ViewRepliesButton = comment.getElementsByClassName('ViewRepliesBtn')[0];
    console.log(comment);
    console.log(ViewRepliesButton);
   

    let meta = comment.getElementsByClassName('meta')[0];
    let attrs = meta.getElementsByClassName('CMDI073')[0]; //for encryption

    const CommentID = attrs.getAttribute('cid');
    const UserID = attrs.getAttribute('uid');

    likeButton.addEventListener('click', () => {
      const formData = new FormData();
      formData.append('ReqType', 7);
      formData.append('CommentID', CommentID);

      fetch('Origin/Operations/User.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const likesCountElement = comment.getElementsByClassName('CommentLikesCNT')[0];
            let likesCount = parseInt(likesCountElement.innerHTML);
            likesCount += parseInt(data.Insertion);
            likesCountElement.innerHTML = likesCount;
            const likeIcon = likeButton.getElementsByTagName('img')[0];
            likeIcon.src = data.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';
          }
        })
        .catch(error => console.error('Error:', error));
    });

    commentButton.addEventListener('click', () => {
      //check if the reply form is already created
      let CreateReply= comment.getElementsByClassName('CreateCommentReply')[0];
      if(!CreateReply){
          comment.insertAdjacentHTML('beforeend', ` 
            <form class="CreateModalComment CreateCommentReply" >
                  <div contenteditable="true" class="CommentInput" placeholder="Reply to comment" rows="1"></div>
                  <input type="submit" value="" class="BrandBtn CommentSubmitBtn">

            </form>
            
          `);

        //reselect CreateCommentReply for the newly created form
          CreateReply= comment.getElementsByClassName('CreateCommentReply')[0];
          

      }


      CreateReply.addEventListener('submit',  (e) => {
        e.preventDefault();
        

        let Unfiltered=CreateReply.getElementsByClassName('CommentInput')[0].innerHTML;
        const Reply = Unfiltered.replace(/<[^>]+contenteditable="false"[^>]*>.*?<\/[^>]+>/gi, '').replace(/<[^>]*>/g, '').trim(); // strip any other HTML.trim();

        const formData = new FormData();
        formData.append('ReqType', 8);
        formData.append('CommentID', CommentID);
        formData.append('Reply', Reply);

        Submit('POST', 'Origin/Operations/User.php', formData);

        //createReply(comment,);
      })


    });


    if(ViewRepliesButton){

      ViewRepliesButton.addEventListener('click', () => {
            let RepliesContainer = comment.getElementsByClassName('RepliesContainer')[0];
            RepliesContainer.classList.remove('hidden');

        const formData = new FormData();
        formData.append('ReqType', 10);
        formData.append('CommentID', CommentID);

        fetch('Origin/Operations/User.php', { method: 'POST', body: formData })
          .then(response => response.json())
          .then(data => {
            let RepliesContainer = comment.getElementsByClassName('RepliesContainer')[0];
            RepliesContainer.classList.remove('hidden');

            data.forEach(reply => {
              RepliesContainer.insertAdjacentHTML('beforeend', createCommentHTML(reply,2));
              //get last inserted reply
              const lastInsertedReply = RepliesContainer.getElementsByClassName('CommentContainer')[RepliesContainer.getElementsByClassName('CommentContainer').length - 1];
              attachReplyInteractions(lastInsertedReply, comment);

            });

          })
        
      })
    }


  })
}

function attachReplyInteractions(reply, parentComment) {
    const likeButton = reply.getElementsByClassName("FeedPostLike")[0];
    const commentButton = reply.getElementsByClassName("FeedPostComment")[0];

    //meta data
    let meta = reply.getElementsByClassName('meta')[0];
    let attrs = meta.getElementsByClassName('CMDI073')[0]; //for encryption

    const ReplyID = attrs.getAttribute('crid');
    const ReplyUserID = attrs.getAttribute('uid');

    likeButton.addEventListener("click", () => {
      const formData = new FormData();
      formData.append("ReqType", 9);
      formData.append("ReplyID", ReplyID);

      fetch("Origin/Operations/User.php", { method: "POST", body: formData })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            const likesCountElement =
            reply.getElementsByClassName("CommentLikesCNT")[0];
            let likesCount = parseInt(likesCountElement.innerHTML);
            likesCount += parseInt(data.Insertion);
            likesCountElement.innerHTML = likesCount;
            const likeIcon = likeButton.getElementsByTagName("img")[0];
            likeIcon.src = data.liked
              ? "Imgs/Icons/liked.svg"
              : "Imgs/Icons/like.svg";
          }
        })
        .catch((error) => console.error("Error:", error));
    });


    commentButton.addEventListener("click", () => {
      //check if the reply form is already created on parent
      let CreateReply = parentComment.getElementsByClassName("CreateCommentReply")[0];

      //get the username of the user bieng replied to
      let ReplyTo = reply.getElementsByClassName("username-readonly")[0].innerHTML;

      
      if (!CreateReply) {
        parentComment.insertAdjacentHTML("beforeend",
        ` 
        <form class="CreateModalComment CreateCommentReply" ">
              <div contenteditable="true" class="CommentInput" rows="1">
              <span class="ReplyTag" contenteditable="false" replyto="${ReplyUserID}">@${ReplyTo}</span>

              </div>
              <input type="submit" value="" class="BrandBtn CommentSubmitBtn">

        </form>
    
        `
        );

        //reselect CreateCommentReply for the newly created form
        CreateReply = parentComment.getElementsByClassName("CreateCommentReply")[0];

      }else{
        //just append the tag to the existing reply form
        let CommentInput = CreateReply.getElementsByClassName("CommentInput")[0];

        //reset placeholder in CommentInput
        CommentInput.setAttribute("placeholder", "");


        //check if a tag already exists
        let ReplyTag = CommentInput.getElementsByClassName("ReplyTag")[0];
        if(ReplyTag){
          //reset
          ReplyTag.innerHTML = "@" + ReplyTo;
          ReplyTag.setAttribute("replyto", ReplyUserID);
        }else{

         CommentInput.insertAdjacentHTML("beforeend", `<span class="ReplyTag" contenteditable="false" replyto="${ReplyUserID}">@${ReplyTo}</span>`)
        }
      }


      CreateReply.addEventListener('submit',  (e) => {
        e.preventDefault();
        
        let meta = parentComment.getElementsByClassName('meta')[0];
        let attrs = meta.getElementsByClassName('CMDI073')[0]; //for encryption
        const CommentID = attrs.getAttribute('cid');


        //comment
        let Unfiltered=CreateReply.getElementsByClassName('CommentInput')[0].innerHTML;

        const Reply = Unfiltered.replace(/<[^>]+contenteditable="false"[^>]*>.*?<\/[^>]+>/gi, '').replace(/<[^>]*>/g, '').trim(); // strip any other HTML.trim();
        

        const formData = new FormData();
        formData.append('ReqType', 8);
        formData.append('CommentID', CommentID);
        formData.append('Reply', Reply);
        formData.append('ReplyTo', ReplyUserID);

        Submit('POST', 'Origin/Operations/User.php', formData);

        //createReply(comment,);
      })

    });
}


// Toggles modal visibility
function toggleModal(modal, show) {

  if(show) {
    modal.classList.remove('hidden');
    document.body.classList.add("ModalOpen");

  }else{
    modal.classList.add('hidden');
    document.body.classList.remove("ModalOpen");
  }


}

function showInfoBox(message,Type=0) {
  let InfoTypeDiv= '';

  switch (Type) {
    case 1:
      InfoTypeDiv = 'Success';
      break;
    case 2:
      InfoTypeDiv = 'Error';
      break;

  }


  const infoBox = document.createElement('div');
  infoBox.className = 'InfoBox ' + InfoTypeDiv;
  infoBox.textContent = message;
  document.body.appendChild(infoBox);

  setTimeout(() => {
    infoBox.classList.add('Show');
  }, 100);

  setTimeout(() => {
    infoBox.classList.remove('Show');
    setTimeout(() => {
      document.body.removeChild(infoBox);
    }, 500);
  }, 3000);
}






function DeletePostUploadedFiles(Btn,Type=1){

  const fileName = Btn.getAttribute('filename');
  validSelectedFiles = validSelectedFiles.filter(f => f.name !== fileName);
  Btn.parentElement.remove();

  let inputElement;
  let CPostContainer = document.getElementsByClassName('CPostContainer')[0];


  if(validSelectedFiles.length === 0){

    let FileUpload = CPostContainer.getElementsByClassName('FileUpload')[0];
    let FileLabels = FileUpload.getElementsByTagName('label');
    
    FileLabels = [...FileLabels];

    FileLabels.forEach(label => {
      label.classList.remove('hidden');
    });

    let UploadOverview = CPostContainer.getElementsByClassName('UploadOverview')[0];
    UploadOverview.classList.add('hidden');

    
  }

  if(Type === 1){
    inputElement= CPostContainer.getElementsByClassName('DocumentPostUpload')[0]; 

    if(validSelectedFiles.length > 0){
      let DocumentPostUploadLabel = document.getElementById('DocumentPostUploadLabel');
      DocumentPostUploadLabel.classList.remove('hidden');
    }

  }else if(Type === 2){
    inputElement= CPostContainer.getElementsByClassName('ImagePostUpload')[0];

    if(validSelectedFiles.length > 0){
      let ImagePostUploadLabel = document.getElementById('ImagePostUploadLabel');
      ImagePostUploadLabel.classList.remove('hidden');
    }

  }

  inputElement.value = '';
  const DT = new DataTransfer();
  validSelectedFiles.forEach(file => DT.items.add(file));
  inputElement.files = DT.files
}

function PopulatePostUploadedFiles(Files,Type=1){
    let CPostContainer = document.getElementsByClassName('CPostContainer')[0];
    let UploadOverview= CPostContainer.getElementsByClassName('UploadOverview')[0];
    UploadOverview.classList.remove('hidden');

    let UploadedFiles= UploadOverview.getElementsByClassName('UploadedFiles')[0];
        // Clear previous files before adding new ones
    UploadedFiles.innerHTML = '';

    let ImgSrc= '';
    if(Files.length >= MAX_FILE_COUNT){
      let FileUpload = CPostContainer.getElementsByClassName('FileUpload')[0];
      let FileLabels = FileUpload.getElementsByTagName('label');
      
      FileLabels = [...FileLabels];

      FileLabels.forEach(label => {
        label.classList.add('hidden');
      });

    }else {
      //hide other type file upload
      if(Type === 1){
        let ImagePostUpload = CPostContainer.getElementsByClassName('ImagePostUpload')[0];
        let ImagePostUploadLabel = document.getElementById('ImagePostUploadLabel');
        ImagePostUpload.value = '';
        ImagePostUploadLabel.classList.add('hidden');
      }else if(Type === 2){
        let DocumentPostUpload = CPostContainer.getElementsByClassName('DocumentPostUpload')[0];
        let DocumentPostUploadLabel = document.getElementById('DocumentPostUploadLabel');
        DocumentPostUpload.value = '';
        DocumentPostUploadLabel.classList.add('hidden');
      }

    }

    if(Type === 1) {
      ImgSrc = 'Imgs/Icons/Document.svg';


    }else if(Type === 2) {
      ImgSrc = 'Imgs/Icons/Image.svg';

    }


    for(let i = 0; i < Files.length; i++) {
      UploadedFiles.insertAdjacentHTML('beforeend', `
      <div class="UploadedFile">
        <div class="FileName">
            <img src="${ImgSrc}">
            <p>${Files[i].name}</p>
        </div>
        <span class="RemoveUploadedFile" filename="${Files[i].name}">&times;</span>

      </div>
      `);
    }

    let RemoveUploadBtns= UploadedFiles.getElementsByClassName('RemoveUploadedFile');
    RemoveUploadBtns = [...RemoveUploadBtns];
    RemoveUploadBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        ShowConfirmModal({
          Title: 'Are You Sure You Want To Remove This File?',
          ConfirmText: 'Remove',
          onConfirm: () => DeletePostUploadedFiles(btn,Type),
          Action : 'Close'

        });

      });
    });
}

async function DeletePost(){
  const formData = new FormData();
  formData.append('ReqType', 6);
  formData.append('FeedPostID', currentPostID);

  fetch('Origin/Operations/User.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        window.location.reload();
      }
    })
    .catch(error => console.error('Error:', error));

}


// Handles file uploads for documents and images
function handlePostFileUpload(inputElement, allowedExtensions, otherInput, Type) {

  if (otherInput.files.length > 0) {
    inputElement.value = '';

    validSelectedFiles = [];
    alert('You can only upload one file type at a time. Please choose either images or documents.');
  }

  if (validSelectedFiles.length + inputElement.files.length  > MAX_FILE_COUNT) {
      //filesOmitted = true;
      alert(`You cannot upload more than ${MAX_FILE_COUNT} files at a time. Please clear the previous selection.`);
      return;
  }


  let filesOmitted = false;
  const omittedFileNames = [];

  const dataTransfer = new DataTransfer();

  validSelectedFiles.forEach(file => dataTransfer.items.add(file));

  for (const file of inputElement.files) {
    if (file.size > MAX_FILE_SIZE) {
      alert(`File ${file.name} exceeds the maximum allowed size of ${MAX_FILE_SIZE / 1024 / 1024} MB and will be removed.`);
      continue;
    }

    if (dataTransfer.files.length >= MAX_FILE_COUNT) {
      filesOmitted = true;
      omittedFileNames.push(file.name);
      break;
    }

    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExtension)) {
      alert(`Invalid file type: ${file.name}.`);
      break;
    }

    dataTransfer.items.add(file);
    validSelectedFiles.push(file);
  }

  if (filesOmitted) {
    //alert(`You cannot upload more than ${MAX_FILE_COUNT} files. Extra files have been removed.`);
        alert(`You cannot upload more than ${MAX_FILE_COUNT} files , The following files were omitted:\n${omittedFileNames.join('\n')}`);

  }

  inputElement.files = dataTransfer.files;


  PopulatePostUploadedFiles(inputElement.files,Type);
}


document.addEventListener('DOMContentLoaded', () => {
  // Modal cancellation
  const modals = document.getElementsByClassName('Modal');
  [...modals].forEach(modal => {
    const cancelBtn = modal.getElementsByClassName('ModalCancel')[0];
    const cancelBtnAlt = modal.getElementsByClassName('ModalCancelBtn')[0];

    if(!modal.classList.contains('Confirm')){
        if (cancelBtn) {
          cancelBtn.addEventListener('click', () => toggleModal(modal, false));
        }
        if (cancelBtnAlt) {
          cancelBtnAlt.addEventListener('click', () => toggleModal(modal, false));
        }
    }
  });

  // Create post button
  const CreatePostBtn = document.getElementsByClassName('CreatePostBtn')[0];
  const createPostModal = document.getElementsByClassName('CPostContainer')[0];
  CreatePostBtn.addEventListener('click', () => toggleModal(createPostModal, true));

  // File upload handlers
  const postDocumentUpload = document.getElementsByClassName('DocumentPostUpload')[0];
  const postImageUpload = document.getElementsByClassName('ImagePostUpload')[0];

  postDocumentUpload.addEventListener('change', () => 
    handlePostFileUpload(postDocumentUpload, ALLOWED_DOCUMENT_EXTENSIONS, postImageUpload,1));
  
  postImageUpload.addEventListener('change', () => 
    handlePostFileUpload(postImageUpload, ALLOWED_IMAGE_EXTENSIONS, postDocumentUpload,2));


  // Post submission
  // Post submission
  const postForm = document.getElementsByClassName('CPost')[0];
  const postContent = document.getElementById('CPostContent');
  postForm.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('content', postContent.value);
    formData.append('ReqType', 1);

    if (postDocumentUpload.files.length > 0) {
      for (const file of postDocumentUpload.files) {
        formData.append('document[]', file);
      }
    } else if (postImageUpload.files.length > 0) {
      for (const file of postImageUpload.files) {
        formData.append('images[]', file);
      }
    }

    const submitBtn = postForm.getElementsByClassName('PostSubmitBtn')[0];
    const loader = postForm.getElementsByClassName('Loader')[0];

    submitBtn.disabled = true;
    loader.classList.remove('hidden');

    fetch('Origin/Operations/User.php', { method: 'POST', body: formData })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const feedContainer = document.getElementsByClassName('FeedContainer')[0];
          const newPostHTML = createPostHTML(data.post);
          feedContainer.insertAdjacentHTML('afterbegin', newPostHTML);
          const newPostElement = feedContainer.firstElementChild;
          attachPostInteractions(newPostElement);

          validSelectedFiles = [];
          postDocumentUpload.value = '';
          postImageUpload.value = '';
          postContent.value = '';
          toggleModal(createPostModal, false);
          showInfoBox('Post created successfully!',1);
        } else {
          alert(data.message || 'An error occurred.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An unexpected error occurred. Please try again.');
      })
      .finally(() => {
        submitBtn.disabled = false;
        loader.classList.add('hidden');
      });
  });

  // Comment submission
  const commentForm = document.getElementById('CreateNewComment');
  commentForm.addEventListener('submit', e => {
    e.preventDefault();
    console.log('Current Post ID (Submission):', currentPostID);
    const commentContent = commentForm.getElementsByClassName('CommentInput')[0].value;
    const formData = new FormData();
    formData.append('ReqType', 3);
    formData.append('FeedPostID', currentPostID);
    formData.append('CommentContent', commentContent);

    fetch('Origin/Operations/User.php', { method: 'POST', body: formData })
      .then(response => response.json())
      .then(() => {
        commentForm.getElementsByClassName('CommentInput')[0].value = '';
        window.location.reload();
      })
      .catch(error => console.error('Error:', error));
  });



  // Initialize existing posts
  const feedPosts = document.getElementsByClassName('FeedPost');
  [...feedPosts].forEach(post => attachPostInteractions(post));

  // Scroll event for infinite loading
  if( document.body.classList.contains('FetchPostsOnScroll')) {
    window.addEventListener('scroll', fetchMorePosts());
  }


  //content editable placeholder display on empty   and change UID in fetch 

  let CommentSectionModal= document.getElementsByClassName('CommentSection')[0];


  CommentSectionModal.addEventListener('input', (event) => {
    const targetElement = event.target;

    // Check if the event originated from a .CommentInput element
    if (targetElement && targetElement.classList.contains('CommentInput')) {

/*       //get parent element of the target element
      const Form = targetElement.parentElement;
      const hasReplyTag = targetElement.getElementsByClassName('ReplyTag')[0]; //reply tag in comment input
      if (!hasReplyTag) {
        Form.removeAttribute('replyto');
      } else {
        const uid = hasReplyTag.getAttribute('data-uid');
        Form.setAttribute('data-reply-to', uid);
      } */



      const cleanedText = targetElement.textContent.replace(/\u00A0/g, '').trim();
      // Now, you're working with the specific element that was changed
      if (cleanedText === '') {

          targetElement.textContent  = '';
          targetElement.classList.remove('has-content');
      } else {
          targetElement.classList.add('has-content');
      }
    }
  });

});