{{-- Inline Drawer Template --}}
{{-- We use a script to populate this --}}
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

<script>
    // Function to generate HTML for existing fields
    // Made global-ish so it can be called from the modal success callback
    window.generateFieldsHtml = function(fields, csrfToken) {
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

            // Edit Form (Hidden by default)
            const editFormId = `edit-field-form-${field.id}`;
            const displayId = `display-field-${field.id}`;

            return `
                <div class="mb-3 border-bottom pb-2 field-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <label class="form-label fw-bold small mb-1 text-secondary text-truncate" style="max-width: 70%;">${field.field_name}</label>
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

        // If content is already loaded (has the button), don't reload
        const contentContainer = document.getElementById(`drawer-content-${employeeId}`);
        if(contentContainer.querySelector('.btn-outline-primary')) {
            return;
        }

        // Load Content Logic
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const fieldsHtml = generateFieldsHtml(employee.custom_fields, csrfToken);

        // Replace Template Strings
        let template = document.getElementById('drawer-content-template').innerHTML;
        template = template.replace(/\${csrf}/g, csrfToken) // Keep just in case needed for other things
                           .replace(/\${employeeId}/g, employeeId)
                           .replace('${fieldsHtml}', fieldsHtml);

        contentContainer.innerHTML = template;
    }
</script>
