{{-- Inline Drawer Template --}}
{{-- We use a script to populate this --}}
<script id="drawer-content-template" type="text/template">
    <div class="employee-drawer-content">
        <h6 class="fw-bold border-bottom pb-2 mb-3">Custom Fields</h6>

        <div class="custom-fields-list mb-4 custom-fields-container">
            ${fieldsHtml}
        </div>

        <h6 class="fw-bold border-bottom pb-2 mb-3">Add New Field</h6>
        <form action="${addUrl}" method="POST" enctype="multipart/form-data" onsubmit="submitCustomField(event, this, ${employeeId})">
            <input type="hidden" name="_token" value="${csrf}">

            <div class="mb-3">
                <label class="form-label small">Field Name (Label)</label>
                <input type="text" name="field_name" class="form-control form-control-sm" required placeholder="e.g. Additional Note, Medical Cert">
            </div>

            <div class="mb-3">
                 <label class="form-label small">Field Type</label>
                 <select name="field_type" class="form-select form-select-sm" onchange="toggleDrawerInputs(this)">
                     <option value="text">Text Box</option>
                     <option value="date">Date</option>
                     <option value="file">File Attachment</option>
                 </select>
            </div>

            {{-- Dynamic Inputs --}}
            <div class="mb-3 input-group-text-type">
                <label class="form-label small">Value</label>
                <textarea name="field_value" class="form-control form-control-sm" rows="2"></textarea>
            </div>

            <div class="mb-3 input-group-date-type d-none">
                <label class="form-label small">Select Date</label>
                <input type="date" name="field_date_value" class="form-control form-control-sm">
            </div>

            <div class="mb-3 input-group-file-type d-none">
                <label class="form-label small">Select File</label>
                <input type="file" name="field_file" class="form-control form-control-sm">
                <div class="mt-2">
                    <label class="form-label small">Description (Optional)</label>
                    <input type="text" name="field_value_desc" class="form-control form-control-sm" placeholder="File description...">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm w-100 btn-add-field">Add Field</button>
        </form>
    </div>
</script>

<script>
    function toggleDrawerInputs(select) {
        const form = select.closest('form');
        form.querySelector('.input-group-text-type').classList.add('d-none');
        form.querySelector('.input-group-date-type').classList.add('d-none');
        form.querySelector('.input-group-file-type').classList.add('d-none');

        if(select.value === 'text') form.querySelector('.input-group-text-type').classList.remove('d-none');
        if(select.value === 'date') form.querySelector('.input-group-date-type').classList.remove('d-none');
        if(select.value === 'file') form.querySelector('.input-group-file-type').classList.remove('d-none');
    }

    // Function to generate HTML for existing fields
    function generateFieldsHtml(fields, csrfToken) {
        if(!fields || fields.length === 0) return '<p class="text-muted small fst-italic no-fields-msg">No custom fields added.</p>';

        return fields.map(field => {
            let valueHtml = '';
            let editInputHtml = '';

            if(field.field_type === 'text') {
                valueHtml = `<p class="mb-1 bg-white p-2 border rounded small">${field.field_value || '-'}</p>`;
                editInputHtml = `<textarea name="field_value" class="form-control form-control-sm mb-2" rows="2">${field.field_value || ''}</textarea>`;
            } else if(field.field_type === 'date') {
                valueHtml = `<div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-calendar"></i> ${field.field_value}</div>`;
                editInputHtml = `<input type="date" name="field_date_value" class="form-control form-control-sm mb-2" value="${field.field_value}">`;
            } else if(field.field_type === 'file') {
                 valueHtml = `
                    <div class="d-flex align-items-center gap-2 mb-1 bg-white p-2 border rounded">
                        <i class="bi bi-paperclip"></i>
                        <a href="/storage/${field.file_path}" target="_blank" class="text-decoration-none text-truncate" style="max-width: 150px;">View File</a>
                    </div>
                    ${field.field_value ? `<div class="small text-muted fst-italic">${field.field_value}</div>` : ''}
                 `;
                 editInputHtml = `<input type="text" name="field_value" class="form-control form-control-sm mb-2" value="${field.field_value || ''}" placeholder="Update description...">`;
            }

            // Edit Form (Hidden by default)
            const editFormId = `edit-field-form-${field.id}`;
            const displayId = `display-field-${field.id}`;

            return `
                <div class="mb-3 border-bottom pb-2 field-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <label class="form-label fw-bold small mb-1 text-secondary">${field.field_name}</label>
                        <div class="dropdown">
                            <button class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item small" href="#" onclick="event.preventDefault(); document.getElementById('${displayId}').classList.add('d-none'); document.getElementById('${editFormId}').classList.remove('d-none');">Edit</a></li>
                                <li>
                                    <form action="/production/registration/custom-fields/${field.id}" method="POST" onsubmit="return confirm('Delete this field?');">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="dropdown-item small text-danger">Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div id="${displayId}">
                        ${valueHtml}
                    </div>

                    <form id="${editFormId}" action="/production/registration/custom-fields/${field.id}" method="POST" class="d-none mt-2">
                         <input type="hidden" name="_token" value="${csrfToken}">
                         <input type="hidden" name="_method" value="PUT">
                         <input type="text" name="field_name" class="form-control form-control-sm mb-2" value="${field.field_name}" placeholder="Field Name">
                         ${editInputHtml}
                         <div class="d-flex justify-content-end gap-2">
                             <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('${displayId}').classList.remove('d-none'); document.getElementById('${editFormId}').classList.add('d-none');">Cancel</button>
                             <button type="submit" class="btn btn-xs btn-primary">Save</button>
                         </div>
                    </form>
                </div>
            `;
        }).join('');
    }

    function toggleInlineDrawer(employeeId, employee) {
        // Toggle the collapse using Bootstrap API
        const drawerEl = document.getElementById(`drawer-employee-${employeeId}`);
        const bsCollapse = new bootstrap.Collapse(drawerEl, { toggle: true });

        // If content is already loaded (has a form), don't reload
        const contentContainer = document.getElementById(`drawer-content-${employeeId}`);
        if(contentContainer.querySelector('form')) {
            return;
        }

        // Load Content Logic
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const addUrl = `/production/registration/custom-fields/${employeeId}`;
        const fieldsHtml = generateFieldsHtml(employee.custom_fields, csrfToken);

        // Replace Template Strings
        let template = document.getElementById('drawer-content-template').innerHTML;
        template = template.replace(/\${addUrl}/g, addUrl)
                           .replace(/\${csrf}/g, csrfToken)
                           .replace(/\${employeeId}/g, employeeId)
                           .replace('${fieldsHtml}', fieldsHtml);

        contentContainer.innerHTML = template;
    }

    // AJAX Submission for New Field
    function submitCustomField(event, form, employeeId) {
        event.preventDefault();

        const btn = form.querySelector('.btn-add-field');
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Adding...';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest', // Important for Laravel to detect ajax()
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
                // Find the specific container for this employee
                const container = document.getElementById(`drawer-content-${employeeId}`).querySelector('.custom-fields-container');
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // If 'no fields' message exists, remove it
                const noMsg = container.querySelector('.no-fields-msg');
                if(noMsg) noMsg.remove();

                const newHtml = generateFieldsHtml(data.employee.custom_fields, csrfToken);
                container.innerHTML = newHtml;

                // Reset Form
                form.reset();
                toggleDrawerInputs(form.querySelector('select[name="field_type"]'));

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
    }
</script>
