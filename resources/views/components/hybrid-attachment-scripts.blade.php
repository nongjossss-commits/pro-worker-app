{{-- ... (โค้ดก่อนหน้า) ... --}}
<script>
    function hybridAttachmentManager() {
        return {
            // ... (โค้ด basket, isUploading, uploadProgress, modalInstances) ...
            // --- Existing/New Employee States (Transient) ---
            availableEmployees: [],
            selectedEmployeeIds: [],
            isLoading: false,
            searchQuery: '', // This is for EMPLOYEE search

            // --- V2.4-S15 (Plan B) New States ---
            availableEmployersList: [], // For employer search results
            employerSearchQuery: '', // For employer search input
            selectedEmployer: null, // Stores the selected employer object {id, name}
            isLoadingEmployers: false, // Loading state for employer search
            // --- V2.4-S15 (Plan B) END ---

            defaultNewEmployeeForm: {
                // ... (โค้ด newEmployeeForm) ...
            },
            newEmployeeForm: {},
            uploadStatus: {}, // For New Employee Modal uploads

            // Initialize the component
            init() {
                // ... (โค้ด init()) ...
            },

            // V2.4-S11: New function to restore state from old() helper
            restoreOldInput() {
                // ... (โค้ด restoreOldInput()) ...
            },

            // --- Core Basket Functions ---
            // ... (โค้ด totalItemsCount, formatBytes, removeConfirm) ...

            // --- V2.4-S15 (Plan B) New Employer Search Functions ---
            // Fetches employer list from the new API
            async fetchEmployersList() {
                if (!this.employerSearchQuery) {
                    this.availableEmployersList = [];
                    return;
                }
                this.isLoadingEmployers = true;
                try {
                    // Use the new API route
                    const response = await fetch(`{{ route('api-web.employers.list.api') }}?q=${this.employerSearchQuery}`);
                    if (!response.ok) throw new Error('Failed to fetch employers');
                    this.availableEmployersList = await response.json();
                } catch (error) {
                    console.error(error);
                    showToast('เกิดข้อผิดพลาดในการค้นหานายจ้าง', 'danger');
                } finally {
                    this.isLoadingEmployers = false;
                }
            },

            // Stores the selected employer and clears the search
            selectEmployer(employer) {
                this.selectedEmployer = employer;
                this.employerSearchQuery = '';
                this.availableEmployersList = [];
            },

            // Clears the selected employer, allowing a new search
            clearEmployerSelection() {
                this.selectedEmployer = null;
                // We might also want to clear the employee list or searchQuery here if needed
                this.searchQuery = '';
            },
            // --- V2.4-S15 (Plan B) END ---

            // --- Existing Employee Functions (Modified) ---
            async fetchEmployees() {
                // V2.4-S15: We now fetch ALL employees for Admin on demand,
                // so we MUST clear the cache if the selected employer changes.
                // We will rely on openExistingEmployeeModal to fetch.
                if (this.availableEmployees.length > 0) {
                    // If we already have employees, don't refetch unless forced
                    return;
                }

                this.isLoading = true;
                try {
                    const response = await fetch('{{ route('api-web.employer.employees.index') }}');
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
                await this.fetchEmployees(); // Always ensure employees are loaded

                // V2.4-S15 (Plan B): Reset employer search state
                this.clearEmployerSelection();
                this.availableEmployersList = [];
                this.isLoadingEmployers = false;

                this.selectedEmployeeIds = this.basket.existing_employees.map(e => e.id.toString());
                if (this.modalInstances.existing) this.modalInstances.existing.show();
            },

            filteredEmployees() {
                // V2.4-S15: Get IDs of employees already in the basket (integers)
                const basketIds = new Set(this.basket.existing_employees.map(e => e.id));
                const selectedIds = new Set(this.selectedEmployeeIds.map(id => parseInt(id)));
                const query = this.searchQuery.toLowerCase();

                // --- V2.4-S15 (Plan B) Filtering Logic START ---

                // 1. Filter by Selected Employer (if Admin/Staff has selected one)
                let filteredByEmployer = this.availableEmployees;
                if (this.selectedEmployer) {
                    filteredByEmployer = this.availableEmployees.filter(employee => {
                        return employee.employer_id === this.selectedEmployer.id;
                    });
                }
                // Note: If user is 'employer', this.selectedEmployer will be null,
                // and availableEmployees only contains their own staff anyway, so it works.

                // 2. Filter by Basket Status and Search Query
                return filteredByEmployer.filter(employee => {
                    // Rule 1: If it's already in the basket...
                    if (basketIds.has(employee.id)) {
                        // ...only show it if it's currently selected (string check)
                        return selectedIds.has(employee.id);
                    }
                    // Rule 2: (Not in basket) Match search query (if any)
                    if (!this.searchQuery) {
                        return true; // No query, not in basket, matches employer = Show
                    }

                    // Rule 3: (Not in basket) Match search logic
                    return (employee.employeeNameTh && employee.employeeNameTh.toLowerCase().includes(query)) ||
                           (employee.employeeNameEn && employee.employeeNameEn.toLowerCase().includes(query)) ||
                           (employee.employeePassport && employee.employeePassport.toLowerCase().includes(query));
                });
                // --- V2.4-S15 (Plan B) Filtering Logic END ---
            },

            confirmSelection() {
                // ... (โค้ด confirmSelection() ไม่เปลี่ยนแปลง) ...
            },

            // --- New Employee Functions ---
            // ... (โค้ด resetNewEmployeeForm, openNewEmployeeModal, handleFileUpload, submitNewEmployeeForm) ...

            // --- General File Attachment Functions ---
            // ... (โค้ด triggerFileInput, handleGeneralFileUpload) ...
        }
    }
</script>
