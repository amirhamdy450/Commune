// Shared UI utility functions used across page scripts and component modules.
// Import from here instead of duplicating or passing as injected dependencies.

export function showInfoBox(message, Type = 0) {
  let InfoTypeClass = '';
  switch (Type) {
    case 1: InfoTypeClass = 'Success'; break;
    case 2: InfoTypeClass = 'Error'; break;
  }
  const infoBox = document.createElement('div');
  infoBox.className = 'InfoBox' + (InfoTypeClass ? ' ' + InfoTypeClass : '');
  infoBox.textContent = message;
  document.body.appendChild(infoBox);
  setTimeout(() => infoBox.classList.add('Show'), 100);
  setTimeout(() => {
    infoBox.classList.remove('Show');
    setTimeout(() => {
      if (infoBox.parentNode) infoBox.parentNode.removeChild(infoBox);
    }, 500);
  }, 3000);
}
