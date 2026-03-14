
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
            productionItems: initialData.productionItems || [], // List of {id, name, employee_id}
            employees: initialData.employees || [], // List of candidates {id, name}
            selectedTransactionItems: [], // List of IDs (ProductionItem ID or 'emp_ID')

            // Tax Settings
            vatIncluded: false,
            vatRate: 7,
            whtEnabled: false,
            whtRate: 3,

            // Custom Header Data
            showCustomHeaderModal: false,
            useCustomHeader: false,
            customHeader: { name:'', address:'', tax_id:'', phone:'', logo:'' },
            selectedProfileId: '',

            // Custom Customer (Bill To) Data
            showCustomCustomerModal: false,
            useCustomCustomer: false,
            customCustomerData: { name:'', address:'', tax_id:'', phone:'' },
            selectedAgentId: '',

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
            newTransaction: { type: 'installment', amount: '', due_date: '', notes: '' },
            editingTransaction: {},
            selectedFile: null,

            // Separate Saving States
            isSavingSettings: false,
            isSavingTransaction: false,

            selectedTransactionIds: [],
            documentTypeToGenerate: '',

            // Context
            productionId: initialData.productionId,
            csrfToken: initialData.csrfToken,

            init() {
                if (this.financialGroups.length > 0) {
                    this.switchGroup(this.financialGroups[0].id);
                }
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

                this.vatIncluded = !!data.vat_included;
                this.vatRate = data.vat_rate !== undefined && data.vat_rate !== null && data.vat_rate !== '' ? parseFloat(data.vat_rate) : 7;
                this.whtEnabled = !!data.wht_enabled;
                this.whtRate = data.wht_rate || 3;

                this.useCustomHeader = !!data.custom_header;
                this.customHeader = data.custom_header || { name:'', address:'', tax_id:'', phone:'', logo:'' };
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
                            // Need to handle type safety carefully
                            return !processedIds.some(newId => newId == existingId);
                        });
                    }
                });

                // 2. Overwrite target tier with new selection
                const targetTier = this.pricingTiers[tierIndex];
                targetTier.item_ids = processedIds;

                // 3. Update count for display
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
            get tierCountSum() {
                // Use item_ids.length for accurate count
                return this.pricingTiers.reduce((sum, t) => sum + (t.item_ids ? t.item_ids.length : 0), 0);
            },

            get billedItemIds() {
                const ids = new Set();
                this.filteredTransactions.forEach(t => {
                    if (t.items && Array.isArray(t.items)) {
                        t.items.forEach(item => ids.add(item.id));
                    }
                });
                return ids;
            },

            get unassignedEmployeeCount() {
                const assignedIds = new Set();
                this.pricingTiers.forEach(t => {
                    if (t.item_ids) t.item_ids.forEach(id => assignedIds.add(id));
                });

                let count = 0;
                this.allEmployeesForTier.forEach(emp => {
                    if (!assignedIds.has(emp.id)) count++;
                });
                return count;
            },

            getTierRemainingCount(index) {
                const tier = this.pricingTiers[index];
                if (!tier || !tier.item_ids) return 0;

                const billedIds = this.billedItemIds;
                return tier.item_ids.filter(id => !billedIds.has(id)).length;
            },

            get allEmployeesForTier() {
                // Return Merged List for Price Tier Modal
                // V2.6 Update: We now allow showing ALL employees even if they are in a transaction,
                // but we might want to know they are "billed". However, for management, showing all is more flexible.

                const list = [];
                const itemsByEmpId = {};

                // 1. Production Items
                this.productionItems.forEach(item => {
                    if (item.employee_id) itemsByEmpId[item.employee_id] = item.id;
                    list.push({ ...item, type: 'item' });
                });

                // 2. Candidates
                this.employees.forEach(emp => {
                    if (itemsByEmpId[emp.id]) return; // Already exists as item

                    list.push({
                        id: 'emp_' + emp.id,
                        name: emp.name,
                        // Map properties from employee object to match item structure
                        name_en: emp.name_en,
                        title_en: emp.title_en,
                        photo: emp.photo,
                        nationality: emp.nationality,
                        employee_id: emp.id,
                        type: 'employee'
                    });
                });

                return list;
            },

            get availableItems() {
                if (!this.activeGroupId) return [];

                // 1. Collect Used IDs (ProductionItem IDs) in this Group
                const usedItemIds = this.billedItemIds;
                const usedEmployeeIds = new Set();
                this.filteredTransactions.forEach(t => {
                     if (this.editingTransaction.id && t.id === this.editingTransaction.id) return;
                     if (t.items) t.items.forEach(i => { if(i.employee_id) usedEmployeeIds.add(i.employee_id); });
                });

                const list = [];
                const itemsByEmpId = {}; // Map employee_id -> ProductionItem ID

                // 2. Add Existing Production Items
                this.productionItems.forEach(item => {
                    // Skip if used in another transaction in this group
                    if (this.editingTransaction.id) {
                        const isCurrentlyAttached = this.editingTransaction.items && this.editingTransaction.items.some(i => i.id === item.id);
                        if (!isCurrentlyAttached && usedItemIds.has(item.id)) return;
                    } else if (usedItemIds.has(item.id)) {
                        return;
                    }

                    // V2.6 Update: Employees MUST have an assigned price to be billed in Per Head mode
                    let hasPrice = true;
                    if (this.pricingMode === 'per_head') {
                         hasPrice = !!this.getTierForItem(item.id);
                    }

                    if (this.pricingMode === 'per_head' && !hasPrice) return;

                    if (item.employee_id) {
                        itemsByEmpId[item.employee_id] = item.id;
                    }

                    list.push({
                        id: item.id, // Value
                        name: item.name,
                        photo: item.photo,
                        name_en: item.name_en,
                        title_en: item.title_en,
                        nationality: item.nationality,
                        type: 'item'
                    });
                });

                // 3. Add Candidates (Employees)
                // Only show candidates if NOT in per_head mode (since they can't have a price yet)
                if (this.pricingMode !== 'per_head') {
                    this.employees.forEach(emp => {
                        // Check if this employee is already represented by an existing Production Item
                        if (itemsByEmpId[emp.id]) return;
                        // Check if already used in another transaction
                        if (usedEmployeeIds.has(emp.id)) return;

                        list.push({
                            id: 'emp_' + emp.id, // Value with prefix to distinguish
                            name: emp.name,
                            photo: emp.photo,
                            name_en: emp.name_en,
                            title_en: emp.title_en,
                            nationality: emp.nationality,
                            type: 'employee'
                        });
                    });
                }

                return list;
            },

            get editModalItems() {
                // Show items that are available OR attached to this transaction
                if (!this.activeGroupId) return [];

                // IDs currently attached to editing transaction
                const attachedItemIds = new Set();
                if (this.editingTransaction.items && Array.isArray(this.editingTransaction.items)) {
                    this.editingTransaction.items.forEach(item => attachedItemIds.add(item.id));
                }

                // IDs used by OTHER transactions
                const usedItemIds = new Set();
                const usedEmployeeIds = new Set();

                this.filteredTransactions.forEach(t => {
                    if (this.editingTransaction.id && t.id === this.editingTransaction.id) return;
                    if (t.items && Array.isArray(t.items)) {
                        t.items.forEach(item => {
                            usedItemIds.add(item.id);
                            if(item.employee_id) usedEmployeeIds.add(item.employee_id);
                        });
                    }
                });

                const list = [];
                const itemsByEmpId = {};

                // 1. Production Items
                this.productionItems.forEach(item => {
                    if (item.employee_id) itemsByEmpId[item.employee_id] = item.id;

                    const isAttached = attachedItemIds.has(item.id);
                    const isUsed = usedItemIds.has(item.id); // Used elsewhere

                    if (isAttached || !isUsed) {
                         // V2.6 Per Head Filter
                         let hasPrice = true;
                         if (this.pricingMode === 'per_head') {
                            hasPrice = !!this.getTierForItem(item.id);
                         }
                         if (this.pricingMode === 'per_head' && !hasPrice && !isAttached) return;

                         list.push({
                            id: item.id,
                            name: item.name,
                            photo: item.photo,
                            name_en: item.name_en,
                            title_en: item.title_en,
                            nationality: item.nationality,
                            type: 'item',
                            attached: isAttached
                        });
                    }
                });

                // 2. Candidates
                if (this.pricingMode !== 'per_head') {
                    this.employees.forEach(emp => {
                        if (itemsByEmpId[emp.id]) return; // Already has item
                        if (usedEmployeeIds.has(emp.id)) return; // Used elsewhere

                        list.push({
                            id: 'emp_' + emp.id,
                            name: emp.name,
                            photo: emp.photo,
                            name_en: emp.name_en,
                            title_en: emp.title_en,
                            nationality: emp.nationality,
                            type: 'employee',
                            attached: false
                        });
                    });
                }

                return list;
            },

            isItemAttached(itemId) {
                if (!this.editingTransaction.items) return false;
                // Only check numeric IDs for attachment status as candidates are never "attached" until saved
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

            recalcEditAmount() {
                if (this.pricingMode === 'per_head') {
                    let total = 0;
                    this.selectedTransactionItems.forEach(val => {
                         total += this.getItemPrice(val);
                    });
                    this.editingTransaction.amount = total;
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

            // --- Transaction Actions ---
            openAddModal() {
                this.selectedTransactionItems = [];
                this.newTransaction.amount = '';
                if(typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(this.$refs.addModal);
                    modal.show();
                }
            },

            addTransaction() {
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
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        this.transactions.push(data.transaction);
                        bootstrap.Modal.getOrCreateInstance(this.$refs.addModal).hide();
                        this.newTransaction = { type: 'installment', amount: '', due_date: '', notes: '' };
                        this.selectedTransactionItems = [];

                        // Update local productionItems list with potentially newly created items
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

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Transaction added successfully',
                            timer: 1500,
                            showConfirmButton: false
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
                if (t.items) {
                    this.selectedTransactionItems = t.items.map(i => i.id);
                } else {
                    this.selectedTransactionItems = [];
                }
                this.selectedFile = null;
                bootstrap.Modal.getOrCreateInstance(this.$refs.payModal).show();
            },

            updateTransaction() {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('paid_amount', this.editingTransaction.paid_amount || 0);
                formData.append('amount', this.editingTransaction.amount); // V2.6: Allow amount update from items
                formData.append('status', this.editingTransaction.status);
                formData.append('notes', this.editingTransaction.notes || '');
                if (this.selectedFile) formData.append('slip_file', this.selectedFile);

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
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const idx = this.transactions.findIndex(t => t.id === data.transaction.id);
                        if(idx !== -1) this.transactions[idx] = data.transaction;

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

                        bootstrap.Modal.getInstance(this.$refs.payModal).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Updated successfully',
                            timer: 1500,
                            showConfirmButton: false
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
                    title: 'Add Tab',
                    input: 'text',
                    inputValue: 'New Tab',
                    showCancelButton: true,
                    confirmButtonText: 'Add'
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        fetch(`/production/${this.productionId}/financial-groups`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ name: result.value })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.financialGroups.push(data.group);
                                this.switchGroup(data.group.id);
                            }
                        });
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
                        .then(res => res.json())
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
                    title: 'Rename Tab',
                    input: 'text',
                    inputValue: currentName,
                    showCancelButton: true,
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        fetch(`/production/${this.productionId}/financial-groups/${groupId}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ name: result.value })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                 const group = this.financialGroups.find(g => g.id === groupId);
                                 if(group) group.name = result.value;
                            }
                        });
                    }
                });
            },

            // --- Pricing Logic ---
            addTier() {
                this.pricingTiers.push({ price: 0, count: 0, note: '', item_ids: [] });
            },
            removeTier(index) {
                this.pricingTiers.splice(index, 1);
                this.updateTotal();
            },

            // Manage Employees Modal Actions
            openManageEmployeesModal(index) {
                this.activeTierIndex = index;
                this.modalSearch = '';
                this.modalSelectedIds = [...(this.pricingTiers[index].item_ids || [])];

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
                    bootstrap.Modal.getInstance(el).hide();
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
                         const count = t.item_ids ? t.item_ids.length : (parseInt(t.count) || 0); // Fallback for legacy
                         return sum + (parseFloat(t.price || 0) * count);
                    }, 0);
                } else {
                    gross = parseFloat(this.fixedTotal) || 0;
                }
                this.baseTotal = gross;

                let netBase = Math.max(0, gross - (parseFloat(this.discount) || 0));

                if (this.vatIncluded) {
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

            get scheduledAmount() {
                return this.filteredTransactions.reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
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
            get remainingSchedule() {
                return Math.max(0, this.grandTotalReceivable - this.scheduledAmount);
            },
            get isFullyScheduled() {
                return Math.abs(this.grandTotalReceivable - this.scheduledAmount) < 1;
            },
            get headerNameDisplay() {
                if (this.useCustomHeader) return this.customHeader.name || 'Custom Header';
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
                    vat_included: this.vatIncluded,
                    vat_rate: parseFloat(this.vatRate),
                    wht_enabled: this.whtEnabled,
                    wht_rate: this.whtRate,
                    advance_items: this.advanceItems,
                    custom_header: this.useCustomHeader ? this.customHeader : null,
                    profile_id: this.selectedProfileId,
                    customer_override: this.useCustomCustomer ? this.customCustomerData : null
                };

                fetch(`/production/${this.productionId}/financial-groups/${this.activeGroupId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.group) {
                            // Update local group data to match server (especially ID conversions)
                            const idx = this.financialGroups.findIndex(g => g.id === this.activeGroupId);
                            if (idx !== -1) {
                                this.financialGroups[idx] = data.group;
                                // Re-sync active state without full reset if possible, or just reload data
                                this.switchGroup(this.activeGroupId);
                            }
                        }

                        // Add new items (converted from candidates) to productionItems list
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

                        Swal.fire({ icon: 'success', title: 'Saved', text: 'Settings updated.', timer: 1000, showConfirmButton: false });
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
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                this.transactions = this.transactions.filter(t => t.id !== id);
                                Swal.fire('Deleted!', 'Transaction has been deleted.', 'success');
                            }
                        });
                    }
                });
            },
            formatCurrency(val) {
                return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(val);
            },
            formatDate(date) { return date ? new Date(date).toLocaleDateString('th-TH') : '-'; },
            formatType(type) {
                const map = {
                    installment: window.trans('Installment (งวดงาน)'),
                    down_payment: window.trans('Down Payment (มัดจำ)'),
                    full_payment: window.trans('Full Payment (จ่ายเต็ม)'),
                    advance_payment: window.trans('Advance Payment (เงินสำรองจ่าย)')
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
                bootstrap.Modal.getOrCreateInstance(this.$refs.docSelectionModal).show();
            },
            generateSelectedDocument() {
                if (this.selectedTransactionIds.length === 0) return;
                const ids = this.selectedTransactionIds.join(',');
                let mode = null;
                if (this.documentTypeToGenerate === 'advance_receipt') {
                    mode = 'advance_only';
                }
                this.openDocument(this.documentTypeToGenerate, ids, mode);
                bootstrap.Modal.getInstance(this.$refs.docSelectionModal).hide();
            },
            openDocument(type, transactionIds = null, mode = null) {
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
                window.open(url, '_blank');
            },
            uploadLogo() {
                const input = this.$refs.logoInput;
                if (!input.files || input.files.length === 0) return;
                Swal.fire('Info', 'Please upload logo in Settings > Company Profiles first, then select it.', 'info');
            },
            saveAsNewProfile() {
                 Swal.fire('Info', 'Feature coming soon.', 'info');
            },
            loadAgentData() {
                if(!this.selectedAgentId) return;
                // Basic stub if we don't have an endpoint for this yet
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
