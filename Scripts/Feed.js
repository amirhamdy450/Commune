
import { Submit } from "./Forms.js";
//GLOBALS
const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 MB in bytes
const MAX_FILE_COUNT = 2;
const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx'];
const ALLOWED_IMAGE_EXTENSIONS = ['xbm', 'tif', 'jfif', 'ico', 'tiff', 'gif', 'svg', 'webp', 'svgz', 'jpg', 'jpeg', 'png', 'bmp', 'pjp', 'apng', 'pjpeg', 'avif'];

let validSelectedFiles = [];
let filesToDelete = []; //for editing posts
let currentPostID = null;


let CurrentConfirmModalFunction = null;

//check if pid is set in the URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('pid')) {
  currentPostID = urlParams.get('pid');
}

//follow function 
async function FollowHandler(followButton, uid){
  const formData = new FormData();
  formData.append('ReqType', 11);
  formData.append('UserID', uid);

  let data= await Submit('POST','Origin/Operations/Feed.php', formData);

  if (data.success) {
    if (!data.Followed) {
      followButton.classList.remove('Followed');
      followButton.classList.add('FollowBtn');
      followButton.textContent = 'Follow';
    } else {
      followButton.classList.remove('FollowBtn');
      followButton.classList.add('Followed');
      followButton.textContent = 'Following';
    }
  }

}


async function blockUser(uid, postElement){
  const formData = new FormData();
  formData.append('ReqType', 13);
  formData.append('BlockedUID', uid);

  let data= await Submit('POST','Origin/Operations/Feed.php', formData);

  if (data.success) {
    postElement.remove();


     //wait 
  }
}



async function savePost(post,postID){

  const formData = new FormData();
  formData.append('ReqType', 12);
  formData.append('PostID', postID);

  let data= await Submit('POST','Origin/Operations/Feed.php', formData);
  if (data.success) {
    showInfoBox(data.message,1);
    //update post attribute based on saved status
    if(data.Saved){
      post.setAttribute('Saved', '1');
    }else{
      post.setAttribute('Saved', '0');
    }


  }
  
}


// Creates HTML for a single post
export function createPostHTML(post) {
  const mediaContent = generateMediaContent(post);
  //DEPRECATED
/*   const postDeleteButton = post.CurrentUserPrivilege 
    ? `<div class="DeleteBtn PostDeleteBtn"><img src="Imgs/Icons/trash.png" alt=""></div>` 
    : ''; */
  //END OF DEPRECATED
  const likeIcon = post.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';

  let followbtn="";



  if(post.Self == 0){
    
    const following = post.following ? 'Followed' : '';

    const follow_text= post.following ? 'Following' : 'Follow';


    followbtn= `<button class="BrandBtn FollowBtn ${following} "> ${follow_text}</button>`;
    
  }





  /* const UrlSafeUID */
  return `
    <div class="FeedPost" PID="${post.PID}" UID="${post.UID}"  Self="${post.Self}"> 
      <div class="FeedPostHeader">
        <div class="FeedPostAuthorContainer">
          <a class="FeedPostAuthor" href="index.php?redirected_from=profile&target=profile&uid=${encodeURIComponent(post.UID)}">
            <img src="${post.ProfilePic}" alt="">
            <p>${post.name}</p>
          </a>
          
          ${followbtn}
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

  comment.name=comment.Fname + ' ' + comment.Lname;

  const isSelf = comment.IsSelf ? '1' : '0';
  

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

        <div class="ModalComment" Self="${isSelf}">
          <div class="ModalCommentHeader">
            <div class="ModalCommentAuthor">
              <img src="${comment.ProfilePic}" alt="">
              <p>${comment.name}</p>
            </div>
           <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg" alt="Options"></div>
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

        <div class="ModalComment" Self="${isSelf}">
          <div class="ModalCommentHeader">
            <div class="ModalCommentAuthor">
              <img src="${comment.SenderProfilePic}" alt="">
              <p>${comment.Sender}</p>
            </div>
           <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg" alt="Options"></div>

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

        fetch('Origin/Operations/Feed.php', { method: 'POST', body: formData })
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
export function attachPostInteractions(post) {
  console.log('Attaching interactions to post:', post);
  const postId = post.getAttribute('PID');
  const uid = post.getAttribute('UID');
  const followButton= post.getElementsByClassName('FollowBtn')[0];
  const likeButton = post.getElementsByClassName('FeedPostLike')[0];
  const commentButton = post.getElementsByClassName('FeedPostComment')[0];
  const shareButton = post.getElementsByClassName('FeedPostShare')[0];
  //const deleteButton = post.getElementsByClassName('PostDeleteBtn')[0];
  

  //optimizable way to deal with post options
  const actionButton = post.getElementsByClassName('ActionBtn')[0];
  


  if(followButton){
    followButton.addEventListener('click', () => {
      FollowHandler(followButton, uid);
    });


    
  }

  likeButton.addEventListener('click', () => {
    const formData = new FormData();
    formData.append('ReqType', 2);
    formData.append('FeedPostID', postId);

    fetch('Origin/Operations/Feed.php', { method: 'POST', body: formData })
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

    fetch('Origin/Operations/Feed.php', { method: 'POST', body: formData })
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
    const ShareLink = `${Url}?target=post&pid=${encodeURIComponent(postId)}`;



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

  if (false) {
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


  if (actionButton) {
    actionButton.addEventListener('click', (e) => {
      e.stopPropagation(); // Stop click from bubbling to the document

      // Remove any other open menus
      const existingMenu = document.querySelector('.PostOptionsMenu');
      if (existingMenu) {
        existingMenu.remove();
      }

      // Create new menu
      const menu = document.createElement('div');
      menu.className = 'ActionMenu PostContext';

      const postAuthorUID = post.getAttribute('UID');
      const postPID = post.getAttribute('PID');

      const IsSaved =post.getAttribute('Saved')==1;

      let menuOptions = '';
      
      
      const isSelfPost = post.getAttribute('Self') == 1;
      // Option 1: Hide Post
      menuOptions += `<div class="PostOption" data-action="hide"><img src="Imgs/Icons/EyeOff.svg" alt="">Hide Post</div>`;

      // Option 2: Save Post
      if(!IsSaved){
        menuOptions += `<div class="PostOption" data-action="save" data-pid="${postPID}"><img src="Imgs/Icons/save.svg" alt="">Save Post</div>`;
      }else{
        menuOptions += `<div class="PostOption" data-action="save" data-pid="${postPID}"><img src="Imgs/Icons/unsave.svg" alt="">Unsave Post</div>`;
      }



      // Option 3: Block User (if not self)
      if (!isSelfPost) {
          menuOptions += `<div class="PostOption" data-action="block" data-uid="${postAuthorUID}"><img src="Imgs/Icons/block.svg" alt="">Block User</div>`;
        }

        // Option 4: Delete Post (if self)
        if (isSelfPost) {
          menuOptions += `<div class="PostOption Delete" data-action="delete" data-pid="${postPID}"><img src="Imgs/Icons/trash.svg" alt="">Delete Post</div>`;
          menuOptions += `<div class="PostOption" data-action="edit" data-pid="${postPID}"><img src="Imgs/Icons/edit.svg" alt="">Edit Post</div>`; // Add Edit
        }

      menu.innerHTML = menuOptions;

      // Add click listeners to menu options
      menu.addEventListener('click', (e) => {
          e.stopPropagation();
          const target = e.target.closest('.PostOption');
          if (!target) return;

          const action = target.dataset.action;

          if (action === 'hide') {
              post.style.display = 'none'; // Simple hide
          } 
          else if (action === 'save') {
              savePost(post,postId);
          }
          else if (action === 'block') {
              //fetch user first and last name
              let PostAuthor= post.getElementsByClassName('FeedPostAuthor')[0];
              console.log(post);
              let Name= PostAuthor.getElementsByTagName('p')[0].innerText;
              ShowConfirmModal({
                  Title: 'Are You Sure You Want To Block ' + Name + '?',
                  ConfirmText: 'Block',
                  onConfirm: async() => await blockUser(uid, post),
                  Action: 'refresh'

              });
          }
          else if (action === 'edit') {
              openEditModal(target.dataset.pid); // Use target.dataset.pid
          }
          else if (action === 'delete') {
              currentPostID = target.dataset.pid;
              ShowConfirmModal({
                  Title: 'Are You Sure You Want To Delete This Post?',
                  ConfirmText: 'Delete',
                  onConfirm: () => DeletePost()
              });
          }
          menu.remove(); // Close menu after action
      });

      // Append menu to the post header
      post.getElementsByClassName('FeedPostHeader')[0].appendChild(menu);
    });
}
}


function toggleCommentMenu(event, type, id, element, isSelf) {
    event.stopPropagation();
    
    // Remove any existing menus first
    const existing = document.querySelector('.CommentOptionsMenu');
    if (existing) existing.remove();

    const menu = document.createElement('div');
    menu.className = 'ActionMenu CommentContext'; 
    
    // --- START DYNAMIC MENU BUILDER ---
    let menuHTML = '';

    // Option 1: Hide (Available to EVERYONE)
    // Note: We are using "ActionOption" here in the HTML
    menuHTML += `
        <div class="ActionOption" data-action="hide">
            <img src="Imgs/Icons/EyeOff.svg"> Hide
        </div>
    `;

    // Option 2: Delete (Available ONLY if Self)
    if (isSelf) {
        menuHTML += `
            <div class="ActionOption Delete" data-action="delete">
                <img src="Imgs/Icons/trash.svg"> Delete
            </div>
        `;
    }
    // --- END DYNAMIC MENU BUILDER ---

    menu.innerHTML = menuHTML;

    // Handle Clicks
    menu.addEventListener('click', (e) => {
        e.stopPropagation();
        const option = e.target.closest('.ActionOption');
        if (!option) return;

        const action = option.dataset.action;

        if (action === 'hide') {
            element.style.display = 'none'; // Temporary Hide
        } else if (action === 'delete') {
            ShowConfirmModal({
                Title: 'Delete this?',
                ConfirmText: 'Delete',
                onConfirm: async () => {
                    const formData = new FormData();
                    // Determine Request Type based on 'type' param (1=Comment, 2=Reply)
                    formData.append('ReqType', type === 1 ? 16 : 17);
                    formData.append(type === 1 ? 'CommentID' : 'ReplyID', id);

                    const data = await Submit('POST', 'Origin/Operations/Feed.php', formData);
                    if (data.success) {
                        element.remove();
                    } else {
                        alert(data.message);
                    }
                },
                Action: 'Close'
            });
        }
        menu.remove();
    });

    // Append to the header
    element.querySelector('.ModalCommentHeader').appendChild(menu);
    
    // Close menu when clicking outside
    const closeMenu = () => {
        menu.remove();
        document.removeEventListener('click', closeMenu);
    };
    document.addEventListener('click', closeMenu);
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


    let ModalComment = comment.getElementsByClassName('ModalComment')[0];




    const menuBtn = comment.querySelector('.ActionBtn');
    if (menuBtn) {
        menuBtn.addEventListener('click', (e) => {
            // Check the data attribute we set in createCommentHTML
            const isSelf = ModalComment.getAttribute('Self') === '1';
            toggleCommentMenu(e, 1, CommentID, comment, isSelf); // 1 = Comment Type
        });
    }

    likeButton.addEventListener('click', () => {
      const formData = new FormData();
      formData.append('ReqType', 7);
      formData.append('CommentID', CommentID);

      fetch('Origin/Operations/Feed.php', { method: 'POST', body: formData })
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

        Submit('POST', 'Origin/Operations/Feed.php', formData);

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

        fetch('Origin/Operations/Feed.php', { method: 'POST', body: formData })
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

    const menuBtn = reply.querySelector('.ActionBtn');

    let ModalComment = reply.getElementsByClassName('ModalComment')[0];

    if (menuBtn) {
        menuBtn.addEventListener('click', (e) => {
            // Check the data attribute we set in createCommentHTML
            const isSelf = ModalComment.getAttribute('Self') === '1';

            toggleCommentMenu(e, 2, ReplyID, reply, isSelf); // 2 = Reply Type
        });
    }

    likeButton.addEventListener("click", () => {
      const formData = new FormData();
      formData.append("ReqType", 9);
      formData.append("ReplyID", ReplyID);

      fetch("Origin/Operations/Feed.php", { method: "POST", body: formData })
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

        Submit('POST', 'Origin/Operations/Feed.php', formData);

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


// edit post function
async function openEditModal(postID) {
    const modal = document.getElementsByClassName('CPostContainer')[0];
    const form = document.getElementById('CreatePostForm');
    const contentTextarea = document.getElementById('CPostContent');
    const editIDField = document.getElementById('CPostEditID');
    const filesToDeleteField = document.getElementById('CPostFilesToDelete');
    const overviewContainer = modal.querySelector('.UploadOverview');
    const filesContainer = modal.querySelector('.UploadedFiles');
    
    // 1. Reset and configure modal for "Edit"
    form.reset();
    validSelectedFiles = [];
    filesToDelete = []; // Reset files to delete
    filesContainer.innerHTML = '';
    overviewContainer.classList.add('hidden');
    
    modal.querySelector('h1').textContent = 'Edit Post';
    form.querySelector('.PostSubmitBtn').value = 'Save Changes';
    editIDField.value = postID; // Set the PostID
    filesToDeleteField.value = '[]';
    
    // 2. Fetch post data from new API
    const formData = new FormData();
    formData.append('ReqType', 14);
    formData.append('FeedPostID', postID);

    try {
        const data = await Submit("POST", "Origin/Operations/Feed.php", formData);
        
        if (data.success) {
            // 3. Populate modal with existing data
            contentTextarea.value = data.Content;

            if (data.MediaFiles && data.MediaFiles.length > 0) {
                overviewContainer.classList.remove('hidden');
                let fileHTML = '';
                const isImage = data.MediaType === 2;
                const icon = isImage ? 'Imgs/Icons/Image.svg' : 'Imgs/Icons/Document.svg';

                data.MediaFiles.forEach(file => {
                    fileHTML += `
                    <div class="UploadedFile existing" data-filename="${file.name}">
                        <div class="FileName">
                            <img src="${icon}">
                            <p>${file.name}</p>
                        </div>
                        <span class="RemoveUploadedFile" data-filename="${file.name}">&times;</span>
                    </div>
                    `;
                });
                filesContainer.innerHTML = fileHTML;

                // 4. Attach listeners to new "remove" buttons
                filesContainer.querySelectorAll('.RemoveUploadedFile').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const fileDiv = btn.closest('.UploadedFile');
                        const filename = fileDiv.dataset.filename;
                        
                        ShowConfirmModal({
                          Title: 'Are You Sure You Want To Remove This File?',
                          ConfirmText: 'Remove',
                          onConfirm: () => {
                            // Add to delete list and hide from UI
                            filesToDelete.push(filename);
                            filesToDeleteField.value = JSON.stringify(filesToDelete); // Update hidden form field
                            fileDiv.classList.add('hidden'); // Hide it
                          },
                          Action : 'Close'
                        });
                    });
                });
            }
            toggleModal(modal, true); // Show modal *after* data is loaded
        } else {
            showInfoBox(data.message, 2); // Show error
        }
    } catch (error) {
        console.error('Error fetching post for edit:', error);
        showInfoBox('Could not load post for editing.', 2);
    }
}

//reset of edit post modal
function resetPostModal() {
    const form = document.getElementById('CreatePostForm');
    const modal = document.getElementsByClassName('CPostContainer')[0];
    
    form.reset();
    validSelectedFiles = [];
    filesToDelete = [];
    
    // Reset UI Elements
    modal.querySelector('h1').textContent = 'Create Post';
    form.querySelector('.PostSubmitBtn').value = 'Post';
    document.getElementById('CPostEditID').value = '';
    document.getElementById('CPostFilesToDelete').value = '[]';
    
    // Clear File Previews
    modal.querySelector('.UploadedFiles').innerHTML = '';
    modal.querySelector('.UploadOverview').classList.add('hidden');
    
    // Reset File Inputs (Cloning checks might be needed depending on browser, but reset() usually handles value)
    document.querySelector('.DocumentPostUpload').value = '';
    document.querySelector('.ImagePostUpload').value = '';
}

// handle post submit and edit
async function handlePostSubmit(e) {
    e.preventDefault();
    
    const form = document.getElementById('CreatePostForm');
    const editID = document.getElementById('CPostEditID').value;
    const isEditing = (editID !== '');
    
    const formData = new FormData(form); // Automatically grabs content & inputs
    
    // 1. Configure Request Type
    if (isEditing) {
        formData.append('ReqType', 15);
        // 'PostID' and 'files_to_delete' are already in formData via HTML inputs
    } else {
        formData.append('ReqType', 1);
    }

    // 2. Append New Files (from global array)
    // (Assuming handlePostFileUpload populates validSelectedFiles)
    if (validSelectedFiles.length > 0) {
        const fileType = validSelectedFiles[0].type.startsWith('image/') ? 'images[]' : 'document[]';
        validSelectedFiles.forEach(file => formData.append(fileType, file));
    }

    // 3. Submit
    const btn = form.querySelector('.PostSubmitBtn');
    const loader = form.querySelector('.Loader');
    
    btn.disabled = true;
    loader.classList.remove('hidden');

    try {
        const data = await Submit("POST", "Origin/Operations/Feed.php", formData);
        
        if (data.success) {
            if (isEditing) {
                updatePostInDOM(editID, data.post); // Updates specific post
                showInfoBox('Post updated successfully!', 1);
            } else {
                prependPostToFeed(data.post); // Adds new post to top
                showInfoBox('Post created successfully!', 1);
            }
            
            resetPostModal();
            toggleModal(document.getElementsByClassName('CPostContainer')[0], false);
        } else {
            showInfoBox(data.message || "Error processing post", 2);
        }
    } catch (error) {
        console.error(error);
        showInfoBox("An unexpected error occurred.", 2);
    } finally {
        btn.disabled = false;
        loader.classList.add('hidden');
    }
}

// Helper to update DOM (Clean separation of concerns)
function updatePostInDOM(pid, postData) {
    const oldElement = document.querySelector(`.FeedPost[PID="${pid}"]`);
    if (oldElement) {
        // Use your existing createPostHTML function
        const newHTML = createPostHTML(postData); 
        oldElement.outerHTML = newHTML;
        // Re-attach events to the new DOM node
        attachPostInteractions(document.querySelector(`.FeedPost[PID="${pid}"]`)); 
    }
}

// Helper to prepend DOM
function prependPostToFeed(postData) {
    const container = document.getElementsByClassName('FeedContainer')[0];
    container.insertAdjacentHTML('afterbegin', createPostHTML(postData));
    attachPostInteractions(container.firstElementChild);
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

  fetch('Origin/Operations/Feed.php', { method: 'POST', body: formData })
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
  CreatePostBtn.addEventListener('click', () => {
      resetPostModal();
      toggleModal(createPostModal, true)
  });

  // File upload handlers
  const postDocumentUpload = document.getElementsByClassName('DocumentPostUpload')[0];
  const postImageUpload = document.getElementsByClassName('ImagePostUpload')[0];

  postDocumentUpload.addEventListener('change', () => 
    handlePostFileUpload(postDocumentUpload, ALLOWED_DOCUMENT_EXTENSIONS, postImageUpload,1));
  
  postImageUpload.addEventListener('change', () => 
    handlePostFileUpload(postImageUpload, ALLOWED_IMAGE_EXTENSIONS, postDocumentUpload,2));


  // Post submission
  const postForm = document.getElementsByClassName('CPost')[0];
  const postContent = document.getElementById('CPostContent');
/*   postForm.addEventListener('submit', e => {
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

    fetch('Origin/Operations/Feed.php', { method: 'POST', body: formData })
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
  }); */

  postForm.addEventListener('submit', handlePostSubmit);
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

    fetch('Origin/Operations/Feed.php', { method: 'POST', body: formData })
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



  // follow btn in profile preview
  
  //check if the body has an id of VProfile
  if(document.body.id === 'VProfile'){
    const followButton= document.getElementsByClassName('FollowBtn')[0];
    const uid= followButton.getAttribute('uid');

    followButton.addEventListener('click', () => {
      FollowHandler(followButton, uid);
    });

  }


});