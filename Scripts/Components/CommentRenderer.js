import { TimeAgo } from "../Forms.js";

function renderCommentMentions(text) {
  if (!text) return "";
  return text.replace(/@(\w+)/g, '<a class="MentionLink" href="index.php?target=profile&username=$1">@$1</a>');
}

function buildCommentAuthorMeta({ name, username, isBlueTick, date, profileUrl, profilePic }) {
  const blueTick = isBlueTick ? '<span class="BlueTick" title="Verified"></span>' : '';
  const timeAgo = date ? TimeAgo(date) : '';

  return `
    <a class="ModalCommentAuthor" href="${profileUrl}">
      <img src="${profilePic}" alt="">
      <div class="ModalCommentAuthorInfo">
        <div class="ModalCommentNameRow">
          <span class="ModalCommentName">${name}</span>
          ${blueTick}
          ${timeAgo ? `<span class="ModalCommentTime">${timeAgo}</span>` : ''}
        </div>
        ${username ? `<span class="ModalCommentUsername">@${username}</span>` : ''}
      </div>
    </a>
  `;
}

export function createCommentHTML(comment, type = 1) {
  const likeIcon = comment.liked ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';
  const isSelf = comment.IsSelf ? '1' : '0';

  if (type === 1) {
    const commentName = comment.Fname + ' ' + comment.Lname;
    const profileUrl = `index.php?target=profile&uid=${encodeURIComponent(comment.UID)}`;
    const viewRepliesButton = comment.ReplyCounter > 0
      ? `<div class="ViewRepliesBtn">${comment.ReplyCounter} Replies</div>`
      : '';

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
            ${buildCommentAuthorMeta({
              name: commentName,
              username: comment.Username,
              isBlueTick: comment.IsBlueTick,
              date: comment.Date,
              profileUrl,
              profilePic: comment.ProfilePic
            })}
            <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg" alt="Options"></div>
          </div>
          <div class="ModalCommentContent">
            <p>${renderCommentMentions(comment.comment)}</p>
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

        ${viewRepliesButton}
        <span class="username-readonly hidden">${comment.Username}</span>
        <div class="RepliesContainer hidden"></div>
      </div>
    `;
  }

  const taggedUser = comment.TaggedUser
    ? `<a class="ReplyTag MentionLink" href="index.php?target=profile&username=${encodeURIComponent(comment.TaggedUser)}">@${comment.TaggedUser}</a>`
    : '';
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
          ${buildCommentAuthorMeta({
            name: comment.Sender,
            username: comment.SenderUsername,
            isBlueTick: comment.IsBlueTick,
            date: comment.Date,
            profileUrl: replyProfileUrl,
            profilePic: comment.SenderProfilePic
          })}
          <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg" alt="Options"></div>
        </div>
        <div class="ModalCommentContent">
          ${taggedUser} <p>${renderCommentMentions(comment.Reply)}</p>
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
