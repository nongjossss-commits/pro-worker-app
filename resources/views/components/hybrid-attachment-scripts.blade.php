{{-- resources/views/components/hybrid-attachment-scripts.blade.php --}}
<script>
function hybridAttachmentManager(config = {}) {
    return {
        // --- Core States ---
        basket: { existing_employees: [], new_employees: [], files: [] },
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
            other_doc_1_desc: '', employee_doc_10: null, other_doc_2_desc: '',
            employee_doc_11: null, other_doc_3_desc: '', employee_doc_12: null,
            other_doc_4_desc: '',
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

            if (this.isContextAdminCreate && this.contextEmployerId) {
                this.fetchEmployees();
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

        // --- Existing Functions (some may be adapted) ---

        restoreOldInput() {
            // ... (remains the same)
        },
        totalItemsCount() {
            return (this.basket.existing_employees?.length || 0) +
                   (this.basket.new_employees?.length || 0) +
                   (this.basket.files?.length || 0);
        },
        formatBytes(bytes, decimals = 2) {
            // ... (remains the same)
        },
        removeConfirm(type, index, itemName) {
            // ... (remains the same)
        },
        async fetchEmployees() {
            // ... (remains the same)
        },
        openExistingEmployeeModal() {
            // ... (remains the same)
        },
        handleEmployerChange(newEmployerId) {
            // ... (remains the same)
        },
        filteredEmployees() {
            // ... (remains the same)
        },
        confirmSelection() {
            // ... (remains the same)
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
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกนายจ้าง', text: 'โปรดเลือกนายจ้างก่อนทำการเพิ่มลูกจ้างใหม่' });
                return;
            }
            this.resetNewEmployeeForm();
            if (this.modalInstances.new) this.modalInstances.new.show();
        },

        // --- V2.5-S3: Upgraded File Upload Handling with Preview ---
        async handleFileUpload(event, fieldName, previewSelector = null) {
            const file = event.target.files[0];
            if (!file) return;

            // Handle image preview locally before upload
            if (previewSelector && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    // This is a special field for the preview URL
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

                this.newEmployeeForm[fieldName] = data.path; // Store the permanent path
                status.url = data.url; // Store the temporary URL for links if needed

                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                    Toast.fire({ icon: 'success', title: `'${file.name}' อัปโหลดสำเร็จ` });
                }

            } catch (error) {
                console.error(`Upload error for ${fieldName}:`, error);
                status.error = error.message;
                this.newEmployeeForm[fieldName] = null;
                event.target.value = null; // Clear the input
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'อัปโหลดล้มเหลว', text: error.message });
                }
            } finally {
                status.loading = false;
            }
        },

        submitNewEmployeeForm() {
            // ... (remains the same)
        },
        triggerFileInput() {
            // ... (remains the same)
        },
        async handleGeneralFileUpload(event) {
            // ... (remains the same)
        },
    }
}
</script>