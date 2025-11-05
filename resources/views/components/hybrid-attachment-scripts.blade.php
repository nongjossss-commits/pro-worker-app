{{-- resources/views/components/hybrid-attachment-scripts.blade.php --}}
<script>
// V2.4-S11: Unified Alpine.js component for Hybrid Attachments (Create & Reply)
function hybridAttachmentManager() {
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
            this.$nextTick(() => {
                if (typeof bootstrap !== 'undefined') {
                    // Check if elements exist before initializing (important for reusability)
                    const existingModalEl = document.getElementById('existingEmployeeModal');
                    const newModalEl = document.getElementById('newEmployeeModal');

                    if (existingModalEl) this.modalInstances.existing = new bootstrap.Modal(existingModalEl);
                    if (newModalEl) this.modalInstances.new = new bootstrap.Modal(newModalEl);
                }
            });
            this.resetNewEmployeeForm();
            // V2.4-S11: Restore state if validation failed
            this.restoreOldInput();
        },

        // V2.4-S11: Restore basket from Laravel's old() input
        restoreOldInput() {
            const oldAttachments = @json(old('attachments'));
            if (!oldAttachments) return;

            const storageBaseUrl = '{{ Storage::disk('public')->url('') }}'.replace(/\/$/, '');

            // Restore Files
            if (Array.isArray(oldAttachments.files)) {
                this.basket.files = oldAttachments.files.map(file => {
                    // Ensure URL is present for display
                    if (!file.url || !file.url.startsWith('http')) {
                        file.url = storageBaseUrl + '/' + file.path;
                    }
                    return file;
                });
            }

            // Restore Existing Employees
            // Since old() input for existing employees often contains only IDs, we must fetch the full list
            // to map those IDs back to the objects needed for the basket display (e.g., name, photo).
            if (Array.isArray(oldAttachments.existing_employees) && oldAttachments.existing_employees.length > 0) {
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
                    // If validation failed, the data might be restored as arrays (if prepareForValidation ran)
                    // or potentially still as strings if validation failed very early or if submitted as JSON string. Handle both.
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
            return this.basket.existing_employees.length + this.basket.new_employees.length + this.basket.files.length;
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
                if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ ' + itemName + '?')) this.basket[type].splice(index, 1);
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
                        this.basket[type].splice(index, 1);
                    });
                }
            });
        },

        // --- Existing Employee Functions ---
        async fetchEmployees() {
            // Ensure we return a Promise for consistency (used in restoreOldInput)
            if (this.availableEmployees.length > 0) return Promise.resolve();
            this.isLoading = true;
            try {
                // NOTE: This route currently relies on the logged-in user's scope (Employer sees own, Admin/Staff sees based on their permissions).
                const response = await fetch('{{ route('api-web.employer.employees.index') }}');
                if (!response.ok) throw new Error('Failed to fetch employees');
                this.availableEmployees = await response.json();
                return Promise.resolve();
            } catch (error) {
                console.error(error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: 'เกิดข้อผิดพลาดในการโหลดข้อมูลลูกจ้าง'
                    });
                } else {
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูลลูกจ้าง');
                }
                return Promise.reject(error);
            } finally {
                this.isLoading = false;
            }
        },
        async openExistingEmployeeModal() {
            await this.fetchEmployees();
            // Ensure IDs are strings for x-model binding
            this.selectedEmployeeIds = this.basket.existing_employees.map(e => e.id.toString());
            if (this.modalInstances.existing) this.modalInstances.existing.show();
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
                    this.uploadStatus[key] = {
                        loading: false,
                        error: null,
                        url: null
                    };
                }
            });
            // Reset the actual HTML form element if it exists
            const formElement = document.getElementById('newEmployeeActualForm');
            if (formElement) formElement.reset();
        },
        openNewEmployeeModal() {
            this.resetNewEmployeeForm();
            if (this.modalInstances.new) this.modalInstances.new.show();
        },
        async handleFileUpload(event, fieldName) {
            // (Handles uploads specifically within the New Employee Modal)
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
            const isModalUploading = Object.values(this.uploadStatus).some(status => status.loading);
            if (isModalUploading) {
                Swal.fire({
                    icon: 'warning',
                    title: 'รอสักครู่',
                    text: 'กรุณารอให้การอัปโหลดไฟล์เสร็จสิ้นก่อน'
                });
                return;
            }
            this.basket.new_employees.push(JSON.parse(JSON.stringify(this.newEmployeeForm)));
            if (this.modalInstances.new) this.modalInstances.new.hide();
            this.resetNewEmployeeForm();
        },

        // --- General File Attachment Functions ---
        triggerFileInput() {
            // Check for common ref names used in Create (generalFileInput) and Reply (replyFileInput) views.
            if (this.$refs.generalFileInput) {
                this.$refs.generalFileInput.click();
            } else if (this.$refs.replyFileInput) {
                this.$refs.replyFileInput.click();
            }
        },
        async handleGeneralFileUpload(event) {
            const files = Array.from(event.target.files);
            if (files.length === 0) return;

            this.isUploading = true;
            this.filesToUploadCount = files.length;
            this.filesUploadedCount = 0;
            let errors = [];

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการอัปโหลดบางไฟล์',
                    html: errors.join('<br>')
                });
            }
        },
    }
}
</script>
