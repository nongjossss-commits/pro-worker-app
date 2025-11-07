{{-- resources/views/components/hybrid-attachment-scripts.blade.php --}}
<script>
// V2.4-S13: Unified Alpine.js component for Hybrid Attachments (Create & Reply)
// Now with Dynamic Context for Admin/Staff usage.
function hybridAttachmentManager(config = {}) {
    return {
        // --- Core Basket State (Persistent) ---
        basket: {
            existing_employees: [],
            new_employees: [],
            files: [],
        },

        // --- General Upload State ---
        isUploading: false,
        uploadProgress: 0,
        filesToUploadCount: 0,
        filesUploadedCount: 0,

        // --- Modal Instances (Bootstrap) ---
        modalInstances: {
            existing: null,
            new: null
        },

        // --- Existing/New Employee States (Transient) ---
        availableEmployees: [],
        selectedEmployeeIds: [],
        isLoading: false,
        searchQuery: '',

        // --- V2.4-S13: New Context State ---
        // This holds the employer ID for the current context (e.g., the ticket owner).
        // It's `null` for an employer user (API uses tenancy) but set for Admin/Staff.
        contextEmployerId: null,
        // This flag is true only on the Admin "Create Ticket" page, which has a dynamic employer dropdown.
        isContextAdminCreate: false,

        defaultNewEmployeeForm: {
            employeeTitleTh: 'นาย',
            employeeNameTh: '',
            employeeTitleEn: 'Mr.',
            employeeNameEn: '',
            employeeNationality: '',
            employeePassport: '',
            nature_of_work: '',
            employeePhoto: null,
            document_1: null,
        },
        newEmployeeForm: {},
        uploadStatus: {},

        // Initialize the component
        init() {
            // V2.4-S13: Set context from config passed via x-data
            this.contextEmployerId = config.employerId || null;
            this.isContextAdminCreate = config.is_admin_create_view || false;

            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    const existingModalEl = document.getElementById('existingEmployeeModal');
                    const newModalEl = document.getElementById('newEmployeeModal');

                    if (existingModalEl) this.modalInstances.existing = new bootstrap.Modal(existingModalEl);
                    if (newModalEl) this.modalInstances.new = new bootstrap.Modal(newModalEl);
                }
            });
            this.resetNewEmployeeForm();
            this.restoreOldInput();

            // V2.4-S13: If this is the admin create view and an old employer_id was selected,
            // pre-fetch the employees for that employer.
            if (this.isContextAdminCreate && this.contextEmployerId) {
                console.log(`Admin create context detected with pre-selected employer: ${this.contextEmployerId}. Fetching employees.`);
                this.fetchEmployees();
            }
        },

        // Restore basket from Laravel's old() input
        restoreOldInput() {
            const oldAttachments = @json(old('attachments'));
            if (!oldAttachments) return;

            const storageBaseUrl = '{{ Storage::disk('public')->url('') }}'.replace(/\/$/, '');

            // Restore Files
            if (Array.isArray(oldAttachments.files)) {
                this.basket.files = oldAttachments.files.map(file => {
                    if (file && typeof file === 'object') {
                        // Ensure URL is present for display
                        if ((!file.url || (typeof file.url === 'string' && !file.url.startsWith('http'))) && file.path) {
                            file.url = storageBaseUrl + '/' + file.path;
                        }
                        // Ensure size is integer
                        file.size = parseInt(file.size) || 0;
                        return file;
                    }
                    return null;
                }).filter(Boolean); // Filter out nulls
            }

            // Restore Existing Employees
            if (Array.isArray(oldAttachments.existing_employees) && oldAttachments.existing_employees.length > 0) {
                // We must fetch the list to map IDs back to objects.
                // Use the robust fetchEmployees (which handles its own isLoading state)
                this.fetchEmployees().then(() => {
                    const oldIds = new Set(oldAttachments.existing_employees.map(id => parseInt(id)));
                    this.basket.existing_employees = this.availableEmployees.filter(employee => {
                        return oldIds.has(employee.id);
                    });
                }).catch(error => {
                    console.error("Could not restore existing employees from old input:", error);
                });
            }

            // Restore New Employees (Data is self-contained)
            if (Array.isArray(oldAttachments.new_employees)) {
                this.basket.new_employees = oldAttachments.new_employees.map(item => {
                    // Handle potential JSON strings if validation failed early
                    if (typeof item === 'string') {
                        try {
                            return JSON.parse(item);
                        } catch (e) {
                            console.error("Error parsing old new_employee data:", e);
                            return null;
                        }
                    }
                    return item;
                }).filter(item => item !== null && typeof item === 'object');
            }
        },

        // --- Core Basket Functions ---
        totalItemsCount() {
            // Ensure arrays exist before checking length
            const filesCount = this.basket.files ? this.basket.files.length : 0;
            const existingCount = this.basket.existing_employees ? this.basket.existing_employees.length : 0;
            const newCount = this.basket.new_employees ? this.basket.new_employees.length : 0;
            return existingCount + newCount + filesCount;
        },

        formatBytes(bytes, decimals = 2) {
            if (!+bytes) return '0 Bytes'
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
        },

        removeConfirm(type, index, itemName) {
            if (typeof Swal === 'undefined') {
                if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ ' + itemName + '?')) {
                    if(this.basket[type] && typeof this.basket[type].splice === 'function') {
                        this.basket[type].splice(index, 1);
                    }
                }
                return;
            }

            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: `คุณต้องการลบ '${itemName}' ออกจากตะกร้าใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Use $nextTick to ensure the DOM updates correctly
                    this.$nextTick(() => {
                        if(this.basket[type] && typeof this.basket[type].splice === 'function') {
                            this.basket[type].splice(index, 1);
                        }
                    });
                }
            });
        },

        // --- Existing Employee Functions ---

        // V2.4-S13: CRITICAL - This function now handles dynamic context.
        async fetchEmployees() {
            // ... (Jules: Context/Loading checks คงเดิม) ...
            if (this.isContextAdminCreate && !this.contextEmployerId) {
                this.availableEmployees = [];
                return Promise.resolve();
            }
            if (this.availableEmployees.length > 0 || this.isLoading) return Promise.resolve();
            this.isLoading = true;
            try {
                // ... (Jules: apiUrl construction คงเดิม) ...
                let apiUrl = '{{ route('api-web.employer.employees.index') }}';
                const params = new URLSearchParams();
                if (this.contextEmployerId) params.append('employer_id', this.contextEmployerId);
                if (params.toString()) apiUrl += (apiUrl.includes('?') ? '&' : '?') + params.toString();

                // V2.4-S21: Define Headers for AJAX Best Practices
                // Ensures Laravel correctly identifies the request type and handles session/CSRF robustly.
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest', // Crucial for AJAX identification
                };

                if (this.isContextAdminCreate) {
                    headers['X-Context'] = 'smart-ticket-create';
                }

                // Add CSRF Token (Best practice)
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    headers['X-CSRF-TOKEN'] = csrfToken;
                }

                // Execute the fetch request with the headers
                const response = await fetch(apiUrl, {
                    headers: headers
                });

                if (!response.ok) {
                    // ... (Jules: Error handling logic คงเดิม) ...
                    let errorDetails = 'N/A';
                    try {
                        errorDetails = await response.text();
                    } catch (e) { /* ignore */ }
                    if (response.status === 401 || response.status === 403) {
                        throw new Error(`Authentication failed (Session expired?). Please refresh the page. Status: ${response.status}.`);
                    }
                    throw new Error(`Failed to fetch employees. Status: ${response.status}. Details: ${errorDetails.substring(0, 200)}`);
                }
                this.availableEmployees = await response.json();
                return Promise.resolve();
            } catch (error) {
                // ... (Jules: Error reporting logic คงเดิม) ...
                console.error("fetchEmployees Error:", error);
                const errorMessage = 'เกิดข้อผิดพลาดในการโหลดข้อมูลลูกจ้าง (API Error): ' + error.message;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: errorMessage
                    });
                } else {
                    alert(errorMessage);
                }
                return Promise.reject(error);
            } finally {
                this.isLoading = false;
            }
        },

        openExistingEmployeeModal() {
            // V2.4-S13: Prevent opening modal if no employer is selected in the dynamic context.
            if (this.isContextAdminCreate && !this.contextEmployerId) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกนายจ้าง', text: 'โปรดเลือกนายจ้างก่อนทำการเพิ่มลูกจ้างที่มีอยู่' });
                return;
            }

            if (this.modalInstances.existing) {
                this.modalInstances.existing.show();
            }

            this.fetchEmployees().then(() => {
                if (this.basket.existing_employees) {
                    this.selectedEmployeeIds = this.basket.existing_employees.map(e => e.id.toString());
                } else {
                    this.selectedEmployeeIds = [];
                }
            }).catch(error => {
                console.log("Modal data preparation failed (handled).");
            });
        },

        // V2.4-S13: New handler for the employer dropdown in the Admin Create view.
        handleEmployerChange(newEmployerId) {
            console.log(`Employer selection changed to: ${newEmployerId}`);
            // Update the context ID.
            this.contextEmployerId = newEmployerId;

            // CRITICAL: Reset state to prevent data from the previous employer from leaking.
            this.availableEmployees = [];
            this.basket.existing_employees = [];
            this.basket.new_employees = [];
            this.selectedEmployeeIds = [];
            this.searchQuery = '';

            // If a valid employer is selected, pre-fetch their employees.
            // If the selection is cleared (e.g., placeholder), the API call will be blocked by fetchEmployees.
            if (newEmployerId) {
                this.fetchEmployees();
            }
        },

        filteredEmployees() {
            if (!this.searchQuery) return this.availableEmployees;

            const query = this.searchQuery.toLowerCase();
            return this.availableEmployees.filter(employee => {
                return (employee.employeeNameTh && employee.employeeNameTh.toLowerCase().includes(query)) ||
                    (employee.employeeNameEn && employee.employeeNameEn.toLowerCase().includes(query)) ||
                    (employee.employeePassport && employee.employeePassport.toLowerCase().includes(query));
            });
        },

        confirmSelection() {
            const transientIds = new Set(this.selectedEmployeeIds.map(id => parseInt(id)));
            this.basket.existing_employees = this.availableEmployees.filter(employee => transientIds.has(employee.id));
            if (this.modalInstances.existing) this.modalInstances.existing.hide();
            this.searchQuery = '';
        },


        // --- New Employee Functions ---
        resetNewEmployeeForm() {
            this.newEmployeeForm = JSON.parse(JSON.stringify(this.defaultNewEmployeeForm));
            this.uploadStatus = {};
            Object.keys(this.defaultNewEmployeeForm).forEach(key => {
                if (key === 'employeePhoto' || key.startsWith('document_')) {
                    this.uploadStatus[key] = { loading: false, error: null, url: null };
                }
            });
            const formElement = document.getElementById('newEmployeeActualForm');
            if (formElement) formElement.reset();
        },

        openNewEmployeeModal() {
            // V2.4-S13: Prevent opening modal if no employer is selected in the dynamic context.
            if (this.isContextAdminCreate && !this.contextEmployerId) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกนายจ้าง', text: 'โปรดเลือกนายจ้างก่อนทำการเพิ่มลูกจ้างใหม่' });
                return;
            }
            this.resetNewEmployeeForm();
            if (this.modalInstances.new) this.modalInstances.new.show();
        },

        async handleFileUpload(event, fieldName) {
            // ... (Jules: ฟังก์ชันนี้คงเดิม) ...
            const file = event.target.files[0];
            if (!file || !this.uploadStatus[fieldName]) return;

            const status = this.uploadStatus[fieldName];
            status.loading = true;
            status.error = null;

            const formData = new FormData();
            formData.append('file', file);

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch('{{ route('api-web.temp_upload.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData,
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Upload failed');
                this.newEmployeeForm[fieldName] = data.path;
                status.url = data.url;
            } catch (error) {
                console.error('Upload error:', error);
                status.error = error.message;
                this.newEmployeeForm[fieldName] = null;
                event.target.value = null;
            } finally {
                status.loading = false;
            }
        },
        submitNewEmployeeForm() {
            // ... (Jules: ฟังก์ชันนี้คงเดิม) ...
            const isModalUploading = Object.values(this.uploadStatus).some(status => status.loading);
            if (isModalUploading) {
                const message = 'กรุณารอให้การอัปโหลดไฟล์เสร็จสิ้นก่อน';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'รอสักครู่', text: message });
                } else {
                    alert(message);
                }
                return;
            }
            if (!this.basket.new_employees) {
                this.basket.new_employees = [];
            }
            this.basket.new_employees.push(JSON.parse(JSON.stringify(this.newEmployeeForm)));
            if (this.modalInstances.new) this.modalInstances.new.hide();
            this.resetNewEmployeeForm();
        },

        // --- General File Attachment Functions ---
        triggerFileInput() {
            // ... (Jules: ฟังก์ชันนี้คงเดิม) ...
            if (this.$refs.generalFileInput) {
                this.$refs.generalFileInput.click();
            } else if (this.$refs.replyFileInput) {
                this.$refs.replyFileInput.click();
            }
        },

        async handleGeneralFileUpload(event) {
            // ... (Jules: ฟังก์ชันนี้คงเดิม) ...
            const files = Array.from(event.target.files);
            if (files.length === 0) return;

            this.isUploading = true;
            this.filesToUploadCount = files.length;
            this.filesUploadedCount = 0;
            let errors = [];
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            if (!this.basket.files) {
                this.basket.files = [];
            }

            // Process files sequentially
            for (const file of files) {
                try {
                    this.uploadProgress = Math.round((this.filesUploadedCount / this.filesToUploadCount) * 100);

                    const formData = new FormData();
                    formData.append('file', file);

                    const response = await fetch('{{ route('api-web.temp_upload.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData,
                    });
                    const data = await response.json();

                    if (!response.ok) throw new Error(data.error || 'Upload failed');
                    this.basket.files.push({
                        path: data.path,
                        name: file.name,
                        size: file.size,
                        url: data.url
                    });
                    this.filesUploadedCount++;
                } catch (error) {
                    console.error('Upload error for file ' + file.name + ':', error);
                    errors.push(`${file.name}: ${error.message}`);
                }
            }
            this.isUploading = false;
            this.uploadProgress = 0;
            event.target.value = null;

            if (errors.length > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาดในการอัปโหลดบางไฟล์',
                        html: errors.join('<br>')
                    });
                } else {
                    alert('เกิดข้อผิดพลาดในการอัปโหลดบางไฟล์:\n' + errors.join('\n'));
                }
            }
        },
    }
}
</script>