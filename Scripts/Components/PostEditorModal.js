import * as FeedApi from "../Api/FeedApi.js";
import { confirmRemoval } from "./ConfirmActions.js";
import { attachMentionDropdown, collectMentions } from "./MentionDropdown.js";
import { showInfoBox } from "../Utilities.js";
import { VisibilityIcons, VisibilityTitles, VisibilityLabels } from "../Constants.js";
import { createPostHTML, attachPostInteractions } from "./PostCard.js";

const MAX_FILE_SIZE = 2 * 1024 * 1024;
const MAX_FILE_COUNT = 2;
const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx'];
const ALLOWED_IMAGE_EXTENSIONS = ['xbm', 'tif', 'jfif', 'ico', 'tiff', 'gif', 'svg', 'webp', 'svgz', 'jpg', 'jpeg', 'png', 'bmp', 'pjp', 'apng', 'pjpeg', 'avif'];

let state = null;

function setVisibility(val) {
  const { modal } = state;
  const visibilityDropdown = modal.querySelector('#VisibilityDropdown');
  const visibilityInput = modal.querySelector('#CPostVisibility');
  const visibilityLabel = modal.querySelector('#VisibilityLabel');

  val = parseInt(val, 10) || 0;
  if (visibilityInput) visibilityInput.value = val;
  if (visibilityLabel) visibilityLabel.textContent = VisibilityLabels[val] || 'Everyone';

  const sourceIcon = visibilityDropdown
    ? visibilityDropdown.querySelector(`.VisibilityOption[data-value="${val}"] svg`)
    : null;
  const buttonIcon = modal.querySelector('#VisibilitySelectorIcon');
  if (sourceIcon && buttonIcon) {
    const newIcon = sourceIcon.cloneNode(true);
    newIcon.id = 'VisibilitySelectorIcon';
    newIcon.classList.add('VisibilityIcon');
    newIcon.setAttribute('width', '15');
    newIcon.setAttribute('height', '15');
    buttonIcon.replaceWith(newIcon);
  }

  if (visibilityDropdown) {
    [...visibilityDropdown.getElementsByClassName('VisibilityOption')].forEach(option => {
      option.classList.toggle('Active', parseInt(option.dataset.value, 10) === val);
    });
  }
}

function updatePostInDOM(pid, postData) {
  const oldElement = document.querySelector(`.FeedPost[PID="${pid}"]`);
  if (!oldElement) return;

  oldElement.outerHTML = createPostHTML(postData);
  attachPostInteractions(document.querySelector(`.FeedPost[PID="${pid}"]`));
}

function prependPostToFeed(postData) {
  const container = document.getElementsByClassName('FeedContainer')[0];
  if (!container) return;

  container.insertAdjacentHTML('afterbegin', createPostHTML(postData));
  attachPostInteractions(container.firstElementChild);
}

function deleteUploadedFiles(button, type = 1) {
  const fileName = button.dataset.filename || button.getAttribute('filename');
  state.validSelectedFiles = state.validSelectedFiles.filter(file => file.name !== fileName);

  // Remove the UI element
  const thumb = button.closest('.ImagePreviewThumb');
  if (thumb) thumb.remove();
  else button.closest('.UploadedFile')?.remove();

  const modal = state.modal;

  if (state.validSelectedFiles.length === 0) {
    modal.getElementsByClassName('UploadOverview')[0].classList.add('hidden');
    state.currentUploadType = null;
  }

  const inputElement = type === 1
    ? modal.getElementsByClassName('DocumentPostUpload')[0]
    : modal.getElementsByClassName('ImagePostUpload')[0];

  inputElement.value = '';
  const transfer = new DataTransfer();
  state.validSelectedFiles.forEach(file => transfer.items.add(file));
  inputElement.files = transfer.files;

  syncUploadButtonVisibility(type);
  clearUploadError();
}

function populateUploadedFiles(files, type = 1) {
  const modal = state.modal;
  const uploadOverview = modal.getElementsByClassName('UploadOverview')[0];
  uploadOverview.classList.remove('hidden');

  const uploadedFiles = uploadOverview.getElementsByClassName('UploadedFiles')[0];
  uploadedFiles.innerHTML = '';

  if (type === 2) {
    // Image files: thumbnail grid
    const grid = document.createElement('div');
    grid.className = 'ImagePreviewGrid';
    uploadedFiles.appendChild(grid);

    for (let i = 0; i < files.length; i++) {
      const objectUrl = URL.createObjectURL(files[i]);
      const thumb = document.createElement('div');
      thumb.className = 'ImagePreviewThumb';
      thumb.innerHTML = `
        <img src="${objectUrl}" alt="${files[i].name}">
        <span class="RemoveUploadedFile" data-filename="${files[i].name}">&times;</span>
      `;
      grid.appendChild(thumb);
    }

    [...grid.getElementsByClassName('RemoveUploadedFile')].forEach(button => {
      button.addEventListener('click', () => {
        confirmRemoval({ onConfirm: () => deleteUploadedFiles(button, type) });
      });
    });
  } else {
    // Document files: chip list
    for (let i = 0; i < files.length; i++) {
      uploadedFiles.insertAdjacentHTML('beforeend', `
        <div class="UploadedFile">
          <div class="FileName">
            <img src="Imgs/Icons/Document.svg">
            <p>${files[i].name}</p>
          </div>
          <span class="RemoveUploadedFile" data-filename="${files[i].name}">&times;</span>
        </div>
      `);
    }

    [...uploadedFiles.getElementsByClassName('RemoveUploadedFile')].forEach(button => {
      button.addEventListener('click', () => {
        confirmRemoval({ onConfirm: () => deleteUploadedFiles(button, type) });
      });
    });
  }
}

function showUploadError(msg) {
  const el = state.modal.querySelector('#CPostUploadError');
  if (!el) return;
  el.textContent = msg;
  el.classList.remove('hidden');
  clearTimeout(state._uploadErrorTimer);
  state._uploadErrorTimer = setTimeout(() => el.classList.add('hidden'), 4000);
}

function clearUploadError() {
  const el = state.modal.querySelector('#CPostUploadError');
  if (el) el.classList.add('hidden');
}

function syncUploadButtonVisibility(type) {
  const modal = state.modal;
  const docLabel = modal.querySelector('#DocumentPostUploadLabel');
  const imgLabel = modal.querySelector('#ImagePostUploadLabel');
  const hasFiles = state.validSelectedFiles.length > 0;
  const atMax = state.validSelectedFiles.length >= MAX_FILE_COUNT;

  if (atMax) {
    docLabel?.classList.add('hidden');
    imgLabel?.classList.add('hidden');
    return;
  }

  if (!hasFiles) {
    // No validated files — show both buttons again
    docLabel?.classList.remove('hidden');
    imgLabel?.classList.remove('hidden');
    return;
  }

  // Has some validated files — hide the opposite type button
  if (type === 1) {
    // docs validated → hide image button
    imgLabel?.classList.add('hidden');
    docLabel?.classList.remove('hidden');
  } else {
    // images validated → hide doc button
    docLabel?.classList.add('hidden');
    imgLabel?.classList.remove('hidden');
  }
}

function handlePostFileUpload(inputElement, allowedExtensions, otherInput, type) {
  clearUploadError();

  // Mixed type check
  if (state.validSelectedFiles.length > 0 && state.currentUploadType !== type) {
    inputElement.value = '';
    showUploadError('You can only attach images or documents — not both at the same time.');
    syncUploadButtonVisibility(state.currentUploadType);
    return;
  }

  const incoming = [...inputElement.files];
  const errors = [];
  const dataTransfer = new DataTransfer();

  state.validSelectedFiles.forEach(file => dataTransfer.items.add(file));

  for (const file of incoming) {
    if (dataTransfer.files.length >= MAX_FILE_COUNT) {
      errors.push(`Max ${MAX_FILE_COUNT} files allowed — "${file.name}" skipped.`);
      break;
    }

    const ext = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(ext)) {
      errors.push(`"${file.name}" has an unsupported file type.`);
      continue;
    }

    if (file.size > MAX_FILE_SIZE) {
      errors.push(`"${file.name}" exceeds the 2 MB size limit.`);
      continue;
    }

    dataTransfer.items.add(file);
    state.validSelectedFiles.push(file);
  }

  if (errors.length > 0) {
    showUploadError(errors.join(' '));
  }

  inputElement.files = dataTransfer.files;

  if (state.validSelectedFiles.length === 0) {
    // All files were rejected — reset everything
    const uploadOverview = state.modal.getElementsByClassName('UploadOverview')[0];
    uploadOverview.classList.add('hidden');
    state.modal.querySelector('.UploadedFiles').innerHTML = '';
    state.currentUploadType = null;
    syncUploadButtonVisibility(type);
    return;
  }

  state.currentUploadType = type;
  syncUploadButtonVisibility(type);
  populateUploadedFiles(state.validSelectedFiles, type);
}

export function resetPostEditorModal() {
  const { modal } = state;
  const form = document.getElementById('CreatePostForm');

  form.reset();
  const contentDiv = document.getElementById('CPostContent');
  if (contentDiv) {
    contentDiv.innerHTML = '';
    contentDiv.classList.remove('has-content');
  }

  state.validSelectedFiles = [];
  state.filesToDelete = [];
  state.currentUploadType = null;

  clearUploadError();
  modal.querySelector('.CPostHeader h1').textContent = 'Create Post';
  form.querySelector('.PostSubmitBtn').value = 'Post';
  document.getElementById('CPostEditID').value = '';
  document.getElementById('CPostFilesToDelete').value = '[]';

  const postAsPageID = document.getElementById('CPostAsPageID');
  if (postAsPageID) postAsPageID.value = '';
  document.getElementById('PostAsDropdown')?.classList.add('hidden');
  const selfOption = document.querySelector('.PostAsOptionSelf');
  if (selfOption && window.SelectPostAsOption) window.SelectPostAsOption(selfOption);

  const postAsRow = document.getElementById('PostAsSelector')?.closest('.PostAsRow');
  if (typeof window.SyncPostAsVisibility === 'function') {
    window.SyncPostAsVisibility();
  } else if (postAsRow) {
    postAsRow.style.display = '';
  }

  modal.querySelector('#VisibilityDropdown')?.classList.add('hidden');
  setVisibility(0);
  modal.querySelector('.UploadedFiles').innerHTML = '';
  modal.querySelector('.UploadOverview').classList.add('hidden');
  modal.querySelector('.DocumentPostUpload').value = '';
  modal.querySelector('.ImagePostUpload').value = '';
  // Ensure both upload buttons are visible after reset
  modal.querySelector('#DocumentPostUploadLabel')?.classList.remove('hidden');
  modal.querySelector('#ImagePostUploadLabel')?.classList.remove('hidden');
}

export async function openEditPostModal(postID) {
  const { modal, onToggleModal } = state;
  const form = document.getElementById('CreatePostForm');
  const contentField = document.getElementById('CPostContent');
  const editIDField = document.getElementById('CPostEditID');
  const filesToDeleteField = document.getElementById('CPostFilesToDelete');
  const overviewContainer = modal.querySelector('.UploadOverview');
  const filesContainer = modal.querySelector('.UploadedFiles');

  form.reset();
  state.validSelectedFiles = [];
  state.filesToDelete = [];
  filesContainer.innerHTML = '';
  overviewContainer.classList.add('hidden');

  modal.querySelector('.CPostHeader h1').textContent = 'Edit Post';
  form.querySelector('.PostSubmitBtn').value = 'Save Changes';
  editIDField.value = postID;
  filesToDeleteField.value = '[]';

  const postAsRow = document.getElementById('PostAsSelector')?.closest('.PostAsRow');
  if (postAsRow) postAsRow.style.display = 'none';

  try {
    const data = await FeedApi.fetchPostForEdit(postID);
    if (!data.success) {
      showInfoBox(data.message, 2);
      return;
    }

    const mentionMap = data.MentionMap || {};
    const safeContent = data.Content
      ? data.Content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      : '';
    contentField.innerHTML = safeContent.replace(/@([\w]+)/g, (_match, username) => {
      const encUID = mentionMap[username] || '';
      return `<span class="ReplyTag MentionTag" contenteditable="false" mention-uid="${encUID}" mention-username="${username}">@${username}</span>`;
    });

    setVisibility(data.Visibility || 0);

    if (data.MediaFiles && data.MediaFiles.length > 0) {
      overviewContainer.classList.remove('hidden');
      const isImages = data.MediaType === 2;

      if (isImages) {
        const grid = document.createElement('div');
        grid.className = 'ImagePreviewGrid';
        filesContainer.appendChild(grid);
        data.MediaFiles.forEach(file => {
          const thumb = document.createElement('div');
          thumb.className = 'ImagePreviewThumb existing';
          thumb.dataset.filename = file.name;
          thumb.innerHTML = `
            <img src="${file.path}" alt="${file.name}">
            <span class="RemoveUploadedFile" data-filename="${file.name}">&times;</span>
          `;
          grid.appendChild(thumb);
        });
      } else {
        filesContainer.innerHTML = data.MediaFiles.map(file => `
          <div class="UploadedFile existing" data-filename="${file.name}">
            <div class="FileName">
              <img src="Imgs/Icons/Document.svg">
              <p>${file.name}</p>
            </div>
            <span class="RemoveUploadedFile" data-filename="${file.name}">&times;</span>
          </div>
        `).join('');
      }

      filesContainer.querySelectorAll('.RemoveUploadedFile').forEach(button => {
        button.addEventListener('click', event => {
          event.stopPropagation();
          const filename = button.dataset.filename;
          confirmRemoval({
            onConfirm: () => {
              state.filesToDelete.push(filename);
              filesToDeleteField.value = JSON.stringify(state.filesToDelete);
              const container = button.closest('.ImagePreviewThumb') || button.closest('.UploadedFile');
              if (container) container.classList.add('hidden');
            }
          });
        });
      });
    }

    onToggleModal(modal, true);
  } catch (error) {
    console.error('Error fetching post for edit:', error);
    showInfoBox('Could not load post for editing.', 2);
  }
}

async function handlePostSubmit(event) {
  event.preventDefault();

  const { modal, onToggleModal } = state;
  const form = document.getElementById('CreatePostForm');
  const editID = document.getElementById('CPostEditID').value;
  const isEditing = editID !== '';
  const formData = new FormData(form);

  const contentDiv = document.getElementById('CPostContent');
  formData.set('content', contentDiv.innerText.trim());

  const postMentions = collectMentions(contentDiv);
  postMentions.forEach(uid => formData.append('Mentions[]', uid));

  if (!isEditing) {
    const postAsPageID = document.getElementById('CPostAsPageID');
    if (postAsPageID && postAsPageID.value) {
      formData.set('PostAsPageID', postAsPageID.value);
    }
  }

  if (state.validSelectedFiles.length > 0) {
    const fileType = state.validSelectedFiles[0].type.startsWith('image/') ? 'images[]' : 'document[]';
    state.validSelectedFiles.forEach(file => formData.append(fileType, file));
  }

  const button = form.querySelector('.PostSubmitBtn');
  const loader = form.querySelector('.Loader');
  button.disabled = true;
  loader.classList.remove('hidden');

  try {
    const data = isEditing
      ? await FeedApi.submitPostEdit(formData)
      : await FeedApi.submitCreatePost(formData);

    if (!data.success) {
      showInfoBox(data.message || 'Error processing post', 2);
      return;
    }

    if (isEditing) {
      updatePostInDOM(editID, data.post);
      showInfoBox('Post updated successfully!', 1);
    } else {
      prependPostToFeed(data.post);
      showInfoBox('Post created successfully!', 1);
    }

    resetPostEditorModal();
    onToggleModal(modal, false);
  } catch (error) {
    console.error(error);
    showInfoBox('An unexpected error occurred.', 2);
  } finally {
    button.disabled = false;
    loader.classList.add('hidden');
  }
}

export function initPostEditorModal(options) {
  state = {
    ...options,
    validSelectedFiles: [],
    filesToDelete: [],
    currentUploadType: null
  };

  const { modal, onToggleModal, attachPlainTextPaste } = state;
  if (!modal) return;

  [...document.getElementsByClassName('CreatePostBtn')].forEach(button => {
    button.addEventListener('click', () => {
      resetPostEditorModal();
      onToggleModal(modal, true);
    });
  });

  const documentUpload = modal.getElementsByClassName('DocumentPostUpload')[0];
  const imageUpload = modal.getElementsByClassName('ImagePostUpload')[0];
  documentUpload.addEventListener('change', () => handlePostFileUpload(documentUpload, ALLOWED_DOCUMENT_EXTENSIONS, imageUpload, 1));
  imageUpload.addEventListener('change', () => handlePostFileUpload(imageUpload, ALLOWED_IMAGE_EXTENSIONS, documentUpload, 2));

  modal.getElementsByClassName('CPost')[0].addEventListener('submit', handlePostSubmit);

  const visibilitySelector = modal.querySelector('#VisibilitySelector');
  const visibilityDropdown = modal.querySelector('#VisibilityDropdown');
  if (visibilitySelector && visibilityDropdown) {
    visibilitySelector.addEventListener('click', event => {
      event.stopPropagation();
      visibilityDropdown.classList.toggle('hidden');
    });

    visibilityDropdown.addEventListener('click', event => {
      const option = event.target.closest('.VisibilityOption');
      if (!option) return;
      setVisibility(option.dataset.value);
      visibilityDropdown.classList.add('hidden');
    });

    document.addEventListener('click', event => {
      if (!visibilitySelector.contains(event.target)) {
        visibilityDropdown.classList.add('hidden');
      }
    });
  }

  attachPlainTextPaste(modal);
  attachMentionDropdown(modal);
}
