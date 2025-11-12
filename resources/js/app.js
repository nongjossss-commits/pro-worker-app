import { Modal } from 'bootstrap';
import './bootstrap';
import './employment-history.js';
import './terminate-employee.js';
import './address-handler.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', function () {
    const universalPreviewModalEl = document.getElementById('universalPreviewModal');
    if (!universalPreviewModalEl) return;

    const modal = Modal.getOrCreateInstance(universalPreviewModalEl);
    const modalBody = universalPreviewModalEl.querySelector('.modal-body');
    const modalTitle = universalPreviewModalEl.querySelector('.modal-title');

    const loadingSpinnerHtml = `
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;

    document.addEventListener('click', function (event) {
        const previewButton = event.target.closest('.btn-preview');
        if (!previewButton) return;

        event.preventDefault();

        const modelType = previewButton.dataset.modelType;
        const modelId = previewButton.dataset.modelId;

        if (!modelType || !modelId) {
            console.error('Preview button is missing data-model-type or data-model-id');
            return;
        }

        // 1. Reset modal and show spinner
        if (modalTitle) {
            modalTitle.textContent = 'พรีวิวข้อมูล';
        }
        modalBody.innerHTML = loadingSpinnerHtml;
        modal.show();

        // 2. Fetch data
        fetch(`/preview?type=${modelType}&id=${modelId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                // 3. On Success: Inject the returned HTML
                modalBody.innerHTML = html;
            })
            .catch(error => {
                // 4. On Failure: Inject an error message
                console.error('Error fetching preview data:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        เกิดข้อผิดพลาดในการโหลดข้อมูล: ${error.message}
                    </div>`;
            });
    });
});
