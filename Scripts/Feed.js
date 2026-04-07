
import { Submit, CsrfToken, TimeAgo } from "./Forms.js";

//GLOBALS
const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 MB in bytes
const MAX_FILE_COUNT = 2;
const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx'];
const ALLOWED_IMAGE_EXTENSIONS = ['xbm', 'tif', 'jfif', 'ico', 'tiff', 'gif', 'svg', 'webp', 'svgz', 'jpg', 'jpeg', 'png', 'bmp', 'pjp', 'apng', 'pjpeg', 'avif'];

let validSelectedFiles = [];
let filesToDelete = []; //for editing posts
let currentPostID = null;


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


// Converts @username mentions in plain text to clickable profile links
function renderMentions(text) {
  if (!text) return '';
  return text.replace(/@([\w]+)/g, '<a class="MentionLink" href="index.php?target=profile&username=$1">@$1</a>');
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
  const authorHeader = post.PageName
    ? `<a class="FeedPageBadge" href="index.php?target=page&handle=${encodeURIComponent(post.PageHandle)}">
        ${post.PageLogo
          ? `<img class="FeedPageLogo" src="${post.PageLogo}" alt="">`
          : `<div class="FeedPageLogoPlaceholder">${post.PageName.charAt(0).toUpperCase()}</div>`}
        <div class="FeedPostAuthorInfo">
          <div class="FeedPostNameRow">
            <p class="FeedPostAuthorName">${post.PageName}</p>
            ${post.PageIsVerified ? '<span class="BlueTick" title="Verified"></span>' : ''}
            <span class="PageTypeBadge">Page</span>
            ${post.Date ? `<span class="FeedPostTime" data-date="${post.Date}"></span>` : ''}
          </div>
          <span class="FeedPostUsername">@${post.PageHandle}</span>
        </div>
      </a>`
    : `<a class="FeedPostAuthor" href="index.php?redirected_from=profile&target=profile&uid=${encodeURIComponent(post.UID)}">
        <img src="${post.ProfilePic}" alt="">
        <div class="FeedPostAuthorInfo">
          <div class="FeedPostNameRow">
            <p class="FeedPostAuthorName">${post.name}</p>
            ${post.IsBlueTick ? '<span class="BlueTick" title="Verified"></span>' : ''}
            ${post.Date ? `<span class="FeedPostTime">· ${TimeAgo(post.Date)}</span>` : ''}
          </div>
          ${post.Username ? `<span class="FeedPostUsername">@${post.Username}</span>` : ''}
        </div>
      </a>
      ${followbtn}`;

  return `
    <div class="FeedPost${post.PageName ? ' PageFeedPost' : ''}" PID="${post.PID}" UID="${post.UID}"  Self="${post.Self}">
      <div class="FeedPostHeader">
        <div class="FeedPostAuthorContainer">
          ${authorHeader}
        </div>
        <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>
    



      </div>
      <div class="FeedPostContent">
        <p>${renderMentions(post.Content)}</p>
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


export function createCommentHTML(comment,type=1){
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
  

  const blueTick = comment.IsBlueTick ? '<span class="BlueTick" title="Verified"></span>' : '';
  const timeAgo = comment.Date ? TimeAgo(comment.Date) : '';
  const profileUrl = `index.php?target=profile&uid=${encodeURIComponent(comment.UID)}`;

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
            <a class="ModalCommentAuthor" href="${profileUrl}">
              <img src="${comment.ProfilePic}" alt="">
              <div class="ModalCommentAuthorInfo">
                <div class="ModalCommentNameRow">
                  <span class="ModalCommentName">${comment.name}</span>
                  ${blueTick}
                  ${timeAgo ? `<span class="ModalCommentTime">· ${timeAgo}</span>` : ''}
                </div>
                <span class="ModalCommentUsername">@${comment.Username}</span>
              </div>
            </a>
           <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg" alt="Options"></div>
          </div>
          <div class="ModalCommentContent">
            <p>${renderMentions(comment.comment)}</p>
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
        TaggedUser = `<a class="ReplyTag MentionLink" href="index.php?target=profile&username=${encodeURIComponent(comment.TaggedUser)}">@${comment.TaggedUser}</a>`;
      }else{
        TaggedUser = '';
      }


        const replyBlueTick = comment.IsBlueTick ? '<span class="BlueTick" title="Verified"></span>' : '';
        const replyTimeAgo = comment.Date ? TimeAgo(comment.Date) : '';
        const replyProfileUrl = `index.php?target=profile&uid=${encodeURIComponent(comment.UID)}`;

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
            <a class="ModalCommentAuthor" href="${replyProfileUrl}">
              <img src="${comment.SenderProfilePic}" alt="">
              <div class="ModalCommentAuthorInfo">
                <div class="ModalCommentNameRow">
                  <span class="ModalCommentName">${comment.Sender}</span>
                  ${replyBlueTick}
                  ${replyTimeAgo ? `<span class="ModalCommentTime">· ${replyTimeAgo}</span>` : ''}
                </div>
                ${comment.SenderUsername ? `<span class="ModalCommentUsername">@${comment.SenderUsername}</span>` : ''}
              </div>
            </a>
           <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg" alt="Options"></div>

          </div>
          <div class="ModalCommentContent">
             ${TaggedUser} <p>${renderMentions(comment.Reply)}</p>
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

        fetch('Origin/Operations/Feed.php', { method: 'POST', headers: { 'X-CSRF-Token': CsrfToken }, body: formData })
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

    fetch('Origin/Operations/Feed.php', { method: 'POST', headers: { 'X-CSRF-Token': CsrfToken }, body: formData })
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

    fetch('Origin/Operations/Feed.php', { method: 'POST', headers: { 'X-CSRF-Token': CsrfToken }, body: formData })
      .then(response => response.json())
      .then(data => {
        const commentsContainer = document.getElementsByClassName('ModalCommentsContainer')[0];

        commentsContainer.innerHTML = '';

        if(data && data.length !== 0) {
          data.forEach(comment => {

            commentsContainer.insertAdjacentHTML('beforeend', createCommentHTML(comment));


            
          });
          attachCommentInteractions();

        }else{
          commentsContainer.insertAdjacentHTML('beforeend', `
                <div class="NoComments">
                    <img src="Imgs/Icons/comment.svg" alt="">
                    <h4>No comments yet</h4>
                    <p>Be the first to share your thoughts.</p>
                </div>
          `);
        }
        let CommentSectionModal = document.getElementsByClassName('CommentSection')[0];
        toggleModal(CommentSectionModal, true);
      })
      .catch(error => console.error('Error:', error));

    currentPostID = postId;

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
      showInfoBox('Failed to copy link. Please try again.', 2);
    }





  });

if (actionButton) {
    actionButton.addEventListener('click', (e) => {
      e.stopPropagation();
      const existingMenu = document.querySelector('.PostOptionsMenu');
      if (existingMenu) existingMenu.remove();

      const menu = document.createElement('div');
      menu.className = 'ActionMenu PostContext';
      const postAuthorUID = post.getAttribute('UID');
      const postPID = post.getAttribute('PID');
      const IsSaved =post.getAttribute('Saved')==1;
      let menuOptions = '';
      const isSelfPost = post.getAttribute('Self') == 1;

      // --- FIX: Only show "Hide Post" if we are NOT on the single post page ---
      const isSinglePostView = document.body.classList.contains('PostView');
      
      if (!isSinglePostView) {
          menuOptions += `<div class="PostOption" data-action="hide"><img src="Imgs/Icons/EyeOff.svg">Hide Post</div>`;
      }
      
      if(!IsSaved){
        menuOptions += `<div class="PostOption" data-action="save" data-pid="${postPID}"><img src="Imgs/Icons/save.svg">Save Post</div>`;
      }else{
        menuOptions += `<div class="PostOption" data-action="save" data-pid="${postPID}"><img src="Imgs/Icons/unsave.svg">Unsave Post</div>`;
      }

      if (!isSelfPost) {
          menuOptions += `<div class="PostOption" data-action="block" data-uid="${postAuthorUID}"><img src="Imgs/Icons/block.svg">Block User</div>`;
      }
      if (isSelfPost) {
          menuOptions += `<div class="PostOption Delete" data-action="delete" data-pid="${postPID}"><img src="Imgs/Icons/trash.svg">Delete Post</div>`;
          menuOptions += `<div class="PostOption" data-action="edit" data-pid="${postPID}"><img src="Imgs/Icons/edit.svg">Edit Post</div>`; 
      }

      menu.innerHTML = menuOptions;
      menu.addEventListener('click', (e) => {
          e.stopPropagation();
          const target = e.target.closest('.PostOption');
          if (!target) return;
          const action = target.dataset.action;

          if (action === 'hide') {
              post.style.display = 'none';
          } else if (action === 'save') {
              savePost(post,postId);
          } else if (action === 'block') {
              let PostAuthor= post.getElementsByClassName('FeedPostAuthor')[0];
              let Name= PostAuthor.getElementsByTagName('p')[0].innerText;
              ShowConfirmModal({
                  Title: 'Block ' + Name + '?',
                  ConfirmText: 'Block',
                  onConfirm: async() => await blockUser(uid, post),
                  Action: 'refresh'
              });
          } else if (action === 'edit') {
              openEditModal(target.dataset.pid);
          } else if (action === 'delete') {
              currentPostID = target.dataset.pid;
              ShowConfirmModal({
                  Title: 'Delete This Post?',
                  ConfirmText: 'Delete',
                  onConfirm: () => DeletePost()
              });
          }
          menu.remove();
      });
      post.getElementsByClassName('FeedPostHeader')[0].appendChild(menu);
    });
  }
}


// Attaches a delegated paste listener to a container so that any contenteditable .CommentInput
// inside it receives plain text only — preventing browsers from pasting rich HTML with inline
// styles, background colors, font changes, or scroll containers from external sources.
export function attachPlainTextPaste(container) {
  container.addEventListener('paste', (e) => {
    const target = e.target;
    // Only intercept paste on contenteditable elements
    if (target.getAttribute('contenteditable') !== 'true') return;

    e.preventDefault();

    // Extract plain text from clipboard
    const plainText = e.clipboardData ? e.clipboardData.getData('text/plain') : '';
    if (!plainText) return;

    // Insert at cursor position using Selection API, which correctly handles
    // cursor placement even when a non-editable ReplyTag span is present
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return;

    const range = selection.getRangeAt(0);
    // Delete any currently selected text first
    range.deleteContents();

    const textNode = document.createTextNode(plainText);
    range.insertNode(textNode);

    // Move cursor to end of inserted text
    range.setStartAfter(textNode);
    range.setEndAfter(textNode);
    selection.removeAllRanges();
    selection.addRange(range);

    // Trigger input event so the placeholder/has-content logic still runs
    target.dispatchEvent(new Event('input', { bubbles: true }));
  });
}

// ─── MENTION SYSTEM ──────────────────────────────────────────────────────────
// Attaches @mention dropdown behavior to all contenteditable .CommentInput
// elements inside the given container. Call once per container on init.
export function attachMentionDropdown(container) {
  // Tracks per-input state: the active @ query start offset and the dropdown el
  let mentionState = null; // { input, atOffset, dropdown }
  // exposed so callers (e.g. modal close) can force-close the dropdown
  container._closeMentionDropdown = () => closeDropdown();
  let mentionDebounce = null;

  // ── helpers ──────────────────────────────────────────────────────────────

  function getTextBeforeCaret(input) {
    // Returns plain text from start of contenteditable up to caret,
    // skipping non-editable ReplyTag spans (contenteditable=false)
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return '';

    // Walk only editable text nodes inside the input and concatenate until we hit
    // the caret's container/offset — this correctly skips non-editable spans
    const caretRange = sel.getRangeAt(0);
    const walker = document.createTreeWalker(input, NodeFilter.SHOW_TEXT);
    let text = '';
    while (walker.nextNode()) {
      const node = walker.currentNode;
      // Skip text inside non-editable spans
      if (node.parentElement && node.parentElement.getAttribute('contenteditable') === 'false') continue;
      if (node === caretRange.endContainer) {
        text += node.textContent.slice(0, caretRange.endOffset);
        break;
      }
      text += node.textContent;
    }
    return text;
  }

  function closeDropdown() {
    if (mentionState && mentionState.dropdown) {
      mentionState.dropdown.remove();
    }
    mentionState = null;
    clearTimeout(mentionDebounce);
  }

  function insertMentionTag(input, atOffset, user) {
    // Remove the typed "@query" text from the input, then insert the tag span
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;

    // Walk text nodes to find and delete from the @ sign up to the caret
    const range = sel.getRangeAt(0).cloneRange();

    // Find the text node containing the @ and remove from it to caret
    // We use a TreeWalker to accumulate characters until we reach atOffset
    const walker = document.createTreeWalker(input, NodeFilter.SHOW_TEXT);
    let chars = 0;
    let atNode = null, atNodeOffset = 0;

    while (walker.nextNode()) {
      const node = walker.currentNode;
      // Skip text inside non-editable spans (ReplyTag)
      if (node.parentElement && node.parentElement.getAttribute('contenteditable') === 'false') continue;
      if (chars + node.length > atOffset) {
        atNode = node;
        atNodeOffset = atOffset - chars;
        break;
      }
      chars += node.length;
    }

    if (!atNode) return;

    // Delete from @ to caret
    const deleteRange = document.createRange();
    deleteRange.setStart(atNode, atNodeOffset);
    deleteRange.setEnd(range.endContainer, range.endOffset);
    deleteRange.deleteContents();

    // Build and insert the mention tag span
    const tag = document.createElement('span');
    tag.className = 'ReplyTag MentionTag';
    tag.setAttribute('contenteditable', 'false');
    tag.setAttribute('mention-uid', user.EncUID);
    tag.setAttribute('mention-username', user.Username);
    tag.textContent = '@' + user.Username;

    // Re-get selection after deletion
    const selAfter = window.getSelection();
    const rangeAfter = selAfter.getRangeAt(0);
    rangeAfter.insertNode(tag);

    // Place cursor after the inserted tag
    rangeAfter.setStartAfter(tag);
    rangeAfter.setEndAfter(tag);

    // Add a trailing space text node so cursor lands naturally after tag
    const space = document.createTextNode('\u00A0');
    rangeAfter.insertNode(space);
    rangeAfter.setStartAfter(space);
    rangeAfter.setEndAfter(space);

    selAfter.removeAllRanges();
    selAfter.addRange(rangeAfter);

    input.dispatchEvent(new Event('input', { bubbles: true }));
    closeDropdown();
  }

  function renderDropdown(input, users, atOffset) {
    // Remove any existing dropdown
    if (mentionState && mentionState.dropdown) mentionState.dropdown.remove();

    if (users.length === 0) { closeDropdown(); return; }

    const dropdown = document.createElement('div');
    dropdown.className = 'MentionDropdown';

    users.forEach(user => {
      const item = document.createElement('div');
      item.className = 'MentionItem';

      const followBadge = user.IFollowThem == 1
        ? '<span class="MentionFollowBadge">Follows you</span>'
        : '';
      const blueTick = user.IsBlueTick == 1
        ? '<span class="BlueTick" title="Verified"></span>'
        : '';

      item.innerHTML = `
        <img class="MentionAvatar" src="${user.ProfilePic}" alt="">
        <div class="MentionUserInfo">
          <div class="MentionNameRow">
            <span class="MentionName">${user.Fname} ${user.Lname}</span>
            ${blueTick}
            ${followBadge}
          </div>
          <span class="MentionUsername">@${user.Username}</span>
        </div>
      `;

      // mousedown (not click) so it fires before the input loses focus
      item.addEventListener('mousedown', (e) => {
        e.preventDefault();
        insertMentionTag(input, atOffset, user);
      });

      dropdown.appendChild(item);
    });

    // Position at the caret, not the input element rect
    const sel = window.getSelection();
    let caretRect = null;
    if (sel && sel.rangeCount > 0) {
      const r = sel.getRangeAt(0).getBoundingClientRect();
      // getBoundingClientRect on a collapsed range gives a zero-width rect at caret
      if (r && r.width >= 0 && r.height > 0) caretRect = r;
    }
    // Fall back to input element rect if caret rect is unavailable
    const anchorRect = caretRect || input.getBoundingClientRect();
    const spaceBelow = window.innerHeight - anchorRect.bottom;
    const goDown = spaceBelow > 240;

    dropdown.style.width = '272px';
    dropdown.style.left = Math.min(anchorRect.left, window.innerWidth - 280) + 'px';

    if (goDown) {
      dropdown.style.top = (anchorRect.bottom + 6) + 'px';
      dropdown.style.bottom = 'auto';
    } else {
      dropdown.style.bottom = (window.innerHeight - anchorRect.top + 6) + 'px';
      dropdown.style.top = 'auto';
    }

    document.body.appendChild(dropdown);

    // Update state with the live dropdown reference
    if (mentionState) mentionState.dropdown = dropdown;
  }

  async function fetchMentionUsers(query, input, atOffset) {
    const formData = new FormData();
    formData.append('ReqType', 5);
    formData.append('query', query);

    try {
      const res = await fetch('Origin/Operations/Search.php', {
        method: 'POST',
        headers: { 'X-CSRF-Token': CsrfToken },
        body: formData
      });
      const data = await res.json();
      // Only render if mention state is still active for this input
      if (mentionState && mentionState.input === input) {
        renderDropdown(input, data.users || [], atOffset);
      }
    } catch (e) {
      // Silently ignore network errors in mention lookup
    }
  }

  // ── delegated input listener ──────────────────────────────────────────────

  container.addEventListener('input', (e) => {
    const input = e.target;
    if (input.getAttribute('contenteditable') !== 'true') return;

    const textBefore = getTextBeforeCaret(input);
    // Find last unspaced @ in the text before caret
    const atMatch = textBefore.match(/@([\w]*)$/);

    if (!atMatch) {
      // No active @ sequence — close dropdown if open
      if (mentionState && mentionState.input === input) closeDropdown();
      return;
    }

    const query = atMatch[1];                    // text after @
    const atOffset = textBefore.length - query.length - 1; // char offset of the @

    // Update or initialise mention state
    if (mentionState && mentionState.input === input) {
      mentionState.atOffset = atOffset;
    } else {
      closeDropdown();
      mentionState = { input, atOffset, dropdown: null };
    }

    clearTimeout(mentionDebounce);
    // Show immediately on bare @ with no query yet; debounce only when filtering by typed chars
    const delay = query.length === 0 ? 0 : 250;
    mentionDebounce = setTimeout(() => {
      fetchMentionUsers(query, input, atOffset);
    }, delay);
  });

  // Close dropdown on keydown Escape or arrow navigation
  container.addEventListener('keydown', (e) => {
    if (!mentionState) return;
    if (e.key === 'Escape') { closeDropdown(); return; }

    if (!mentionState.dropdown) return;

    const items = mentionState.dropdown.getElementsByClassName('MentionItem');
    const focused = mentionState.dropdown.querySelector('.MentionItem.Focused');
    let idx = -1;
    if (focused) idx = [...items].indexOf(focused);

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (focused) focused.classList.remove('Focused');
      const next = items[Math.min(idx + 1, items.length - 1)];
      if (next) next.classList.add('Focused');
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (focused) focused.classList.remove('Focused');
      const prev = items[Math.max(idx - 1, 0)];
      if (prev) prev.classList.add('Focused');
    } else if (e.key === 'Enter' || e.key === 'Tab') {
      const focusedItem = mentionState.dropdown.querySelector('.MentionItem.Focused');
      if (focusedItem) {
        e.preventDefault();
        focusedItem.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
      }
    }
  });

  // Close when the input loses focus (with a small delay so mousedown on item fires first)
  container.addEventListener('focusout', (e) => {
    if (!mentionState) return;
    if (e.target.getAttribute('contenteditable') !== 'true') return;
    setTimeout(() => {
      // Only close if focus didn't move inside the dropdown
      if (!mentionState) return;
      const dd = mentionState.dropdown;
      if (dd && dd.contains(document.activeElement)) return;
      closeDropdown();
    }, 150);
  });
}

// Returns an array of encrypted UIDs from all MentionTag spans inside a CommentInput element
export function collectMentions(inputEl) {
  const tags = inputEl.getElementsByClassName('MentionTag');
  const uids = [];
  const seen = new Set();
  for (const tag of tags) {
    const uid = tag.getAttribute('mention-uid');
    if (uid && !seen.has(uid)) { seen.add(uid); uids.push(uid); }
  }
  return uids;
}
// ─── END MENTION SYSTEM ───────────────────────────────────────────────────────

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
                        showInfoBox(data.message || 'Failed to delete.', 2);
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

export function attachCommentInteractions(specificContainer = null) {
  let container;
  if (specificContainer) {
      container = specificContainer;
  } else {
      container = document.getElementsByClassName('CommentSection')[0];
  }

  if (!container) return;

  
  let Comments= container.getElementsByClassName('CommentContainer');


  [...Comments].forEach(comment => {

    if(comment.classList.contains('Reply')){ //skip replies , we handle them through the main comment
      return;
    }

    // Skip if interactions already attached — prevents duplicate listeners when re-calling after live insert
    if(comment.dataset.bound === '1') return;
    comment.dataset.bound = '1';

    const likeButton = comment.getElementsByClassName('FeedPostLike')[0];
    const commentButton = comment.getElementsByClassName('FeedPostComment')[0];
    const ViewRepliesButton = comment.getElementsByClassName('ViewRepliesBtn')[0];
   

    let meta = comment.getElementsByClassName('meta')[0];
    let attrs = meta.getElementsByClassName('CMDI073')[0]; //for encryption

    const CommentID = attrs.getAttribute('cid');


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

      fetch('Origin/Operations/Feed.php', { method: 'POST', headers: { 'X-CSRF-Token': CsrfToken }, body: formData })
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

          // Attach submit listener ONLY when the form is first created to prevent stacking listeners on repeat clicks
          CreateReply.addEventListener('submit',  (e) => {
            e.preventDefault();

            const CommentInputEl = CreateReply.getElementsByClassName('CommentInput')[0];
            let Unfiltered = CommentInputEl.innerHTML;
            const Reply = Unfiltered.replace(/<[^>]+contenteditable="false"[^>]*>.*?<\/[^>]+>/gi, '').replace(/<[^>]*>/g, '').trim(); // strip any other HTML tags

            // Collect any @mention tags the user inserted via the dropdown
            const Mentions = collectMentions(CommentInputEl);

            const formData = new FormData();
            formData.append('ReqType', 8);
            formData.append('CommentID', CommentID);
            formData.append('Reply', Reply);
            // Append each mention UID so the backend can fire mention notifications
            Mentions.forEach(uid => formData.append('Mentions[]', uid));

            Submit('POST', 'Origin/Operations/Feed.php', formData).then(data => {
              if (data.success) {
                CreateReply.remove();
                // Live-insert the new reply into the RepliesContainer without reload
                let RepliesContainer = comment.getElementsByClassName('RepliesContainer')[0];
                RepliesContainer.classList.remove('hidden');
                if (data.reply) {
                  const replyHTML = createCommentHTML(data.reply, 2);
                  RepliesContainer.insertAdjacentHTML('beforeend', replyHTML);
                  const newReply = RepliesContainer.getElementsByClassName('CommentContainer')[RepliesContainer.getElementsByClassName('CommentContainer').length - 1];
                  attachReplyInteractions(newReply, comment);
                }
                // Update "X Replies" button counter
                if (ViewRepliesButton) {
                  const currentCount = parseInt(ViewRepliesButton.textContent) || 0;
                  ViewRepliesButton.textContent = (currentCount + 1) + ' Replies';
                }
                showInfoBox('Reply posted!', 1);
              } else {
                showInfoBox(data.message || 'Failed to post reply.', 2);
              }
            });

            //createReply(comment,);
          });
      }

    });


    if(ViewRepliesButton){

      ViewRepliesButton.addEventListener('click', () => {
            let RepliesContainer = comment.getElementsByClassName('RepliesContainer')[0];
            RepliesContainer.classList.remove('hidden');

            // If replies already loaded, just toggle visibility — don't re-fetch
            if (RepliesContainer.dataset.loaded === '1') return;

        const formData = new FormData();
        formData.append('ReqType', 10);
        formData.append('CommentID', CommentID);

        fetch('Origin/Operations/Feed.php', { method: 'POST', headers: { 'X-CSRF-Token': CsrfToken }, body: formData })
          .then(response => response.json())
          .then(data => {
            let RepliesContainer = comment.getElementsByClassName('RepliesContainer')[0];
            RepliesContainer.classList.remove('hidden');

            // Mark as loaded so re-clicking the button doesn't re-fetch
            RepliesContainer.dataset.loaded = '1';

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
    // Skip if interactions already attached — prevents duplicate listeners on re-attach
    if (reply.dataset.bound === '1') return;
    reply.dataset.bound = '1';

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

      fetch("Origin/Operations/Feed.php", { method: "POST", headers: { 'X-CSRF-Token': CsrfToken }, body: formData })
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
        <form class="CreateModalComment CreateCommentReply">
              <div contenteditable="true" class="CommentInput" rows="1">
              <span class="ReplyTag" contenteditable="false" replyto="${ReplyUserID}">@${ReplyTo}</span>

              </div>
              <input type="submit" value="" class="BrandBtn CommentSubmitBtn">

        </form>

        `
        );

        //reselect CreateCommentReply for the newly created form
        CreateReply = parentComment.getElementsByClassName("CreateCommentReply")[0];

        // Attach submit listener ONLY when the form is first created to prevent stacking listeners on repeat reply clicks
        CreateReply.addEventListener('submit', (e) => {
          e.preventDefault();

          let meta = parentComment.getElementsByClassName('meta')[0];
          let attrs = meta.getElementsByClassName('CMDI073')[0]; //for encryption
          const CommentID = attrs.getAttribute('cid');

          //strip HTML tags from contenteditable, keeping only plain text reply content
          const CommentInputEl = CreateReply.getElementsByClassName('CommentInput')[0];
          let Unfiltered = CommentInputEl.innerHTML;
          const Reply = Unfiltered.replace(/<[^>]+contenteditable="false"[^>]*>.*?<\/[^>]+>/gi, '').replace(/<[^>]*>/g, '').trim(); // strip any other HTML

          // Collect any @mention tags the user inserted via the dropdown
          const Mentions = collectMentions(CommentInputEl);

          const formData = new FormData();
          formData.append('ReqType', 8);
          formData.append('CommentID', CommentID);
          formData.append('Reply', Reply);
          formData.append('ReplyTo', ReplyUserID);
          // Append each mention UID so the backend can fire mention notifications
          Mentions.forEach(uid => formData.append('Mentions[]', uid));

          Submit('POST', 'Origin/Operations/Feed.php', formData).then(data => {
            if (data.success) {
              CreateReply.remove();
              // Live-insert the new reply into the RepliesContainer without reload
              let RepliesContainer = parentComment.getElementsByClassName('RepliesContainer')[0];
              RepliesContainer.classList.remove('hidden');
              if (data.reply) {
                const replyHTML = createCommentHTML(data.reply, 2);
                RepliesContainer.insertAdjacentHTML('beforeend', replyHTML);
                const newReply = RepliesContainer.getElementsByClassName('CommentContainer')[RepliesContainer.getElementsByClassName('CommentContainer').length - 1];
                attachReplyInteractions(newReply, parentComment);
              }
              // Update "X Replies" button counter on the parent comment
              const parentViewRepliesBtn = parentComment.getElementsByClassName('ViewRepliesBtn')[0];
              if (parentViewRepliesBtn) {
                const currentCount = parseInt(parentViewRepliesBtn.textContent) || 0;
                parentViewRepliesBtn.textContent = (currentCount + 1) + ' Replies';
              }
              showInfoBox('Reply posted!', 1);
            } else {
              showInfoBox(data.message || 'Failed to post reply.', 2);
            }
          });

          //createReply(comment,);
        });

      }else{
        //just append the tag to the existing reply form (form already exists, just update the @mention tag)
        let CommentInput = CreateReply.getElementsByClassName("CommentInput")[0];

        //reset placeholder in CommentInput
        CommentInput.setAttribute("placeholder", "");


        //check if a tag already exists
        let ReplyTag = CommentInput.getElementsByClassName("ReplyTag")[0];
        if(ReplyTag){
          //reset existing tag to the new reply target
          ReplyTag.innerHTML = "@" + ReplyTo;
          ReplyTag.setAttribute("replyto", ReplyUserID);
        }else{

         CommentInput.insertAdjacentHTML("beforeend", `<span class="ReplyTag" contenteditable="false" replyto="${ReplyUserID}">@${ReplyTo}</span>`)
        }
      }

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
    // Close any open mention dropdown that belongs to this modal
    if (typeof modal._closeMentionDropdown === 'function') modal._closeMentionDropdown();
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
            // 3. Populate modal with existing data — reconstruct mention chips with encrypted UIDs
            const mentionMap = data.MentionMap || {};
            const safeContent = data.Content
                ? data.Content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                : '';
            contentTextarea.innerHTML = safeContent.replace(/@([\w]+)/g, (_match, username) => {
                const encUID = mentionMap[username] || '';
                return `<span class="ReplyTag MentionTag" contenteditable="false" mention-uid="${encUID}" mention-username="${username}">@${username}</span>`;
            });

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

    form.reset(); // Resets hidden inputs and file inputs
    // CPostContent is a contenteditable div — form.reset() doesn't clear it
    const contentDiv = document.getElementById('CPostContent');
    if (contentDiv) { contentDiv.innerHTML = ''; contentDiv.classList.remove('has-content'); }

    validSelectedFiles = [];
    filesToDelete = [];
    
    // Reset UI Elements
    modal.querySelector('h1').textContent = 'Create Post';
    form.querySelector('.PostSubmitBtn').value = 'Post';
    document.getElementById('CPostEditID').value = '';
    document.getElementById('CPostFilesToDelete').value = '[]';

    // Reset PostAs switcher to self
    const postAsPageID = document.getElementById('CPostAsPageID');
    if (postAsPageID) postAsPageID.value = '';
    const postAsDropdown = document.getElementById('PostAsDropdown');
    if (postAsDropdown) postAsDropdown.classList.add('hidden');
    const selfOption = document.querySelector('.PostAsOptionSelf');
    if (selfOption && window.SelectPostAsOption) window.SelectPostAsOption(selfOption);

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

    const formData = new FormData(form); // Grabs hidden inputs (PostID, files_to_delete) and file inputs

    // CPostContent is now a contenteditable div so FormData won't capture it automatically —
    // read its plain text and append manually
    const contentDiv = document.getElementById('CPostContent');
    const postContentText = contentDiv.innerText.trim();
    formData.set('content', postContentText); // 'set' overwrites any stale value from old textarea

    // Collect @mention UIDs inserted via the dropdown
    const postMentions = collectMentions(contentDiv);
    postMentions.forEach(uid => formData.append('Mentions[]', uid));
    
    // 1. Configure Request Type
    if (isEditing) {
        formData.append('ReqType', 15);
        // 'PostID' and 'files_to_delete' are already in formData via HTML inputs
    } else {
        formData.append('ReqType', 1);
        const postAsPageID = document.getElementById('CPostAsPageID');
        if (postAsPageID && postAsPageID.value) {
            formData.set('PostAsPageID', postAsPageID.value);
        }
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

  fetch('Origin/Operations/Feed.php', { method: 'POST', headers: { 'X-CSRF-Token': CsrfToken }, body: formData })
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

    fetch('Origin/Operations/Feed.php', { method: 'POST', headers: { 'X-CSRF-Token': CsrfToken }, body: formData })
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

  // Enable @mention dropdown and plain-text paste in the create/edit post modal
  attachPlainTextPaste(createPostModal);
  attachMentionDropdown(createPostModal);
  // Comment submission
  const commentForm = document.getElementById('CreateNewComment');
  commentForm.addEventListener('submit', e => {
    e.preventDefault();
    const commentInputEl = commentForm.getElementsByClassName('CommentInput')[0];
    // Strip all HTML tags (including any accidentally pasted markup), keeping plain text only
    const commentContent = commentInputEl.innerHTML.replace(/<[^>]*>/g, '').trim();
    if (!commentContent) return;

    // Collect @mention UIDs inserted via the dropdown
    const Mentions = collectMentions(commentInputEl);

    const formData = new FormData();
    formData.append('ReqType', 3);
    formData.append('FeedPostID', currentPostID);
    formData.append('CommentContent', commentContent);
    Mentions.forEach(uid => formData.append('Mentions[]', uid));

    Submit('POST', 'Origin/Operations/Feed.php', formData)
      .then(data => {
        if (data.success) {
          // Clear contenteditable input
          commentInputEl.innerHTML = '';
          commentInputEl.classList.remove('has-content');

          // Remove empty state if present
          const commentsContainer = document.getElementsByClassName('ModalCommentsContainer')[0];
          const noComments = commentsContainer.getElementsByClassName('NoComments')[0];
          if (noComments) noComments.remove();

          // Insert the new comment live at the top
          const newCommentHTML = createCommentHTML(data.comment);
          commentsContainer.insertAdjacentHTML('afterbegin', newCommentHTML);

          // Attach interactions to the newly inserted comment
          const newCommentEl = commentsContainer.getElementsByClassName('CommentContainer')[0];
          attachCommentInteractions(newCommentEl.closest('.CommentSection') || document.getElementsByClassName('CommentSection')[0]);

          // Update comment counter on the post
          const activePosts = document.querySelectorAll(`.FeedPost[PID="${currentPostID}"]`);
          activePosts.forEach(post => {
            const counter = post.getElementsByClassName('CommentCounter')[0];
            if (counter) counter.textContent = parseInt(counter.textContent) + 1;
          });

          showInfoBox('Comment posted!', 1);
        } else {
          showInfoBox(data.message || 'Failed to post comment.', 2);
        }
      });
  });



  // Initialize existing posts
  const feedPosts = document.getElementsByClassName('FeedPost');
  [...feedPosts].forEach(post => attachPostInteractions(post));

  // Hydrate server-rendered post timestamps
  const dateMetas = document.querySelectorAll('.FeedPostTime[data-date]');
  dateMetas.forEach(el => {
    const ts = parseInt(el.getAttribute('data-date'), 10);
    el.textContent = TimeAgo(ts);
  });

  // Scroll event for infinite loading
  if( document.body.classList.contains('FetchPostsOnScroll')) {
    window.addEventListener('scroll', fetchMorePosts());
  }


  //content editable placeholder display on empty   and change UID in fetch 

  let CommentSectionModal= document.getElementsByClassName('CommentSection')[0];


  // Intercept paste in any contenteditable .CommentInput to strip rich HTML/styles and insert plain text only
  attachPlainTextPaste(CommentSectionModal);

  // Enable @mention dropdown for all reply inputs inside the comment section modal
  attachMentionDropdown(CommentSectionModal);

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