{{-- Inline Drawer Template for PRODUCTION ORDER (MOU demand card) --}}
<script id="drawer-content-template-order" type="text/template">
    <div class="order-drawer-content">
        <h6 class="fw-bold border-bottom pb-2 mb-3">{{ __('Custom Fields') }}</h6>

        <div class="custom-fields-list mb-4 custom-fields-container">
            ${fieldsHtml}
        </div>

        <div class="d-grid">
            <button class="btn btn-outline-primary btn-sm" onclick="openAddCustomFieldModal(${orderId}, 'order')">
                <i class="bi bi-plus-lg"></i> {{ __('Add New Field') }}
            </button>
        </div>
    </div>
</script>

<script>
    // generateOrderFieldsHtml: ใช้รูปแบบเดียวกับ generateFieldsHtml ในเมนูมติลงทะเบียน
    // แต่ URL ชี้ไปที่ /production/order/custom-fields/{field} เพราะ ProductionOrder ไม่ผูกกับ resolution tab
    window.generateOrderFieldsHtml = function(fields, csrfToken) {
        if (!fields || fields.length === 0) {
            return '<p class="text-muted small fst-italic no-fields-msg">{{ __('No custom fields added.') }}</p>';
        }

        return fields.map(field => {
            let valueHtml = '';
            let editInputHtml = '';

            if (field.field_type === 'text') {
                valueHtml = `<p class="mb-1 bg-white p-2 border rounded small text-break">${field.field_value || '-'}</p>`;
                editInputHtml = `<textarea name="field_value" class="form-control form-control-sm mb-2" rows="2">${field.field_value || ''}</textarea>`;
            } else if (field.field_type === 'date') {
                valueHtml = `<div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-calendar"></i> ${field.field_value || ''}</div>`;
                editInputHtml = `<input type="date" name="field_date_value" class="form-control form-control-sm mb-2" value="${field.field_value || ''}">`;
            } else if (field.field_type === 'file') {
                valueHtml = `
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1 bg-white p-2 border rounded">
                        <div class="d-flex align-items-center gap-2 text-truncate">
                            <i class="bi bi-paperclip text-muted"></i>
                            <span class="small text-secondary text-truncate" style="max-width: 150px;">{{ __('Attachment') }}</span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="#" onclick="event.preventDefault(); viewPDF('/storage/${field.file_path}', '${field.field_name}')" class="btn btn-sm btn-success text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;" title="{{ __('View File') }}">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            <a href="/custom-fields/${field.id}/pdf?type=order" download class="btn btn-sm btn-danger text-white rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;" title="{{ __('Download') }}">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                            </a>
                        </div>
                    </div>
                    ${field.field_value ? `<div class="small text-muted fst-italic text-break mt-1">${field.field_value}</div>` : ''}
                `;
                editInputHtml = `<input type="text" name="field_value" class="form-control form-control-sm mb-2" value="${field.field_value || ''}" placeholder="{{ __('Description') }}...">`;
                editInputHtml += `<input type="file" name="field_file" class="form-control form-control-sm mb-2">`;
            }

            const updateUrl = `/production/order/custom-fields/${field.id}`;
            const deleteUrl = `/production/order/custom-fields/${field.id}`;

            const editFormId = `edit-order-field-form-${field.id}`;
            const displayId = `display-order-field-${field.id}`;

            return `
                <div class="mb-3 border-bottom pb-2 field-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <label class="form-label fw-bold small mb-1 text-secondary text-truncate" style="max-width: 70%;">${field.field_name}</label>
                        <div class="dropdown">
                            <button class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item small" href="#" onclick="event.preventDefault(); document.getElementById('${displayId}').classList.add('d-none'); document.getElementById('${editFormId}').classList.remove('d-none');">{{ __('Edit') }}</a></li>
                                <li>
                                    <form action="${deleteUrl}" method="POST" onsubmit="return confirm('{{ __('Delete this field?') }}');">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="dropdown-item small text-danger">{{ __('Delete') }}</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div id="${displayId}">
                        ${valueHtml}
                    </div>

                    <form id="${editFormId}" action="${updateUrl}" method="POST" enctype="multipart/form-data" class="d-none mt-2">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="text" name="field_name" class="form-control form-control-sm mb-2" value="${field.field_name}" placeholder="{{ __('Field Name') }}">
                        ${editInputHtml}
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('${displayId}').classList.remove('d-none'); document.getElementById('${editFormId}').classList.add('d-none');">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-xs btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            `;
        }).join('');
    };

    window.toggleOrderInlineDrawer = function(orderId, customFields) {
        const drawerEl = document.getElementById(`drawer-order-${orderId}`);
        if (!drawerEl) {
            console.warn(`Drawer element drawer-order-${orderId} not found`);
            return;
        }
        const bsCollapse = new bootstrap.Collapse(drawerEl, { toggle: true });

        const contentContainer = document.getElementById(`drawer-content-order-${orderId}`);
        if (!contentContainer) return;

        // โหลดเนื้อหาแค่ครั้งแรก (ถ้ามีปุ่ม Add แล้ว แปลว่าโหลดแล้ว)
        if (contentContainer.querySelector('.btn-outline-primary')) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const fieldsHtml = window.generateOrderFieldsHtml(customFields || [], csrfToken);

        let template = document.getElementById('drawer-content-template-order').innerHTML;
        template = template.replace(/\${orderId}/g, orderId)
                           .replace('${fieldsHtml}', fieldsHtml);

        contentContainer.innerHTML = template;
    };
</script>
