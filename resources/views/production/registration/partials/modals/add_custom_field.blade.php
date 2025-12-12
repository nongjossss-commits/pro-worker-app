<div class="modal fade" id="addCustomFieldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Custom Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCustomFieldForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Action URL will be set via JS -->

                    <div class="mb-3">
                        <label class="form-label">Field Name (Label)</label>
                        <input type="text" name="field_name" class="form-control" required placeholder="e.g. Additional Note, Medical Cert">
                    </div>

                    <div class="mb-3">
                         <label class="form-label">Field Type</label>
                         <select name="field_type" class="form-select" onchange="toggleModalInputs(this)">
                             <option value="text">Text Box</option>
                             <option value="date">Date</option>
                             <option value="file">File Attachment</option>
                         </select>
                    </div>

                    {{-- Dynamic Inputs --}}
                    <div class="mb-3 input-group-text-type">
                        <label class="form-label">Value</label>
                        <textarea name="field_value" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3 input-group-date-type d-none">
                        <label class="form-label">Select Date</label>
                        <input type="date" name="field_date_value" class="form-control">
                    </div>

                    <div class="mb-3 input-group-file-type d-none">
                        <label class="form-label">Select File</label>
                        <input type="file" name="field_file" class="form-control">
                        <div class="mt-2">
                            <label class="form-label small">Description (Optional)</label>
                            <input type="text" name="field_value_desc" class="form-control form-control-sm" placeholder="File description...">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-add-field">Add Field</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openAddCustomFieldModal(employeeId) {
        const modalEl = document.getElementById('addCustomFieldModal');
        const form = document.getElementById('addCustomFieldForm');

        // Set Form Action
        form.action = `/production/registration/custom-fields/${employeeId}`;

        // Store employee ID for post-success logic
        form.dataset.employeeId = employeeId;

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function toggleModalInputs(select) {
        const form = select.closest('form');
        form.querySelector('.input-group-text-type').classList.add('d-none');
        form.querySelector('.input-group-date-type').classList.add('d-none');
        form.querySelector('.input-group-file-type').classList.add('d-none');

        if(select.value === 'text') form.querySelector('.input-group-text-type').classList.remove('d-none');
        if(select.value === 'date') form.querySelector('.input-group-date-type').classList.remove('d-none');
        if(select.value === 'file') form.querySelector('.input-group-file-type').classList.remove('d-none');
    }

    // Handle Form Submission via AJAX to update the drawer list live
    document.getElementById('addCustomFieldForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const btn = form.querySelector('.btn-add-field');
        const originalText = btn.innerText;
        const employeeId = form.dataset.employeeId;

        btn.disabled = true;
        btn.innerText = 'Adding...';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => { throw new Error(data.message || 'Server error'); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update the drawer content if it exists/is visible
                const container = document.getElementById(`drawer-content-${employeeId}`);
                if (container) {
                    const fieldsContainer = container.querySelector('.custom-fields-container');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    if (fieldsContainer) {
                        // Remove "no fields" msg
                        const noMsg = fieldsContainer.querySelector('.no-fields-msg');
                        if(noMsg) noMsg.remove();

                        // Regenerate list
                        // Note: generateFieldsHtml must be globally available or we need to duplicate/move it.
                        // Ideally, it's defined in index.blade.php or offcanvas_drawer.blade.php which are loaded.
                        if (typeof generateFieldsHtml === 'function') {
                             fieldsContainer.innerHTML = generateFieldsHtml(data.employee.custom_fields, csrfToken);
                        }
                    }
                }

                // Close Modal
                const modalEl = document.getElementById('addCustomFieldModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if(modal) modal.hide();

                // Reset Form
                form.reset();
                toggleModalInputs(form.querySelector('select[name="field_type"]'));

                if(typeof showToast === 'function') {
                    showToast('Field added successfully', 'success');
                }
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error(error);
            if(typeof showToast === 'function') {
                showToast(error.message, 'danger');
            } else {
                alert(error.message);
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerText = originalText;
        });
    });
</script>
