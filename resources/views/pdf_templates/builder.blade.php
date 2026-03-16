@extends('layouts.app')

@section('content')
<div class="h-screen flex flex-col" x-data="pdfBuilder()">
    <!-- Toolbar -->
    <div class="bg-white border-b px-4 py-3 flex justify-between items-center shadow-sm z-30 sticky top-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.pdf-templates.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h1 class="text-lg font-bold text-gray-800"><span x-text="templateName"></span> <span class="text-sm font-normal text-gray-500">(Builder Mode)</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <!-- Page Navigation -->
            <div class="flex items-center gap-2" x-show="totalPages > 1">
                <label class="text-sm text-gray-600">Go to Page:</label>
                <select x-model="currentPage" @change="scrollToPage(currentPage)" class="form-select form-select-sm w-20">
                    <template x-for="p in totalPages" :key="p">
                        <option :value="p" x-text="p"></option>
                    </template>
                </select>
                <span class="text-sm text-gray-500">of <span x-text="totalPages"></span></span>
            </div>

            <div class="border-l h-6 mx-2"></div>

            <button type="button" @click="openTemplateSettings()" class="btn btn-outline-secondary btn-sm flex items-center gap-2">
                <i class="bi bi-sliders"></i> Settings
            </button>

            <button @click="saveMapping()" class="btn btn-primary btn-sm flex items-center gap-2" :disabled="isSaving">
                <span x-show="isSaving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <i class="bi bi-save" x-show="!isSaving"></i> Save Template
            </button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden h-[calc(100vh-64px)]">
        <!-- Sidebar -->
        <div class="w-80 bg-gray-50 border-r flex flex-col overflow-y-auto z-20 shadow-lg">
            <div class="p-4 border-b bg-white flex flex-col gap-3 shadow-sm z-10 relative">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-gray-700 m-0">Data Fields</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500" title="Employees per page">Slots:</span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" @click="if(metaData.employees_per_page > 1) metaData.employees_per_page--" class="btn btn-outline-secondary px-2 py-0">-</button>
                                <span class="btn btn-outline-secondary px-3 py-0 disabled bg-light text-dark font-bold" x-text="metaData.employees_per_page || 1"></span>
                                <button type="button" @click="metaData.employees_per_page = (metaData.employees_per_page || 1) + 1" class="btn btn-outline-secondary px-2 py-0">+</button>
                            </div>
                        </div>
                    </div>
                    <input type="text" x-model="searchQuery" placeholder="Search fields..." class="form-control form-control-sm">
                </div>

                <!-- Employee Slots Tabs -->
                <div x-show="(metaData.employees_per_page || 1) > 1" class="flex flex-wrap gap-1 bg-gray-100 p-1 rounded-md border">
                    <template x-for="i in (metaData.employees_per_page || 1)" :key="i">
                        <button type="button"
                                @click="currentEmployeeSlot = i"
                                :class="{'bg-white shadow-sm font-bold text-blue-600 border-gray-300': currentEmployeeSlot === i, 'text-gray-500 hover:bg-gray-200 border-transparent': currentEmployeeSlot !== i}"
                                class="flex-1 min-w-[3rem] text-xs py-1 px-2 rounded border transition-colors flex items-center justify-center gap-1">
                            <i class="bi bi-person-fill" x-show="currentEmployeeSlot === i"></i>
                            <span x-text="'Emp ' + i"></span>
                        </button>
                    </template>
                </div>
                <div x-show="(metaData.employees_per_page || 1) > 1" class="text-[10px] text-orange-600 bg-orange-50 p-1.5 rounded border border-orange-100 mt-1 leading-tight text-center">
                    <i class="bi bi-info-circle me-1"></i> Dragging fields will assign them to <strong>Emp <span x-text="currentEmployeeSlot"></span></strong>.
                </div>
            </div>

            <div class="p-3 space-y-4 flex-1 bg-gray-50">
                <!-- Tools Section -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2 border-b pb-1">Tools</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Static Text -->
                        <div class="bg-white p-2 border rounded shadow-sm cursor-grab hover:bg-orange-50 transition-colors flex flex-col items-center justify-center gap-1 text-center"
                             draggable="true"
                             @dragstart="dragStart($event, {type: 'static', label: 'Static Text'})">
                            <i class="bi bi-type text-xl text-gray-600"></i>
                            <span class="text-xs font-medium">Text</span>
                        </div>
                        <!-- Signature -->
                        <div class="bg-white p-2 border rounded shadow-sm cursor-grab hover:bg-orange-50 transition-colors flex flex-col items-center justify-center gap-1 text-center"
                             draggable="true"
                             @dragstart="dragStart($event, {type: 'signature', label: 'Signature Box'})">
                            <i class="bi bi-pen text-xl text-gray-600"></i>
                            <span class="text-xs font-medium">Signature</span>
                        </div>

                        <!-- Stamp -->
                        <div class="border rounded p-2 bg-white cursor-move hover:border-red-500 hover:bg-red-50 flex flex-col items-center justify-center gap-1 text-center"
                             draggable="true"
                             @dragstart="dragStart($event, {type: 'stamp', label: 'Employer Stamp'})">
                            <i class="bi bi-vinyl text-xl text-gray-600"></i>
                            <span class="text-xs font-medium">Stamp</span>
                        </div>
                    </div>
                </div>

                <!-- Data Fields Groups -->
                <template x-for="(group, groupName) in filteredGroups" :key="groupName">
                    <div class="mb-2">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-2 border-b pb-1" x-text="groupName"></h4>
                        <div class="space-y-1">
                            <template x-for="field in group" :key="field.key">
                                <div class="bg-white p-2 border rounded shadow-sm cursor-grab hover:bg-orange-50 transition-colors flex items-center justify-between"
                                     draggable="true"
                                     @dragstart="dragStart($event, {type: 'db', key: field.key, label: field.label})">
                                    <span class="text-sm" x-text="field.label"></span>
                                    <i class="bi bi-grip-vertical text-gray-400"></i>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="flex-1 bg-gray-200 overflow-y-auto flex justify-center p-8 relative" id="main-scroll-container">
            <div class="flex flex-col gap-8 pb-20">
                <!-- Loop through pages -->
                <template x-for="pageNum in totalPages" :key="pageNum">
                    <div class="relative shadow-lg bg-white"
                         :id="'page-container-' + pageNum"
                         :style="pageDimensions[pageNum] ? `width: ${pageDimensions[pageNum].width}px; height: ${pageDimensions[pageNum].height}px;` : 'min-height: 200px;'">

                        <!-- Page Label -->
                        <div class="absolute -top-6 left-0 text-sm font-bold text-gray-500">
                            Page <span x-text="pageNum"></span>
                        </div>

                        <!-- PDF Canvas -->
                        <canvas :id="'canvas-page-' + pageNum" class="block bg-white"></canvas>

                        <!-- Drop Overlay -->
                        <div class="absolute inset-0 z-10"
                             @dragover.prevent
                             @drop.prevent="drop($event, pageNum)">

                            <!-- Placed Items -->
                            <template x-for="(item, index) in items" :key="index">
                                <div class="absolute border cursor-move group flex px-1"
                                     :class="{
                                        'border-blue-500 bg-blue-50/30 hover:bg-blue-100/50': item.type === 'db',
                                        'border-gray-500 bg-gray-50/30 hover:bg-gray-100/50': item.type === 'static',
                                        'border-purple-500 bg-purple-50/30 hover:bg-purple-100/50': item.type === 'signature',
                                        'border-red-500 bg-red-50/30 hover:bg-red-100/50': item.type === 'stamp'
                                     }"
                                     :style="`display: ${parseInt(item.page) === pageNum ? 'flex' : 'none'}; left: ${item.x}%; top: ${item.y}%; width: ${item.w}%; height: ${item.h}%;`"
                                     @mousedown.self="startMove($event, index, pageNum)">

                                    <!-- Stamp Content -->
                                    <template x-if="item.type === 'stamp'">
                                        <div class="w-full h-full flex flex-col items-center justify-center pointer-events-none select-none relative">
                                            <!-- SVG Stamp Placeholder -->
                                            <div class="w-16 h-16 rounded-full border-4 border-red-500/50 flex items-center justify-center text-red-500/50 font-bold rotate-12 bg-white/30">
                                                STAMP
                                            </div>

                                            <!-- Label Overlay -->
                                            <div class="absolute bottom-0 right-0 bg-white/80 text-[10px] px-1 rounded border border-red-200 text-red-800 font-bold flex gap-1">
                                                <span>(Employer Stamp)</span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Signature Content -->
                                    <template x-if="item.type === 'signature'">
                                        <div class="w-full h-full flex flex-col items-center justify-center pointer-events-none select-none relative">
                                            <!-- SVG Signature Placeholder -->
                                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgNTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iYmx1ZSIgc3Ryb2tlLXdpZHRoPSIyIj48cGF0aCBkPSJNMTAsNDAgUTMwLDEwIDUwLDQwIFQ5MCwyMCIgLz48L3N2Zz4="
                                                 class="max-w-full max-h-full opacity-60" style="object-fit: contain;">

                                            <!-- Label Overlay -->
                                            <div class="absolute bottom-0 right-0 bg-white/80 text-[10px] px-1 rounded border border-purple-200 text-purple-800 font-bold flex gap-1">
                                                <span x-show="item.employeeIndex && (metaData.employees_per_page || 1) > 1" class="bg-orange-100 text-orange-800 px-1 rounded">E<span x-text="item.employeeIndex"></span></span>
                                                <span x-text="getSignatureLabel(item.signatureGroup)"></span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Text Content (DB & Static) -->
                                    <template x-if="item.type !== 'signature' && item.type !== 'stamp'">
                                        <div class="w-full h-full flex flex-col justify-end overflow-hidden pointer-events-none select-none relative"
                                             :style="`font-family: 'THSarabunNew', sans-serif; font-size: ${getFontSize(item, pageNum)}; text-align: ${item.align || 'left'}; color: #000;`">

                                            <div class="absolute top-0 right-0 bg-white/80 text-[10px] px-1 rounded-bl border-b border-l border-blue-200 text-blue-800 font-bold" x-show="item.employeeIndex && (metaData.employees_per_page || 1) > 1">
                                                E<span x-text="item.employeeIndex"></span>
                                            </div>

                                            <span class="block w-full whitespace-nowrap" x-text="getPreviewText(item)" style="line-height: 1;"></span>
                                        </div>
                                    </template>

                                    <!-- Resize Handles (8 directions) -->
                                    <!-- Corners -->
                                    <div class="resize-handle absolute top-0 left-0 w-2 h-2 bg-white border border-gray-400 cursor-nwse-resize z-20 -translate-x-1/2 -translate-y-1/2 rounded-full"
                                         @mousedown.stop="startResize($event, index, pageNum, 'nw')"></div>
                                    <div class="resize-handle absolute top-0 right-0 w-2 h-2 bg-white border border-gray-400 cursor-nesw-resize z-20 translate-x-1/2 -translate-y-1/2 rounded-full"
                                         @mousedown.stop="startResize($event, index, pageNum, 'ne')"></div>
                                    <div class="resize-handle absolute bottom-0 right-0 w-2 h-2 bg-white border border-gray-400 cursor-nwse-resize z-20 translate-x-1/2 translate-y-1/2 rounded-full"
                                         @mousedown.stop="startResize($event, index, pageNum, 'se')"></div>
                                    <div class="resize-handle absolute bottom-0 left-0 w-2 h-2 bg-white border border-gray-400 cursor-nesw-resize z-20 -translate-x-1/2 translate-y-1/2 rounded-full"
                                         @mousedown.stop="startResize($event, index, pageNum, 'sw')"></div>

                                    <!-- Sides -->
                                    <div class="resize-handle absolute top-0 left-1/2 w-2 h-2 bg-white border border-gray-400 cursor-ns-resize z-20 -translate-x-1/2 -translate-y-1/2 rounded-full"
                                         @mousedown.stop="startResize($event, index, pageNum, 'n')"></div>
                                    <div class="resize-handle absolute top-1/2 right-0 w-2 h-2 bg-white border border-gray-400 cursor-ew-resize z-20 translate-x-1/2 -translate-y-1/2 rounded-full"
                                         @mousedown.stop="startResize($event, index, pageNum, 'e')"></div>
                                    <div class="resize-handle absolute bottom-0 left-1/2 w-2 h-2 bg-white border border-gray-400 cursor-ns-resize z-20 -translate-x-1/2 translate-y-1/2 rounded-full"
                                         @mousedown.stop="startResize($event, index, pageNum, 's')"></div>
                                    <div class="resize-handle absolute top-1/2 left-0 w-2 h-2 bg-white border border-gray-400 cursor-ew-resize z-20 -translate-x-1/2 -translate-y-1/2 rounded-full"
                                         @mousedown.stop="startResize($event, index, pageNum, 'w')"></div>

                                    <!-- Controls -->
                                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-white shadow-lg rounded border flex gap-1 p-1 hidden group-hover:flex z-50">
                                        <!-- Settings Button (Context Aware) -->
                                        <button @click.stop="openSettings(index)" class="p-1 hover:bg-gray-100 rounded text-gray-600" title="Settings">
                                            <i class="bi bi-gear"></i>
                                        </button>

                                        <!-- Delete Button (Red Trash Can) -->
                                        <button @click.stop="deleteItem(index)" class="p-1 hover:bg-red-50 rounded text-red-600 bg-white border border-red-200 shadow-sm" title="Remove">
                                            <i class="bi bi-trash-fill text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Item Settings Modal -->
    <div class="modal fade" id="itemSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" x-data="{ currentItem: {} }">
                <div class="modal-header">
                    <h5 class="modal-title">Item Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" x-show="editingIndex !== null">
                    <!-- Static Text Edit -->
                    <template x-if="items[editingIndex]?.type === 'static'">
                        <div class="mb-3">
                            <label class="form-label">Text Content</label>
                            <input type="text" x-model="items[editingIndex].text" class="form-control">
                        </div>
                    </template>

                    <!-- Signature Settings -->
                    <template x-if="items[editingIndex]?.type === 'signature'">
                        <div>
                            <div class="mb-3" x-show="(metaData.employees_per_page || 1) > 1 && items[editingIndex].signatureGroup === 'employee'">
                                <label class="form-label text-orange-600 font-bold">Employee Slot Assignment</label>
                                <select x-model="items[editingIndex].employeeIndex" class="form-select border-orange-300 bg-orange-50">
                                    <template x-for="i in (metaData.employees_per_page || 1)" :key="i">
                                        <option :value="i" x-text="'Employee ' + i"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Signature Group</label>
                                <select x-model="items[editingIndex].signatureGroup" class="form-select">
                                    <option value="employee">Employee</option>
                                    <option value="employer">Employer 1 (Signer 1)</option>
                                    <option value="employer_2">Employer 2 (Signer 2)</option>
                                    <option value="delegate">ลายเซ็นพนักงานบริษัท (Delegate)</option>
                                    <option value="witness_1">Witness 1</option>
                                    <option value="witness_2">Witness 2</option>
                                    <option value="witness_3">Witness 3</option>
                                    <option value="witness_4">Witness 4</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <!-- Text Field Settings (DB & Static) -->
                    <template x-if="items[editingIndex]?.type === 'db' || items[editingIndex]?.type === 'static'">
                        <div>
                             <div class="mb-3" x-show="(metaData.employees_per_page || 1) > 1 && items[editingIndex]?.type === 'db'">
                                <label class="form-label text-orange-600 font-bold">Employee Slot Assignment</label>
                                <select x-model="items[editingIndex].employeeIndex" class="form-select border-orange-300 bg-orange-50">
                                    <template x-for="i in (metaData.employees_per_page || 1)" :key="i">
                                        <option :value="i" x-text="'Employee ' + i"></option>
                                    </template>
                                </select>
                            </div>

                             <div class="mb-3">
                                <label class="form-label">Alignment</label>
                                <select x-model="items[editingIndex].align" class="form-select">
                                    <option value="left">Left (Default)</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="autoFitToggle" x-model="items[editingIndex].autoFit">
                                    <label class="form-check-label" for="autoFitToggle">Auto-fit / Fit to Height</label>
                                </div>
                                <div class="form-text text-xs text-muted">
                                    If enabled, font size will adjust to fit the box height.
                                </div>
                            </div>
                        </div>
                    </template>
                     <template x-if="(items[editingIndex]?.type === 'db' || items[editingIndex]?.type === 'static') && !items[editingIndex]?.autoFit">
                        <div class="mb-3">
                             <label class="form-label">Font Size (pt)</label>
                             <input type="number" x-model="items[editingIndex].fontSize" class="form-control" min="8" max="72">
                        </div>
                    </template>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Settings Modal -->
    <div class="modal fade" id="templateSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Template Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info border-0 d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            These settings apply globally to this template when generating documents.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Template Name</label>
                        <input type="text" x-model="templateName" class="form-control" placeholder="Enter template name">
                    </div>

                    <div class="form-check form-switch p-3 border rounded bg-light mb-3">
                        <input class="form-check-input" type="checkbox" id="autoPrefixToggle" x-model="metaData.auto_prefix_titles">
                        <label class="form-check-label fw-bold" for="autoPrefixToggle">
                            Auto-Prefix Titles
                        </label>
                        <div class="text-muted small mt-1">
                            Automatically add "Mr./Ms." or "นาย/นาง/นางสาว" to names if missing.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    document.addEventListener('alpine:init', () => {
        Alpine.data('pdfBuilder', () => {
            let _pdfDoc = null; // Store PDF doc outside Alpine reactive scope to avoid Proxy issues

            return {
                templateName: @json($template->name ?? ''),
                currentPage: 1,
                totalPages: 1,
                scale: 1.5,
                pageDimensions: {},
                items: @json($template->field_mapping ?? []),
                metaData: @json($template->meta_data ?? ['auto_prefix_titles' => false, 'employees_per_page' => 1]),

                currentEmployeeSlot: 1,
                searchQuery: '',
                isSaving: false,
                editingIndex: null,

                dummyData: {
                    // Employee Personal
                    'employeeNameTh': 'นายสมชาย ใจดี',
                    'employeeNameEn': 'MR. SOMCHAI JAIDEE',
                    'employeeTitleTh': 'นาย',
                    'employeeTitleEn': 'Mr.',
                    'employeeGender': 'ชาย',
                    'age': '30',
                    'employeeDob': '01/01/1993',
                    'employeeDob_day': '01',
                    'employeeDob_month_en': 'JAN',
                    'employeeDob_year_ce': '1993',
                    'employeeNationality': 'Thai',
                    'father_name': 'นายบิดา ใจดี',
                    'mother_name': 'นางมารดา ใจดี',
                    'employeePhone': '081-234-5678',
                    'employee_id_number': '1-2345-67890-12-3',
                    'tax_id_number': '1-2345-67890-12-3',
                    'height': '175',
                    'weight': '70',
                    'bank_name': 'KBANK',
                    'bank_account_number': '123-4-56789-0',
                    'email': 'employee@example.com',

                    // Documents
                    'employeePassport': 'AA1234567',
                    'passportIssueDate': '01/01/2023',
                    'passportIssueDate_day': '01',
                    'passportIssueDate_month_en': 'JAN',
                    'passportIssueDate_year_ce': '2023',
                    'passportExpiryDate': '01/01/2028',
                    'passportExpiryDate_day': '01',
                    'passportExpiryDate_month_en': 'JAN',
                    'passportExpiryDate_year_ce': '2028',
                    'passport_issue_place': 'Bangkok',
                    'passportType': 'Ordinary',
                    'passport_type_cambodia': 'Ordinary',
                    'visaType': 'Non-B',
                    'visaExpiryDate': '01/01/2025',
                    'visa_issue_place': 'Bangkok',
                    'employeeWorkPermit': 'WP123456',
                    'workPermitExpiryDate': '01/01/2025',
                    'workPermitMOUGroup': 'MOU',
                    'workPermitMOUGroupOther': '-',
                    'pinkCardNo': '1234567890123',
                    'name_list_number': '12345',
                    'request_number': 'REQ-2023-001',
                    'ninetyDayReportDate': '01/04/2024',

                    // Job
                    'job_title': 'Worker',
                    'employeePosition': 'General Worker',
                    'department': 'Production',
                    'job_description': 'General Duties',
                    'nature_of_work': 'General Duties',
                    'startDate': '01/01/2023',
                    'workAge': '1 Year',
                    'social_security_number': '1234567890',
                    'sso_issue_date': '01/01/2023',
                    'sso_expiry_date': '01/01/2024',
                    'hospital_name': 'Bangkok Hospital',
                    'insurance_expiry_date_hospital': '01/01/2024',
                    'insurance_company': 'AIA',
                    'insurance_expiry_date_private': '01/01/2024',
                    'insurance_type': 'Social Security',
                    'employer_employee_id': 'EMP001',
                    'employee_reference_id': 'REF001',
                    'outsource_code': 'OS-001',

                    // Employer
                    'employer.employerNameTh': 'บริษัท ตัวอย่าง จำกัด',
                    'employer.employerNameEn': 'EXAMPLE COMPANY CO., LTD.',
                    'employer.employerId': '0123456789012',
                    'employer.employerTaxId': '0123456789012',
                    'employer.employerPhone': '02-123-4567',
                    'employer.employerEmail': 'hr@example.com',
                    'employer.businessType': 'Manufacturing',
                    'employer.regCapital': '1,000,000',
                    'employer.regDate': '01/01/2020',
                    'employer.minimum_wage': '350',
                    'employer.socialSecurityHospital': 'Social Security Hospital',
                    'employer.outsource_re_code': 'OS123',
                    'employer.signerNameTh': 'นายกรรมการ ผู้มีอำนาจ',
                    'employer.signerNameEn': 'MR. DIRECTOR AUTHORIZED',
                    'employer.signer_2_name_th': 'นายกรรมการ สอง',
                    'employer.signer_2_name_en': 'MR. DIRECTOR TWO',
                    'employer.address_th': '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110',
                    'employer.address_en': '123 Sukhumvit Rd, Khlong Toei, Bangkok 10110',
                    'employer.address_th.addrNo': '123',
                    'employer.address_th.addrMoo': '5',
                    'employer.address_th.addrSoi': 'สุขุมวิท 21',
                    'employer.address_th.addrRoad': 'สุขุมวิท',
                    'employer.address_th.addrSubDistrict': 'คลองเตย',
                    'employer.address_th.addrDistrict': 'คลองเตย',
                    'employer.address_th.addrProvince': 'กรุงเทพฯ',
                    'employer.address_th.addrZipCode': '10110',
                    'employer.address_en.addrNoEn': '123',
                    'employer.address_en.addrMooEn': '5',
                    'employer.address_en.addrSoiEn': 'Sukhumvit 21',
                    'employer.address_en.addrRoadEn': 'Sukhumvit',
                    'employer.address_en.addrSubDistrictEn': 'Khlong Toei',
                    'employer.address_en.addrDistrictEn': 'Khlong Toei',
                    'employer.address_en.addrProvinceEn': 'Bangkok',
                    'employer.address_en.addrZipCodeEn': '10110',

                    // Witnesses
                    'witness_1.name_th': 'นายพยาน หนึ่ง',
                    'witness_1.name_en': 'MR. WITNESS ONE',
                    'witness_2.name_th': 'นายพยาน สอง',
                    'witness_2.name_en': 'MR. WITNESS TWO',

                    // Importer
                    'importer.importerNameTh': 'บริษัท นำเข้า จำกัด',
                    'importer.importerNameEn': 'IMPORTER CO., LTD.',
                    'importer.importerId': '0987654321098',
                    'importer.importerLicenseNo': 'LIC-12345',
                    'importer.importerSignerTh': 'นายผู้นำเข้า',
                    'importer.importerSignerEn': 'MR. IMPORTER',
                    'importer.address_th': '456 ถนนพญาไท แขวงวังใหม่ เขตปทุมวัน กรุงเทพฯ 10330',
                    'importer.address_en': '456 Phaya Thai Rd, Wang Mai, Pathum Wan, Bangkok 10330',
                    'importer.address_th.addrNo': '456',
                    'importer.address_th.addrMoo': '',
                    'importer.address_th.addrSoi': '',
                    'importer.address_th.addrRoad': 'พญาไท',
                    'importer.address_th.addrSubDistrict': 'วังใหม่',
                    'importer.address_th.addrDistrict': 'ปทุมวัน',
                    'importer.address_th.addrProvince': 'กรุงเทพฯ',
                    'importer.address_th.addrZipCode': '10330',

                    // Delegate
                    'delegate.delegateNameTh': 'นายตัวแทน บริษัท',
                    'delegate.delegateNameEn': 'MR. DELEGATE COMPANY',
                    'delegate.delegateId': '1234567890123',
                    'delegate.delegateEmployeeId': 'DEL-001',
                    'delegate.delegatePhone': '089-987-6543',
                    'delegate.delegateEmail': 'delegate@example.com',
                    'delegate.address_th': '789 ถนนสีลม แขวงปทุมวัน เขตปทุมวัน กรุงเทพฯ 10330',
                    'delegate.address_en': '789 Silom Rd, Pathum Wan, Pathum Wan, Bangkok 10330',
                    'delegate.address_th.addrNo': '789',
                    'delegate.address_th.addrMoo': '',
                    'delegate.address_th.addrSoi': '',
                    'delegate.address_th.addrRoad': 'สีลม',
                    'delegate.address_th.addrSubDistrict': 'ปทุมวัน',
                    'delegate.address_th.addrDistrict': 'ปทุมวัน',
                    'delegate.address_th.addrProvince': 'กรุงเทพฯ',
                    'delegate.address_th.addrZipCode': '10330',
                },

                // Raw Fields Data
                rawFields: [
                // Employee Personal
                { group: '{{ __('Personal Information') }}', key: 'employeeNameTh', label: '{{ __('Name (TH)') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeNameEn', label: '{{ __('Name (EN)') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeTitleTh', label: '{{ __('Title (TH)') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeTitleEn', label: '{{ __('Title (EN)') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeGender', label: '{{ __('Gender') }}' },
                { group: '{{ __('Personal Information') }}', key: 'age', label: '{{ __('Age') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeDob', label: '{{ __('Date of Birth') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeDob_day', label: '{{ __('Date of Birth (Day)') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeDob_month_en', label: '{{ __('Date of Birth (Month EN)') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeDob_year_ce', label: '{{ __('Date of Birth (Year CE)') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeeNationality', label: '{{ __('Nationality') }}' },
                { group: '{{ __('Personal Information') }}', key: 'father_name', label: '{{ __('Father Name') }}' },
                { group: '{{ __('Personal Information') }}', key: 'mother_name', label: '{{ __('Mother Name') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employeePhone', label: '{{ __('Phone') }}' },
                { group: '{{ __('Personal Information') }}', key: 'email', label: '{{ __('Email') }}' },
                { group: '{{ __('Personal Information') }}', key: 'employee_id_number', label: '{{ __('ID Card No') }}' },
                { group: '{{ __('Personal Information') }}', key: 'tax_id_number', label: '{{ __('Tax ID') }}' },
                { group: '{{ __('Personal Information') }}', key: 'height', label: '{{ __('Height') }} (cm)' },
                { group: '{{ __('Personal Information') }}', key: 'weight', label: '{{ __('Weight') }} (kg)' },
                { group: '{{ __('Personal Information') }}', key: 'bank_name', label: '{{ __('Bank Name') }}' },
                { group: '{{ __('Personal Information') }}', key: 'bank_account_number', label: '{{ __('Bank Account No') }}' },

                // Employee Documents
                { group: '{{ __('Passport & Visa') }}', key: 'employeePassport', label: '{{ __('Passport No') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportIssueDate', label: '{{ __('Passport Issue Date') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportIssueDate_day', label: '{{ __('Passport Issue Date (Day)') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportIssueDate_month_en', label: '{{ __('Passport Issue Date (Month EN)') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportIssueDate_year_ce', label: '{{ __('Passport Issue Date (Year CE)') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportExpiryDate', label: '{{ __('Passport Expiry Date') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportExpiryDate_day', label: '{{ __('Passport Expiry Date (Day)') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportExpiryDate_month_en', label: '{{ __('Passport Expiry Date (Month EN)') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportExpiryDate_year_ce', label: '{{ __('Passport Expiry Date (Year CE)') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passport_issue_place', label: '{{ __('Passport Issue Place') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passportType', label: '{{ __('Passport Type') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'passport_type_cambodia', label: '{{ __('Passport Type (KH)') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'visaType', label: '{{ __('Visa Type') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'visaExpiryDate', label: '{{ __('Visa Expiry Date') }}' },
                { group: '{{ __('Passport & Visa') }}', key: 'visa_issue_place', label: '{{ __('Visa Issue Place') }}' },
                { group: '{{ __('Work Permit & Pink Card') }}', key: 'employeeWorkPermit', label: '{{ __('Work Permit No') }}' },
                { group: '{{ __('Work Permit & Pink Card') }}', key: 'workPermitExpiryDate', label: '{{ __('Work Permit Expiry') }}' },
                { group: '{{ __('Work Permit & Pink Card') }}', key: 'workPermitMOUGroup', label: '{{ __('MOU Group') }}' },
                { group: '{{ __('Work Permit & Pink Card') }}', key: 'workPermitMOUGroupOther', label: '{{ __('Other MOU Group') }}' },
                { group: '{{ __('Work Permit & Pink Card') }}', key: 'pinkCardNo', label: '{{ __('Pink Card No') }}' },
                { group: '{{ __('Work Permit & Pink Card') }}', key: 'name_list_number', label: '{{ __('Name List No') }}' },
                { group: '{{ __('Work Permit & Pink Card') }}', key: 'request_number', label: '{{ __('Request No') }}' },
                { group: '{{ __('Work Permit & Pink Card') }}', key: 'ninetyDayReportDate', label: '{{ __('90-Day Report') }}' },

                // Job & Insurance
                { group: '{{ __('Job Details') }}', key: 'job_title', label: '{{ __('Job Title') }}' },
                { group: '{{ __('Job Details') }}', key: 'employeePosition', label: '{{ __('Position') }}' },
                { group: '{{ __('Job Details') }}', key: 'department', label: '{{ __('Department') }}' },
                { group: '{{ __('Job Details') }}', key: 'job_description', label: '{{ __('Nature of Work / Job Description') }}' },
                { group: '{{ __('Job Details') }}', key: 'nature_of_work', label: '{{ __('Nature of Work') }}' },
                { group: '{{ __('Job Details') }}', key: 'startDate', label: '{{ __('Start Date') }}' },
                { group: '{{ __('Job Details') }}', key: 'workAge', label: '{{ __('Work Age') }}' },
                { group: '{{ __('Job Details') }}', key: 'outsource_code', label: '{{ __('Outsource Code') }}' },
                { group: '{{ __('Insurance') }}', key: 'insurance_type', label: '{{ __('Insurance Type') }}' },
                { group: '{{ __('Insurance') }}', key: 'social_security_number', label: '{{ __('Social Security No') }}' },
                { group: '{{ __('Insurance') }}', key: 'sso_issue_date', label: '{{ __('Social Security Issue Date') }}' },
                { group: '{{ __('Insurance') }}', key: 'sso_expiry_date', label: '{{ __('Social Security Expiry Date') }}' },
                { group: '{{ __('Insurance') }}', key: 'insurance_detail', label: '{{ __('Hospital Rights (SS)') }}' },
                { group: '{{ __('Insurance') }}', key: 'hospital_name', label: '{{ __('Hospital Name') }}' },
                { group: '{{ __('Insurance') }}', key: 'insurance_expiry_date_hospital', label: '{{ __('Hospital Insurance Expiry Date') }}' },
                { group: '{{ __('Insurance') }}', key: 'insurance_company', label: '{{ __('Private Insurance Company') }}' },
                { group: '{{ __('Insurance') }}', key: 'insurance_expiry_date_private', label: '{{ __('Private Insurance Expiry Date') }}' },
                { group: '{{ __('Job Details') }}', key: 'employer_employee_id', label: '{{ __('Internal Emp ID') }}' },
                { group: '{{ __('Job Details') }}', key: 'employee_reference_id', label: '{{ __('Reference ID') }}' },

                // Employer
                { group: '{{ __('Employer Data') }}', key: 'employer.employerNameTh', label: '{{ __('Company Name (TH)') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.employerNameEn', label: '{{ __('Company Name (EN)') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.employerId', label: '{{ __('Employer ID No') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.employerTaxId', label: '{{ __('Employer Tax ID') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.employerPhone', label: '{{ __('Phone') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.employerEmail', label: '{{ __('Email') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.businessType', label: '{{ __('Business Type') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.regCapital', label: '{{ __('Registered Capital') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.regDate', label: '{{ __('Registration Date') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.minimum_wage', label: '{{ __('Minimum Wage') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.socialSecurityHospital', label: '{{ __('Social Security Hospital') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.outsource_re_code', label: '{{ __('Outsource Code') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.signerNameTh', label: '{{ __('Authorized Signatory (Thai)') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.signerNameEn', label: '{{ __('Authorized Signatory (English)') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.signer_2_name_th', label: '{{ __('Authorized Signatory (Thai)') }} 2' },
                { group: '{{ __('Employer Data') }}', key: 'employer.signer_2_name_en', label: '{{ __('Authorized Signatory (English)') }} 2' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th', label: '{{ __('Address Full (TH)') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en', label: '{{ __('Address Full (EN)') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th.addrNo', label: '{{ __('Address (TH) - House No') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th.addrMoo', label: '{{ __('Address (TH) - Moo') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th.addrSoi', label: '{{ __('Address (TH) - Soi') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th.addrRoad', label: '{{ __('Address (TH) - Road') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th.addrSubDistrict', label: '{{ __('Address (TH) - Sub-district') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th.addrDistrict', label: '{{ __('Address (TH) - District') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th.addrProvince', label: '{{ __('Address (TH) - Province') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_th.addrZipCode', label: '{{ __('Address (TH) - Zip Code') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en.addrNoEn', label: '{{ __('Address (EN) - House No') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en.addrMooEn', label: '{{ __('Address (EN) - Moo') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en.addrSoiEn', label: '{{ __('Address (EN) - Soi') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en.addrRoadEn', label: '{{ __('Address (EN) - Road') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en.addrSubDistrictEn', label: '{{ __('Address (EN) - Sub-district') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en.addrDistrictEn', label: '{{ __('Address (EN) - District') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en.addrProvinceEn', label: '{{ __('Address (EN) - Province') }}' },
                { group: '{{ __('Employer Data') }}', key: 'employer.address_en.addrZipCodeEn', label: '{{ __('Address (EN) - Zip Code') }}' },

                // Importer Data
                { group: '{{ __('Importer Data') }}', key: 'importer.importerNameTh', label: '{{ __('Importer Name (TH)') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.importerNameEn', label: '{{ __('Importer Name (EN)') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.importerId', label: '{{ __('Importer ID') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.importerLicenseNo', label: '{{ __('Importer License No') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.importerSignerTh', label: '{{ __('Importer Signer (TH)') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.importerSignerEn', label: '{{ __('Importer Signer (EN)') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th', label: '{{ __('Importer Address Full (TH)') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_en', label: '{{ __('Importer Address Full (EN)') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th.addrNo', label: '{{ __('Importer Address (TH) - House No') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th.addrMoo', label: '{{ __('Importer Address (TH) - Moo') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th.addrSoi', label: '{{ __('Importer Address (TH) - Soi') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th.addrRoad', label: '{{ __('Importer Address (TH) - Road') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th.addrSubDistrict', label: '{{ __('Importer Address (TH) - Sub-district') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th.addrDistrict', label: '{{ __('Importer Address (TH) - District') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th.addrProvince', label: '{{ __('Importer Address (TH) - Province') }}' },
                { group: '{{ __('Importer Data') }}', key: 'importer.address_th.addrZipCode', label: '{{ __('Importer Address (TH) - Zip Code') }}' },

                // Delegate Data
                { group: '{{ __('Delegate Data') }}', key: 'delegate.delegateNameTh', label: '{{ __('Delegate Name (TH)') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.delegateNameEn', label: '{{ __('Delegate Name (EN)') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.delegateId', label: '{{ __('Delegate ID') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.delegateEmployeeId', label: '{{ __('Delegate Employee ID') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.delegatePhone', label: '{{ __('Delegate Phone') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.delegateEmail', label: '{{ __('Delegate Email') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th', label: '{{ __('Delegate Address Full (TH)') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_en', label: '{{ __('Delegate Address Full (EN)') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th.addrNo', label: '{{ __('Delegate Address (TH) - House No') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th.addrMoo', label: '{{ __('Delegate Address (TH) - Moo') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th.addrSoi', label: '{{ __('Delegate Address (TH) - Soi') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th.addrRoad', label: '{{ __('Delegate Address (TH) - Road') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th.addrSubDistrict', label: '{{ __('Delegate Address (TH) - Sub-district') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th.addrDistrict', label: '{{ __('Delegate Address (TH) - District') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th.addrProvince', label: '{{ __('Delegate Address (TH) - Province') }}' },
                { group: '{{ __('Delegate Data') }}', key: 'delegate.address_th.addrZipCode', label: '{{ __('Delegate Address (TH) - Zip Code') }}' },

                // Witnesses
                { group: '{{ __('Global Witnesses') }}', key: 'witness_1.name_th', label: '{{ __('Witness 1 Name (TH)') }}' },
                { group: '{{ __('Global Witnesses') }}', key: 'witness_1.name_en', label: '{{ __('Witness 1 Name (EN)') }}' },
                { group: '{{ __('Global Witnesses') }}', key: 'witness_2.name_th', label: '{{ __('Witness 2 Name (TH)') }}' },
                { group: '{{ __('Global Witnesses') }}', key: 'witness_2.name_en', label: '{{ __('Witness 2 Name (EN)') }}' },
                { group: '{{ __('Global Witnesses') }}', key: 'witness_3.name_th', label: '{{ __('Witness 3 Name (TH)') }}' },
                { group: '{{ __('Global Witnesses') }}', key: 'witness_3.name_en', label: '{{ __('Witness 3 Name (EN)') }}' },
                { group: '{{ __('Global Witnesses') }}', key: 'witness_4.name_th', label: '{{ __('Witness 4 Name (TH)') }}' },
                { group: '{{ __('Global Witnesses') }}', key: 'witness_4.name_en', label: '{{ __('Witness 4 Name (EN)') }}' },
            ],

            get filteredGroups() {
                const q = this.searchQuery.toLowerCase();
                const filtered = this.rawFields.filter(f =>
                    f.label.toLowerCase().includes(q) || f.group.toLowerCase().includes(q)
                );

                // Group by 'group' key
                return filtered.reduce((acc, field) => {
                    if (!acc[field.group]) acc[field.group] = [];
                    acc[field.group].push(field);
                    return acc;
                }, {});
            },

            async init() {
                // Handle null or undefined items
                if (!this.items) {
                    this.items = [];
                }

                // Initial Meta Data check
                if (!this.metaData) {
                    this.metaData = { auto_prefix_titles: false, employees_per_page: 1 };
                }
                if (!this.metaData.employees_per_page) {
                    this.metaData.employees_per_page = 1;
                }

                // Ensure page numbers are integers for correct comparison
                // Also handle potential stringified JSON if DB returns string
                if (typeof this.items === 'string') {
                    try {
                        this.items = JSON.parse(this.items);
                    } catch(e) {
                        this.items = [];
                    }
                }

                // Defensive map: ensure item exists and has a page
                this.items = this.items.map(item => ({
                    ...item,
                    page: item.page ? parseInt(item.page) : 1
                }));

                const url = '{{ route("admin.pdf-templates.file", $template) }}';
                try {
                    const loadingTask = pdfjsLib.getDocument(url);
                    _pdfDoc = await loadingTask.promise;
                    this.totalPages = _pdfDoc.numPages;
                    await this.renderAllPages();
                } catch (error) {
                    console.error('Error loading PDF:', error);
                    showToast('Failed to load PDF.', 'danger');
                }
            },

            async renderAllPages() {
                await this.$nextTick();
                for (let i = 1; i <= this.totalPages; i++) {
                    await this.renderPage(i);
                }
            },

            async renderPage(num) {
                if (!_pdfDoc) return;
                const page = await _pdfDoc.getPage(num);
                const viewport = page.getViewport({ scale: this.scale });

                this.pageDimensions[num] = { width: viewport.width, height: viewport.height };
                await this.$nextTick();

                const canvas = document.getElementById('canvas-page-' + num);
                if (canvas) {
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    await page.render({ canvasContext: context, viewport: viewport }).promise;
                }
            },

            scrollToPage(pageNum) {
                const el = document.getElementById('page-container-' + pageNum);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },

            dragStart(event, data) {
                event.dataTransfer.setData('text/plain', JSON.stringify(data));
                event.dataTransfer.effectAllowed = 'copy';
            },

            drop(event, pageNum) {
                const dims = this.pageDimensions[pageNum];
                if (!dims) return;

                const rect = event.target.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;

                // Defaults
                let wPct = 15;
                let hPct = 3;

                try {
                    const data = JSON.parse(event.dataTransfer.getData('text/plain'));

                    if (data.type === 'signature') {
                        wPct = 10;
                        hPct = 6; // Signatures are taller
                    } else if (data.type === 'stamp') {
                        wPct = 8;
                        hPct = 8; // Stamps are usually square
                    }

                    this.items.push({
                        type: data.type,
                        key: data.key || null,
                        label: data.label,
                        text: data.type === 'static' ? 'Double click to edit' : null,
                        x: (x / dims.width) * 100,
                        y: (y / dims.height) * 100,
                        w: wPct,
                        h: hPct,
                        page: pageNum,
                        fontSize: 12,
                        autoFit: true, // Default to true
                        align: 'left', // Default align
                        signatureGroup: data.type === 'signature' ? 'employee' : null,
                        employeeIndex: (data.type !== 'static' && data.type !== 'stamp') ? this.currentEmployeeSlot : null
                    });

                    // Auto-open settings for new static text
                    if (data.type === 'static') {
                        this.openSettings(this.items.length - 1);
                    }

                } catch (e) {
                    console.error('Drop error', e);
                }
            },

            startMove(event, index, pageNum) {
                // Prevent drag if clicking resize handle
                if (event.target.classList.contains('resize-handle')) return;

                const item = this.items[index];
                const dims = this.pageDimensions[pageNum];
                const startX = event.clientX;
                const startY = event.clientY;
                const startLeft = (item.x / 100) * dims.width;
                const startTop = (item.y / 100) * dims.height;

                const onMouseMove = (e) => {
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;
                    let newLeft = startLeft + dx;
                    let newTop = startTop + dy;

                    // Boundaries
                    newLeft = Math.max(0, Math.min(newLeft, dims.width - ((item.w/100)*dims.width)));
                    newTop = Math.max(0, Math.min(newTop, dims.height - ((item.h/100)*dims.height)));

                    item.x = (newLeft / dims.width) * 100;
                    item.y = (newTop / dims.height) * 100;
                };

                const onMouseUp = () => {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            },

            startResize(event, index, pageNum, direction) {
                const item = this.items[index];
                const dims = this.pageDimensions[pageNum];
                const startX = event.clientX;
                const startY = event.clientY;

                // Current Pixel Values
                const currentLeftPx = (item.x / 100) * dims.width;
                const currentTopPx = (item.y / 100) * dims.height;
                const currentWidthPx = (item.w / 100) * dims.width;
                const currentHeightPx = (item.h / 100) * dims.height;

                const onMouseMove = (e) => {
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;

                    let newLeft = currentLeftPx;
                    let newTop = currentTopPx;
                    let newWidth = currentWidthPx;
                    let newHeight = currentHeightPx;

                    // Handle different directions
                    if (direction.includes('e')) {
                        newWidth = Math.max(20, currentWidthPx + dx);
                    }
                    if (direction.includes('s')) {
                        newHeight = Math.max(10, currentHeightPx + dy);
                    }
                    if (direction.includes('w')) {
                        // For West, we change Left AND Width
                        // If we move mouse left (-dx), width increases, left decreases
                        const proposedWidth = currentWidthPx - dx;
                        if (proposedWidth >= 20) {
                            newWidth = proposedWidth;
                            newLeft = currentLeftPx + dx;
                        }
                    }
                    if (direction.includes('n')) {
                        // For North, we change Top AND Height
                        // If we move mouse up (-dy), height increases, top decreases
                        const proposedHeight = currentHeightPx - dy;
                        if (proposedHeight >= 10) {
                            newHeight = proposedHeight;
                            newTop = currentTopPx + dy;
                        }
                    }

                    // Normalize to percentages
                    item.x = (newLeft / dims.width) * 100;
                    item.y = (newTop / dims.height) * 100;
                    item.w = (newWidth / dims.width) * 100;
                    item.h = (newHeight / dims.height) * 100;
                };

                const onMouseUp = () => {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            },

            deleteItem(index) {
                Swal.fire({
                    title: 'Remove Field?',
                    text: 'Are you sure you want to remove this field from the template?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.items.splice(index, 1);
                        this.editingIndex = null;
                        Swal.fire('Removed!', 'The field has been removed.', 'success');
                    }
                });
            },

            openSettings(index) {
                this.editingIndex = index;
                new bootstrap.Modal(document.getElementById('itemSettingsModal')).show();
            },

            openTemplateSettings() {
                new bootstrap.Modal(document.getElementById('templateSettingsModal')).show();
            },

            getSignatureLabel(group) {
                const labels = {
                    'employee': '(Employee)',
                    'employer': '(Signer 1)',
                    'employer_2': '(Signer 2)',
                    'delegate': '(Delegate)',
                    'witness_1': '(Witness 1)',
                    'witness_2': '(Witness 2)',
                    'witness_3': '(Witness 3)',
                    'witness_4': '(Witness 4)'
                };
                return labels[group] || '(Unknown)';
            },

            getPreviewText(item) {
                if (item.type === 'static') return item.text || 'Static Text';
                return this.dummyData[item.key] || item.label;
            },

            getFontSize(item, pageNum) {
                if (item.type === 'signature' || item.type === 'stamp') return '12px';

                if (item.autoFit) {
                    const dims = this.pageDimensions[pageNum];
                    if (!dims) return '12px';
                    const boxH = (item.h / 100) * dims.height;
                    const sizePx = boxH * 0.7;
                    return `${sizePx}px`;
                } else {
                    return `${item.fontSize || 12}pt`;
                }
            },

            async saveMapping() {
                this.isSaving = true;
                try {
                    // Ensure items is a clean array
                    const itemsToSave = JSON.parse(JSON.stringify(this.items));
                    const metaDataToSave = JSON.parse(JSON.stringify(this.metaData));

                    const response = await fetch('{{ route("admin.pdf-templates.update", $template) }}', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            name: this.templateName,
                            field_mapping: itemsToSave,
                            meta_data: metaDataToSave
                        })
                    });

                    if (response.ok) {
                        showToast('Template saved successfully!', 'success');
                    } else {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Save failed');
                    }
                } catch (error) {
                    showToast('Error saving template: ' + error.message, 'danger');
                    console.error(error);
                } finally {
                    this.isSaving = false;
                }
            }
        }; // Return object end
        });
    });
</script>

<style>
    [x-cloak] { display: none !important; }
    .cursor-grab { cursor: grab; }
    .cursor-move { cursor: move; }
    .resize-handle { display: none; }
    .group:hover .resize-handle { display: block; }

    @font-face {
        font-family: 'THSarabunNew';
        src: url('/fonts/THSarabunNew.ttf') format('truetype');
    }
    .font-sarabun { font-family: 'THSarabunNew', sans-serif; }
</style>
@endpush
@endsection
