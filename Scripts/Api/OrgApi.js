import { Submit } from "../Forms.js";

function buildRequest(fields = {}, files = null) {
  const formData = new FormData();
  Object.entries(fields).forEach(([key, value]) => {
    if (value !== undefined && value !== null) formData.append(key, value);
  });
  if (files) {
    Object.entries(files).forEach(([key, file]) => {
      if (file) formData.append(key, file);
    });
  }
  return formData;
}

function submitOrgRequest(reqType, fields = {}, files = null) {
  return Submit("POST", "Origin/Operations/Org.php", buildRequest({ ReqType: reqType, ...fields }, files));
}

export function toggleFollowPage(pageID, action) {
  return submitOrgRequest(4, { PageID: pageID, Action: action });
}

export function checkPageHandleAvailability(handle) {
  return submitOrgRequest(2, { Handle: handle });
}

export function updatePage(pageID, fields, files = null) {
  return submitOrgRequest(5, { PageID: pageID, ...fields }, files);
}

export function fetchMorePagePosts(pageID, lastPostID) {
  return submitOrgRequest(6, { PageID: pageID, LastPostID: lastPostID });
}
