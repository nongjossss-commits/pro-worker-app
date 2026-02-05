<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewSelectedBtn = document.getElementById('btn-view-selected');
    const container = document.getElementById('selected-list-container');
    const modalEl = document.getElementById('viewSelectedModal');
    // Check if modal exists before initializing
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    if (viewSelectedBtn && modal) {
        viewSelectedBtn.addEventListener('click', function() {
            const data = window.getGlobalSelectedData();
            if (data.length === 0) {
                showToast('{{ __('No employees selected') }}', 'danger');
                return;
            }

            container.innerHTML = '';
            data.forEach(item => {
                const li = document.createElement('div');
                li.className = 'list-group-item d-flex align-items-center justify-content-between';
                li.id = `selected-item-${item.id}`;

                const photoUrl = item.photo || 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC';
                const nameTh = item.name_th || 'N/A';
                const nameEn = item.name_en || 'N/A';
                const employerName = item.employer_name || 'N/A';

                // --- Safe DOM Creation ---
                const leftDiv = document.createElement('div');
                leftDiv.className = 'd-flex align-items-center';

                const img = document.createElement('img');
                img.src = photoUrl;
                img.alt = 'Photo';
                img.className = 'rounded-circle me-3';
                img.style.width = '40px';
                img.style.height = '40px';
                img.style.objectFit = 'cover';
                leftDiv.appendChild(img);

                const infoDiv = document.createElement('div');

                const nameEnDiv = document.createElement('div');
                nameEnDiv.className = 'fw-bold';
                nameEnDiv.textContent = nameEn;
                infoDiv.appendChild(nameEnDiv);

                const nameThDiv = document.createElement('div');
                nameThDiv.className = 'text-muted small';
                nameThDiv.textContent = nameTh;
                infoDiv.appendChild(nameThDiv);

                const employerDiv = document.createElement('div');
                employerDiv.className = 'text-muted small';
                const buildingIcon = document.createElement('i');
                buildingIcon.className = 'bi bi-building me-1';
                employerDiv.appendChild(buildingIcon);
                employerDiv.appendChild(document.createTextNode(employerName));
                infoDiv.appendChild(employerDiv);

                leftDiv.appendChild(infoDiv);
                li.appendChild(leftDiv);

                const rightDiv = document.createElement('div');
                rightDiv.className = 'd-flex align-items-center gap-2';

                // Employee Preview Button
                const empPreviewBtn = document.createElement('button');
                empPreviewBtn.type = 'button';
                empPreviewBtn.className = 'btn btn-sm btn-outline-info btn-preview';
                empPreviewBtn.dataset.modelType = 'employee';
                empPreviewBtn.dataset.modelId = item.id;
                empPreviewBtn.title = '{{ __('Preview Employee') }}';
                empPreviewBtn.innerHTML = '<i class="bi bi-person-lines-fill"></i>';
                rightDiv.appendChild(empPreviewBtn);

                // Employer Preview Button (Conditional)
                if (item.employer_id) {
                    const emrPreviewBtn = document.createElement('button');
                    emrPreviewBtn.type = 'button';
                    emrPreviewBtn.className = 'btn btn-sm btn-outline-primary btn-preview';
                    emrPreviewBtn.dataset.modelType = 'employer';
                    emrPreviewBtn.dataset.modelId = item.employer_id;
                    emrPreviewBtn.title = '{{ __('Preview Employer') }}';
                    emrPreviewBtn.innerHTML = '<i class="bi bi-building"></i>';
                    rightDiv.appendChild(emrPreviewBtn);
                }

                // Remove Button
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger btn-remove-selected';
                removeBtn.dataset.id = item.id;
                removeBtn.title = '{{ __('Remove from selection') }}';
                removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
                rightDiv.appendChild(removeBtn);

                li.appendChild(rightDiv);
                container.appendChild(li);
            });

            modal.show();
        });
    }

    // Handle removal from within the modal
    if (container) {
        container.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.btn-remove-selected');
            if (removeBtn) {
                const id = removeBtn.dataset.id;
                // Remove from global storage
                // Use fallback if window helper is not available (it's in layout but check ensures safety)
                if(window.removeItemsByIds) {
                    window.removeItemsByIds([id]);
                } else {
                    // Manual Fallback
                    const current = window.getGlobalSelectedData();
                    const filtered = current.filter(item => item.id !== id);
                    window.setGlobalSelectedData(filtered);
                }

                // Remove from UI
                const itemEl = document.getElementById(`selected-item-${id}`);
                if (itemEl) itemEl.remove();

                // Check if empty
                if (container.children.length === 0) {
                    modal.hide();
                    showToast('{{ __('Selection cleared') }}', 'info');
                }
            }
        });
    }

    // --- Order Select All Delegation (Workflow/Pre-Production) ---
    document.body.addEventListener('change', function (e) {
        if (e.target.matches('.order-select-all')) {
            const orderId = e.target.dataset.orderId;
            const container = document.getElementById(`order-content-${orderId}`);
            if (!container) return;

            const checkboxes = container.querySelectorAll('.employee-checkbox');
            const itemsToAdd = [];
            const idsToRemove = [];

            checkboxes.forEach(cb => {
                cb.checked = e.target.checked;
                if (e.target.checked) {
                    itemsToAdd.push({
                        id: cb.value,
                        employer_id: cb.dataset.employerId || '',
                        name_th: cb.dataset.nameTh || '',
                        name_en: cb.dataset.nameEn || '',
                        photo: cb.dataset.photo || '',
                        employer_name: cb.dataset.employerName || ''
                    });
                } else {
                    idsToRemove.push(cb.value);
                }
            });

            const current = window.getGlobalSelectedData();
            if (e.target.checked) {
                // Merge and unique
                const newIds = itemsToAdd.map(i => String(i.id));
                const currentFiltered = current.filter(i => !newIds.includes(String(i.id)));
                const combined = [...currentFiltered, ...itemsToAdd];
                window.setGlobalSelectedData(combined);
            } else {
                const filtered = current.filter(item => !idsToRemove.includes(String(item.id)));
                window.setGlobalSelectedData(filtered);
            }
        }
    });

    // --- Bulk Actions ---

    // 1. Advanced Export
    const bulkExportBtn = document.getElementById('bulk-advanced-export-btn');
    if (bulkExportBtn) {
        bulkExportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = window.getGlobalSelectedIds();

            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Populate the hidden input with JSON array of IDs
            document.getElementById('export_employee_ids').value = JSON.stringify(selected);

            // Open the modal
            const modalEl = document.getElementById('advancedExportModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    }

    // 2. Bulk Download
    const bulkDownloadBtn = document.getElementById('bulk-download-btn');
    if (bulkDownloadBtn) {
        bulkDownloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = window.getGlobalSelectedIds();
            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            if (window.openBulkDownloadModal) {
                window.openBulkDownloadModal(selected);
            } else {
                console.error('Download modal function not found.');
            }
        });
    }

    // 3. Advanced Edit
    const bulkEditBtn = document.getElementById('bulk-advanced-edit-btn');
    if (bulkEditBtn) {
        bulkEditBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = window.getGlobalSelectedIds();

            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Create a form dynamically and submit POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('employees.bulk_edit.select_fields') }}';

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = 'redirect_to';
            redirectInput.value = window.location.href;
            form.appendChild(redirectInput);

            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'employee_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });
    }

    // 4. Send Data (To Ticket)
    const bulkSendDataBtn = document.getElementById('bulk-send-data-btn');
    if (bulkSendDataBtn) {
        bulkSendDataBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedData = window.getGlobalSelectedData();
            const selectedIds = selectedData.map(item => item.id);

            if (selectedIds.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Step 1: Check if all selected employees belong to the same employer
            let employerIds = new Set();
            selectedData.forEach(item => {
                if (item.employer_id) employerIds.add(item.employer_id);
            });

            if (employerIds.size > 1) {
                 Swal.fire({
                    icon: 'warning',
                    title: '{{ __('Multiple Employers Selected') }}',
                    text: '{{ __('You selected employees from different employers. Please select employees from the same employer for one transaction.') }}'
                });
                return;
            }

            // Step 2: Store selected IDs in a global variable
            window.pendingTicketEmployeeIds = selectedIds;

            // Step 3: Open Modal to Select Target Employer
            const modalEl = document.getElementById('selectTargetEmployerModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    }

    // 5. Send to Production
    const bulkSendProductionBtn = document.getElementById('bulk-send-production-btn');
    if (bulkSendProductionBtn) {
        bulkSendProductionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedData = window.getGlobalSelectedData();
            const selectedIds = selectedData.map(item => item.id);

            if (selectedIds.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            let employerIds = new Set();
            selectedData.forEach(item => {
                if (item.employer_id) employerIds.add(item.employer_id);
            });

            // Redirect to Production Create with IDs
            const idsJson = encodeURIComponent(JSON.stringify(selectedIds));
            const employerId = employerIds.size === 1 ? employerIds.values().next().value : '';

            let url = '{{ route("production.create") }}?employee_ids_json=' + idsJson;
            if(employerId) {
                url += '&employer_id=' + employerId;
            }

            window.location.href = url;
        });
    }

    // 6. Generate PDF
    const bulkGeneratePdfBtn = document.getElementById('bulk-generate-pdf-btn');
    if (bulkGeneratePdfBtn) {
        bulkGeneratePdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = window.getGlobalSelectedIds();

            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Create form to post to generation modal setup
            const form = document.createElement('form');
            form.method = 'POST';
            // Use relative path to avoid protocol mismatch (http vs https) redirects which strip POST data
            form.action = '{{ route("admin.pdf-templates.generate.modal", [], false) }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrf);

            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'employees[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });
    }
});

// Special handler for bulk drag
window.startDragBulk = function(e) {
    const ids = window.getGlobalSelectedIds();
    const count = ids.length;

    if (count === 0) {
        e.preventDefault();
        return;
    }

    const payload = {
        type: 'employees_bulk',
        title: count + ' Employees',
        count: count,
        ids: ids,
        url: window.location.href
    };
    e.dataTransfer.effectAllowed = 'copy';
    e.dataTransfer.setData('application/json', JSON.stringify(payload));
}
</script>
