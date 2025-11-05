{{-- resources/views/components/hybrid-attachment-scripts.blade.php --}}
{{-- V2.4-S13 (Plan B): Final Unified Script --}}
<script>
    function hybridAttachmentManager() {
        // V2.4-S13: Determine Admin/Staff status from Blade
        const userIsAdminOrStaff = @json(Auth::check() && Auth::user()->can('manage-tickets'));

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
            employersList: [], // (V2.4-S13: For Admin Filter) [cite: 58-59]
            selectedEmployeeIds: [], // (IDs currently checked in modal)
            isLoading: false,
            searchQuery: '', // (Employee Search)
            selectedEmployerFilter: '', // (V2.4-S13: Employer Filter ID) [cite: 58-59]

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

                // V2.4-S13 (Plan B): Fetch employer list for the filter [cite: 61]
                if (userIsAdminOrStaff) {
                    this.fetchEmployersList();
                }
            },

            // (restoreOldInput, totalItemsCount, formatBytes, removeConfirm... all remain the same)
            // ... (omitting unchanged functions for brevity)

            // --- V2.4-S13 (Plan B): NEW Function ---
            async fetchEmployersList() {
                try {
                    // Call the new API endpoint
                    const response = await fetch('{{ route('api-web.employers.list_api') }}');
                    if (!response.ok) throw new Error('Failed to fetch employers list');
                    this.employersList = await response.json();
                } catch (error) {
                    console.error('Error fetching employer list:', error);
                }
            },

            // --- V2.4-S13 (Plan B): UPGRADED Function ---
            async fetchEmployees() {
                if (this.availableEmployees.length > 0) return; // Only fetch once
                this.isLoading = true;
                let apiUrl = '{{ route('api-web.employer.employees.index') }}';
                try {
                    const response = await fetch(apiUrl);
                    if (!response.ok) throw new Error('Failed to fetch employees');
                    this.availableEmployees = await response.json();
                } catch (error) {
                    console.error('Error fetching employees:', error);
                } finally {
                    this.isLoading = false;
                }
            },

            // --- V2.4-S11.4 (Refactored) ---
            async openExistingEmployeeModal() {
                // (No longer needs ticketEmployerId)
                await this.fetchEmployees(); // Fetch (if needed)
                this.selectedEmployeeIds = []; // Reset selections
                if (this.modalInstances.existing) this.modalInstances.existing.show();
            },

            // --- V2.4-S13 (Plan B): UPGRADED Function ---
            filteredEmployees() {
                // V2.4-S11.3 (Duplicate Fix): Get IDs of employees already in the reply basket
                const basketIds = new Set(this.basket.existing_employees.map(e => e.id));
                const query = this.searchQuery.toLowerCase();

                // V2.4-S13 (Plan B): Get the selected Employer ID (it's a string)
                const filterEmployerId = this.selectedEmployerFilter;

                return this.availableEmployees.filter(employee => {
                    // Rule 1: (Duplicate Fix) MUST NOT be in the basket.
                    if (basketIds.has(employee.id)) {
                        return false;
                    }

                    // Rule 2: (Plan B) MUST match Employer Filter (if set) [cite: 73]
                    if (userIsAdminOrStaff && filterEmployerId && String(employee.employer_id) !== filterEmployerId) {
                        return false;
                    }

                    // Rule 3: (Search Query) MUST match search query (if any)
                    if (!this.searchQuery) {
                        return true; // No query, matches filters = Show
                    }

                    // Rule 4: Match search logic [cite: 71]
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

                if (this.modalInstances.existing) {
                    this.modalInstances.existing.hide();
                }
                this.searchQuery = ''; // (Do NOT reset selectedEmployerFilter, user might want to add more from same employer)
            },

            // (All functions for New Employee (reset, open, handleFileUpload, submit) remain the same)
            // (All functions for General File (trigger, handleGeneralFileUpload) remain the same)
        }
    }
</script>
