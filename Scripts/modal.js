function ShowConfirmModal(Options){
    let modal = document.getElementsByClassName('Modal Confirm')[0];
  modal.classList.remove('hidden');
  document.body.classList.add("ModalOpen");

  CurrentConfirmModalFunction = Options.onConfirm;
  let ModalTitle = modal.getElementsByClassName('ModalTitle')[0];
  let ModalHint = modal.getElementsByClassName('ModalHint')[0];
  let ConfirmBtn = modal.getElementsByClassName('ConfirmBtn')[0];
  const cancelBtnAlt = modal.getElementsByClassName('ModalCancelBtn')[0];
  if(ModalTitle){
    ModalTitle.innerHTML = Options.Title;
  }

  if (ModalHint) {
    if (Options.Hint) {
      ModalHint.textContent = Options.Hint;
      ModalHint.classList.remove('hidden');
    } else {
      ModalHint.textContent = '';
      ModalHint.classList.add('hidden');
    }
  }

  if(ConfirmBtn){
    ConfirmBtn.innerHTML = Options.ConfirmText;
  }

  ConfirmBtn.addEventListener('click', HandleModalConfirm, { once: true });

  cancelBtnAlt.addEventListener('click', CancelConfirmModal, { once: true });



  async function HandleModalConfirm() {
/*     if(CurrentConfirmModalFunction){
      CurrentConfirmModalFunction();
    } */

    if (CurrentConfirmModalFunction) {
      try {
        // Support async and sync functions
        const result = CurrentConfirmModalFunction();
        if (result instanceof Promise) await result;
      } catch (err) {
        console.error("Error in confirmation function:", err);
        return; // Don't proceed with close/refresh on error
      }
    }


    let Action = Options.Action || 'Close';
    Action = Action.toLowerCase();
    if (Action === 'close') {
      CancelConfirmModal();
    }  else if (Action === 'refresh') {
      window.location.reload();
    }

    


  }

  function CancelConfirmModal() {
    CurrentConfirmModalFunction = null;
    //reset modal content
    if (ModalHint) {
      ModalHint.textContent = '';
      ModalHint.classList.add('hidden');
    }
    modal.classList.add('hidden');
    document.body.classList.remove("ModalOpen");

  }


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
