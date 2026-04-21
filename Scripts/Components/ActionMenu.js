// Single global close listener so it can always be cleaned up before mounting a new menu
let _activeCloseMenu = null;

export function closeExistingActionMenu(selector) {
  if (_activeCloseMenu) {
    document.removeEventListener('click', _activeCloseMenu);
    _activeCloseMenu = null;
  }
  const existing = document.querySelector(selector);
  if (existing) existing.remove();
}

export function mountActionMenu({
  selector,
  className,
  html,
  parent,
  onClick,
  onClose
}) {
  closeExistingActionMenu(selector);

  const menu = document.createElement('div');
  menu.className = className;
  menu.innerHTML = html;

  if (onClick) {
    menu.addEventListener('click', onClick);
  }

  parent.appendChild(menu);

  const closeMenu = () => {
    if (menu && menu.isConnected) menu.remove();
    document.removeEventListener('click', closeMenu);
    _activeCloseMenu = null;
    if (onClose) onClose();
  };

  _activeCloseMenu = closeMenu;
  setTimeout(() => {
    document.addEventListener('click', closeMenu);
  }, 0);

  return { menu, closeMenu };
}
