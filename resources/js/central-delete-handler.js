/**
 * Centralized handler for the Bootstrap confirmation modal.
 *
 * This script listens for clicks on any element with the class '.btn-trigger-delete-modal'.
 * It uses data attributes from the trigger button to dynamically populate the modal's
 * content and form submission URL.
 *
 * Required Data Attributes on Trigger Button:
 * - `data-action`: The URL the form should submit to.
 * - `data-message`: The confirmation message to display in the modal body.
 * - `data-is-force-delete`: ('true' or 'false') Controls the confirmation button's style.
 * - `data-confirm-btn-text`: (Optional) Text for the confirmation button.
 */
document.addEventListener('DOMContentLoaded', () => {
    const deleteModalEl = document.getElementById('centralDeleteConfirmationModal');
    if (!deleteModalEl) return;

    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const modalMessage = document.getElementById('central-delete-modal-message');
    const deleteForm = document.getElementById('central-delete-form');
    const methodInput = document.getElementById('central-delete-form-method');
    const confirmBtn = document.getElementById('central-delete-confirm-btn');

    deleteModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        const action = button.getAttribute('data-action');
        const message = button.getAttribute('data-message');
        const isForceDelete = button.getAttribute('data-is-force-delete') === 'true';
        const confirmBtnText = button.getAttribute('data-confirm-btn-text');

        // 1. Set the form's action URL
        deleteForm.action = action;

        // 2. Set the confirmation message
        modalMessage.textContent = message;

        // 3. Set the form method (DELETE for soft delete, still DELETE for force, but we handle it in controller)
        methodInput.value = 'DELETE';

        // 4. Style the confirmation button
        if (confirmBtnText) {
            confirmBtn.textContent = confirmBtnText;
        } else {
            // Fallback if no translation is provided via attribute
            confirmBtn.textContent = isForceDelete ? 'Yes, force delete' : 'Yes, move to trash';
        }

        confirmBtn.classList.remove('btn-danger', 'btn-primary');
        confirmBtn.classList.add(isForceDelete ? 'btn-danger' : 'btn-primary');
    });

    // Optional: Reset form action when modal is hidden to prevent accidental submissions
    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteForm.action = '';
        modalMessage.textContent = ''; // Clear message
    });
});
