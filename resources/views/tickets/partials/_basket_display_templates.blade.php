{{-- resources/views/tickets/partials/_basket_display_templates.blade.php --}}

{{-- This partial contains the x-template definitions for displaying items in the attachment basket. --}}
{{-- It's used by both resources/views/tickets/create.blade.php and resources/views/admin/tickets/create.blade.php --}}

<!-- 1. Template for Existing Employees (Affiliated) -->
<template x-for="(item, index) in basket.existing_employees" :key="'e-' + item.id">
    <div class="list-group-item d-flex justify-content-between align-items-center py-2"
         draggable="true"
         @dragstart="startDragGlobal($event, 'employee', {
            id: item.id,
            title: item.employeeNameTh || item.employeeNameEn,
            subtitle: item.employeeNameEn ? item.employeeNameEn : item.employeeCode,
            url: '/employees/' + item.id + '/locate'
         })">
        <div class="d-flex align-items-center gap-3">
            <img :src="item.photo_url" alt="Photo" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
            <span>
                <div class="d-flex align-items-center">
                     <i class="bi bi-person-check me-1 text-primary"></i>
                     <span x-text="item.employeeNameTh"></span>
                     <span class="text-muted ms-1" x-text="item.employeeNameEn ? '(' + item.employeeNameEn + ')' : ''"></span>
                </div>
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('existing_employees', index, item.employeeNameTh)">ลบ</button>
    </div>
</template>

<!-- 1.5 Template for External Employees (Non-Affiliated) -->
<template x-for="(item, index) in basket.external_employees" :key="'ext-' + item.id">
    <div class="list-group-item d-flex justify-content-between align-items-center py-2 bg-light border-warning"
         draggable="true"
         @dragstart="startDragGlobal($event, 'employee', {
            id: item.id,
            title: item.employeeNameTh || item.employeeNameEn,
            subtitle: (item.employer_name || 'Ext') + (item.employeeNameEn ? ' - ' + item.employeeNameEn : ''),
            url: '/employees/' + item.id + '/locate'
         })">
        <div class="d-flex align-items-center gap-3">
             <div class="position-relative">
                <img :src="item.photo_url" alt="Photo" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="font-size: 0.6em;">
                    Ext
                </span>
            </div>
            <span>
                <div class="d-flex align-items-center">
                     <i class="bi bi-person-exclamation me-1 text-warning"></i>
                     <span class="fw-bold text-dark" x-text="item.employeeNameTh"></span>
                     <span class="text-muted ms-1" x-text="item.employeeNameEn ? '(' + item.employeeNameEn + ')' : ''"></span>
                </div>
                {{-- Show Employer Name --}}
                <small class="d-block text-muted" style="font-size: 0.8rem;">
                    <i class="bi bi-building me-1"></i> <span x-text="item.employer_name || 'ไม่ทราบสังกัด'"></span>
                </small>
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('external_employees', index, item.employeeNameTh)">ลบ</button>
    </div>
</template>

<!-- 2. Template for New Employees -->
<template x-for="(item, index) in basket.new_employees" :key="'n-' + index">
    <div class="list-group-item d-flex justify-content-between align-items-center py-2"
         draggable="true"
         @dragstart="startDragGlobal($event, 'new_employee_draft', {
            title: 'New: ' + item.employeeNameTh,
            subtitle: 'Passport: ' + (item.employeePassport || 'N/A'),
            data: item // Drag the whole draft object
         })">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-person-plus fs-4 text-success"></i>
            <span>
                ใหม่: <span x-text="item.employeeNameTh"></span>
                <small class="text-muted d-block" x-text="'Passport: ' + (item.employeePassport || 'N/A')"></small>
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('new_employees', index, item.employeeNameTh)">ลบ</button>
    </div>
</template>

<!-- 3. Template for General Files -->
<template x-for="(item, index) in basket.files" :key="'f-' + index">
    <div class="list-group-item d-flex justify-content-between align-items-center py-2"
         draggable="true"
         @dragstart="startDragGlobal($event, 'file', {
            title: item.name,
            subtitle: formatBytes(item.size),
            url: item.url,
            mime: item.mime
         })">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-file-earmark-text fs-4 text-secondary"></i>
            <span>
                <a :href="item.url" target="_blank" x-text="item.name" class="text-decoration-none"></a>
                <small class="text-muted d-block" x-text="formatBytes(item.size)"></small>
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('files', index, item.name)">ลบ</button>
    </div>
</template>
