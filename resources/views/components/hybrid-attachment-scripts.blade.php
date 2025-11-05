{{-- resources/views/components/hybrid-attachment-scripts.blade.php --}}
{{-- V2.4-S12 (Plan B): Final Unified Script --}}
<script>
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
            availableEmployees: [], // (All employees fetched from API)
            employersList: [], // (V2.4-S12: For Admin Filter)
            selectedEmployeeIds: [], // (IDs currently checked in modal)
            isLoading: false,
            searchQuery: '', // (V2.4-S12: Employee Search)
            selectedEmployerFilter: '', // (V2.4-S12: Employer Filter ID)

            // (New Employee Form state remains the same)
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
                // Initialize Bootstrap Modals
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

                // V2.4-S12 (Plan B): Fetch employer list for the filter
                this.fetchEmployersList();
            },

            // (restoreOldInput, totalItemsCount, formatBytes, removeConfirm... all remain the same)
            // ... (ลีลู "ละไว้" (omitting) ฟังก์ชันที่ "ไม่เปลี่ยนแปลง" (unchanged) เพื่อความกระชับ)


            // --- V2.4-S12 (Plan B): NEW Function ---
            async fetchEmployersList() {
                // This only runs if the filter dropdown exists (Admin/Staff)
                if (document.getElementById('employerFilterDropdown')) {
                    try {
                        const response = await fetch('{{ route('api-web.employers.list_api') }}');
                        if (!response.ok) throw new Error('Failed to fetch employers list');
                        this.employersList = await response.json(); [cite: 118]
                    } catch (error) {
                        console.error(error);
                        // (Handle error - e.g., showToast)
                    }
                }
            },

            // --- V2.4-S11.4 (Refactored) ---
            async fetchEmployees() {
                // (This function no longer needs ticketEmployerId)
                if (this.availableEmployees.length > 0) return; // Only fetch once
                this.isLoading = true;
                let apiUrl = '{{ route('api-web.employer.employees.index') }}'; [cite: 106-107]
                try {
                    const response = await fetch(apiUrl);
                    if (!response.ok) throw new Error('Failed to fetch employees');
                    this.availableEmployees = await response.json();
                } catch (error) {
                    console.error(error);
                    // (Handle error)
                } finally {
                    this.isLoading = false;
                }
            },

            // --- V2.4-S11.4 (Refactored) ---
            async openExistingEmployeeModal() {
                // (No longer needs ticketEmployerId)
                await this.fetchEmployees(); // Fetch (if needed)
                this.selectedEmployeeIds = []; // Reset selections [cite: 123-124]
                if (this.modalInstances.existing) this.modalInstances.existing.show();
            },

            // --- V2.4-S12 (Plan B): UPGRADED Function ---
            filteredEmployees() {
                // V2.4-S11.3 (Duplicate Fix): Get IDs of employees already in the reply basket
                const basketIds = new Set(this.basket.existing_employees.map(e => e.id));
                const query = this.searchQuery.toLowerCase();

                // V2.4-S12 (Plan B): Get the selected Employer ID (it's a string, convert to int if needed)
                const filterEmployerId = this.selectedEmployerFilter ? parseInt(this.selectedEmployerFilter, 10) : null;

                return this.availableEmployees.filter(employee => {
                    // Rule 1: (Duplicate Fix) MUST NOT be in the basket.
                    if (basketIds.has(employee.id)) {
                        return false;
                    }
                    // Rule 2: (Plan B) MUST match Employer Filter (if set)
                    if (filterEmployerId && employee.employer_id !== filterEmployerId) { [cite: 113]
                        return false;
                    }

                    // Rule 3: (Search Query) MUST match search query (if any)
                    if (!this.searchQuery) {
                        return true; // No query, matches filters = Show
                    }

                    // Rule 4: Match search logic
                    return (employee.employeeNameTh && employee.employeeNameTh.toLowerCase().includes(query)) ||
                        (employee.employeeNameEn && employee.employeeNameEn.toLowerCase().includes(query)) ||
                        (employee.employeePassport && employee.employeePassport.toLowerCase().includes(query));
                });
            },

            // --- V2.4-S11.4 (Refactored) ---
            confirmSelection() {
                // V2.4-S11.4 (Bug 2 Fix): Get IDs of employees selected in the modal
                const transientIds = new Set(this.selectedEmployeeIds.map(id => parseInt(id)));

                // Find the full employee objects that were selected
                const newEmployeesToAdd = this.availableEmployees.filter(employee => {
                    return transientIds.has(employee.id);
                });

                // *Append* (push) them to the basket
                this.basket.existing_employees.push(...newEmployeesToAdd);

                if (this.modalInstances.existing) this.modalInstances.existing.hide();
                this.searchQuery = ''; // (Do NOT reset selectedEmployerFilter, user might want to add more from same employer)
            },

            // ... (All functions for New Employee (reset, open, handleFileUpload, submit) remain the same)
            // ... (All functions for General File (trigger, handleGeneralFileUpload) remain the same)
        }
    }
</script>
