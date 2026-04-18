export function closeExistingActionMenu(selector) {
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
    if (menu.isConnected) menu.remove();
    document.removeEventListener('click', closeMenu);
    if (onClose) onClose();
  };

  setTimeout(() => {
    document.addEventListener('click', closeMenu);
  }, 0);

  return { menu, closeMenu };
}
