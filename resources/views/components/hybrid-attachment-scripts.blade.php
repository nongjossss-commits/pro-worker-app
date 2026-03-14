<script>
function hybridAttachmentManager(config = {}) {
    return {
        // --- Core States ---
        basket: { existing_employees: [], external_employees: [], new_employees: [], files: [] },
        isUploading: false,
        uploadProgress: 0,
        filesToUploadCount: 0,
        filesUploadedCount: 0,
        modalInstances: { existing: null, new: null },
        availableEmployees: [],
        selectedEmployeeIds: [],
        isLoading: false,
        searchQuery: '',
        contextEmployerId: null,
        isContextAdminCreate: false,

        // V2.5-S16: Global Search State (Updated for V2.5-S17)
        isGlobalSearch: false,

        // --- V2.5-S3: Expanded Default Form State ---
        defaultNewEmployeeForm: {
            employeeTitleTh: 'นาย', employeeNameTh: '', employeeTitleEn: 'Mr.',
            employeeNameEn: '', father_name: '', mother_name: '',
            employeeGender: 'ชาย', employeeDob: '', employeeAge: '',
            employeePhone: '', employeeNationality: '', passportType: '',
            passport_type_cambodia: '', employeePassport: '', passport_issue_date: '',
            passportExpiryDate: '', pinkCardNo: '', visaType: '', visaExpiryDate: '',
            job_title: '', job_description: '', startDate: '', employeeWorkPermit: '',
            workPermitExpiryDate: '', ninetyDayReportDate: '', workPermitMOUGroup: '',
            workPermitMOUGroupOther: '', name_list_number: '', request_number: '',
            employee_id_number: '', tax_id_number: '', employer_employee_id: '',
            employee_reference_id: '', insurance_type: '', social_security_number: '',
            insurance_detail_social: '', insurance_document_path_social: null,
            insurance_detail_hospital: '', insurance_expiry_date_hospital: '',
            insurance_document_path_hospital: null, insurance_detail_private: '',
            insurance_expiry_date_private: '', insurance_document_path_private: null,
            employeeEmail: '', employeePassword: '', employeePhoto: null,
            employee_doc_1: null, employee_doc_2: null, employee_doc_3: null,
            employee_doc_4: null, employee_doc_5: null, employee_doc_6: null,
            employee_doc_7: null, employee_doc_8: null, employee_doc_9: null,
            employee_doc_10: null, employee_doc_11: null, employee_doc_12: null,
            other_doc_1_desc: '', other_doc_2_desc: '', other_doc_3_desc: '', other_doc_4_desc: '',
            // Internal state for photo preview
            photo_preview_url: null
        },
        newEmployeeForm: {},
        uploadStatus: {}, // Holds status for each file field

        // --- Component Initialization ---
        init() {
            this.contextEmployerId = config.employerId || null;
            this.isContextAdminCreate = config.is_admin_create_view || false;

            this.$nextTick(() => {
                const existingModalEl = document.getElementById('existingEmployeeModal');
                const newModalEl = document.getElementById('newEmployeeModal');
                if (existingModalEl) this.modalInstances.existing = new bootstrap.Modal(existingModalEl);
                if (newModalEl) this.modalInstances.new = new bootstrap.Modal(newModalEl);
            });
            this.resetNewEmployeeForm(); // Sets up form and uploadStatus
            this.restoreOldInput();

            // --- V2.5-S3: Setup Watchers for Form Logic ---
            this.$watch('newEmployeeForm.employeeTitleTh', (newVal) => this.syncTitleAndGender('th', newVal));
            this.$watch('newEmployeeForm.employeeTitleEn', (newVal) => this.syncTitleAndGender('en', newVal));
            this.$watch('newEmployeeForm.employeeDob', () => this.calculateAge());

            // V2.5-S16: Watch search query for Global Search
            this.$watch('searchQuery', (value) => {
                if (this.isGlobalSearch) {
                    // Debounce handled by x-model in the view, but here we just call the fetcher
                    // However, x-model.debounce delays the variable update, so this runs after delay.
                    if (value.length > 1) { // Min 2 chars for global search
                        this.fetchEmployees();
                    } else {
                        this.availableEmployees = [];
                    }
                }
            });

            // Watch global search toggle
            this.$watch('isGlobalSearch', (value) => {
                this.searchQuery = '';
                this.availableEmployees = [];
                if (!value && this.contextEmployerId) {
                    // If switching back to local, re-fetch local list
                    this.fetchEmployees();
                }
            });

            if (this.isContextAdminCreate && this.contextEmployerId) {
                this.fetchEmployees();
            }

            // Check for preselected IDs from session (injected via config)
            if (config.preselectedEmployeeIds && config.preselectedEmployeeIds.length > 0) {
                this.selectedEmployeeIds = config.preselectedEmployeeIds;
                this.fetchPreselectedEmployees(true); // true = addToExisting
            }

            // Check for preselected External IDs from session
            if (config.preselectedExternalEmployeeIds && config.preselectedExternalEmployeeIds.length > 0) {
                // We handle these separately to ensure they go to external basket
                this.fetchPreselectedExternalEmployees(config.preselectedExternalEmployeeIds);
            }
        },

        // --- V2.5-S3: New Form Logic Functions ---
        syncTitleAndGender(source, newVal) {
            const thToEnMap = { 'นาย': 'Mr.', 'นางสาว': 'Miss', 'นาง': 'Mrs.' };
            const enToThMap = { 'Mr.': 'นาย', 'Miss': 'นางสาว', 'Mrs.': 'นาง' };

            if (source === 'th') {
                this.newEmployeeForm.employeeTitleEn = thToEnMap[newVal] || 'Mr.';
            } else { // source === 'en'
                this.newEmployeeForm.employeeTitleTh = enToThMap[newVal] || 'นาย';
            }
            // Update Gender based on Thai title
            const thaiTitle = this.newEmployeeForm.employeeTitleTh;
            this.newEmployeeForm.employeeGender = (thaiTitle === 'นาย') ? 'ชาย' : 'หญิง';
        },

        calculateAge() {
            const dob = new Date(this.newEmployeeForm.employeeDob);
            if (!isNaN(dob.getTime())) {
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                this.newEmployeeForm.employeeAge = age > 0 ? age : 0;
            } else {
                this.newEmployeeForm.employeeAge = '';
            }
        },

        restoreOldInput() {
            const oldAttachments = @json(old('attachments'));
            if (oldAttachments) {
                this.basket.files = oldAttachments.files || [];
                this.basket.existing_employees = oldAttachments.existing_employees || [];
                // V2.5-S17: Restore external employees
                this.basket.external_employees = oldAttachments.external_employees || [];

                if (oldAttachments.new_employees) {
                    this.basket.new_employees = oldAttachments.new_employees.map(item => {
                        try {
                            // If it's a string, parse it. If it's already an object, use it.
                            return typeof item === 'string' ? JSON.parse(item) : item;
                        } catch (e) {
                            console.error('Failed to parse old new_employee data:', item, e);
                            return null;
                        }
                    }).filter(Boolean); // Filter out any nulls from parsing errors
                }
            }
        },

        totalItemsCount() {
            return (this.basket.existing_employees?.length || 0) +
                   (this.basket.external_employees?.length || 0) +
                   (this.basket.new_employees?.length || 0) +
                   (this.basket.files?.length || 0);
        },

        formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        removeConfirm(type, index, itemName) {
            Swal.fire({
                title: `ลบ ${itemName}?`,
                text: "{{ __('คุณต้องการนำรายการนี้ออกจากสิ่งที่แนบมาใช่หรือไม่?') }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "{{ __('ใช่, ลบออก') }}",
                cancelButtonText: "{{ __('ยกเลิก') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    if (this.basket[type] && this.basket[type][index]) {
                        this.basket[type].splice(index, 1);
                        Swal.fire({
                           toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                           icon: 'success', title: '{{ __('ลบรายการสำเร็จ') }}'
                        });
                    }
                }
            });
        },

        // --- V2.5-FIX: Fetch Preselected Employees by IDs ---
        async fetchPreselectedEmployees(forceToExisting = false) {
            if (!this.selectedEmployeeIds || this.selectedEmployeeIds.length === 0) {
                return;
            }

            this.isLoading = true;
            try {
                const params = new URLSearchParams();
                // V2.5-S18-FIX: Handle both simple IDs and {id, url} objects
                const idMap = new Map(this.selectedEmployeeIds.map(item => [
                    (typeof item === 'object' ? item.id : item),
                    (typeof item === 'object' ? item.url : null)
                ]));
                idMap.forEach((_, id) => params.append('ids[]', id));


                const response = await fetch(`{{ route('api-web.employer.employees.index') }}?${params.toString()}`);
                if (!response.ok) throw new Error('Network response was not ok');

                const employees = await response.json();

                // Add them to basket immediately
                employees.forEach(emp => {
                     // Check if already in either basket
                     const inExisting = this.basket.existing_employees.some(e => e.id == emp.id);
                     const inExternal = this.basket.external_employees.some(e => e.id == emp.id);

                     if (!inExisting && !inExternal) {
                        const sourceUrl = idMap.get(emp.id); // V2.5-S18-FIX: Get the URL from our map
                        const employeeWithUrl = { ...emp, url: sourceUrl }; // Attach the URL

                        // If forceToExisting is true (from matching employer logic), put in existing
                        if (forceToExisting) {
                            this.basket.existing_employees.push(employeeWithUrl);
                        } else {
                            // Otherwise check context
                            if (this.contextEmployerId && emp.employer_id == this.contextEmployerId) {
                                this.basket.existing_employees.push(employeeWithUrl);
                            } else {
                                this.basket.external_employees.push(employeeWithUrl);
                            }
                        }
                    }
                });

                // Clear selection after adding
                this.selectedEmployeeIds = [];

            } catch (error) {
                console.error('Failed to fetch preselected employees:', error);
                Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลลูกจ้างที่เลือกไว้ได้', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        async fetchPreselectedExternalEmployees(ids) {
            if (!ids || ids.length === 0) return;

            this.isLoading = true;
            try {
                const params = new URLSearchParams();
                ids.forEach(id => params.append('ids[]', id));

                const response = await fetch(`{{ route('api-web.employer.employees.index') }}?${params.toString()}`);
                if (!response.ok) throw new Error('Network response was not ok');

                const employees = await response.json();

                employees.forEach(emp => {
                    const inExisting = this.basket.existing_employees.some(e => e.id == emp.id);
                    const inExternal = this.basket.external_employees.some(e => e.id == emp.id);

                    if (!inExisting && !inExternal) {
                        this.basket.external_employees.push(emp);
                    }
                });
            } catch (error) {
                console.error('Failed to fetch external employees:', error);
            } finally {
                this.isLoading = false;
            }
        },

        async fetchEmployees() {
            // V2.5-S16: Allow fetch if Global Search is active, even without contextEmployerId
            if (!this.contextEmployerId && !this.isGlobalSearch) return;

            this.isLoading = true;
            try {
                // Determine which parameter to send based on context
                const params = new URLSearchParams();

                if (this.isGlobalSearch) {
                    // Global Search Mode
                    params.append('global_search', '1');
                    params.append('search', this.searchQuery);
                } else {
                    // Standard Mode
                    if (this.isContextAdminCreate) {
                        params.append('employer_user_id', this.contextEmployerId);
                    } else {
                        params.append('employer_id', this.contextEmployerId);
                    }
                }

                const response = await fetch(`{{ route('api-web.employer.employees.index') }}?${params.toString()}`);
                if (!response.ok) throw new Error('Network response was not ok');
                this.availableEmployees = await response.json();
            } catch (error) {
                console.error('Failed to fetch employees:', error);
                this.availableEmployees = [];
                // Only show error if not global search (where empty is common on start)
                if (!this.isGlobalSearch) {
                    Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลลูกจ้างได้', 'error');
                }
            } finally {
                this.isLoading = false;
            }
        },

        // Open Modal for Existing Employees (Affiliated)
        openExistingEmployeeModal() {
             if (this.isContextAdminCreate && !this.contextEmployerId) {
                Swal.fire({ icon: 'warning', title: '{{ __('กรุณาเลือกนายจ้าง') }}', text: '{{ __('โปรดเลือกนายจ้างหลักสำหรับตั๋วก่อนทำการแนบลูกจ้าง') }}' });
                return;
            }

            // Switch to Local Mode
            this.isGlobalSearch = false;
            this.searchQuery = '';

            this.fetchEmployees();
            if (this.modalInstances.existing) this.modalInstances.existing.show();
        },

        // V2.5-S17: Open Modal for Global Search (External Employees)
        openGlobalEmployeeSearch() {
            this.isGlobalSearch = true;
            this.searchQuery = ''; // Clear query
            this.availableEmployees = []; // Clear list until typed
            if (this.modalInstances.existing) this.modalInstances.existing.show();
        },

        handleEmployerChange(newEmployerId) {
            this.contextEmployerId = newEmployerId;
            // V2.5-FIX: Do NOT clear basket.existing_employees if they were pre-selected or added.
            // Only clear availableEmployees (the list in the modal).
            this.availableEmployees = [];

            if (newEmployerId && !this.isGlobalSearch) {
                this.fetchEmployees(); // Pre-fetch for the new employer
            }
        },

        filteredEmployees() {
            // V2.5-S16: For Global Search, filtering is done server-side.
            if (this.isGlobalSearch) {
                return this.availableEmployees;
            }

            // Local filtering for specific employer
            if (!this.searchQuery) return this.availableEmployees;
            const query = this.searchQuery.toLowerCase();
            return this.availableEmployees.filter(emp =>
                (emp.employeeNameTh && emp.employeeNameTh.toLowerCase().includes(query)) ||
                (emp.employeeNameEn && emp.employeeNameEn.toLowerCase().includes(query)) ||
                (emp.employeePassport && emp.employeePassport.toLowerCase().includes(query))
            );
        },

        confirmSelection() {
            this.selectedEmployeeIds.forEach(id => {
                // V2.5-FIX: Use loose comparison for ID finding (String vs Int)
                const employee = this.availableEmployees.find(e => e.id == id);
                if (!employee) return;

                // V2.5-S17: Determine destination basket (Existing vs External) based on Affiliation
                // We use weak comparison for IDs just in case
                const isAffiliated = this.contextEmployerId && (employee.employer_id == this.contextEmployerId);
                const targetBasket = isAffiliated ? this.basket.existing_employees : this.basket.external_employees;

                // Prevent duplicates in the target basket
                if (!targetBasket.some(e => e.id == employee.id)) {
                    // Also check if it's in the OTHER basket (e.g. moved?) - Optional, but let's allow it to move if logic dictates
                    // If it was in External but now is Affiliated (e.g. employer changed context?), we should probably handle that,
                    // but for now, simple add is enough.

                    // Deep clone
                    const employeeClone = JSON.parse(JSON.stringify(employee));
                    targetBasket.push(employeeClone);
                }
            });
            this.selectedEmployeeIds = []; // Reset selection
            this.searchQuery = ''; // Reset search
            if (this.modalInstances.existing) this.modalInstances.existing.hide();
        },

        resetNewEmployeeForm() {
            this.newEmployeeForm = JSON.parse(JSON.stringify(this.defaultNewEmployeeForm));
            this.uploadStatus = {};
            // Initialize upload status for every field that could be a file
            Object.keys(this.defaultNewEmployeeForm).forEach(key => {
                if (key.endsWith('_path') || key.startsWith('employee_doc_') || key === 'employeePhoto') {
                     this.uploadStatus[key] = { loading: false, error: null, url: null };
                }
            });
            const formElement = document.getElementById('newEmployeeActualForm');
            if (formElement) formElement.reset();
        },

        openNewEmployeeModal() {
            if (this.isContextAdminCreate && !this.contextEmployerId) {
                Swal.fire({ icon: 'warning', title: '{{ __('กรุณาเลือกนายจ้าง') }}', text: '{{ __('โปรดเลือกนายจ้างก่อนทำการเพิ่มลูกจ้างใหม่') }}' });
                return;
            }
            this.resetNewEmployeeForm();
            if (this.modalInstances.new) this.modalInstances.new.show();
        },

        async handleFileUpload(event, fieldName) {
            const input = event.target;

            // Note: Scanner Interceptor logic is now handled globally via inline onchange="window.interceptFileSelect(event)"
            // If execution reaches here, it means the file is either not intercepted or has been processed by scanner.
            // We just need to ensure we don't process the 'scannerSource' flag logic here as it's handled by the interceptor.

            const file = event.target.files[0];
            if (!file) return;

            // Handle image preview locally before upload
            if (fieldName === 'employeePhoto' && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.newEmployeeForm.photo_preview_url = e.target.result;
                };
                reader.readAsDataURL(file);
            }

            if (!this.uploadStatus[fieldName]) {
                 this.uploadStatus[fieldName] = { loading: false, error: null, url: null };
            }
            const status = this.uploadStatus[fieldName];
            status.loading = true;
            status.error = null;

            const formData = new FormData();
            formData.append('file', file);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch('{{ route('api-web.temp_upload.store') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Upload failed');

                this.newEmployeeForm[fieldName] = data.path;
                status.url = data.url;

                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                Toast.fire({ icon: 'success', title: `'${file.name}' อัปโหลดสำเร็จ` });

            } catch (error) {
                console.error(`Upload error for ${fieldName}:`, error);
                status.error = error.message;
                this.newEmployeeForm[fieldName] = null;
                event.target.value = null; // Clear the input
                Swal.fire({ icon: 'error', title: '{{ __('อัปโหลดล้มเหลว') }}', text: error.message });
            } finally {
                status.loading = false;
            }
        },

        submitNewEmployeeForm() {
            // Since validation is now optional, we just check if a name is present for display purposes.
            // If not, we'll use a placeholder.
             const displayName = this.newEmployeeForm.employeeNameTh?.trim() || 'ลูกจ้างใหม่ (ไม่มีชื่อ)';

            // Create a temporary ID for client-side removal
            const tempId = `new_${Date.now()}`;

            // Add the form data to the basket
            this.basket.new_employees.push({
                ...this.newEmployeeForm,
                temp_id: tempId, // Add temp id
                display_name: displayName // Add display name
            });

            // Reset the form for the next entry
            this.resetNewEmployeeForm();

            // Close the modal
            if (this.modalInstances.new) this.modalInstances.new.hide();

            // Show success message
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            Toast.fire({ icon: 'success', title: `เพิ่ม '${displayName}' ในรายการแนบแล้ว` });
        },

        triggerFileInput() {
            this.$refs.generalFileInput.click();
        },

        triggerScanner() {
            // Target the general file input ID if possible, but since it's inside Alpine x-ref, we can pass a dummy or handle the event manually.
            // However, the scanner writes to an ID. Let's give the file input an ID.
            // Or better, let's create a temporary input ID for the scanner to write to, and then we manually trigger handleGeneralFileUpload
            // The scanner component writes to `input.files`.
            // Let's assume the view has given an ID to x-ref="generalFileInput" or we can pass a unique ID.
            // For now, let's dispatch the event with a known ID that we will add to the input in the view.
            document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'general-attachment-input' } }));
        },

        async handleGeneralFileUpload(event) {
            const input = event.target;

            // Note: Scanner Interceptor logic is now handled globally via inline onchange="window.interceptFileSelect(event)"

            const files = event.target.files;
            if (files.length === 0) return;

            this.isUploading = true;
            this.uploadProgress = 0;
            this.filesToUploadCount = files.length;
            this.filesUploadedCount = 0;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const formData = new FormData();
                formData.append('file', file);

                try {
                    const response = await fetch('{{ route('api-web.temp_upload.store') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: formData
                    });

                    const data = await response.json();
                    if (!response.ok) {
                         throw new Error(data.error || `Failed to upload ${file.name}`);
                    }

                    // Add to basket
                    this.basket.files.push({
                        name: file.name,
                        size: file.size,
                        path: data.path, // The path returned by the server
                        url: data.url
                    });

                } catch (error) {
                    console.error('File upload error:', error);
                    Swal.fire('Upload Error', error.message, 'error');
                } finally {
                    this.filesUploadedCount++;
                    this.uploadProgress = Math.round((this.filesUploadedCount / this.filesToUploadCount) * 100);
                }
            }

            // Reset file input for next time
            event.target.value = '';
            this.isUploading = false;
        },

        // --- Drag & Drop Handler (V2.5-S18 & V2.5-S20) ---
        handleDrop(e) {
            const rawData = e.dataTransfer.getData('application/json');
            if (!rawData) return;

            let data;
            try {
                data = JSON.parse(rawData);
            } catch (err) {
                console.error('Invalid drop data', err);
                return;
            }

            const messageBox = document.getElementById('message');

            // 1. SPECIAL CASE: Notification Card dropped into message box
            if (data.type === 'notification' && data.render_as === 'employee_card' && messageBox) {
                // Create a special formatted string with the rich payload
                const textToAppend = `[[--NOTIFICATION_CARD--]]${JSON.stringify(data)}[[--/NOTIFICATION_CARD--]]`;

                // Append it to the message box
                const currentVal = messageBox.value;
                messageBox.value = currentVal + (currentVal ? '\n' : '') + textToAppend;
                messageBox.dispatchEvent(new Event('input')); // for auto-resize
                return; // Done
            }

            // 2. Handle Employee Drop (from anywhere)
            if (data.type === 'employee') {
                // PART A: Always add a Rich Card to the Chat Message Box
                // This payload structure mimics the Notification Card structure which the PHP controller/view already knows how to render.
                // We map our standard employee drag data to the expected notification format.
                const richCardPayload = {
                    notification_title_th: 'ข้อมูลจาก: ' + (data.source_menu || 'ระบบ'),
                    employee_id: data.id,
                    employee_name_th: data.title_th || data.title,
                    employee_name_en: data.title_en || '',
                    employee_nationality: data.nationality || '',
                    employee_photo_url: data.photo_url || '',
                    employer_name_th: data.employer_name || '',
                    url: data.url // The source URL for highlighting
                };

                const textToAppend = `[[--NOTIFICATION_CARD--]]${JSON.stringify(richCardPayload)}[[--/NOTIFICATION_CARD--]]`;

                // Append it to the message box if it exists
                if (messageBox) {
                    const currentVal = messageBox.value;
                    messageBox.value = currentVal + (currentVal ? '\n' : '') + textToAppend;
                    messageBox.dispatchEvent(new Event('input'));
                }

                // PART B: Add to Attachment Basket (Standard Logic)
                const employeeId = data.id;
                const sourceUrl = data.url || null;

                const inExisting = this.basket.existing_employees.some(emp => emp.id == employeeId);
                const inExternal = this.basket.external_employees.some(emp => emp.id == employeeId);

                if (!inExisting && !inExternal) {
                     this.selectedEmployeeIds = [{ id: employeeId, url: sourceUrl }];
                     this.fetchPreselectedEmployees();
                } else {
                    // Optional: Toast saying "Already attached" but we still allow the card paste above
                    // Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'info', title: '{{ __('ลูกจ้างนี้ถูกเลือกแล้ว') }}' });
                }

                return;
            }

            // 3. Handle Other Types -> Append as a simple link to Message
            if (messageBox) {
                let textToAppend = '';
                if (data.type === 'employer') {
                    textToAppend = `[นายจ้าง: ${data.title}](${data.url}) `;
                } else if (data.type === 'ticket') {
                     textToAppend = `[ตั๋วงาน #${data.id}: ${data.title}](${data.url}) `;
                } else if (data.type === 'notification') { // This now only catches non-employee (simple_text) notifications
                     textToAppend = `[การแจ้งเตือน: ${data.title}](${data.url}) `;
                } else if (data.type === 'file' && data.url) {
                    textToAppend = `[ไฟล์: ${data.title}](${data.url}) `;
                } else if (data.type === 'new_employee_draft') {
                    textToAppend = `[ลูกจ้างใหม่ (ร่าง): ${data.title}] `;
                } else {
                    // Default / Link / Other draggable items without a specific handler
                    if (data.url && data.title) {
                        textToAppend = `[${data.title}](${data.url}) `;
                    } else {
                        textToAppend = `${data.title || 'Attached Item'} `;
                    }
                }

                const currentVal = messageBox.value;
                messageBox.value = currentVal + (currentVal ? '\n' : '') + textToAppend;
                messageBox.dispatchEvent(new Event('input'));
            }
        },
    }
}
</script>
