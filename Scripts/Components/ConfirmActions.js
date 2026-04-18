function showConfirmDialog(options) {
    if (typeof ShowConfirmModal !== 'function') {
        throw new Error('ShowConfirmModal is not available.');
    }

    ShowConfirmModal(options);
}

export function confirmDestructiveAction({
    Title,
    Hint = '',
    ConfirmText = 'Confirm',
    Action = 'Close',
    onConfirm
}) {
    showConfirmDialog({
        Title,
        Hint,
        ConfirmText,
        Action,
        onConfirm
    });
}

export function confirmDelete({
    Title = 'Delete this?',
    Hint = '',
    ConfirmText = 'Delete',
    Action = 'Close',
    onConfirm
}) {
    confirmDestructiveAction({
        Title,
        Hint,
        ConfirmText,
        Action,
        onConfirm
    });
}

export function confirmBlock({
    Name = 'this user',
    Hint = '',
    ConfirmText = 'Block',
    Action = 'Close',
    onConfirm
}) {
    confirmDestructiveAction({
        Title: `Block ${Name}?`,
        Hint,
        ConfirmText,
        Action,
        onConfirm
    });
}

export function confirmRemoval({
    Title = 'Are You Sure You Want To Remove This File?',
    Hint = '',
    ConfirmText = 'Remove',
    Action = 'Close',
    onConfirm
}) {
    confirmDestructiveAction({
        Title,
        Hint,
        ConfirmText,
        Action,
        onConfirm
    });
}

export function confirmAccountDeletion({
    Title = 'Delete your account?',
    Hint = 'Your account will be permanently deleted along with your posts, saved items, and profile data.',
    ConfirmText = 'Permanently Delete',
    Action = 'Close',
    onConfirm
}) {
    confirmDestructiveAction({
        Title,
        Hint,
        ConfirmText,
        Action,
        onConfirm
    });
}
