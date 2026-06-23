
if (typeof window.financialManager === 'undefined') {
    window.financialManager = function(initialData) {
        return {
            // --- Groups (Tabs) ---
            financialGroups: initialData.financialGroups || [],
            activeGroupId: null,

            // --- Data ---
            pricingMode: 'per_head',
            fixedTotal: 0,
            pricingTiers: [],
            employeeCount: initialData.employeeCount || 0,
            discount: 0,

            // Manage Employees Modal State
            activeTierIndex: null,
            modalSelectedIds: [],
            modalSearch: '',

            // Advance Items
            advanceItems: [],
            productionItems: initialData.productionItems || [], // List of {id, name, employee_id} (Scoped to Current Stage)
            employees: initialData.employees || [], // List of candidates {id, name} (Scoped to Current Stage)
            selectedTransactionItems: [], // List of IDs (ProductionItem ID or 'emp_ID')

            // Employee Filter State
            tierEmployeeFilter: 'active', // 'active' or 'cancelled'
            newTransactionEmployeeFilter: 'active',
            editTransactionEmployeeFilter: 'active',

            // Tax Settings
            vatEnabled: true,
            vatIncluded: false,
            vatRate: 7,
            whtEnabled: false,
            whtRate: 3,

            // Custom Header Data
            showCustomHeaderModal: false,
            useCustomHeader: false,
            customHeader: { name:'', address:'', tax_id:'', phone:'', logo:'', biller_profile_id: '', customer_profile_id: '' },
            selectedProfileId: '',

            // Custom Customer (Bill To) Data
            showCustomCustomerModal: false,
            useCustomCustomer: false,
            customCustomerData: { name:'', address:'', tax_id:'', phone:'' },
            selectedAgentId: '',
            selectedEmployerAddressId: '',

            // Calculated values
            totalAmount: 0, // Service Fee Inc VAT
            baseTotal: 0, // Service Fee Gross
            subtotalAmount: 0, // Service Fee Ex VAT
            vatAmount: 0,
            whtAmount: 0,
            advanceTotal: 0, // No VAT
            grandTotalReceivable: 0, // Net Receivable + Advance Total
            netReceivable: 0, // Service Fee Total - WHT

            // Transaction State
            transactions: initialData.transactions || [],
            newTransaction: { type: 'installment', amount: '', due_date: '', notes: '', discount_amount: '', discount_description: '' },
            editingTransaction: { payments: [] },
            selectedFile: null,
            editingPaymentId: null,
            newPayment: { amount: '', paid_at: '', bank_account_id: '', notes: '', slip_path: null },
            paymentSlipFile: null,
            isSavingPayment: false,

            // Separate Saving States
            isSavingSettings: false,
            isSavingTransaction: false,

            selectedTransactionIds: [],
            documentTypeToGenerate: '',
            includeEmployeeList: false,
            // รูปแบบเอกสาร: 'invoice' = ใบเท่านั้น, 'with_list' = ใบ+รายชื่อ, 'list_only' = รายชื่อเท่านั้น
            docVariant: 'invoice',

            // Quick Create Invoice Modal (per-row in Income transactions)
            invoiceModalTransactionId: null,
            invoiceModalLabel: '',
            invoiceModalIncludeList: false,
            // รูปแบบเอกสารใน modal สร้าง invoice รายการเดียว — สอดคล้องกับ docVariant
            invoiceModalVariant: 'invoice',

            // Payment Methods (ช่องทางการชำระเงิน) inside Create Invoice modal.
            // The chosen banks/methods are serialized to base64 JSON and sent
            // to ProductionDocumentController via `payment_methods` query arg.
            invoiceModalBillerProfileId: null,
            invoiceModalProfileBanks: [],     // bank accounts of the biller profile
            invoiceModalThaiBanks: [],        // preset Thai banks (loaded once on init)
            invoiceModalUsingCash: false,
            invoiceModalUsingTransfer: false,
            invoiceModalUsingPromptPay: false,
            invoiceModalPromptPayId: '',
            invoiceModalTransferList: [],     // [{bank_code,bank_name,account_name,account_number}]
            invoiceModalBankPickerOpen: false,
            invoiceModalBankSearch: '',
            invoiceModalShowCustomBank: false,
            invoiceModalCustomBank: { bank_name:'', account_name:'', account_number:'' },

            // Context
            productionId: initialData.productionId,
            csrfToken: initialData.csrfToken,

            init() {
                if (this.financialGroups.length > 0) {
                    this.switchGroup(this.financialGroups[0].id);
                }

                // Listen for insurance-updated event from _employee_card.blade.php
                window.addEventListener('insurance-updated', (e) => {
                    if (e.detail && e.detail.employeeId) {
                        this.updateEmployeeInsurance(e.detail.employeeId, e.detail.insuranceType);
                    }
                });
            },

            switchGroup(groupId) {
                this.activeGroupId = groupId;
                const group = this.financialGroups.find(g => g.id === groupId);
                if (!group) return;

                // Load Group Data (Safely handle nulls)
                const data = group.financial_data || {};

                this.pricingMode = data.pricing_mode || 'per_head';
                this.fixedTotal = data.fixed_base_amount || 0;
                // Ensure reference sharing with group data
                if (!data.pricing_tiers) data.pricing_tiers = [];
                this.pricingTiers = data.pricing_tiers;
                this.discount = data.discount || 0;

                // Load Advance Items
                this.advanceItems = group.advance_items || [];

                this.vatEnabled = data.vat_enabled !== false;
                this.vatIncluded = !!data.vat_included;
                this.vatRate = data.vat_rate !== undefined && data.vat_rate !== null && data.vat_rate !== '' ? parseFloat(data.vat_rate) : 7;
                this.whtEnabled = !!data.wht_enabled;
                this.whtRate = data.wht_rate !== undefined && data.wht_rate !== null && data.wht_rate !== '' ? parseFloat(data.wht_rate) : 3;

                this.useCustomHeader = !!(data.custom_header && (data.custom_header.name || data.custom_header.address || data.custom_header.tax_id || data.custom_header.phone || data.custom_header.logo));
                this.customHeader = data.custom_header || { name:'', address:'', tax_id:'', phone:'', logo:'', biller_profile_id: '', customer_profile_id: '' };
                this.selectedProfileId = data.profile_id || '';

                this.useCustomCustomer = !!data.customer_override;
                this.customCustomerData = data.customer_override || { name:'', address:'', tax_id:'', phone:'' };

                // Initialize item_ids if missing
                this.pricingTiers.forEach(t => {
                    if (!Array.isArray(t.item_ids)) t.item_ids = [];
                });

                // Default tier if empty
                if (this.pricingMode === 'per_head' && this.pricingTiers.length === 0) {
                    this.pricingTiers.push({ price: 0, count: this.employeeCount, note: 'Standard Price', item_ids: [] });
                }

                this.updateTotal();
            },

            updateEmployeeInsurance(employeeId, newType) {
                // Update Production Items (if linked)
                this.productionItems.forEach(item => {
                    if (item.employee_id == employeeId) {
                        item.insurance_type = newType;
                    }
                });

                // Update Candidates (if not linked)
                this.employees.forEach(emp => {
                    if (emp.id == employeeId) {
                        emp.insurance_type = newType;
                    }
                });

                // Force Alpine reactivity if array mutation is deep (though simple prop update usually works)
                // If items are used in filtered lists (like allEmployeesForTier), they are computed properties so they should react.
            },

            // --- Helper Methods for Tier Management ---
            getTierForItem(itemId) {
                // Returns the tier object if the item is assigned to one
                return this.pricingTiers.find(t => {
                    if (!t.item_ids) return false;
                    if (typeof itemId === 'string' && itemId.startsWith('emp_')) {
                        return t.item_ids.includes(itemId);
                    }
                    return t.item_ids.includes(parseInt(itemId));
                });
            },

            getItemPrice(itemId) {
                const tier = this.getTierForItem(itemId);
                return tier ? parseFloat(tier.price || 0) : 0;
            },

            assignItemsToTier(tierIndex, itemIds) {
                // Ensure IDs are integers OR emp_ strings
                // Fix: Do not parseInt candidate IDs (emp_ prefix)
                const processedIds = itemIds.map(id => {
                    if (typeof id === 'string' && id.startsWith('emp_')) return id;
                    return parseInt(id);
                });

                // 1. Remove these items from all other tiers
                this.pricingTiers.forEach((t, idx) => {
                    if (idx !== tierIndex && t.item_ids) {
                        // Filter out if present in the new selection
                        t.item_ids = t.item_ids.filter(existingId => {
                            // Check if existingId is in processedIds
                            return !processedIds.some(newId => newId == existingId);
                        });
                    }
                });

                // 2. Overwrite target tier with new selection
                // KEY CHANGE: We must PRESERVE items that are NOT visible in the current stage (Pre-Prod vs Workflow)
                // but are already in the tier.
                // The `modalSelectedIds` (which feeds `itemIds` here) only represents the selection state of VISIBLE items.

                const targetTier = this.pricingTiers[tierIndex];
                const existingIds = targetTier.item_ids || [];

                // Find items that are currently visible in the modal
                // (i.e. those in allEmployeesForTier)
                const visibleEmployees = this.allEmployeesForTier.map(e => e.id);

                // Items that are in the tier but NOT in the visible list (belong to other stage)
                // We must keep these safe.
                const hiddenIds = existingIds.filter(id => !visibleEmployees.includes(id) && !visibleEmployees.includes(parseInt(id)));

                // Combine hidden items + new selection from visible items
                targetTier.item_ids = [...hiddenIds, ...processedIds];

                // 3. Update count for display (Global Count)
                targetTier.count = targetTier.item_ids.length;

                this.updateTotal();
            },

            selectAllForModal() {
                // Use allEmployeesForTier to respect filters (locked items) and include candidates
                const sourceList = this.allEmployeesForTier;

                if (this.modalSearch) {
                    const term = this.modalSearch.toLowerCase();
                    const visibleIds = sourceList
                        .filter(i => i.name.toLowerCase().includes(term))
                        .map(i => i.id);

                    // Union with existing selection
                    this.modalSelectedIds = [...new Set([...this.modalSelectedIds, ...visibleIds])];
                } else {
                    this.modalSelectedIds = sourceList.map(i => i.id);
                }
            },

            deselectAllForModal() {
                this.modalSelectedIds = [];
            },

            unassignItem(itemId) {
                 this.pricingTiers.forEach(t => {
                    if (t.item_ids) {
                        t.item_ids = t.item_ids.filter(id => {
                            if (typeof itemId === 'string' && itemId.startsWith('emp_')) {
                                return id !== itemId;
                            }
                            return id !== parseInt(itemId);
                        });
                        t.count = t.item_ids.length;
                    }
                });
                this.updateTotal();
            },

            // --- Employee Filter Logic ---
            isCancelledStatus(status) {
                if (!status) return false;
                return status.endsWith('cancelled') || status === 'cancelled';
            },

            get allEmployeesForTier() {
                // Return Merged List for Price Tier Modal
                // กัน crash ถ้า data load fail — return empty list ดีกว่า throw
                if (!Array.isArray(this.transactions) || !Array.isArray(this.productionItems)) return [];

                // Filter logic: Exclude items that are currently used in an Installment (Transaction)
                // Note: Transactions are GLOBAL/SHARED. If an item in Pre-Prod has an installment, it's locked.
                const usedItemIds = new Set();
                const usedEmployeeIds = new Set();

                this.transactions.forEach(t => {
                    if (t.items && Array.isArray(t.items)) {
                        t.items.forEach(item => {
                            usedItemIds.add(item.id);
                            if(item.employee_id) usedEmployeeIds.add(item.employee_id);
                        });
                    }
                });

                // Lock employees already assigned to OTHER tiers so each employee
                // belongs to exactly one tier (prevents double-billing). Match BY employee
                // (not item.id) so it works across Pre-Prod ↔ Workflow where the same
                // employee may have different ProductionItem IDs. Active tier's
                // employees stay visible so the user can untick them.
                const employeesInOtherTiers = new Set();
                const employeesInActiveTier = new Set();
                const itemIdsInOtherTiers = new Set();
                const itemIdsInActiveTier = new Set();
                const itemIdToEmpId = new Map();
                this.productionItems.forEach(it => {
                    if (it.employee_id != null) itemIdToEmpId.set(it.id, it.employee_id);
                });
                this.pricingTiers.forEach((tier, idx) => {
                    if (!tier.item_ids || !Array.isArray(tier.item_ids)) return;
                    const empSet = (idx === this.activeTierIndex) ? employeesInActiveTier : employeesInOtherTiers;
                    const idSet = (idx === this.activeTierIndex) ? itemIdsInActiveTier : itemIdsInOtherTiers;
                    tier.item_ids.forEach(rawId => {
                        idSet.add(rawId);
                        let empId = null;
                        if (typeof rawId === 'string' && rawId.startsWith('emp_')) {
                            const parsed = parseInt(rawId.substring(4));
                            if (!isNaN(parsed)) empId = parsed;
                        } else {
                            empId = itemIdToEmpId.get(rawId);
                            if (empId == null) empId = itemIdToEmpId.get(parseInt(rawId));
                        }
                        if (empId != null) empSet.add(empId);
                    });
                });
                const list = [];
                const itemsByEmpId = {};

                // 1. Production Items
                this.productionItems.forEach(item => {
                    if (usedItemIds.has(item.id)) return; // Locked by installment
                    // Hide if in another tier (by item ID OR by employee match)
                    // UNLESS also in active tier (then keep visible so user can untick)
                    const inOther = itemIdsInOtherTiers.has(item.id) || (item.employee_id != null && employeesInOtherTiers.has(item.employee_id));
                    const inActive = itemIdsInActiveTier.has(item.id) || (item.employee_id != null && employeesInActiveTier.has(item.employee_id));
                    if (inOther && !inActive) return;

                    if (item.employee_id) itemsByEmpId[item.employee_id] = item.id;

                    const isCancelled = this.isCancelledStatus(item.status);
                    if (this.tierEmployeeFilter === 'active' && isCancelled) return;
                    if (this.tierEmployeeFilter === 'cancelled' && !isCancelled) return;

                    list.push({ ...item, type: 'item', last_step_name: item.last_step_name, has_visa: item.has_visa });
                });

                // 2. Candidates
                this.employees.forEach(emp => {
                    if (itemsByEmpId[emp.id]) return; // Already exists as item
                    if (usedEmployeeIds.has(emp.id)) return; // Locked
                    const candidateId = 'emp_' + emp.id;
                    const inOther = itemIdsInOtherTiers.has(candidateId) || employeesInOtherTiers.has(emp.id);
                    const inActive = itemIdsInActiveTier.has(candidateId) || employeesInActiveTier.has(emp.id);
                    if (inOther && !inActive) return;

                    const isCancelled = this.isCancelledStatus(emp.status);
                    if (this.tierEmployeeFilter === 'active' && isCancelled) return;
                    if (this.tierEmployeeFilter === 'cancelled' && !isCancelled) return;

                    list.push({
                        id: 'emp_' + emp.id,
                        name: emp.name,
                        name_en: emp.name_en,
                        title_en: emp.title_en,
                        photo: emp.photo,
                        nationality: emp.nationality,
                        insurance_type: emp.insurance_type,
                        passport: emp.passport,
                        has_visa: emp.has_visa,
                        employee_id: emp.id,
                        last_step_name: emp.last_step_name,
                        status: emp.status,
                        type: 'employee'
                    });
                });

                return list;
            },

            get availableItems() {
                if (!this.activeGroupId) return [];

                // Build map of current-view productionItem.id -> employee_id so we can
                // resolve employee for items even when the transaction's items pivot
                // doesn't carry employee_id directly (Pre-Prod ↔ Workflow consistency).
                const itemIdToEmpId = new Map();
                this.productionItems.forEach(it => {
                    if (it.employee_id != null) itemIdToEmpId.set(it.id, it.employee_id);
                });

                const usedItemIds = new Set();
                const usedEmployeeIds = new Set();

                this.filteredTransactions.forEach(t => {
                    if (this.editingTransaction.id && t.id === this.editingTransaction.id) return;

                    if (t.items && Array.isArray(t.items)) {
                        t.items.forEach(item => {
                            usedItemIds.add(item.id);
                            // Resolve employee_id from the item itself OR from current view's items
                            let empId = item.employee_id;
                            if (empId == null) empId = itemIdToEmpId.get(item.id) || itemIdToEmpId.get(parseInt(item.id));
                            if (empId != null) usedEmployeeIds.add(empId);
                        });
                    }
                });

                const list = [];
                const itemsByEmpId = {};

                // 2. Add Existing Production Items
                this.productionItems.forEach(item => {
                    if (usedItemIds.has(item.id)) return;

                    if (item.employee_id) {
                        itemsByEmpId[item.employee_id] = item.id;
                    }

                    const isCancelled = this.isCancelledStatus(item.status);
                    if (this.newTransactionEmployeeFilter === 'active' && isCancelled) return;
                    if (this.newTransactionEmployeeFilter === 'cancelled' && !isCancelled) return;

                    let hasPrice = true;
                    if (this.pricingMode === 'per_head') {
                         hasPrice = !!this.getTierForItem(item.id);
                    }

                    if (this.pricingMode === 'per_head' && !hasPrice) return;

                    if (item.employee_id) {
                        if (usedEmployeeIds.has(item.employee_id)) return;
                    }

                    list.push({
                        id: item.id, // Value
                        name: item.name,
                        photo: item.photo,
                        name_en: item.name_en,
                        title_en: item.title_en,
                        nationality: item.nationality,
                        insurance_type: item.insurance_type,
                        passport: item.passport,
                        has_visa: item.has_visa,
                        last_step_name: item.last_step_name,
                        status: item.status,
                        type: 'item'
                    });
                });

                // 3. Add Candidates (Employees)
                if (this.pricingMode !== 'per_head') {
                    this.employees.forEach(emp => {
                        if (itemsByEmpId[emp.id]) return;
                        if (usedEmployeeIds.has(emp.id)) return;

                        const isCancelled = this.isCancelledStatus(emp.status);
                        if (this.newTransactionEmployeeFilter === 'active' && isCancelled) return;
                        if (this.newTransactionEmployeeFilter === 'cancelled' && !isCancelled) return;

                        list.push({
                            id: 'emp_' + emp.id,
                            name: emp.name,
                            photo: emp.photo,
                            name_en: emp.name_en,
                            title_en: emp.title_en,
                            nationality: emp.nationality,
                            insurance_type: emp.insurance_type,
                            passport: emp.passport,
                            has_visa: emp.has_visa,
                            last_step_name: emp.last_step_name,
                            status: emp.status,
                            type: 'employee'
                        });
                    });
                }

                return list;
            },

            get editModalItems() {
                if (!this.activeGroupId) return [];

                // Build map of current-view productionItem.id -> employee_id (same robustness
                // as availableItems — handles cross-stage Pre-Prod/Workflow item ID drift).
                const itemIdToEmpId = new Map();
                this.productionItems.forEach(it => {
                    if (it.employee_id != null) itemIdToEmpId.set(it.id, it.employee_id);
                });

                const attachedItemIds = new Set();
                const attachedEmployeeIds = new Set();
                if (this.editingTransaction.items && Array.isArray(this.editingTransaction.items)) {
                    this.editingTransaction.items.forEach(item => {
                        attachedItemIds.add(item.id);
                        let empId = item.employee_id;
                        if (empId == null) empId = itemIdToEmpId.get(item.id) || itemIdToEmpId.get(parseInt(item.id));
                        if (empId != null) attachedEmployeeIds.add(empId);
                    });
                }

                const usedItemIds = new Set();
                const usedEmployeeIds = new Set();

                this.filteredTransactions.forEach(t => {
                    if (this.editingTransaction.id && t.id === this.editingTransaction.id) return;
                    if (t.items && Array.isArray(t.items)) {
                        t.items.forEach(item => {
                            usedItemIds.add(item.id);
                            let empId = item.employee_id;
                            if (empId == null) empId = itemIdToEmpId.get(item.id) || itemIdToEmpId.get(parseInt(item.id));
                            if (empId != null) usedEmployeeIds.add(empId);
                        });
                    }
                });

                const list = [];
                const itemsByEmpId = {};

                // 1. Production Items — show if attached to current tx OR (has tier AND not used elsewhere).
                // Per-head mode requires a tier to be set; attached items remain visible
                // even without a tier so the user can untick them.
                this.productionItems.forEach(item => {
                    if (item.employee_id) itemsByEmpId[item.employee_id] = item.id;

                    const isAttached = attachedItemIds.has(item.id) || (item.employee_id != null && attachedEmployeeIds.has(item.employee_id));
                    const isUsed = usedItemIds.has(item.id) || (item.employee_id != null && usedEmployeeIds.has(item.employee_id));

                    const isCancelled = this.isCancelledStatus(item.status);
                    if (this.editTransactionEmployeeFilter === 'active' && isCancelled) return;
                    if (this.editTransactionEmployeeFilter === 'cancelled' && !isCancelled) return;

                    let hasPrice = true;
                    if (this.pricingMode === 'per_head') {
                        hasPrice = !!this.getTierForItem(item.id);
                    }

                    if (isAttached || (hasPrice && !isUsed)) {
                         list.push({
                            id: item.id,
                            name: item.name,
                            photo: item.photo,
                            name_en: item.name_en,
                            title_en: item.title_en,
                            nationality: item.nationality,
                            insurance_type: item.insurance_type,
                            passport: item.passport,
                            has_visa: item.has_visa,
                            last_step_name: item.last_step_name,
                            status: item.status,
                            type: 'item',
                            attached: isAttached
                        });
                    }
                });

                // 2. Candidates — skip in per-head mode (matches availableItems behavior;
                // candidates without tier shouldn't show until tier is set).
                if (this.pricingMode !== 'per_head') {
                    this.employees.forEach(emp => {
                        if (itemsByEmpId[emp.id]) return; // Already has item
                        if (usedEmployeeIds.has(emp.id) && !attachedEmployeeIds.has(emp.id)) return; // Used elsewhere (and not in current tx)

                        const isCancelled = this.isCancelledStatus(emp.status);
                        if (this.editTransactionEmployeeFilter === 'active' && isCancelled) return;
                        if (this.editTransactionEmployeeFilter === 'cancelled' && !isCancelled) return;

                        list.push({
                            id: 'emp_' + emp.id,
                            name: emp.name,
                            photo: emp.photo,
                            name_en: emp.name_en,
                            title_en: emp.title_en,
                            nationality: emp.nationality,
                            insurance_type: emp.insurance_type,
                            passport: emp.passport,
                            has_visa: emp.has_visa,
                            last_step_name: emp.last_step_name,
                            status: emp.status,
                            type: 'employee',
                            attached: attachedEmployeeIds.has(emp.id)
                        });
                    });
                }

                return list;
            },

            isItemAttached(itemId) {
                if (!this.editingTransaction.items) return false;
                if (String(itemId).startsWith('emp_')) return false;
                return this.editingTransaction.items.some(i => i.id == itemId);
            },

            recalcAmount() {
                if (this.pricingMode === 'per_head') {
                    let total = 0;
                    this.selectedTransactionItems.forEach(val => {
                         total += this.getItemPrice(val);
                    });
                    this.newTransaction.amount = total;
                }
            },

            selectAllAvailable() {
                this.selectedTransactionItems = this.availableItems.map(i => i.id);
                this.recalcAmount();
            },

            deselectAllTransactionItems() {
                this.selectedTransactionItems = [];
                this.recalcAmount();
            },

            recalcEditAmount() {
                 if (this.pricingMode === 'per_head') {
                    let total = 0;
                    this.selectedTransactionItems.forEach(val => {
                         total += this.getItemPrice(val);
                    });
                    // Update editingTransaction.amount dynamically
                    this.editingTransaction.amount = total;
                }
            },

            // --- Transaction Actions ---
            openAddModal() {
                this.selectedTransactionItems = [];
                this.newTransaction.amount = '';
                // Reset editingTransaction so availableItems treats every existing
                // transaction's items as "used" — without this, items from the last
                // transaction the user opened (Update Payment / Edit) would still
                // appear in the Select Employees list of the Add Installment modal.
                this.editingTransaction = { payments: [] };
                if(typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(this.$refs.addModal);
                    modal.show();
                }
            },

            addTransaction(shouldClose = false) {
                if (!this.activeGroupId) {
                    Swal.fire('Error', 'Please select a financial tab first.', 'error');
                    return;
                }
                this.isSavingTransaction = true;

                // Split selected IDs
                const itemIds = [];
                const employeeIds = [];

                this.selectedTransactionItems.forEach(val => {
                    if (String(val).startsWith('emp_')) {
                        employeeIds.push(val.replace('emp_', ''));
                    } else {
                        itemIds.push(val);
                    }
                });

                const payload = {
                    ...this.newTransaction,
                    financial_group_id: this.activeGroupId,
                    item_ids: itemIds,
                    employee_ids: employeeIds
                };

                fetch(`/production/${this.productionId}/transactions`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(data => {
                    if(data.success) {
                        this.transactions.push(data.transaction);

                        if (shouldClose) {
                            bootstrap.Modal.getOrCreateInstance(this.$refs.addModal).hide();
                        }

                        this.newTransaction = { type: 'installment', amount: '', due_date: '', notes: '', discount_amount: '', discount_description: '' };
                        this.selectedTransactionItems = [];

                        if (data.transaction.items) {
                            data.transaction.items.forEach(newItem => {
                                const exists = this.productionItems.find(pi => pi.id === newItem.id);
                                if (!exists) {
                                    this.productionItems.push({
                                        id: newItem.id,
                                        name: newItem.employee ? (newItem.employee.name_th || newItem.employee.name_en) : 'New Item',
                                        employee_id: newItem.employee_id
                                    });
                                }
                            });
                        }

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Transaction added successfully'
                        });
                    } else {
                         throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(err => Swal.fire('Error', err.message, 'error'))
                .finally(() => this.isSavingTransaction = false);
            },

            openPayModal(t) {
                this.editingTransaction = { ...t };
                this.editingTransaction.wht_status = t.wht_status || 'not_required';
                this.editingTransaction.withholding_tax_amount = t.withholding_tax_amount || '';
                this.editingTransaction.bank_account_id = t.bank_account_id || '';
                this.editingTransaction.wht_file = null;

                // Initialize default payment
                let effectiveAmount = t.amount - (parseFloat(t.discount_amount) || 0);
                let defaultAmount = (effectiveAmount - (t.paid_amount || 0)).toFixed(2);
                if (defaultAmount < 0) defaultAmount = 0;

                let today = new Date().toISOString().split('T')[0];
                this.editingPaymentId = null;
                this.newPayment = { amount: defaultAmount, paid_at: today, bank_account_id: '', notes: '', slip_path: null };
                this.paymentSlipFile = null;

                if (t.items) {
                    this.selectedTransactionItems = t.items.map(i => i.id);
                } else {
                    this.selectedTransactionItems = [];
                }
                this.selectedFile = null;
                bootstrap.Modal.getOrCreateInstance(this.$refs.payModal).show();
            },

            handleWhtFileSelect(e) {
                this.editingTransaction.wht_file = e.target.files[0];
            },

            handlePaymentSlipSelect(e) {
                this.paymentSlipFile = e.target.files[0];
            },

            addPayment() {
                if(!this.newPayment.amount || !this.newPayment.paid_at) {
                    Swal.fire('Warning', 'Amount and Date are required.', 'warning');
                    return;
                }

                this.isSavingPayment = true;
                const formData = new FormData();
                formData.append('amount', this.newPayment.amount);
                formData.append('paid_at', this.newPayment.paid_at);
                formData.append('bank_account_id', this.newPayment.bank_account_id || '');
                formData.append('notes', this.newPayment.notes || '');
                if (this.paymentSlipFile) {
                    formData.append('slip_file', this.paymentSlipFile);
                }

                fetch(`/production/transactions/${this.editingTransaction.id}/payments`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: formData
                })
                .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(data => {
                    if(data.success) {
                        const idx = this.transactions.findIndex(t => t.id === data.transaction.id);
                        if(idx !== -1) this.transactions[idx] = data.transaction;

                        this.editingTransaction = { ...data.transaction };

                        // Reset form
                        let effectiveAmt = data.transaction.amount - (parseFloat(data.transaction.discount_amount) || 0);
                        let defaultAmount = (effectiveAmt - (data.transaction.paid_amount || 0)).toFixed(2);
                        if (defaultAmount < 0) defaultAmount = 0;
                        this.editingPaymentId = null;
                        this.newPayment = { amount: defaultAmount, paid_at: new Date().toISOString().split('T')[0], bank_account_id: '', notes: '', slip_path: null };
                        this.paymentSlipFile = null;

                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Payment Added', showConfirmButton: false, timer: 3000 });
                    } else {
                        throw new Error(data.message || 'Error adding payment');
                    }
                })
                .catch(err => Swal.fire('Error', err.message, 'error'))
                .finally(() => {
                    this.isSavingPayment = false;
                    // Clear file input element เสมอ ทั้งใน success และ error path กัน file ค้าง
                    this.paymentSlipFile = null;
                    const fileInput = document.getElementById('paymentSlipInput');
                    if (fileInput) fileInput.value = '';
                });
            },

            editPayment(payment) {
                this.editingPaymentId = payment.id;
                this.newPayment = {
                    amount: payment.amount,
                    paid_at: payment.paid_at ? payment.paid_at.split('T')[0] : '',
                    bank_account_id: payment.bank_account_id || '',
                    notes: payment.notes || '',
                    slip_path: payment.slip_path || null
                };
                this.paymentSlipFile = null;
                // clear file input if possible
                const fileInput = document.getElementById('paymentSlipInput');
                if (fileInput) fileInput.value = '';
            },

            cancelEditPayment() {
                this.editingPaymentId = null;
                let effectiveAmt = this.editingTransaction.amount - (parseFloat(this.editingTransaction.discount_amount) || 0);
                let defaultAmount = (effectiveAmt - (this.editingTransaction.paid_amount || 0)).toFixed(2);
                if (defaultAmount < 0) defaultAmount = 0;
                this.newPayment = { amount: defaultAmount, paid_at: new Date().toISOString().split('T')[0], bank_account_id: '', notes: '', slip_path: null };
                this.paymentSlipFile = null;
                const fileInput = document.getElementById('paymentSlipInput');
                if (fileInput) fileInput.value = '';
            },

            updatePayment() {
                if(!this.newPayment.amount || !this.newPayment.paid_at) {
                    Swal.fire('Warning', 'Amount and Date are required.', 'warning');
                    return;
                }

                this.isSavingPayment = true;
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('amount', this.newPayment.amount);
                formData.append('paid_at', this.newPayment.paid_at);
                formData.append('bank_account_id', this.newPayment.bank_account_id || '');
                formData.append('notes', this.newPayment.notes || '');
                if (this.paymentSlipFile) {
                    formData.append('slip_file', this.paymentSlipFile);
                }

                fetch(`/production/payments/${this.editingPaymentId}`, {
                    method: 'POST', // Use POST with _method=PUT
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: formData
                })
                .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(data => {
                    if(data.success) {
                        const idx = this.transactions.findIndex(t => t.id === data.transaction.id);
                        if(idx !== -1) this.transactions[idx] = data.transaction;

                        this.editingTransaction = { ...data.transaction };

                        this.cancelEditPayment();

                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Payment Updated', showConfirmButton: false, timer: 3000 });
                    } else {
                        throw new Error(data.message || 'Error updating payment');
                    }
                })
                .catch(err => Swal.fire('Error', err.message, 'error'))
                .finally(() => this.isSavingPayment = false);
            },

            deletePayment(paymentId) {
                Swal.fire({
                    title: 'Delete this payment?',
                    text: 'This will revert the paid amount and bank balances.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/production/payments/${paymentId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                        })
                        .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                        .then(data => {
                            if(data.success) {
                                const idx = this.transactions.findIndex(t => t.id === data.transaction.id);
                                if(idx !== -1) this.transactions[idx] = data.transaction;
                                this.editingTransaction = { ...data.transaction };
                                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Payment Deleted', showConfirmButton: false, timer: 3000 });
                            } else {
                                throw new Error(data.message || 'Error deleting payment');
                            }
                        })
                        .catch(err => Swal.fire('Error', err.message, 'error'));
                    }
                });
            },

            updateTransaction() {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                if (this.editingTransaction.amount !== undefined) {
                    formData.append('amount', this.editingTransaction.amount);
                }
                formData.append('status', this.editingTransaction.status);
                formData.append('notes', this.editingTransaction.notes || '');
                formData.append('bank_account_id', this.editingTransaction.bank_account_id || '');
                formData.append('wht_status', this.editingTransaction.wht_status || 'not_required');
                formData.append('withholding_tax_amount', this.editingTransaction.withholding_tax_amount || '');
                formData.append('discount_amount', this.editingTransaction.discount_amount || 0);
                formData.append('discount_description', this.editingTransaction.discount_description || '');

                if (this.selectedFile) formData.append('slip_file', this.selectedFile);
                if (this.editingTransaction.wht_file) formData.append('wht_document', this.editingTransaction.wht_file);

                this.selectedTransactionItems.forEach(val => {
                    if (String(val).startsWith('emp_')) {
                        formData.append('employee_ids[]', val.replace('emp_', ''));
                    } else {
                        formData.append('item_ids[]', val);
                    }
                });

                fetch(`/production/transactions/${this.editingTransaction.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: formData
                })
                .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(data => {
                    if(data.success) {
                        const idx = this.transactions.findIndex(t => t.id === data.transaction.id);
                        if(idx !== -1) this.transactions[idx] = data.transaction;

                        this.editingTransaction = { ...data.transaction };

                        if (data.transaction.items) {
                            data.transaction.items.forEach(newItem => {
                                const exists = this.productionItems.find(pi => pi.id === newItem.id);
                                if (!exists) {
                                    this.productionItems.push({
                                        id: newItem.id,
                                        name: newItem.employee ? (newItem.employee.name_th || newItem.employee.name_en) : 'New Item',
                                        employee_id: newItem.employee_id
                                    });
                                }
                            });
                        }

                        const payModal = bootstrap.Modal.getInstance(this.$refs.payModal);
                        if (payModal) payModal.hide();

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Updated successfully'
                        });
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(err => Swal.fire('Error', err.message, 'error'));
            },

            // --- Groups Logic ---
            addNewGroup() {
                Swal.fire({
                    title: 'ตั้งชื่อแท็บการเงินใหม่',
                    text: 'แท็บใช้แยกประเภทการวางบิล เช่น ค่าบริการ MOU, ค่ามัดจำ, งวดเปลี่ยนนายจ้าง',
                    input: 'text',
                    inputValue: '',
                    inputPlaceholder: 'เช่น "ค่าบริการ MOU พม่า" หรือ "งวดเปลี่ยนนายจ้าง"',
                    inputAttributes: { maxlength: 100, autocapitalize: 'off', autocorrect: 'off' },
                    showCancelButton: true,
                    confirmButtonText: 'สร้างแท็บ',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#3b82f6',
                    inputValidator: (value) => {
                        const v = (value || '').trim();
                        if (!v) return 'กรุณาตั้งชื่อแท็บ';
                        if (v.length < 2) return 'ชื่อแท็บต้องยาวอย่างน้อย 2 ตัวอักษร';
                        if (this.financialGroups.some(g => (g.name || '').trim().toLowerCase() === v.toLowerCase())) {
                            return 'ชื่อแท็บนี้มีอยู่แล้ว — กรุณาเลือกชื่ออื่น';
                        }
                        return null;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const name = result.value.trim();
                        fetch(`/production/${this.productionId}/financial-groups`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ name: name })
                        })
                        .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                        .then(data => {
                            if (data.success) {
                                // Ensure reactivity and defaults for empty arrays
                                const newGroup = { ...data.group, transactions: [] };
                                this.financialGroups = [...this.financialGroups, newGroup];
                                this.switchGroup(newGroup.id);
                                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'สร้างแท็บ "' + name + '" แล้ว', showConfirmButton: false, timer: 2000 });
                            }
                        })
                        .catch(err => Swal.fire('Error', err.message, 'error'));
                    }
                });
            },

            deleteGroup(groupId) {
                 Swal.fire({
                    title: 'Delete Tab?',
                    text: "All transactions in this group will be deleted.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/production/${this.productionId}/financial-groups/${groupId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                        })
                        .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                        .then(data => {
                            if (data.success) {
                                this.financialGroups = this.financialGroups.filter(g => g.id !== groupId);
                                if (this.activeGroupId === groupId) {
                                    this.activeGroupId = this.financialGroups.length > 0 ? this.financialGroups[0].id : null;
                                    if(this.activeGroupId) this.switchGroup(this.activeGroupId);
                                }
                                Swal.fire('Deleted!', 'Tab has been deleted.', 'success');
                            }
                        });
                    }
                });
            },

            renameGroup(groupId, currentName) {
                Swal.fire({
                    title: 'เปลี่ยนชื่อแท็บ',
                    text: 'ชื่อปัจจุบัน: ' + (currentName || '(ยังไม่ตั้งชื่อ)'),
                    input: 'text',
                    inputValue: currentName || '',
                    inputPlaceholder: 'เช่น "ค่าบริการ MOU พม่า" หรือ "งวดเปลี่ยนนายจ้าง"',
                    inputAttributes: { maxlength: 100, autocapitalize: 'off', autocorrect: 'off' },
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#3b82f6',
                    inputValidator: (value) => {
                        const v = (value || '').trim();
                        if (!v) return 'กรุณาตั้งชื่อแท็บ';
                        if (v.length < 2) return 'ชื่อแท็บต้องยาวอย่างน้อย 2 ตัวอักษร';
                        if (v === (currentName || '').trim()) return 'ชื่อเดิม — กรุณาเปลี่ยนเป็นชื่อใหม่หรือกดยกเลิก';
                        if (this.financialGroups.some(g => g.id !== groupId && (g.name || '').trim().toLowerCase() === v.toLowerCase())) {
                            return 'ชื่อแท็บนี้มีอยู่แล้ว — กรุณาเลือกชื่ออื่น';
                        }
                        return null;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const newName = result.value.trim();
                        fetch(`/production/${this.productionId}/financial-groups/${groupId}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ name: newName })
                        })
                        .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                        .then(data => {
                            if (data.success) {
                                 const group = this.financialGroups.find(g => g.id === groupId);
                                 if (group) {
                                     group.name = newName;
                                     // Trigger reactivity
                                     this.financialGroups = [...this.financialGroups];
                                 }
                                 Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'เปลี่ยนชื่อเป็น "' + newName + '" แล้ว', showConfirmButton: false, timer: 2000 });
                            } else {
                                throw new Error(data.message || 'ไม่สามารถเปลี่ยนชื่อแท็บได้');
                            }
                        })
                        .catch(err => Swal.fire('Error', err.message, 'error'));
                    }
                });
            },

            // --- Pricing Logic ---
            addTier() {
                this.pricingTiers.push({ price: 0, count: 0, note: '', item_ids: [] });
            },
            removeTier(index) {
                const tier = this.pricingTiers[index];
                const tierLabel = 'งวด ' + (index + 1) + (tier && tier.price ? ' (' + Number(tier.price).toLocaleString() + ' ฿)' : '');
                const assigned = tier && tier.item_ids ? tier.item_ids.length : 0;
                let warnHtml = '<div class="text-start">คุณต้องการลบ <strong>' + tierLabel + '</strong> ออกจากการตั้งค่าราคา ใช่หรือไม่?</div>';
                if (assigned > 0) {
                    warnHtml += '<div class="alert alert-warning small mt-3 mb-0 py-2 text-start"><i class="bi bi-exclamation-triangle-fill me-1"></i><strong>' + assigned + ' คน</strong> จะถูกถอดออกจากงวดนี้ — ต้องไป assign ใหม่ภายหลัง</div>';
                }
                if (tier && tier.note && tier.note.trim()) {
                    warnHtml += '<div class="text-muted small mt-2 text-start"><i class="bi bi-chat-quote me-1"></i>หมายเหตุที่มีอยู่: <em>"' + tier.note.substring(0, 60) + (tier.note.length > 60 ? '...' : '') + '"</em></div>';
                }
                Swal.fire({
                    title: 'ลบงวดราคานี้?',
                    html: warnHtml,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash me-1"></i> ลบงวด',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    this.pricingTiers.splice(index, 1);
                    this.updateTotal();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'ลบ ' + tierLabel + ' แล้ว — อย่าลืมกด Save Pricing', showConfirmButton: false, timer: 2500 });
                });
            },
            editTierNote(index) {
                const tier = this.pricingTiers[index];
                if (!tier) return;
                const tierLabel = 'งวด ' + (index + 1) + (tier.price ? ' (' + Number(tier.price).toLocaleString() + ' ฿)' : '');
                const escapeHtml = (s) => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                Swal.fire({
                    title: 'หมายเหตุ — ' + tierLabel,
                    html: `
                        <div class="text-muted small mb-3 text-start px-1">
                            <i class="bi bi-info-circle me-1"></i>
                            หมายเหตุนี้จะแสดงในใบแจ้งหนี้ / ใบเสร็จ ที่ออกตามงวดนี้ด้วย
                        </div>
                        <textarea id="swal-tier-note"
                            class="swal2-textarea"
                            placeholder='เช่น "งวดมัดจำ 50%", "งวดเปลี่ยนนายจ้าง - ค่าใช้จ่ายเฉพาะ"'
                            maxlength="500"
                            style="width: 100%; min-height: 180px; max-height: 360px; padding: 12px 14px; font-size: 0.95rem; line-height: 1.5; resize: vertical; box-sizing: border-box; margin: 0; display: block;">${escapeHtml(tier.note || '')}</textarea>
                        <div class="d-flex justify-content-between small text-muted mt-2 px-1">
                            <span><i class="bi bi-keyboard me-1"></i>กด Ctrl+Enter เพื่อบันทึก</span>
                            <span><span id="swal-tier-note-count">0</span> / 500</span>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-check-lg me-1"></i> บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    width: '600px',
                    focusConfirm: false,
                    didOpen: () => {
                        const ta = document.getElementById('swal-tier-note');
                        const counter = document.getElementById('swal-tier-note-count');
                        if (ta) {
                            ta.focus();
                            const setCount = () => { if (counter) counter.textContent = ta.value.length; };
                            setCount();
                            ta.addEventListener('input', setCount);
                            ta.addEventListener('keydown', (e) => {
                                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                                    e.preventDefault();
                                    Swal.clickConfirm();
                                }
                            });
                        }
                    },
                    preConfirm: () => {
                        const ta = document.getElementById('swal-tier-note');
                        return ta ? ta.value : '';
                    }
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    const newNote = (result.value || '').trim();
                    tier.note = newNote;
                    // Trigger Alpine reactivity for in-place display refresh
                    this.pricingTiers = [...this.pricingTiers];
                    if (this.pricingTiers[index]) {
                        this.pricingTiers[index].note = newNote;
                    }
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'อัพเดตหมายเหตุแล้ว — อย่าลืมกด Save Pricing', showConfirmButton: false, timer: 2500 });
                });
            },
            get tierCountSum() {
                // Use item_ids.length for accurate count (including global invisible ones)
                return this.pricingTiers.reduce((sum, t) => sum + (t.item_ids ? t.item_ids.length : 0), 0);
            },

            // Manage Employees Modal Actions
            openManageEmployeesModal(index) {
                this.activeTierIndex = index;
                this.modalSearch = '';

                // Initialize selection with CURRENT TIER items
                // But filtered by visibility (only select items that are in the modal)
                const currentTierIds = this.pricingTiers[index].item_ids || [];
                const visibleIds = this.allEmployeesForTier.map(e => e.id);

                // Pre-select items that are currently in the tier AND visible in the modal
                this.modalSelectedIds = currentTierIds.filter(id => {
                    // Check if id exists in visibleIds (handle string/int conversion)
                    return visibleIds.includes(id) || visibleIds.includes(parseInt(id));
                });

                const el = document.getElementById('manageEmployeesModal-' + this.productionId);
                if (el && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(el);
                    modal.show();
                }
            },

            saveTierSelection() {
                if (this.activeTierIndex === null) return;
                this.assignItemsToTier(this.activeTierIndex, this.modalSelectedIds);

                const el = document.getElementById('manageEmployeesModal-' + this.productionId);
                if (el && typeof bootstrap !== 'undefined') {
                    const inst = bootstrap.Modal.getInstance(el);
                    if (inst) inst.hide();
                }
            },

            // --- Advance Logic ---
            addAdvanceItem() {
                this.advanceItems.push({ description: '', quantity: 1, unit_price: 0 });
            },
            removeAdvanceItem(index) {
                this.advanceItems.splice(index, 1);
                this.updateTotal();
            },

            updateTotal() {
                let gross = 0;
                if (this.pricingMode === 'per_head') {
                    gross = this.pricingTiers.reduce((sum, t) => {
                         const count = t.item_ids ? t.item_ids.length : (parseInt(t.count) || 0);
                         return sum + (parseFloat(t.price || 0) * count);
                    }, 0);
                } else {
                    gross = parseFloat(this.fixedTotal) || 0;
                }
                this.baseTotal = gross;

                let netBase = Math.max(0, gross - (parseFloat(this.discount) || 0));

                if (!this.vatEnabled) {
                    this.totalAmount = netBase;
                    this.subtotalAmount = netBase;
                    this.vatAmount = 0;
                } else if (this.vatIncluded) {
                    this.totalAmount = netBase;
                    this.subtotalAmount = netBase / (1 + (this.vatRate / 100));
                    this.vatAmount = this.totalAmount - this.subtotalAmount;
                } else {
                    this.subtotalAmount = netBase;
                    this.vatAmount = netBase * (this.vatRate / 100);
                    this.totalAmount = this.subtotalAmount + this.vatAmount;
                }

                if (this.whtEnabled) {
                    this.whtAmount = this.subtotalAmount * (this.whtRate / 100);
                } else {
                    this.whtAmount = 0;
                }

                this.netReceivable = this.totalAmount - this.whtAmount;

                this.advanceTotal = this.advanceItems.reduce((sum, item) => {
                    return sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0));
                }, 0);

                this.grandTotalReceivable = this.netReceivable + this.advanceTotal;
            },

            get filteredTransactions() {
                if (!this.activeGroupId) return [];
                return this.transactions.filter(t => t.production_financial_group_id == this.activeGroupId);
            },

            get modalFilteredTransactions() {
                if (this.documentTypeToGenerate === 'advance_receipt') {
                    return this.filteredTransactions.filter(t => t.type === 'advance_payment');
                }
                return this.filteredTransactions;
            },

            get incomeTransactions() {
                return this.filteredTransactions.filter(t => ['installment', 'down_payment', 'full_payment'].includes(t.type));
            },

            get advanceTransactions() {
                return this.filteredTransactions.filter(t => t.type === 'advance_payment');
            },

            get totalPaidAmount() {
                return this.filteredTransactions.reduce((sum, t) => sum + parseFloat(t.paid_amount || 0), 0);
            },
            get remainingBalance() {
                return Math.max(0, this.grandTotalReceivable - this.totalPaidAmount);
            },
            get advancePaid() {
                return this.advanceTransactions.reduce((sum, t) => sum + parseFloat(t.paid_amount || 0), 0);
            },
            get advanceRemaining() {
                return Math.max(0, this.advanceTotal - this.advancePaid);
            },
            get scheduledAmount() {
                return this.filteredTransactions.reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
            },
            get remainingSchedule() {
                return Math.max(0, this.grandTotalReceivable - this.scheduledAmount);
            },
            get isFullyScheduled() {
                return Math.abs(this.grandTotalReceivable - this.scheduledAmount) < 1;
            },
            get headerNameDisplay() {
                if (this.useCustomHeader) return this.customHeader.name || 'Custom Header';
                if (this.customHeader.biller_profile_id) return 'Selected Profile';
                return this.selectedProfileId ? 'Selected System Profile' : 'Default Profile';
            },
            get customerNameDisplay() {
                 return this.useCustomCustomer ? (this.customCustomerData.name || 'Custom Client') : 'Default (Employer)';
            },
            get customCustomer() {
                 return this.useCustomCustomer;
            },

            saveFinancialData() {
                if (!this.activeGroupId) {
                    Swal.fire('Error', 'Please select a financial tab first.', 'error');
                    return;
                }
                this.isSavingSettings = true;
                const payload = {
                    pricing_mode: this.pricingMode,
                    fixed_base_amount: this.fixedTotal,
                    pricing_tiers: this.pricingTiers,
                    discount: this.discount,
                    vat_enabled: this.vatEnabled,
                    vat_included: this.vatIncluded,
                    vat_rate: parseFloat(this.vatRate),
                    wht_enabled: this.whtEnabled,
                    wht_rate: this.whtRate,
                    advance_items: this.advanceItems,
                    custom_header: this.useCustomHeader ? this.customHeader : (this.customHeader.biller_profile_id || this.customHeader.customer_profile_id ? { biller_profile_id: this.customHeader.biller_profile_id, customer_profile_id: this.customHeader.customer_profile_id } : null),
                    profile_id: this.selectedProfileId,
                    customer_override: this.useCustomCustomer ? this.customCustomerData : null
                };

                fetch(`/production/${this.productionId}/financial-groups/${this.activeGroupId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(data => {
                    if (data.success) {
                        if (data.group) {
                            const idx = this.financialGroups.findIndex(g => g.id === this.activeGroupId);
                            if (idx !== -1) {
                                this.financialGroups[idx] = data.group;
                                this.switchGroup(this.activeGroupId);
                            }
                        }

                        if (data.new_items && Array.isArray(data.new_items)) {
                            data.new_items.forEach(newItem => {
                                const exists = this.productionItems.find(pi => pi.id === newItem.id);
                                if (!exists) {
                                    this.productionItems.push({
                                        id: newItem.id,
                                        name: newItem.name,
                                        name_en: newItem.name_en,
                                        title_en: newItem.title_en,
                                        photo: newItem.photo,
                                        nationality: newItem.nationality,
                                        employee_id: newItem.employee_id
                                    });
                                }
                            });
                        }

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Settings updated'
                        });
                    }
                })
                .catch(err => Swal.fire('Error', 'Failed to save settings', 'error'))
                .finally(() => this.isSavingSettings = false);
            },

            handleFileSelect(e) { this.selectedFile = e.target.files[0]; },
            deleteTransaction(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This transaction will be permanently deleted.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/production/transactions/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                        })
                        .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                        .then(data => {
                            if(data.success) {
                                this.transactions = this.transactions.filter(t => t.id !== id);
                                Swal.fire('Deleted!', 'Transaction has been deleted.', 'success');
                            } else {
                                throw new Error(data.message || 'Failed to delete transaction');
                            }
                        })
                        .catch(err => Swal.fire('Error', err.message, 'error'));
                    }
                });
            },
            formatCurrency(val) {
                return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(val);
            },
            formatDate(date) { return date ? new Date(date).toLocaleDateString('th-TH') : '-'; },
            formatType(type) {
                const map = {
                    installment: 'Installment (งวดงาน)',
                    down_payment: 'Down Payment (มัดจำ)',
                    full_payment: 'Full Payment (จ่ายเต็ม)',
                    advance_payment: 'Advance Payment (เงินสำรองจ่าย)'
                };
                return map[type] || type;
            },
            formatStatus(status) {
                const map = { pending: 'Pending', partial: 'Partial', paid: 'Paid', overdue: 'Overdue' };
                return map[status] || status;
            },
            statusClass(status) {
                const map = { pending: 'bg-secondary', partial: 'bg-warning text-dark', paid: 'bg-success', overdue: 'bg-danger' };
                return map[status] || 'bg-light text-dark';
            },
            openSelectionModal(type) {
                this.documentTypeToGenerate = type;
                this.selectedTransactionIds = [];
                this.includeEmployeeList = false;
                this.docVariant = 'invoice'; // reset เริ่มต้นเป็นเอกสารธรรมดา
                bootstrap.Modal.getOrCreateInstance(this.$refs.docSelectionModal).show();
            },
            openCreateInvoiceModal(t) {
                this.invoiceModalTransactionId = t.id;
                this.invoiceModalLabel = `${this.formatType(t.type)} — ${this.formatCurrency(t.amount)}`;
                this.invoiceModalIncludeList = false;
                this.invoiceModalVariant = 'invoice'; // reset เริ่มต้น

                // --- Reset payment-method state ---
                this.invoiceModalUsingCash = false;
                this.invoiceModalUsingTransfer = false;
                this.invoiceModalUsingPromptPay = false;
                this.invoiceModalPromptPayId = '';
                this.invoiceModalTransferList = [];
                this.invoiceModalBankPickerOpen = false;
                this.invoiceModalBankSearch = '';
                this.invoiceModalShowCustomBank = false;
                this.invoiceModalCustomBank = { bank_name:'', account_name:'', account_number:'' };
                this.invoiceModalProfileBanks = [];

                // Resolve which biller profile is in effect for this group so we
                // know whose bank accounts to offer in the picker.
                this.invoiceModalBillerProfileId = this.customHeader?.biller_profile_id || null;
                if (this.invoiceModalBillerProfileId) {
                    this.loadInvoiceProfileBanks();
                }
                // Lazy-load preset Thai banks once per session.
                if (this.invoiceModalThaiBanks.length === 0) {
                    this.loadInvoiceThaiBanks();
                }

                bootstrap.Modal.getOrCreateInstance(this.$refs.createInvoiceModal).show();
            },

            async loadInvoiceProfileBanks() {
                try {
                    const res = await fetch(`/finance/api/profiles/${this.invoiceModalBillerProfileId}/bank-accounts`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (res.ok) {
                        this.invoiceModalProfileBanks = await res.json();
                    }
                } catch (e) { console.error('loadInvoiceProfileBanks failed', e); }
            },

            async loadInvoiceThaiBanks() {
                // We don't have a dedicated endpoint; reuse the profile builder's
                // config by hitting a tiny meta endpoint. Fallback to empty if
                // missing — picker still works using just profile accounts + custom.
                try {
                    const res = await fetch('/finance/api/thai-banks', {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (res.ok) {
                        this.invoiceModalThaiBanks = await res.json();
                    }
                } catch (e) { /* optional resource — ignore */ }
            },

            // --- Picker getters ---
            get invoiceProfileTransferBanks() {
                return (this.invoiceModalProfileBanks || []).filter(a => a.bank_type !== 'promptpay');
            },
            get invoiceProfilePromptPays() {
                return (this.invoiceModalProfileBanks || []).filter(a => a.bank_type === 'promptpay');
            },
            get filteredInvoiceProfileBanks() {
                const q = (this.invoiceModalBankSearch || '').trim().toLowerCase();
                const list = this.invoiceProfileTransferBanks;
                if (!q) return list;
                return list.filter(a =>
                    (a.bank_name || '').toLowerCase().includes(q) ||
                    (a.account_name || '').toLowerCase().includes(q) ||
                    (a.account_number || '').toLowerCase().includes(q)
                );
            },
            get filteredInvoiceThaiBanks() {
                const q = (this.invoiceModalBankSearch || '').trim().toLowerCase();
                const list = this.invoiceModalThaiBanks || [];
                if (!q) return list;
                return list.filter(b =>
                    (b.name_th || '').toLowerCase().includes(q) ||
                    (b.name_en || '').toLowerCase().includes(q) ||
                    (b.code || '').toLowerCase().includes(q)
                );
            },

            invoiceBankBadge(item) {
                if (item.bank_code) {
                    const preset = (this.invoiceModalThaiBanks || []).find(b => b.code === item.bank_code);
                    if (preset) return { initial: preset.initial, color: preset.color };
                }
                if (item.bank_type === 'promptpay') return { initial: 'PP', color: '#1d4ed8' };
                const ch = (item.bank_name || '?').charAt(0).toUpperCase();
                return { initial: ch, color: '#6b7280' };
            },

            // --- Picker actions ---
            onInvoiceUsingTransferChange() {
                if (this.invoiceModalUsingTransfer && this.invoiceModalTransferList.length === 0) {
                    this.invoiceModalBankPickerOpen = true;
                    this.invoiceModalBankSearch = '';
                    this.invoiceModalShowCustomBank = false;
                } else if (!this.invoiceModalUsingTransfer) {
                    this.invoiceModalBankPickerOpen = false;
                    this.invoiceModalShowCustomBank = false;
                }
            },
            openInvoiceBankPicker() {
                this.invoiceModalBankPickerOpen = true;
                this.invoiceModalBankSearch = '';
                this.invoiceModalShowCustomBank = false;
            },
            closeInvoiceBankPicker() {
                this.invoiceModalBankPickerOpen = false;
                this.invoiceModalBankSearch = '';
            },
            addInvoiceProfileTransfer(acc) {
                this.invoiceModalTransferList.push({
                    bank_code: acc.bank_code || '',
                    bank_name: acc.bank_name || '',
                    account_name: acc.account_name || '',
                    account_number: acc.account_number || '',
                });
                this.invoiceModalBankPickerOpen = false;
                this.invoiceModalBankSearch = '';
            },
            addInvoicePresetTransfer(b) {
                this.invoiceModalTransferList.push({
                    bank_code: b.code,
                    bank_name: b.name_th,
                    account_name: '',
                    account_number: '',
                });
                this.invoiceModalBankPickerOpen = false;
                this.invoiceModalBankSearch = '';
            },
            openInvoiceCustomBank() {
                this.invoiceModalShowCustomBank = true;
                this.invoiceModalBankPickerOpen = false;
                this.invoiceModalCustomBank = { bank_name:'', account_name:'', account_number:'' };
            },
            addInvoiceCustomBank() {
                if (!this.invoiceModalCustomBank.bank_name.trim()) return;
                this.invoiceModalTransferList.push({
                    bank_code: '',
                    bank_name: this.invoiceModalCustomBank.bank_name.trim(),
                    account_name: this.invoiceModalCustomBank.account_name.trim(),
                    account_number: this.invoiceModalCustomBank.account_number.trim(),
                });
                this.invoiceModalShowCustomBank = false;
                this.invoiceModalCustomBank = { bank_name:'', account_name:'', account_number:'' };
            },
            removeInvoiceTransfer(idx) {
                this.invoiceModalTransferList.splice(idx, 1);
            },

            // --- Build payment_methods payload ---
            buildInvoicePaymentMethods() {
                const out = [];
                if (this.invoiceModalUsingCash) out.push({ type: 'cash' });
                if (this.invoiceModalUsingTransfer) {
                    this.invoiceModalTransferList.forEach(t => out.push({
                        type: 'transfer',
                        bank_code: t.bank_code || null,
                        bank_name: t.bank_name || null,
                        account_name: t.account_name || null,
                        account_number: t.account_number || null,
                    }));
                }
                if (this.invoiceModalUsingPromptPay && this.invoiceModalPromptPayId.trim()) {
                    out.push({ type: 'promptpay', promptpay_id: this.invoiceModalPromptPayId.trim() });
                }
                return out;
            },

            generateInvoiceFromButton() {
                if (!this.invoiceModalTransactionId) return;
                const includeList = this.invoiceModalVariant !== 'invoice';
                const listOnly = this.invoiceModalVariant === 'list_only';
                const paymentMethods = this.buildInvoicePaymentMethods();
                this.openDocument('invoice', String(this.invoiceModalTransactionId), null, includeList, listOnly, paymentMethods);
                const inst = bootstrap.Modal.getInstance(this.$refs.createInvoiceModal);
                if (inst) inst.hide();
            },
            generateSelectedDocument() {
                if (this.selectedTransactionIds.length === 0) return;
                const ids = this.selectedTransactionIds.join(',');
                let mode = null;
                if (this.documentTypeToGenerate === 'advance_receipt') {
                    mode = 'advance_only';
                }
                const includeList = this.docVariant !== 'invoice';
                const listOnly = this.docVariant === 'list_only';
                this.openDocument(this.documentTypeToGenerate, ids, mode, includeList, listOnly);
                const inst = bootstrap.Modal.getInstance(this.$refs.docSelectionModal);
                if (inst) inst.hide();
            },
            openDocument(type, transactionIds = null, mode = null, includeEmployeeList = false, listOnly = false, paymentMethods = null) {
                let url = `/production/${this.productionId}/documents/${type}?profile_id=${this.selectedProfileId}`;
                if (this.activeGroupId) {
                    url += `&group_id=${this.activeGroupId}`;
                }
                if (transactionIds) {
                    url += `&transaction_ids=${transactionIds}`;
                }
                if (mode) {
                    url += `&mode=${mode}`;
                }
                if (includeEmployeeList) {
                    url += `&include_employee_list=1`;
                }
                if (listOnly) {
                    url += `&list_only=1`;
                }
                if (Array.isArray(paymentMethods) && paymentMethods.length > 0) {
                    // Base64-encode the JSON so it survives URL transport even when
                    // bank names contain Thai chars / spaces / punctuation.
                    const json = JSON.stringify(paymentMethods);
                    const b64 = btoa(unescape(encodeURIComponent(json)));
                    url += `&payment_methods=${encodeURIComponent(b64)}`;
                }
                window.open(url, '_blank');
            },
            generatePaymentDocument(paymentId, type) {
                let url = `/production/${this.productionId}/documents/payment/${paymentId}/${type}?profile_id=${this.selectedProfileId}`;
                if (this.activeGroupId) {
                    url += `&group_id=${this.activeGroupId}`;
                }
                window.open(url, '_blank');

                // Optimistically update the UI to show it's generated
                const payment = this.editingTransaction.payments.find(p => p.id === paymentId);
                if (payment) {
                    payment.receipt_generated_at = new Date().toISOString();
                }
            },
            uploadLogo() {
                const input = this.$refs.logoInput;
                if (!input.files || input.files.length === 0) return;
                Swal.fire('Info', 'Please upload logo in Settings > Company Profiles first, then select it.', 'info');
            },
            saveAsNewProfile() {
                 Swal.fire('Info', 'Feature coming soon.', 'info');
            },

            get billedItemIds() {
                const ids = new Set();
                this.transactions.forEach(t => {
                    // Only count active transactions
                    if (t.status === 'cancelled') return;

                    if (t.items && Array.isArray(t.items)) {
                        t.items.forEach(i => ids.add(i.id));
                    }
                });
                return ids;
            },

            getTierRemainingCount(index) {
                const tier = this.pricingTiers[index];
                if (!tier || !tier.item_ids) return 0;
                // item_ids can be integers (ProductionItem) or strings (Candidate emp_X)
                // billedItemIds are always integers (from transactions)
                // So any 'emp_X' is automatically NOT billed (remains available)
                // Any integer ID is checked against the set
                const billed = this.billedItemIds;
                return tier.item_ids.filter(id => {
                    if (String(id).startsWith('emp_')) return true;
                    return !billed.has(parseInt(id));
                }).length;
            },

            get unassignedEmployeeCount() {
                // Calculate Total Unique People Pool (Local View Only)
                // We only care about employees visible in THIS screen for the count logic here,
                // OR we want the global unassigned?
                // The "Unassigned" warning should probably reflect the *active* screen context.
                // If I am in Pre-Prod, I want to know how many Pre-Prod people have no price.

                const linkedEmployeeIds = new Set();
                this.productionItems.forEach(pi => {
                    if (pi.employee_id) linkedEmployeeIds.add(pi.employee_id);
                });

                const uniqueCandidates = this.employees.filter(e => !linkedEmployeeIds.has(e.id)).length;
                const totalLocalPool = this.productionItems.length + uniqueCandidates;

                // Assigned Count Calculation (Local View)
                // We need to count how many LOCAL items are assigned to tiers.
                let localAssignedCount = 0;
                const localItemIds = new Set(this.productionItems.map(i => i.id));

                this.pricingTiers.forEach(t => {
                    if (t.item_ids) {
                        t.item_ids.forEach(id => {
                            if (localItemIds.has(parseInt(id))) localAssignedCount++;
                            else if (typeof id === 'string' && id.startsWith('emp_')) {
                                // Candidates are local by definition
                                localAssignedCount++;
                            }
                        });
                    }
                });

                return Math.max(0, totalLocalPool - localAssignedCount);
            },

            loadEmployerData() {
                if(!this.selectedEmployerAddressId) return;
                const select = document.querySelector(`select[x-model="selectedEmployerAddressId"]`);
                if(select) {
                    const option = select.options[select.selectedIndex];
                    const nameTh = option.dataset.nameTh || '';
                    const nameEn = option.dataset.nameEn || '';
                    const nameParts = [nameTh, nameEn].filter(n => n);

                    const addressTh = option.dataset.addressTh || '';
                    const addressEn = option.dataset.addressEn || '';
                    const addressParts = [addressTh, addressEn].filter(a => a);

                    this.customCustomerData.name = nameParts.length > 0 ? nameParts.join(' / ') : 'N/A';
                    this.customCustomerData.address = addressParts.join('\n');
                    this.customCustomerData.tax_id = option.dataset.taxId || '';
                    this.customCustomerData.phone = option.dataset.phone || '';
                    this.useCustomCustomer = true; // Automatically check the override box
                }
            },

            loadAgentData() {
                if(!this.selectedAgentId) return;
                const select = document.querySelector(`select[x-model="selectedAgentId"]`);
                if(select) {
                    const option = select.options[select.selectedIndex];
                    this.customCustomerData.name = option.dataset.name || '';
                    this.customCustomerData.phone = option.dataset.phone || '';
                }
            }
        }
    }
}
