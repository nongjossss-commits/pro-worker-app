from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()

    # Define mock HTML content that simulates the financial tab
    html_content = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Financial Verification</title>
        <link href="https:#cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https:#cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <script src="https:#cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script defer src="https:#cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js"></script>
        <meta name="csrf-token" content="mock-token">
    </head>
    <body class="bg-light p-4">
        <div class="container">
            <h1>Production Financial Verification</h1>

            <!-- Mock Production Variable Context -->
            <script>
                # Mocking the Blade data injection
                const productionId = 1;
            </script>

            <!-- Inject the Blade Partial Content (Simulated) -->
            <!-- I will copy the key parts of the modified blade file here for verification -->

            <div x-data="financialManager()" class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header bg-white fw-bold">Pricing Settings</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small text-muted">Pricing Mode</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="pricing_mode" id="mode_fixed" value="fixed" x-model="pricingMode" @change="updateTotal()">
                                    <label class="btn btn-outline-primary btn-sm" for="mode_fixed">Fixed Total</label>
                                    <input type="radio" class="btn-check" name="pricing_mode" id="mode_per_head" value="per_head" x-model="pricingMode" @change="updateTotal()">
                                    <label class="btn btn-outline-primary btn-sm" for="mode_per_head">Per Head</label>
                                </div>
                            </div>

                            <div x-show="pricingMode === 'fixed'" class="mb-3">
                                <label class="form-label">Total Project Value (Before VAT)</label>
                                <input type="number" class="form-control" x-model="fixedTotal" @input="updateTotal()">
                            </div>

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
                        </div>
                    </div>

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
                        </div>
                    </div>

                    <!-- Document Center Buttons -->
                    <div class="card shadow-sm border-0 mb-3">
                         <div class="card-header bg-white fw-bold">Document Center</div>
                         <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-secondary btn-sm text-start" @click="openSelectionModal('invoice')">
                                    Invoice
                                </button>
                                <button class="btn btn-outline-danger btn-sm text-start" :disabled="!canRefund">
                                    Credit Note
                                </button>
                            </div>
                         </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                         <div class="card-header bg-white d-flex justify-content-between">
                            <h5>Installments</h5>
                            <button class="btn btn-primary btn-sm" @click="openAddModal()">Add Installment</button>
                         </div>
                         <div class="card-body">
                            <table class="table">
                                <thead><tr><th>Desc</th><th>Amount</th><th>Action</th></tr></thead>
                                <tbody>
                                    <template x-for="t in transactions" :key="t.id">
                                        <tr>
                                            <td x-text="t.type"></td>
                                            <td x-text="formatCurrency(t.amount)"></td>
                                            <td><button class="btn btn-sm btn-outline-danger" @click="deleteTransaction(t.id)">Del</button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal Mock -->
            <div class="modal fade" id="addTransactionModal" tabindex="-1" x-ref="addModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Add</h5></div>
                        <div class="modal-body">
                            <input type="text" placeholder="Amount" x-model="newTransaction.amount">
                            <button class="btn btn-primary" @click="addTransaction()">Save</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Selection Modal Mock -->
            <div class="modal fade" id="documentSelectionModal" tabindex="-1" x-ref="docSelectionModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Select</h5></div>
                        <div class="modal-body">
                             <div class="list-group">
                                <template x-for="t in transactions" :key="t.id">
                                    <label class="list-group-item">
                                        <input type="checkbox" :value="t.id" x-model="selectedTransactionIds">
                                        <span x-text="t.type"></span>
                                    </label>
                                </template>
                            </div>
                            <button class="btn btn-primary mt-2" @click="generateSelectedDocument()">Generate</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            function financialManager() {
                return {
                    pricingMode: 'fixed',
                    fixedTotal: 10000,
                    unitPrice: 0,
                    employeeCount: 10,
                    vatIncluded: false,
                    vatRate: 7,

                    totalAmount: 0,
                    subtotalAmount: 0,
                    vatAmount: 0,

                    transactions: [
                        {id: 1, type: 'installment', amount: 5000, paid_amount: 5000, status: 'paid'},
                        {id: 2, type: 'installment', amount: 5700, paid_amount: 0, status: 'pending'}
                    ],

                    newTransaction: { type: 'installment', amount: '', due_date: '', notes: '' },
                    selectedTransactionIds: [],
                    documentTypeToGenerate: '',
                    actualDeliveredCount: 10,

                    init() { this.updateTotal(); },

                    updateTotal() {
                        let base = parseFloat(this.fixedTotal) || 0;
                        if (this.vatIncluded) {
                            this.totalAmount = base;
                            this.subtotalAmount = base / (1 + (this.vatRate / 100));
                            this.vatAmount = this.totalAmount - this.subtotalAmount;
                        } else {
                            this.subtotalAmount = base;
                            this.vatAmount = base * (this.vatRate / 100);
                            this.totalAmount = this.subtotalAmount + this.vatAmount;
                        }
                    },

                    formatCurrency(val) { return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(val); },

                    get totalPaid() { return this.transactions.reduce((sum, t) => sum + parseFloat(t.paid_amount || 0), 0); },
                    get refundAmount() { return Math.max(0, this.totalPaid - this.totalAmount); }, # Simplified for mock
                    get canRefund() { return this.refundAmount > 0; },

                    openAddModal() {
                        new bootstrap.Modal(this.$refs.addModal).show();
                    },

                    addTransaction() {
                        # Mock Save
                        this.transactions.push({id: Date.now(), type: 'installment', amount: this.newTransaction.amount, paid_amount:0, status:'pending'});
                        bootstrap.Modal.getInstance(this.$refs.addModal).hide();
                    },

                    openSelectionModal(type) {
                        this.documentTypeToGenerate = type;
                        new bootstrap.Modal(this.$refs.docSelectionModal).show();
                    },

                    generateSelectedDocument() {
                        alert('Generating ' + this.documentTypeToGenerate + ' for IDs: ' + this.selectedTransactionIds.join(','));
                        bootstrap.Modal.getInstance(this.$refs.docSelectionModal).hide();
                    }
                }
            }
            </script>
        </div>
    </body>
    </html>
    """

    page.set_content(html_content)
    page.wait_for_timeout(500) # Wait for Alpine init

    # Verify VAT Calculation (Initial: 10000 Excl VAT -> Total 10700)
    page.screenshot(path="verification/verify_initial_calc.png")

    # Toggle VAT Included
    page.click("label[for='vatIncluded']")
    page.wait_for_timeout(200)
    page.screenshot(path="verification/verify_vat_included.png")

    # Open Add Modal
    page.click("text=Add Installment")
    page.wait_for_timeout(500) # Wait for modal
    page.screenshot(path="verification/verify_add_modal.png")

    # Close Modal (simulating save)
    # Note: In real test we'd fill inputs, but for visual check this is enough

    # Open Document Selection
    page.click("text=Invoice")
    page.wait_for_timeout(500)
    page.screenshot(path="verification/verify_doc_selection.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
