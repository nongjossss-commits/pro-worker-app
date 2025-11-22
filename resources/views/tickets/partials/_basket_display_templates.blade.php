{{-- resources/views/tickets/partials/_basket_display_templates.blade.php --}}

{{-- This partial contains the x-template definitions for displaying items in the attachment basket. --}}
{{-- It's used by both resources/views/tickets/create.blade.php and resources/views/admin/tickets/create.blade.php --}}

<!-- 1. Template for Existing Employees -->
<template x-for="(item, index) in basket.existing_employees" :key="'e-' + item.id">
    <div class="list-group-item d-flex justify-content-between align-items-center py-2">
        <div class="d-flex align-items-center gap-3">
            <img :src="item.photo_url" alt="Photo" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
            <span>
                <div class="d-flex align-items-center">
                     <i class="bi bi-person-check me-1 text-primary"></i>
                     <span x-text="item.employeeNameTh"></span>
                     <span class="text-muted ms-1" x-text="item.employeeNameEn ? '(' + item.employeeNameEn + ')' : ''"></span>
                </div>
                {{-- V2.5-S16: Show External Tag/Employer Name if available and possibly external --}}
                <template x-if="item.employer_name">
                    <small class="d-block text-info" style="font-size: 0.8rem;">
                        <i class="bi bi-building me-1"></i> <span x-text="item.employer_name"></span>
                    </small>
                </template>
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('existing_employees', index, item.employeeNameTh)">ลบ</button>
        {{-- Hidden inputs have been moved to _basket_form_inputs.blade.php to ensure reliable form submission --}}
    </div>
</template>

<!-- 2. Template for New Employees -->
<template x-for="(item, index) in basket.new_employees" :key="'n-' + index">
    <div class="list-group-item d-flex justify-content-between align-items-center py-2">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-person-plus fs-4 text-success"></i>
            <span>
                ใหม่: <span x-text="item.employeeNameTh"></span>
                <small class="text-muted d-block" x-text="'Passport: ' + (item.employeePassport || 'N/A')"></small>
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('new_employees', index, item.employeeNameTh)">ลบ</button>
        {{-- Hidden inputs have been moved to _basket_form_inputs.blade.php to ensure reliable form submission --}}
    </div>
</template>

<!-- 3. Template for General Files -->
<template x-for="(item, index) in basket.files" :key="'f-' + index">
    <div class="list-group-item d-flex justify-content-between align-items-center py-2">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-file-earmark-text fs-4 text-secondary"></i>
            <span>
                <a :href="item.url" target="_blank" x-text="item.name" class="text-decoration-none"></a>
                <small class="text-muted d-block" x-text="formatBytes(item.size)"></small>
            </span>
        </div>
        <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('files', index, item.name)">ลบ</button>
        {{-- Hidden inputs have been moved to _basket_form_inputs.blade.php to ensure reliable form submission --}}
    </div>
</template>
