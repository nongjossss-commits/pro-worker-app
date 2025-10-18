import './bootstrap';

import './employment-history.js';

import './terminate-employee.js';

import './address-handler.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// START: Central Delete Modal Handler (NEW JS IMPLEMENTATION)
document.addEventListener('DOMContentLoaded', function() {
    // Requires Bootstrap 5 (imported via bootstrap.js)
    const deleteModalEl = document.getElementById('centralDeleteConfirmationModal');
    if (!deleteModalEl) return;

    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const deleteForm = document.getElementById('centralDeleteForm');
    const deleteMessage = document.getElementById('deleteModalMessage');
    const confirmBtnText = document.getElementById('confirmCentralDeleteBtnText');
    const modalTitle = document.getElementById('centralDeleteConfirmationModalLabel');

    deleteModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const actionUrl = button.getAttribute('data-action');

       const resourceType = button.getAttribute('data-type');
        const resourceName = button.getAttribute('data-name');
        const isForceDelete = button.hasAttribute('data-force-delete');

        // 1. Set the form action
        deleteForm.action = actionUrl;

        // 2. Update Modal content
        if (isForceDelete) {
            modalTitle.textContent = 'ยืนยันการลบอย่างถาวร';

            deleteMessage.innerHTML = `คุณแน่ใจหรือไม่ที่จะลบ ${resourceType} **${resourceName}** อย่างถาวร? <br><strong class="text-danger">การกระทำนี้ไม่สามารถกู้คืนได้เลย.</strong>`;
            confirmBtnText.textContent = 'ยืนยันการลบถาวร';
            deleteForm.querySelector('input[name="_method"]').value = 'DELETE';

        } else {
            // Standard soft delete confirmation (used for resources that don't need detailed inputs like termination)
            modalTitle.textContent = 'ยืนยันการลบ';

            deleteMessage.innerHTML = `คุณแน่ใจหรือไม่ที่จะลบ ${resourceType} **${resourceName}**? <br>รายการจะถูกย้ายไปที่ถังขยะ`;
            confirmBtnText.textContent = 'ยืนยันการลบ';
            deleteForm.querySelector('input[name="_method"]').value = 'DELETE';
        }
    });
});

// END: Central Delete Modal Handler