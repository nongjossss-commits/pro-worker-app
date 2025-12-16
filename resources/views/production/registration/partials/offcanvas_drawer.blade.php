{{-- Inline Drawer Template --}}
{{-- We use a script to populate this --}}
{{-- Inline Drawer Template for EMPLOYEES --}}
<script id="drawer-content-template" type="text/template">
    <div class="employee-drawer-content">
        <h6 class="fw-bold border-bottom pb-2 mb-3">Custom Fields</h6>

        <div class="custom-fields-list mb-4 custom-fields-container">
            ${fieldsHtml}
        </div>

        <div class="d-grid">
            <button class="btn btn-outline-primary btn-sm" onclick="openAddCustomFieldModal(${employeeId})">
                <i class="bi bi-plus-lg"></i> Add New Field
            </button>
        </div>
    </div>
</script>

{{-- Inline Drawer Template for EMPLOYERS --}}
<script id="drawer-content-template-employer" type="text/template">
    <div class="employer-drawer-content">
        <h6 class="fw-bold border-bottom pb-2 mb-3">Custom Fields (Employer)</h6>

        <div class="custom-fields-list mb-4 custom-fields-container">
            ${fieldsHtml}
        </div>

        <div class="d-grid">
            <button class="btn btn-outline-primary btn-sm" onclick="openAddCustomFieldModal(${employerId}, 'employer')">
                <i class="bi bi-plus-lg"></i> Add New Field
            </button>
        </div>
    </div>
</script>

<script>
    // Function to generate HTML for existing fields
    // Made global-ish so it can be called from the modal success callback
    // Updated to accept an optional 'context' ('employee' or 'employer') to adjust delete/update URLs
    window.generateFieldsHtml = function(fields, csrfToken, context = 'employee') {
        if(!fields || fields.length === 0) return '<p class="text-muted small fst-italic no-fields-msg">No custom fields added.</p>';

        return fields.map(field => {
            let valueHtml = '';
            let editInputHtml = '';

            if(field.field_type === 'text') {
                valueHtml = `<p class="mb-1 bg-white p-2 border rounded small text-break">${field.field_value || '-'}</p>`;
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
                    ${field.field_value ? `<div class="small text-muted fst-italic text-break">${field.field_value}</div>` : ''}
                 `;
                 editInputHtml = `<input type="text" name="field_value" class="form-control form-control-sm mb-2" value="${field.field_value || ''}" placeholder="Update description...">`;
            }

            // Determine routes based on context
            const updateUrl = context === 'employer'
                ? `/production/registration/employer-custom-fields/${field.id}` // Placeholder for update if implemented later, currently we only implemented store/delete for simplicity or reuse logic
                : `/production/registration/custom-fields/${field.id}`;

            // NOTE: Update logic for employer fields is not fully implemented in controller yet (only store/delete).
            // We'll hide the EDIT button for employer fields for now unless requested, or use the delete logic primarily.
            // Actually, let's just use the delete logic for MVP as the user asked for "Add Field".
            // Or reuse the existing route pattern if I add update method later.

            const deleteUrl = context === 'employer'
                ? `/production/registration/employer-custom-fields/${field.id}`
                : `/production/registration/custom-fields/${field.id}`;


            // Edit Form (Hidden by default)
            const editFormId = `edit-field-form-${field.id}`;
            const displayId = `display-field-${field.id}`;

            // Hide Edit for Employer context for now as Controller doesn't have update method explicitly added yet (I added store/destroy).
            // Actually, let's keep it simple.
            // USER REQUEST: Enable Edit for Employer too.
            const showEdit = true;

            // For file type editing, we need to allow re-upload
            if (field.field_type === 'file') {
                 // Append file input to editInputHtml
                 editInputHtml += `<input type="file" name="field_file" class="form-control form-control-sm mb-2">`;
            }

            return `
                <div class="mb-3 border-bottom pb-2 field-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <label class="form-label fw-bold small mb-1 text-secondary text-truncate" style="max-width: 70%;">${field.field_name}</label>
                        <div class="dropdown">
                            <button class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                ${showEdit ? `<li><a class="dropdown-item small" href="#" onclick="event.preventDefault(); document.getElementById('${displayId}').classList.add('d-none'); document.getElementById('${editFormId}').classList.remove('d-none');">Edit</a></li>` : ''}
                                <li>
                                    <form action="${deleteUrl}" method="POST" onsubmit="return confirm('Delete this field?');">
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

                    ${showEdit ? `
                    <form id="${editFormId}" action="${updateUrl}" method="POST" enctype="multipart/form-data" class="d-none mt-2">
                         <input type="hidden" name="_token" value="${csrfToken}">
                         <input type="hidden" name="_method" value="PUT">
                         <input type="text" name="field_name" class="form-control form-control-sm mb-2" value="${field.field_name}" placeholder="Field Name">
                         ${editInputHtml}
                         <div class="d-flex justify-content-end gap-2">
                             <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('${displayId}').classList.remove('d-none'); document.getElementById('${editFormId}').classList.add('d-none');">Cancel</button>
                             <button type="submit" class="btn btn-xs btn-primary">Save</button>
                         </div>
                    </form>` : ''}
                </div>
            `;
        }).join('');
    }

    function toggleInlineDrawer(employeeId, employee) {
        // Toggle the collapse using Bootstrap API
        const drawerEl = document.getElementById(`drawer-employee-${employeeId}`);
        const bsCollapse = new bootstrap.Collapse(drawerEl, { toggle: true });

        // If content is already loaded (has the button), don't reload
        const contentContainer = document.getElementById(`drawer-content-${employeeId}`);
        if(contentContainer.querySelector('.btn-outline-primary')) {
            return;
        }

        // Load Content Logic
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const fieldsHtml = generateFieldsHtml(employee.custom_fields, csrfToken, 'employee');

        // Replace Template Strings
        let template = document.getElementById('drawer-content-template').innerHTML;
        template = template.replace(/\${csrf}/g, csrfToken)
                           .replace(/\${employeeId}/g, employeeId)
                           .replace('${fieldsHtml}', fieldsHtml);

        contentContainer.innerHTML = template;
    }

    function toggleEmployerInlineDrawer(employerId, customFields) {
        const drawerEl = document.getElementById(`drawer-employer-${employerId}`);
        const bsCollapse = new bootstrap.Collapse(drawerEl, { toggle: true });

        const contentContainer = document.getElementById(`drawer-content-employer-${employerId}`);
        if(contentContainer.querySelector('.btn-outline-primary')) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const fieldsHtml = generateFieldsHtml(customFields, csrfToken, 'employer');

        let template = document.getElementById('drawer-content-template-employer').innerHTML;
        template = template.replace(/\${employerId}/g, employerId)
                           .replace('${fieldsHtml}', fieldsHtml);

        contentContainer.innerHTML = template;
    }
</script>
</script>
