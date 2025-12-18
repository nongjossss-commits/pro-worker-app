
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
            totalAmount: 0,
            baseTotal: 0,
            subtotalAmount: 0,
            vatAmount: 0,
            whtAmount: 0,
            netReceivable: 0,

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
                } else {
                    // Safety fallback: If no groups exist, user can add one.
                    // Or auto-create?
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
                this.pricingTiers = data.pricing_tiers || [];
                this.discount = data.discount || 0;

                this.vatIncluded = !!data.vat_included;
                this.vatRate = data.vat_rate || 7;
                this.whtEnabled = !!data.wht_enabled;
                this.whtRate = data.wht_rate || 3;

                this.useCustomHeader = !!data.custom_header;
                this.customHeader = data.custom_header || { name:'', address:'', tax_id:'', phone:'', logo:'' };
                this.selectedProfileId = data.profile_id || '';

                this.useCustomCustomer = !!data.customer_override;
                this.customCustomerData = data.customer_override || { name:'', address:'', tax_id:'', phone:'' };

                // Default tier if empty
                if (this.pricingMode === 'per_head' && this.pricingTiers.length === 0) {
                    this.pricingTiers.push({ price: 0, count: this.employeeCount, note: 'Standard Price' });
                }

                this.updateTotal();
            },

            addNewGroup() {
                Swal.fire({
                    title: 'Add Tab',
                    input: 'text',
                    inputLabel: 'Enter new tab name',
                    inputValue: 'New Tab',
                    showCancelButton: true,
                    confirmButtonText: 'Add',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const name = result.value;
                        fetch(`/admin/production/production/${this.productionId}/financial-groups`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ name: name })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.financialGroups.push(data.group);
                                this.switchGroup(data.group.id); // Switch to new group
                            }
                        });
                    }
                });
            },

            deleteGroup(groupId) {
                 Swal.fire({
                    title: 'Are you sure you want to delete this tab?',
                    text: "All transactions in this group will be deleted.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/admin/production/production/${this.productionId}/financial-groups/${groupId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Remove from local array
                                this.financialGroups = this.financialGroups.filter(g => g.id !== groupId);
                                // Switch to first group if active one was deleted
                                if (this.activeGroupId === groupId) {
                                    if (this.financialGroups.length > 0) {
                                        this.switchGroup(this.financialGroups[0].id);
                                    } else {
                                        this.activeGroupId = null;
                                    }
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
                        fetch(`/admin/production/production/${this.productionId}/financial-groups/${groupId}`, {
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
                this.pricingTiers.push({ price: 0, count: 0, note: '' });
            },
            removeTier(index) {
                this.pricingTiers.splice(index, 1);
                this.updateTotal();
            },
            get tierCountSum() {
                return this.pricingTiers.reduce((sum, t) => sum + parseInt(t.count || 0), 0);
            },

            updateTotal() {
                // 1. Calculate Gross Base
                let gross = 0;
                if (this.pricingMode === 'per_head') {
                    gross = this.pricingTiers.reduce((sum, t) => sum + (parseFloat(t.price || 0) * parseFloat(t.count || 0)), 0);
                } else {
                    gross = parseFloat(this.fixedTotal) || 0;
                }
                this.baseTotal = gross;

                // 2. Apply Discount
                let netBase = Math.max(0, gross - (parseFloat(this.discount) || 0));

                // 3. VAT Logic
                if (this.vatIncluded) {
                    // Formula: NetBase = Total (Inc VAT)
                    // Subtotal (Ex VAT) = Total / (1 + Rate)
                    this.totalAmount = netBase;
                    this.subtotalAmount = netBase / (1 + (this.vatRate / 100));
                    this.vatAmount = this.totalAmount - this.subtotalAmount;
                } else {
                    // Formula: NetBase = Subtotal (Ex VAT)
                    // Total = Subtotal + VAT
                    this.subtotalAmount = netBase;
                    this.vatAmount = netBase * (this.vatRate / 100);
                    this.totalAmount = this.subtotalAmount + this.vatAmount;
                }

                // 4. WHT Logic (Standard: Calculated on Base Amount before VAT)
                if (this.whtEnabled) {
                    this.whtAmount = this.subtotalAmount * (this.whtRate / 100);
                } else {
                    this.whtAmount = 0;
                }

                // 5. Net Receivable (Total - WHT)
                this.netReceivable = this.totalAmount - this.whtAmount;
            },

            get filteredTransactions() {
                if (!this.activeGroupId) return [];
                return this.transactions.filter(t => t.production_financial_group_id == this.activeGroupId);
            },

            get scheduledAmount() {
                return this.filteredTransactions.reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
            },
            get remainingSchedule() {
                return Math.max(0, this.totalAmount - this.scheduledAmount);
            },
            get isFullyScheduled() {
                return Math.abs(this.totalAmount - this.scheduledAmount) < 1;
            },
            get headerNameDisplay() {
                if (this.useCustomHeader) {
                    return this.customHeader.name || 'Custom Header';
                }
                return this.selectedProfileId ? 'Selected System Profile' : 'Default Profile';
            },

            get customerNameDisplay() {
                 return this.useCustomCustomer ? (this.customCustomerData.name || 'Custom Client') : 'Default (Employer)';
            },

            get customCustomer() {
                 return this.useCustomCustomer;
            },

            // --- Save Logic ---
            saveFinancialData() {
                if (!this.activeGroupId) return;

                this.isSavingSettings = true;

                const payload = {
                    financial: {
                        pricing_mode: this.pricingMode,
                        fixed_base_amount: this.fixedTotal,
                        pricing_tiers: this.pricingTiers,
                        discount: this.discount,

                        vat_included: this.vatIncluded,
                        vat_rate: this.vatRate,
                        wht_enabled: this.whtEnabled,
                        wht_rate: this.whtRate,

                        total_amount: this.totalAmount,

                        // Header Data
                        custom_header: this.useCustomHeader ? this.customHeader : null,
                        profile_id: this.selectedProfileId,

                        // Customer Override Data
                        customer_override: this.useCustomCustomer ? this.customCustomerData : null
                    },
                    financial_group_id: this.activeGroupId,
                    _method: 'PUT'
                };

                fetch(`/admin/production/production/${this.productionId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    this.isSavingSettings = false;
                    const group = this.financialGroups.find(g => g.id === this.activeGroupId);
                    if (group) {
                        group.financial_data = payload.financial;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Settings Saved',
                        showConfirmButton: false,
                        timer: 1500,
                        toast: true,
                        position: 'top-end'
                    });
                })
                .catch(err => {
                    this.isSavingSettings = false;
                    Swal.fire('Error', 'Error saving data', 'error');
                });
            },

            // --- Agent Logic ---
            loadAgentData() {
                if (!this.selectedAgentId) return;
                // Need to find the selected option text in a way that works inside Alpine
                // We'll trust the user to have bound the data attributes or fetch via ID if needed.
                // Simplified: use a map or lookup if options aren't easily accessible via $el.
                // Workaround: We will use a lookup object passed in or fetch it.
                // For now, let's assume the DOM logic in the view still works if we use $refs or querySelector relative to $el
                // But $el scope is the whole component.
                // Let's use a simpler approach: Just use the ID and fetch, or rely on the view to have populated a data object.
                // Actually, the view logic `const select = this.$el.querySelector('select')` works fine if we bind correctly.

                // Let's try to get the element via document.querySelector inside the modal (scoped)
                // Or better, just fetch the agent details? No, let's stick to DOM reading if it worked.
                const select = document.querySelector(`#customCustomerModal-${this.productionId} select`);
                // We need to ensure unique IDs for modals!

                if(select) {
                    const option = select.options[select.selectedIndex];
                    const name = option.getAttribute('data-name');
                    const phone = option.getAttribute('data-phone');

                    this.customCustomerData.name = name;
                    this.customCustomerData.phone = phone;
                    this.customCustomerData.address = '';
                    this.customCustomerData.tax_id = '';

                    this.useCustomCustomer = true;
                    Swal.fire({ icon: 'success', title: 'Agent Data Loaded', timer: 1500, showConfirmButton: false });
                }
            },

            // --- Profile Logic ---
            saveAsNewProfile() {
                if (!this.customHeader.name) {
                    Swal.fire('Error', 'Please enter a Company Name', 'error');
                    return;
                }

                fetch('/admin/settings/financial', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(this.customHeader)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', 'Profile Saved!', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                         Swal.fire('Error', 'Failed to save profile', 'error');
                    }
                });
            },

            // --- Logo Upload ---
            uploadLogo() {
                // Unique ref needed? `x-ref="logoInput"` works within the component scope.
                const fileInput = this.$el.querySelector('input[type="file"]'); // Quick hack if ref fails
                // Better: use x-ref="logoInput" and access via this.$refs.logoInput
                // But $refs might be tricky if inside a loop.
                // Let's assume standard behavior.
                // Actually, inside the loop, refs are scoped to the `x-data` root.

                const file = this.$refs.logoInput ? this.$refs.logoInput.files[0] : null;
                if (!file) return;

                const formData = new FormData();
                formData.append('logo', file);

                fetch(`/admin/production/production/${this.productionId}/upload-logo`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.customHeader.logo = data.path;
                        Swal.fire('Success', 'Logo uploaded!', 'success');
                    } else {
                        Swal.fire('Error', 'Upload failed', 'error');
                    }
                });
            },

            // --- Transactions ---
            openAddModal() {
                if(typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(this.$refs.addModal);
                    modal.show();
                }
            },
            addTransaction() {
                if (!this.activeGroupId) return;
                this.isSavingTransaction = true;
                const payload = { ...this.newTransaction, financial_group_id: this.activeGroupId };

                fetch(`/admin/production/production/${this.productionId}/transactions`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => {
                    if (!res.ok) {
                         // Check if response is JSON, else throw text
                         const contentType = res.headers.get("content-type");
                         if (contentType && contentType.indexOf("application/json") !== -1) {
                             return res.json().then(err => { throw new Error(err.message || 'Server Error'); });
                         } else {
                             return res.text().then(text => { throw new Error(text || 'Server Error'); });
                         }
                    }
                    return res.json();
                })
                .then(data => {
                    if(data.success) {
                        this.transactions.push(data.transaction);
                        bootstrap.Modal.getOrCreateInstance(this.$refs.addModal).hide();
                        this.newTransaction = { type: 'installment', amount: '', due_date: '', notes: '' };
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
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.message || 'Failed to add transaction. Please check your inputs.',
                    });
                })
                .finally(() => this.isSavingTransaction = false);
            },
            openPayModal(t) {
                this.editingTransaction = { ...t };
                this.selectedFile = null;
                bootstrap.Modal.getOrCreateInstance(this.$refs.payModal).show();
            },
            handleFileSelect(e) { this.selectedFile = e.target.files[0]; },
            updateTransaction() {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('paid_amount', this.editingTransaction.paid_amount);
                formData.append('status', this.editingTransaction.status);
                if (this.selectedFile) formData.append('slip_file', this.selectedFile);

                fetch(`/admin/production/production/transactions/${this.editingTransaction.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: formData
                })
                .then(res => {
                     if (!res.ok) {
                         const contentType = res.headers.get("content-type");
                         if (contentType && contentType.indexOf("application/json") !== -1) {
                             return res.json().then(err => { throw new Error(err.message || 'Server Error'); });
                         } else {
                             return res.text().then(text => { throw new Error(text || 'Server Error'); });
                         }
                    }
                    return res.json();
                })
                .then(data => {
                    if(data.success) {
                        const idx = this.transactions.findIndex(t => t.id === data.transaction.id);
                        if(idx !== -1) this.transactions[idx] = data.transaction;
                        bootstrap.Modal.getInstance(this.$refs.payModal).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Payment updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: err.message || 'Could not update transaction.',
                    });
                });
            },
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
                        fetch(`/admin/production/production/transactions/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                this.transactions = this.transactions.filter(t => t.id !== id);
                                Swal.fire('Deleted!', 'Transaction has been deleted.', 'success');
                            } else {
                                Swal.fire('Error', data.message || 'Failed to delete', 'error');
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
                const map = { installment: 'Installment', down_payment: 'Down Payment', full_payment: 'Full Payment' };
                return map[type] || type;
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
                this.openDocument(this.documentTypeToGenerate, ids);
                bootstrap.Modal.getInstance(this.$refs.docSelectionModal).hide();
            },
            openDocument(type, transactionIds = null) {
                let url = `/admin/production/production/${this.productionId}/documents/${type}?profile_id=${this.selectedProfileId}`;
                if (this.activeGroupId) {
                    url += `&group_id=${this.activeGroupId}`;
                }
                if (transactionIds) {
                    url += `&transaction_ids=${transactionIds}`;
                }
                window.open(url, '_blank');
            }
        }
    }
}
