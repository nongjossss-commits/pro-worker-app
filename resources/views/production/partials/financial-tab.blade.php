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
                    <label class="form-label">Total Project Value</label>
                    <div class="input-group">
                        <span class="input-group-text">฿</span>
                        <input type="number" class="form-control" x-model="fixedTotal" @input="updateTotal()">
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
                    <span class="text-muted">Total Project Value:</span>
                    <span class="fw-bold text-primary" x-text="formatCurrency(totalAmount)"></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Scheduled:</span>
                    <span x-text="formatCurrency(scheduledAmount)" :class="{'text-success': isFullyScheduled, 'text-warning': !isFullyScheduled}"></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Remaining to Schedule:</span>
                    <span x-text="formatCurrency(remainingSchedule)" class="text-danger fw-bold"></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
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
                    <button @click="openDocument('invoice')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-receipt me-2"></i>Invoice (ใบแจ้งหนี้)
                    </button>
                    <button @click="openDocument('receipt')" class="btn btn-outline-secondary btn-sm text-start">
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
                     <button @click="openDocument('credit_note')" class="btn btn-outline-danger btn-sm text-start" :disabled="refundAmount <= 0">
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
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
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
                                <th class="text-end">Amount</th>
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
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
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
                    <button type="submit" class="btn btn-primary w-100">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Payment Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1">
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

<script>
function financialManager() {
    return {
        // Init Data from PHP
        pricingMode: '{{ $production->financial_data['pricing_mode'] ?? 'fixed' }}',
        fixedTotal: {{ $production->financial_data['total_amount'] ?? 0 }}, // Stored 'total' used as fixed default
        unitPrice: {{ $production->financial_data['unit_price'] ?? 0 }},
        employeeCount: {{ $production->items->count() }},

        // Dynamic Total (The one used for calculations)
        totalAmount: {{ $production->financial_data['total_amount'] ?? 0 }},

        transactions: @json(\App\Models\FinancialTransaction::where('production_order_id', $production->id)->get()),
        selectedProfile: '',
        newTransaction: { type: 'installment', amount: '', due_date: '', notes: '' },
        editingTransaction: {},
        selectedFile: null,
        isSaving: false,

        // Refund Logic
        actualDeliveredCount: {{ $production->items->count() }}, // Default to current

        init() {
            // Re-calculate total on load just in case count changed but DB wasn't updated
            this.updateTotal();
        },

        updateTotal() {
            if (this.pricingMode === 'per_head') {
                this.totalAmount = this.unitPrice * this.employeeCount;
            } else {
                this.totalAmount = this.fixedTotal;
            }
        },

        get calculatedTotal() {
             return this.unitPrice * this.employeeCount;
        },

        saveFinancialData() {
            this.isSaving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Build the financial array to merge into existing
            // Note: In ProductionController@update, we map request->financial to financial_data
            const payload = {
                financial: {
                    pricing_mode: this.pricingMode,
                    unit_price: this.unitPrice,
                    total_amount: this.totalAmount // Important: Save the computed total
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
                // Since update redirects/reloads in standard controller, we might get a redirect.
                // But if we want AJAX behavior, we need to check response.
                // The current controller returns a Redirect.
                // For better UX, we'll reload to show success flash message or handle it.
                // Or better, let's just reload to be safe and simple as requested.
                window.location.reload();
            })
            .catch(err => {
                this.isSaving = false;
                console.error(err);
                // Fallback reload
                window.location.reload();
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
            // Rough estimate: Total Paid / Unit Price (if per_head)
            if(this.pricingMode === 'per_head' && this.unitPrice > 0) {
                return Math.floor(this.totalPaid / this.unitPrice);
            }
            return 0;
        },
        get refundAmount() {
            if (this.pricingMode === 'per_head') {
                 // Calculate difference in heads
                 // If we have paid for X heads, but actual is Y (where Y < X)
                 // Or easier: Total Paid - (Actual Count * Unit Price)
                 const actualValue = this.actualDeliveredCount * this.unitPrice;
                 return Math.max(0, this.totalPaid - actualValue);
            }
            // For fixed mode, maybe just Paid - Total?
            return Math.max(0, this.totalPaid - this.totalAmount);
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

        addTransaction() {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/production/{{ $production->id }}/transactions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(this.newTransaction)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    this.transactions.push(data.transaction);
                    bootstrap.Modal.getInstance(document.getElementById('addTransactionModal')).hide();
                    this.newTransaction = { type: 'installment', amount: '', due_date: '', notes: '' };
                } else {
                    alert(data.message || 'Error');
                }
            });
        },

        openPayModal(t) {
            this.editingTransaction = { ...t }; // Clone
            this.selectedFile = null;
            new bootstrap.Modal(document.getElementById('updatePaymentModal')).show();
        },

        handleFileSelect(e) {
            this.selectedFile = e.target.files[0];
        },

        updateTransaction() {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const formData = new FormData();
            formData.append('_method', 'PUT'); // Fake PUT for file upload
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
                    bootstrap.Modal.getInstance(document.getElementById('updatePaymentModal')).hide();
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

        openDocument(type) {
            let url = `/production/{{ $production->id }}/documents/${type}?profile_id=${this.selectedProfile}`;

            // Pass refund params if credit note
            if (type === 'credit_note') {
                url += `&actual_count=${this.actualDeliveredCount}&refund_amount=${this.refundAmount}`;
            }

            window.open(url, '_blank');
        }
    }
}
</script>
