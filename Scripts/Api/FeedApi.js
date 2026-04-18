import { Submit } from "../Forms.js";

function buildRequest(fields = {}, listFields = {}) {
  const formData = new FormData();

  Object.entries(fields).forEach(([key, value]) => {
    if (value !== undefined && value !== null) {
      formData.append(key, value);
    }
  });

  Object.entries(listFields).forEach(([key, values]) => {
    (values || []).forEach(value => formData.append(key, value));
  });

  return formData;
}

function submitFeedRequest(reqType, fields = {}, listFields = {}) {
  const formData = buildRequest({ ReqType: reqType, ...fields }, listFields);
  return Submit("POST", "Origin/Operations/Feed.php", formData);
}

export function fetchComments(feedPostId) {
  return submitFeedRequest(4, { FeedPostID: feedPostId });
}

export function createComment(feedPostId, commentContent, mentions = []) {
  return submitFeedRequest(3, {
    FeedPostID: feedPostId,
    CommentContent: commentContent
  }, {
    "Mentions[]": mentions
  });
}

export function likePost(feedPostId) {
  return submitFeedRequest(2, { FeedPostID: feedPostId });
}

export function fetchFeedPage(feedOffset) {
  return submitFeedRequest(5, { FeedOffset: feedOffset });
}

export function deletePost(feedPostId) {
  return submitFeedRequest(6, { FeedPostID: feedPostId });
}

export function likeComment(commentId) {
  return submitFeedRequest(7, { CommentID: commentId });
}

export function createReply(commentId, reply, mentions = [], replyTo = null) {
  return submitFeedRequest(8, {
    CommentID: commentId,
    Reply: reply,
    ReplyTo: replyTo
  }, {
    "Mentions[]": mentions
  });
}

export function likeReply(replyId) {
  return submitFeedRequest(9, { ReplyID: replyId });
}

export function fetchReplies(commentId) {
  return submitFeedRequest(10, { CommentID: commentId });
}

export function toggleFollowUser(userId) {
  return submitFeedRequest(11, { UserID: userId });
}

export function toggleSavePost(postId) {
  return submitFeedRequest(12, { PostID: postId });
}

export function blockUser(userId) {
  return submitFeedRequest(13, { BlockedUID: userId });
}

export function fetchPostForEdit(feedPostId) {
  return submitFeedRequest(14, { FeedPostID: feedPostId });
}

export function submitCreatePost(formData) {
  formData.set("ReqType", 1);
  return Submit("POST", "Origin/Operations/Feed.php", formData);
}

export function submitPostEdit(formData) {
  formData.set("ReqType", 15);
  return Submit("POST", "Origin/Operations/Feed.php", formData);
}

export function deleteComment(commentId) {
  return submitFeedRequest(16, { CommentID: commentId });
}

export function deleteReply(replyId) {
  return submitFeedRequest(17, { ReplyID: replyId });
}

export function recordPostView(feedPostId) {
  return submitFeedRequest(18, { FeedPostID: feedPostId });
}
