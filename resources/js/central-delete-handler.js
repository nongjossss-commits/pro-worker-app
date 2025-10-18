document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('centralDeleteConfirmationModal');
    if (!deleteModal) {
        return;
    }

    const deleteForm = document.getElementById('centralDeleteForm');
    const deleteModalMessage = document.getElementById('deleteModalMessage');
    const confirmBtn = document.getElementById('confirmCentralDeleteBtn');

    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        const action = button.getAttribute('data-action');
        const message = button.getAttribute('data-message');
        const isForceDelete = button.getAttribute('data-is-force-delete') === 'true';

        // Set the form action
        if (action) {
            deleteForm.setAttribute('action', action);
        }

        // Set the modal message
        if (message) {
            deleteModalMessage.innerHTML = message;
        } else {
            deleteModalMessage.innerHTML = 'คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?';
        }

        // Adjust button for force delete
        if (isForceDelete) {
            confirmBtn.classList.remove('btn-danger');
            confirmBtn.classList.add('btn-warning');
            confirmBtn.textContent = 'ยืนยันการลบถาวร';
        } else {
            confirmBtn.classList.remove('btn-warning');
            confirmBtn.classList.add('btn-danger');
            confirmBtn.textContent = 'ยืนยันการลบ';
        }
    });
});