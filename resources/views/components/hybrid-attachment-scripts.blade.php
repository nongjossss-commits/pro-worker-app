{{-- resources/views/components/hybrid-attachment-scripts.blade.php --}}
{{-- V2.4-S11: Unified Reusable Alpine.js Component for Hybrid Attachments --}}
<script>
    function hybridAttachmentManager(options = {}) { // V2.4-S11.3: Add options
        const ticketEmployerId = options.ticketEmployerId || null; // V2.4-S11.3: Store the ID
        return {
            // --- Core Basket State (Persistent) ---
            basket: {
                existing_employees: [],
                new_employees: [],
                // Format: { path: 'temp_uploads/uuid.jpg', url: 'http://...', name: 'filename.jpg', size: 1024 }
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
            uploadStatus: {}, // For New Employee Modal uploads

            // Initialize the component
            init() {
                // Initialize Bootstrap Modals
                this.$nextTick(() => {
                    if (typeof bootstrap !== 'undefined') {
                        const existingModalEl = document.getElementById('existingEmployeeModal');
                        const newModalEl = document.getElementById('newEmployeeModal');
                        if(existingModalEl) {
                            this.modalInstances.existing = new bootstrap.Modal(existingModalEl);
                        }
                        if(newModalEl) {
                            this.modalInstances.new = new bootstrap.Modal(newModalEl);
                        }
                    }
                });

                // Initialize New Employee Form State
                this.resetNewEmployeeForm();

                // V2.4-S11: Restore old input if validation fails
                this.restoreOldInput();
            },

            // V2.4-S11: New function to restore state from old() helper
            restoreOldInput() {
                try {
                    const oldAttachments = @json(old('attachments'));
                    if (oldAttachments) {
                        if (Array.isArray(oldAttachments.files)) {
                             // Can't fully restore files, but can show a message.
                        }
                        if (Array.isArray(oldAttachments.existing_employees)) {
                            // This is complex as it requires fetching full employee data.
                            // For now, we'll just log it. A more advanced implementation might re-fetch.
                            console.log('Restoring existing employees:', oldAttachments.existing_employees);
                        }
                        if (Array.isArray(oldAttachments.new_employees)) {
                            // New employees are JSON strings, so we need to parse them back
                           this.basket.new_employees = oldAttachments.new_employees.map(emp => {
                                return (typeof emp === 'string') ? JSON.parse(emp) : emp;
                           });
                        }
                    }
                } catch (e) {
                    console.error("Error restoring old input:", e);
                }
            },


            // --- Core Basket Functions ---
            totalItemsCount() {
                const filesCount = this.basket.files ? this.basket.files.length : 0;
                const existingCount = this.basket.existing_employees ? this.basket.existing_employees.length : 0;
                const newCount = this.basket.new_employees ? this.basket.new_employees.length : 0;
                return filesCount + existingCount + newCount;
            },

            formatBytes(bytes, decimals = 2) {
                if (!+bytes) return '0 Bytes'
                const k = 1024
                const dm = decimals < 0 ? 0 : decimals
                const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
                const i = Math.floor(Math.log(bytes) / Math.log(k))
                return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
            },

            removeConfirm(type, index, itemName) {
                if (typeof Swal === 'undefined') {
                    if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ ${itemName} ออกจากตะกร้า?`)) {
                        this.basket[type].splice(index, 1);
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
                        this.$nextTick(() => {
                           if(this.basket[type] && typeof this.basket[type].splice === 'function') {
                                this.basket[type].splice(index, 1);
                           }
                        });
                    }
                });
            },

            // --- Existing Employee Functions ---
            async fetchEmployees(ticketEmployerId = null) { // V2.4-S11.3: Accept ID
                if (this.availableEmployees.length > 0) return;
                this.isLoading = true;

                // V2.4-S11.3: Build the API URL
                let apiUrl = '{{ route('api-web.employer.employees.index') }}';
                if (ticketEmployerId) {
                    apiUrl += `?ticket_employer_id=${ticketEmployerId}`;
                }

                try {
                    const response = await fetch(apiUrl); // Use the new URL
                    if (!response.ok) throw new Error('Failed to fetch employees');
                    this.availableEmployees = await response.json();
                } catch (error) {
                    console.error(error);
                     showToast('เกิดข้อผิดพลาดในการโหลดข้อมูลลูกจ้าง', 'danger');
                } finally {
                    this.isLoading = false;
                }
            },

            async openExistingEmployeeModal() {
                // V2.4-S11.3: Pass the stored ID (null for employer, ID for admin)
                await this.fetchEmployees(ticketEmployerId);

                // V2.4-S11.3 (Bug 2 Fix): Reset selections, don't pre-load
                this.selectedEmployeeIds = [];
                if (this.modalInstances.existing) this.modalInstances.existing.show();
            },

            filteredEmployees() {
                // V2.4-S11.3 (Bug 2 Fix): Get IDs of employees already in the basket
                const basketIds = new Set(this.basket.existing_employees.map(e => e.id));
                const query = this.searchQuery.toLowerCase();

                return this.availableEmployees.filter(employee => {
                    // Rule 1: MUST NOT be in the basket.
                    if (basketIds.has(employee.id)) {
                        return false;
                    }

                    // Rule 2: (It's not in the basket) Match search query (if any)
                    if (!this.searchQuery) {
                        return true; // No query, not in basket = Show
                    }

                    // Rule 3: (It's not in the basket) Match search logic
                    return (employee.employeeNameTh && employee.employeeNameTh.toLowerCase().includes(query)) ||
                           (employee.employeeNameEn && employee.employeeNameEn.toLowerCase().includes(query)) ||
                           (employee.employeePassport && employee.employeePassport.toLowerCase().includes(query));
                });
            },

            confirmSelection() {
                // V2.4-S11.3 (Bug 2 Fix): Get IDs of employees selected in the modal
                const transientIds = new Set(this.selectedEmployeeIds.map(id => parseInt(id)));

                // V2.4-S11.3: Find the full employee objects that were selected
                const newEmployeesToAdd = this.availableEmployees.filter(employee => {
                    return transientIds.has(employee.id);
                });

                // V2.4-S11.3: *Append* (push) them to the basket
                this.basket.existing_employees.push(...newEmployeesToAdd);

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
                if (formElement) {
                    formElement.reset();
                }
            },

            openNewEmployeeModal() {
                this.resetNewEmployeeForm();
                if (this.modalInstances.new) this.modalInstances.new.show();
            },

            async handleFileUpload(event, fieldName) {
                const file = event.target.files[0];
                if (!file) return;

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
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Upload failed');
                    }

                    this.newEmployeeForm[fieldName] = data.path;
                    status.url = data.url;
                } catch (error) {
                    console.error('Upload error:', error);
                    status.error = error.message;
                    this.newEmployeeForm[fieldName] = null;
                    event.target.value = null; // Clear the input
                } finally {
                    status.loading = false;
                }
            },

            submitNewEmployeeForm() {
                const isModalUploading = Object.values(this.uploadStatus).some(status => status.loading);
                if (isModalUploading) {
                    // V2.4-S11-P1: Add Swal stability check
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('รอสักครู่', 'กรุณารอให้การอัปโหลดไฟล์เสร็จสิ้นก่อนเพิ่มเข้าตะกร้า', 'warning');
                    } else {
                        alert('กรุณารอให้การอัปโหลดไฟล์เสร็จสิ้นก่อนเพิ่มเข้าตะกร้า');
                    }
                    return;
                }
                this.basket.new_employees.push(JSON.parse(JSON.stringify(this.newEmployeeForm)));
                if (this.modalInstances.new) {
                    this.modalInstances.new.hide();
                }
                this.resetNewEmployeeForm();
            },

            // --- General File Attachment Functions ---
            triggerFileInput() {
                // The ref name is now consistent across create and reply forms
                this.$refs.replyFileInput ? this.$refs.replyFileInput.click() : this.$refs.generalFileInput.click();
            },

            async handleGeneralFileUpload(event) {
                const files = Array.from(event.target.files);
                if (files.length === 0) return;

                this.isUploading = true;
                this.filesToUploadCount = files.length;
                this.filesUploadedCount = 0;
                this.uploadProgress = 0;
                let errors = [];
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                for (const file of files) {
                    this.uploadProgress = Math.round((this.filesUploadedCount / this.filesToUploadCount) * 100);
                    try {
                        const formData = new FormData();
                        formData.append('file', file);

                        const response = await fetch('{{ route('api-web.temp_upload.store') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
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
                        console.error(`Upload error for ${file.name}:`, error);
                        errors.push(`${file.name}: ${error.message}`);
                    }
                }

                this.isUploading = false;
                this.uploadProgress = 0;
                event.target.value = null; // Reset file input

                if (errors.length > 0) {
                    // V2.4-S11-P1: Add Swal stability check
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาดในการอัปโหลดบางไฟล์',
                            html: errors.join('<br>'),
                        });
                    } else {
                        alert('เกิดข้อผิดพลาดในการอัปโหลดบางไฟล์:\n' + errors.join('\n'));
                    }
                }
            },
        }
    }
</script>
