<div x-data="financialManager()" class="row">
    <!-- Left Column: Summary & Pricing Logic -->
    <div class="col-md-4">

        <!-- Pricing Logic Card (NEW) -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-calculator me-2"></i>Pricing Settings</div>
            <div class="card-body">
                <!-- Mode Selection -->
                <div class="mb-3">
                    <label class="form-label small text-muted">Pricing Mode</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="pricing_mode" id="mode_fixed" value="fixed" x-model="pricingMode" @change="updateTotal()">
                        <label class="btn btn-outline-primary btn-sm" for="mode_fixed">Fixed Total</label>

                        <input type="radio" class="btn-check" name="pricing_mode" id="mode_per_head" value="per_head" x-model="pricingMode" @change="updateTotal()">
                        <label class="btn btn-outline-primary btn-sm" for="mode_per_head">Per Head</label>
                    </div>
                </div>

                <!-- Inputs based on Mode -->
                <div x-show="pricingMode === 'per_head'" class="mb-3">
                    <label class="form-label">Unit Price (Per Head)</label>
                    <div class="input-group">
                        <span class="input-group-text">฿</span>
                        <input type="number" class="form-control" x-model="unitPrice" @input="updateTotal()">
                    </div>
                    <div class="form-text small">
                        Multiplied by <strong x-text="employeeCount"></strong> employees = <span x-text="formatCurrency(calculatedTotal)"></span>
                    </div>
                </div>

                <div x-show="pricingMode === 'fixed'" class="mb-3">
                    <label class="form-label">Total Project Value (Before VAT)</label>
                    <div class="input-group">
                        <span class="input-group-text">฿</span>
                        <input type="number" class="form-control" x-model="fixedTotal" @input="updateTotal()">
                    </div>
                </div>

                <!-- VAT Settings -->
                <div class="mb-3 border-top pt-3">
                    <label class="form-label small text-muted">VAT Settings</label>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="vatIncluded" x-model="vatIncluded" @change="updateTotal()">
                        <label class="form-check-label" for="vatIncluded">Price Includes VAT</label>
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">VAT Rate</span>
                        <input type="number" step="0.1" class="form-control" x-model="vatRate" @input="updateTotal()">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <!-- Save Pricing Button -->
                <button class="btn btn-primary btn-sm w-100" @click="saveFinancialData()" :disabled="isSaving">
                    <i class="bi bi-save me-1"></i> Save Pricing Settings
                </button>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold">Financial Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal:</span>
                    <span x-text="formatCurrency(subtotalAmount)"></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">VAT (<span x-text="vatRate"></span>%):</span>
                    <span x-text="formatCurrency(vatAmount)"></span>
                </div>
                <div class="d-flex justify-content-between mb-2 border-top pt-2">
                    <span class="fw-bold">Grand Total:</span>
                    <span class="fw-bold text-primary" x-text="formatCurrency(totalAmount)"></span>
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Scheduled:</span>
                    <span x-text="formatCurrency(scheduledAmount)" :class="{'text-success': isFullyScheduled, 'text-warning': !isFullyScheduled}"></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Remaining to Schedule:</span>
                    <span x-text="formatCurrency(remainingSchedule)" class="text-danger fw-bold"></span>
                </div>
                <div class="d-flex justify-content-between mb-2 border-top pt-2">
                    <span class="text-muted">Paid Amount:</span>
                    <span x-text="formatCurrency(totalPaid)" class="text-success fw-bold"></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Outstanding Balance:</span>
                    <span x-text="formatCurrency(outstandingBalance)" class="text-danger"></span>
                </div>
            </div>
        </div>

        <!-- Document Center -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-printer me-2"></i>Document Center</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Select Bill Header</label>
                    <select class="form-select form-select-sm" x-model="selectedProfile">
                        <option value="">Default Company Profile</option>
                        @foreach(\App\Models\CompanyProfile::all() as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button @click="openDocument('quotation')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-file-earmark-text me-2"></i>Quotation (ใบเสนอราคา)
                    </button>
                    <!-- Invoice Button with Modal Trigger -->
                    <button @click="openSelectionModal('invoice')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-receipt me-2"></i>Invoice (ใบแจ้งหนี้)
                    </button>
                    <!-- Receipt Button with Modal Trigger -->
                    <button @click="openSelectionModal('receipt')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-check-circle me-2"></i>Receipt (ใบเสร็จรับเงิน)
                    </button>
                </div>
            </div>
        </div>

         <!-- Refund / Credit Note Section (New) -->
         <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold text-danger"><i class="bi bi-arrow-return-left me-2"></i>Refund / Credit Note</div>
            <div class="card-body">
                <div class="mb-2 small text-muted">Use this if actual delivered count is less than paid.</div>

                <div class="mb-3">
                    <label class="form-label small">Actual Delivered Count</label>
                    <input type="number" class="form-control form-control-sm" x-model="actualDeliveredCount">
                </div>

                <div x-show="refundAmount > 0" class="alert alert-warning py-2 mb-2">
                    <div class="small fw-bold">Refund Due: <span x-text="formatCurrency(refundAmount)"></span></div>
                    <div class="small text-muted">(Paid for <span x-text="paidHeadCount"></span> heads)</div>
                </div>

                <div class="d-grid">
                     <!-- Allow clicking but show alert if no refund due -->
                     <button @click="generateCreditNote()" class="btn btn-outline-danger btn-sm text-start">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Generate Credit Note (ใบคืนยอด)
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Transactions Column -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Installments & Payments</h5>
                <button class="btn btn-primary btn-sm" @click="openAddModal()">
                    <i class="bi bi-plus-lg"></i> Add Installment
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Description</th>
                                <th>Due Date</th>
                                <th class="text-end">Amount (Inc. VAT)</th>
                                <th class="text-end">Paid</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="t in transactions" :key="t.id">
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold" x-text="formatType(t.type)"></div>
                                        <div class="small text-muted" x-text="t.notes || '-'"></div>
                                        <div x-show="t.slip_path" class="mt-1">
                                            <a :href="'/storage/' + t.slip_path" target="_blank" class="badge bg-info text-decoration-none">
                                                <i class="bi bi-paperclip"></i> View Slip
                                            </a>
                                        </div>
                                    </td>
                                    <td x-text="formatDate(t.due_date)"></td>
                                    <td class="text-end" x-text="formatCurrency(t.amount)"></td>
                                    <td class="text-end" x-text="formatCurrency(t.paid_amount)"></td>
                                    <td class="text-center">
                                        <span class="badge" :class="statusClass(t.status)" x-text="formatStatus(t.status)"></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-success" @click="openPayModal(t)" title="Update Payment">
                                            <i class="bi bi-cash"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" @click="deleteTransaction(t.id)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="transactions.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">No transactions recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- Add Transaction Modal -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" x-ref="addModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTransactionForm" @submit.prevent="addTransaction">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" x-model="newTransaction.type" required>
                                <option value="installment">Installment (งวดงาน)</option>
                                <option value="down_payment">Down Payment (มัดจำ)</option>
                                <option value="full_payment">Full Payment (จ่ายเต็ม)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" x-model="newTransaction.amount" required>
                            <div class="form-text">Remaining: <span x-text="formatCurrency(remainingSchedule)"></span></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" x-model="newTransaction.due_date">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" x-model="newTransaction.notes" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" :disabled="isSaving">
                            <span x-show="isSaving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            <span x-show="!isSaving">Save</span>
                            <span x-show="isSaving">Saving...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Payment Modal -->
    <div class="modal fade" id="updatePaymentModal" tabindex="-1" x-ref="payModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Payment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updatePaymentForm" @submit.prevent="updateTransaction">
                        <div class="mb-3">
                            <label class="form-label">Paid Amount</label>
                            <input type="number" step="0.01" class="form-control" x-model="editingTransaction.paid_amount">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" x-model="editingTransaction.status">
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Slip</label>
                            <input type="file" class="form-control" @change="handleFileSelect">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Selection Modal -->
    <div class="modal fade" id="documentSelectionModal" tabindex="-1" x-ref="docSelectionModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Installments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Select which installments to include in this document.</p>
                    <div class="list-group">
                        <template x-for="t in transactions" :key="t.id">
                            <label class="list-group-item">
                                <input class="form-check-input me-1" type="checkbox" :value="t.id" x-model="selectedTransactionIds">
                                <span x-text="formatType(t.type)"></span> -
                                <span x-text="formatCurrency(t.amount)"></span>
                                <span class="badge ms-2" :class="statusClass(t.status)" x-text="formatStatus(t.status)"></span>
                            </label>
                        </template>
                    </div>
                    <div class="mt-3 d-grid">
                        <button class="btn btn-primary" @click="generateSelectedDocument()" :disabled="selectedTransactionIds.length === 0">
                            Generate Document
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function financialManager() {
    return {
        // Init Data from PHP
        pricingMode: '{{ $production->financial_data['pricing_mode'] ?? 'fixed' }}',

        // VAT Settings
        vatIncluded: {{ ($production->financial_data['vat_included'] ?? false) ? 'true' : 'false' }},
        vatRate: {{ $production->financial_data['vat_rate'] ?? 7 }},

        fixedTotal: {{ $production->financial_data['fixed_base_amount'] ?? ($production->financial_data['total_amount'] ?? 0) }}, // Base amount before VAT logic
        unitPrice: {{ $production->financial_data['unit_price'] ?? 0 }},
        employeeCount: {{ $production->items->count() }},

        // Dynamic Total (The one used for calculations)
        totalAmount: 0, // Calculated in updateTotal()
        subtotalAmount: 0,
        vatAmount: 0,

        transactions: @json(\App\Models\FinancialTransaction::where('production_order_id', $production->id)->get()),
        selectedProfile: '',
        newTransaction: { type: 'installment', amount: '', due_date: '', notes: '' },
        editingTransaction: {},
        selectedFile: null,
        isSaving: false,

        // Document Selection
        selectedTransactionIds: [],
        documentTypeToGenerate: '',

        // Refund Logic
        actualDeliveredCount: {{ $production->items->count() }}, // Default to current

        init() {
            // Re-calculate total on load just in case count changed but DB wasn't updated
            this.updateTotal();
        },

        updateTotal() {
            let base = 0;
            if (this.pricingMode === 'per_head') {
                base = this.unitPrice * this.employeeCount;
            } else {
                base = parseFloat(this.fixedTotal) || 0;
            }

            // VAT Logic
            if (this.vatIncluded) {
                // Base is inclusive: Total = Base
                // Subtotal = Base / (1 + Rate/100)
                this.totalAmount = base;
                this.subtotalAmount = base / (1 + (this.vatRate / 100));
                this.vatAmount = this.totalAmount - this.subtotalAmount;
            } else {
                // Base is exclusive: Total = Base + (Base * Rate/100)
                this.subtotalAmount = base;
                this.vatAmount = base * (this.vatRate / 100);
                this.totalAmount = this.subtotalAmount + this.vatAmount;
            }
        },

        get calculatedTotal() {
             return this.unitPrice * this.employeeCount;
        },

        saveFinancialData() {
            this.isSaving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Build the financial array to merge into existing
            const payload = {
                financial: {
                    pricing_mode: this.pricingMode,
                    unit_price: this.unitPrice,
                    fixed_base_amount: this.fixedTotal, // Save the input value
                    total_amount: this.totalAmount, // Save the calculated final total
                    vat_included: this.vatIncluded,
                    vat_rate: this.vatRate
                },
                _method: 'PUT'
            };

            fetch('{{ route("production.update", $production->id) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isSaving = false;
                // Removed page reload to keep modal open
                if(typeof showToast === 'function') {
                    showToast('Pricing Settings Saved', 'success');
                } else {
                    alert('Pricing Settings Saved');
                }
            })
            .catch(err => {
                this.isSaving = false;
                console.error(err);
                alert('Error saving data');
            });
        },

        get scheduledAmount() {
            return this.transactions.reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
        },
        get remainingSchedule() {
            return Math.max(0, this.totalAmount - this.scheduledAmount);
        },
        get isFullyScheduled() {
            return Math.abs(this.totalAmount - this.scheduledAmount) < 1;
        },
        get totalPaid() {
            return this.transactions.reduce((sum, t) => sum + parseFloat(t.paid_amount || 0), 0);
        },
        get outstandingBalance() {
            return Math.max(0, this.totalAmount - this.totalPaid);
        },

        // Refund Getters
        get paidHeadCount() {
            // Estimate based on unit price
            if(this.unitPrice > 0) {
                 // We should use Subtotal (Ex-VAT) for unit price calc logic if unit price is Ex-VAT?
                 // Assuming unit price input follows the VAT setting logic.
                 // If totalPaid is inclusive, and unitPrice is inclusive, it matches.
                return Math.floor(this.totalPaid / this.unitPrice);
            }
            return 0;
        },
        get refundAmount() {
            // Simple Logic: Paid - (Actual * UnitPrice)
            // But we need to account for VAT settings?
            // Let's assume refund is based on the final amounts (Total).

            // If Pricing Mode is Per Head
            if (this.pricingMode === 'per_head') {
                 // Calculate value of delivered goods
                 let deliveredValue = this.actualDeliveredCount * this.unitPrice;

                 // If excluded VAT, add VAT to delivered value to compare with Total Paid (which is Inc VAT presumably)
                 if (!this.vatIncluded) {
                     deliveredValue = deliveredValue * (1 + (this.vatRate/100));
                 }

                 return Math.max(0, this.totalPaid - deliveredValue);
            }

            // Fixed Mode
            return Math.max(0, this.totalPaid - this.totalAmount);
        },
        get canRefund() {
            return this.refundAmount > 0;
        },

        generateCreditNote() {
            if (!this.canRefund) {
                alert('No refund due (Refund Amount is 0). Please check Paid Amount and Actual Delivered Count.');
                return;
            }
            this.openDocument('credit_note');
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(val);
        },
        formatDate(date) {
            return date ? new Date(date).toLocaleDateString('th-TH') : '-';
        },
        formatType(type) {
            const map = { installment: 'Installment', down_payment: 'Down Payment', full_payment: 'Full Payment' };
            return map[type] || type;
        },
        formatStatus(status) {
            return status.charAt(0).toUpperCase() + status.slice(1);
        },
        statusClass(status) {
            const map = { pending: 'bg-secondary', partial: 'bg-warning text-dark', paid: 'bg-success', overdue: 'bg-danger' };
            return map[status] || 'bg-light text-dark';
        },

        // --- Improved Modal Handling ---
        openAddModal() {
            const modalEl = this.$refs.addModal;
            if(modalEl) {
                // Use Bootstrap API if available
                if(typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            }
        },

        addTransaction() {
            this.isSaving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            console.log("Submitting transaction:", this.newTransaction); // Debug log

            fetch('/production/{{ $production->id }}/transactions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(this.newTransaction)
            })
            .then(async response => {
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                     return response.json().then(data => {
                        if (!response.ok) {
                            throw new Error(data.message || 'Server returned error');
                        }
                        return data;
                    });
                } else {
                     const text = await response.text();
                     console.error("Non-JSON response", text);
                     throw new Error('Server error (Non-JSON response)');
                }
            })
            .then(data => {
                if(data.success) {
                    this.transactions.push(data.transaction);

                    // Close Modal Safely
                    if(typeof bootstrap !== 'undefined') {
                        const modalEl = document.getElementById('addTransactionModal');
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl); // Use getOrCreateInstance
                        if(modal) modal.hide();
                    }

                    this.newTransaction = { type: 'installment', amount: '', due_date: '', notes: '' };
                } else {
                    alert(data.message || 'Error');
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                alert('An error occurred while saving: ' + err.message);
            })
            .finally(() => {
                this.isSaving = false;
            });
        },

        openPayModal(t) {
            this.editingTransaction = { ...t };
            this.selectedFile = null;
            if(typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('updatePaymentModal'));
                modal.show();
            }
        },

        handleFileSelect(e) {
            this.selectedFile = e.target.files[0];
        },

        updateTransaction() {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('paid_amount', this.editingTransaction.paid_amount);
            formData.append('status', this.editingTransaction.status);
            if (this.selectedFile) {
                formData.append('slip_file', this.selectedFile);
            }

            fetch('/production/transactions/' + this.editingTransaction.id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const idx = this.transactions.findIndex(t => t.id === data.transaction.id);
                    if(idx !== -1) this.transactions[idx] = data.transaction;

                    if(typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('updatePaymentModal'));
                        if(modal) modal.hide();
                    }
                } else {
                    alert(data.message || 'Error');
                }
            });
        },

        deleteTransaction(id) {
            if(!confirm('Delete this item?')) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/production/transactions/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    this.transactions = this.transactions.filter(t => t.id !== id);
                } else {
                    alert('Error');
                }
            });
        },

        // --- Document Generation ---
        openSelectionModal(type) {
            this.documentTypeToGenerate = type;
            this.selectedTransactionIds = [];
            // Pre-select all if empty? No, let user choose.

            if(typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('documentSelectionModal'));
                modal.show();
            }
        },

        generateSelectedDocument() {
            if (this.selectedTransactionIds.length === 0) return;

            const ids = this.selectedTransactionIds.join(',');
            this.openDocument(this.documentTypeToGenerate, ids);

            // Hide modal
            if(typeof bootstrap !== 'undefined') {
                 const modal = bootstrap.Modal.getInstance(document.getElementById('documentSelectionModal'));
                 if(modal) modal.hide();
            }
        },

        openDocument(type, transactionIds = null) {
            let url = `/production/{{ $production->id }}/documents/${type}?profile_id=${this.selectedProfile}`;

            if (transactionIds) {
                url += `&transaction_ids=${transactionIds}`;
            }

            // Pass refund params if credit note
            if (type === 'credit_note') {
                url += `&actual_count=${this.actualDeliveredCount}&refund_amount=${this.refundAmount}`;
                // Also pass VAT settings if needed, or controller gets from DB
            }

            window.open(url, '_blank');
        }
    }
}
</script>
