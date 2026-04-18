import { Submit } from "../Forms.js";

function buildRequest(fields = {}) {
  const formData = new FormData();
  Object.entries(fields).forEach(([key, value]) => {
    if (value !== undefined && value !== null) formData.append(key, value);
  });
  return formData;
}

function submitSearchRequest(reqType, fields = {}) {
  return Submit("POST", "Origin/Operations/Search.php", buildRequest({ ReqType: reqType, ...fields }));
}

export function searchMentionUsers(query) {
  return submitSearchRequest(5, { query });
}
