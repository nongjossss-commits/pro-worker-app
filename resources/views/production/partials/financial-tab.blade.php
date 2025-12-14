@can('view-finance')
<div x-data="financialManager()" class="row">

    <!-- TAB NAVIGATION -->
    <div class="col-12 mb-3">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-2">
            <ul class="nav nav-tabs border-0" id="financialTabs" role="tablist">
                <template x-for="(group, index) in financialGroups" :key="group.id">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                :class="{ 'active fw-bold': activeGroupId === group.id }"
                                @click="switchGroup(group.id)"
                                type="button"
                                role="tab">
                            <span x-text="group.name"></span>
                            <!-- Edit Name Button (Mini) -->
                            <i class="bi bi-pencil-square ms-2 text-muted small"
                               @click.stop="renameGroup(group.id, group.name)"
                               title="{{ __('Rename Tab') }}"
                               style="font-size: 0.8rem; cursor: pointer;"></i>
                            <!-- Delete Tab Button (Only for 2nd tab onwards) -->
                             <i class="bi bi-x-circle-fill ms-1 text-danger small"
                                x-show="index > 0"
                                @click.stop="deleteGroup(group.id)"
                                title="{{ __('Delete Tab') }}"
                                style="font-size: 0.8rem; cursor: pointer;"></i>
                        </button>
                    </li>
                </template>
                <li class="nav-item ms-2">
                    <button class="btn btn-sm btn-outline-success border-0" @click="addNewGroup()">
                        <i class="bi bi-plus-circle-fill"></i> {{ __('Add Tab') }}
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Left Column: Summary & Pricing Logic -->
    <div class="col-md-5">

        <!-- Pricing Logic Card -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-calculator me-2"></i>{{ __('Pricing Settings') }}</div>
            <div class="card-body">
                <!-- Mode Selection -->
                <div class="mb-3">
                    <label class="form-label small text-muted">{{ __('Pricing Mode') }}</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="pricing_mode" id="mode_fixed" value="fixed" x-model="pricingMode" @change="updateTotal()">
                        <label class="btn btn-outline-primary btn-sm" for="mode_fixed">{{ __('Fixed Total') }}</label>

                        <input type="radio" class="btn-check" name="pricing_mode" id="mode_per_head" value="per_head" x-model="pricingMode" @change="updateTotal()">
                        <label class="btn btn-outline-primary btn-sm" for="mode_per_head">{{ __('Per Head (Tiered)') }}</label>
                    </div>
                </div>

                <!-- Fixed Price Input -->
                <div x-show="pricingMode === 'fixed'" class="mb-3">
                    <label class="form-label">{{ __('Total Project Value') }} <span x-show="!vatIncluded">{{ __('(Excl. VAT)') }}</span><span x-show="vatIncluded">{{ __('(Incl. VAT)') }}</span></label>
                    <div class="input-group">
                        <span class="input-group-text">฿</span>
                        <input type="number" class="form-control" x-model="fixedTotal" @input="updateTotal()">
                    </div>
                </div>

                <!-- Tiered Pricing Inputs -->
                <div x-show="pricingMode === 'per_head'" class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">{{ __('Pricing Tiers') }}</label>
                        <button class="btn btn-sm btn-outline-primary" @click="addTier()">
                            <i class="bi bi-plus-lg"></i> {{ __('Add Tier') }}
                        </button>
                    </div>

                    <div class="table-responsive border rounded p-2 mb-2 bg-light">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th>{{ __('Price (฿)') }}</th>
                                    <th>{{ __('Count') }}</th>
                                    <th>{{ __('Note') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(tier, index) in pricingTiers" :key="index">
                                    <tr>
                                        <td><input type="number" class="form-control form-control-sm" x-model="tier.price" @input="updateTotal()" placeholder="Price"></td>
                                        <td><input type="number" class="form-control form-control-sm" x-model="tier.count" @input="updateTotal()" placeholder="Qty"></td>
                                        <td><input type="text" class="form-control form-control-sm" x-model="tier.note" placeholder="Opt."></td>
                                        <td>
                                            <button class="btn btn-sm btn-link text-danger p-0" @click="removeTier(index)">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Validation Message -->
                    <div class="d-flex justify-content-between small">
                        <span>{{ __('Total Employees') }}: <strong x-text="employeeCount"></strong></span>
                        <span :class="{'text-success': tierCountSum === employeeCount, 'text-danger': tierCountSum !== employeeCount}">
                            {{ __('Assigned') }}: <strong x-text="tierCountSum"></strong>
                        </span>
                    </div>
                    <div x-show="tierCountSum !== employeeCount" class="text-danger small mt-1">
                        * Assigned count must equal total employees.
                    </div>
                </div>

                <!-- Discount Field -->
                <div class="mb-3">
                    <label class="form-label">{{ __('Discount (From Total)') }}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">฿</span>
                        <input type="number" class="form-control" x-model="discount" @input="updateTotal()">
                    </div>
                </div>

                <!-- VAT & WHT Settings -->
                <div class="mb-3 border-top pt-3">
                    <label class="form-label small text-muted">{{ __('Tax Settings') }}</label>

                    <!-- VAT -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="vatIncluded" x-model="vatIncluded" @change="updateTotal()">
                            <label class="form-check-label small" for="vatIncluded">{{ __('Price Includes VAT') }}</label>
                        </div>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <span class="input-group-text">VAT</span>
                            <input type="number" step="0.1" class="form-control text-end" x-model="vatRate" @input="updateTotal()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <!-- WHT -->
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="whtEnabled" x-model="whtEnabled" @change="updateTotal()">
                            <label class="form-check-label small" for="whtEnabled">{{ __('Withholding Tax (WHT)') }}</label>
                        </div>
                        <div class="input-group input-group-sm" style="width: 160px;" x-show="whtEnabled">
                            <span class="input-group-text">WHT</span>
                            <input type="number" step="0.1" class="form-control text-end" x-model="whtRate" @input="updateTotal()">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div x-show="whtEnabled" class="form-text small mt-1 text-end">
                        <span class="badge bg-info text-dark">{{ __('International Standard') }}</span> {{ __('Deducted from Base Amount') }}
                    </div>
                </div>

                <!-- Save Pricing Button -->
                <button class="btn btn-primary btn-sm w-100" @click="saveFinancialData()" :disabled="isSaving || (pricingMode === 'per_head' && tierCountSum !== employeeCount)">
                    <i class="bi bi-save me-1"></i> {{ __('Save Pricing Settings') }}
                </button>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold">{{ __('Financial Summary') }}</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">{{ __('Gross Amount') }}:</span>
                    <span x-text="formatCurrency(baseTotal)"></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small text-success" x-show="discount > 0">
                    <span>Discount:</span>
                    <span>- <span x-text="formatCurrency(discount)"></span></span>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Base (Excl. VAT)') }}:</span>
                    <span x-text="formatCurrency(subtotalAmount)"></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">VAT (<span x-text="vatRate"></span>%):</span>
                    <span x-text="formatCurrency(vatAmount)"></span>
                </div>

                <div class="d-flex justify-content-between mb-2 fw-bold bg-light p-1 rounded">
                    <span>Total (Inc. VAT):</span>
                    <span class="text-primary" x-text="formatCurrency(totalAmount)"></span>
                </div>

                <div x-show="whtEnabled" class="d-flex justify-content-between mb-1 text-danger small">
                    <span>Less WHT (<span x-text="whtRate"></span>%):</span>
                    <span>- <span x-text="formatCurrency(whtAmount)"></span></span>
                </div>

                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                    <span class="fw-bold">{{ __('Net Receivable') }}:</span>
                    <span class="fw-bold text-success" x-text="formatCurrency(netReceivable)"></span>
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">{{ __('Scheduled') }}:</span>
                    <span x-text="formatCurrency(scheduledAmount)" :class="{'text-success': isFullyScheduled, 'text-warning': !isFullyScheduled}"></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">{{ __('Remaining') }}:</span>
                    <span x-text="formatCurrency(remainingSchedule)" class="text-danger fw-bold"></span>
                </div>
            </div>
        </div>

        <!-- Document Center & Headers -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-printer me-2"></i>{{ __('Document Header') }}</span>
                <button class="btn btn-xs btn-outline-secondary" @click="showCustomHeaderModal = true">
                    <i class="bi bi-pencil"></i> {{ __('Edit') }}
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Active Profile</label>
                    <div class="d-flex align-items-center gap-2">
                        <div x-show="useCustomHeader" class="badge bg-warning text-dark">{{ __('Custom Override') }}</div>
                        <div x-show="!useCustomHeader" class="badge bg-secondary">{{ __('System Profile') }}</div>
                    </div>
                    <div class="small mt-1 text-truncate fw-bold" x-text="headerNameDisplay"></div>
                </div>

                <div class="d-grid gap-2">
                    <button @click="openDocument('quotation')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-file-earmark-text me-2"></i>{{ __('Quotation (ใบเสนอราคา)') }}
                    </button>
                    <button @click="openSelectionModal('invoice')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-receipt me-2"></i>{{ __('Invoice (ใบแจ้งหนี้)') }}
                    </button>
                    <button @click="openSelectionModal('receipt')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-check-circle me-2"></i>{{ __('Receipt (ใบเสร็จรับเงิน)') }}
                    </button>
                     <button @click="openSelectionModal('tax_invoice')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Tax Invoice (ใบกำกับภาษี)') }}
                    </button>
                    <button @click="openSelectionModal('credit_note')" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-file-earmark-minus me-2"></i>{{ __('Credit Note (ใบลดหนี้)') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Bill To / Customer Settings -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-badge me-2"></i>{{ __('Bill To (Customer)') }}</span>
                <button class="btn btn-xs btn-outline-secondary" @click="showCustomCustomerModal = true">
                    <i class="bi bi-pencil"></i> {{ __('Edit') }}
                </button>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label small text-muted">Active Customer</label>
                    <div class="d-flex align-items-center gap-2">
                         <div x-show="customCustomer" class="badge bg-warning text-dark">{{ __('Custom Override') }}</div>
                         <div x-show="!customCustomer" class="badge bg-secondary">{{ __('Active Customer') }}</div>
                    </div>
                    <div class="small mt-1 text-truncate fw-bold" x-text="customerNameDisplay"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Transactions -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Installments & Payments') }}</h5>
                <button class="btn btn-primary btn-sm" @click="openAddModal()">
                    <i class="bi bi-plus-lg"></i> {{ __('Add Installment') }}
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">{{ __('Description') }}</th>
                                <th>{{ __('Due Date') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th class="text-end">{{ __('Paid') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                                <th class="text-end pe-3">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="t in filteredTransactions" :key="t.id">
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold" x-text="formatType(t.type)"></div>
                                        <div class="small text-muted" x-text="t.notes || '-'"></div>
                                        <div x-show="t.slip_path" class="mt-1">
                                            <a :href="'/storage/' + t.slip_path" target="_blank" class="badge bg-info text-decoration-none">View Slip</a>
                                        </div>
                                    </td>
                                    <td x-text="formatDate(t.due_date)"></td>
                                    <td class="text-end" x-text="formatCurrency(t.amount)"></td>
                                    <td class="text-end" x-text="formatCurrency(t.paid_amount)"></td>
                                    <td class="text-center">
                                        <span class="badge" :class="statusClass(t.status)" x-text="formatStatus(t.status)"></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-success py-0" @click="openPayModal(t)" title="Update Payment">
                                                <i class="bi bi-cash"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger py-0" @click="deleteTransaction(t.id)" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredTransactions.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">No transactions recorded for this group.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Custom Header Modal -->
    <div class="modal fade" id="customHeaderModal" tabindex="-1" x-show="showCustomHeaderModal" style="display: none;" :class="{ 'show d-block': showCustomHeaderModal }">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Document Header Settings</h5>
                    <button type="button" class="btn-close" @click="showCustomHeaderModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="useCustomHeaderToggle" x-model="useCustomHeader">
                        <label class="form-check-label fw-bold" for="useCustomHeaderToggle">Use Custom Header (Manual Override)</label>
                    </div>

                    <div x-show="!useCustomHeader">
                        <div class="alert alert-info small">
                            Using Default Company Profile. <br>
                            You can configure global profiles in Settings.
                        </div>
                        <select class="form-select form-select-sm" x-model="selectedProfileId">
                            <option value="">Default Profile</option>
                            @foreach(\App\Models\CompanyProfile::all() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="useCustomHeader">
                        <div class="mb-2">
                            <label class="form-label small">Company Name</label>
                            <input type="text" class="form-control form-control-sm" x-model="customHeader.name">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Address</label>
                            <textarea class="form-control form-control-sm" rows="3" x-model="customHeader.address"></textarea>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small">Tax ID</label>
                                <input type="text" class="form-control form-control-sm" x-model="customHeader.tax_id">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Phone</label>
                                <input type="text" class="form-control form-control-sm" x-model="customHeader.phone">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Logo</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="file" class="form-control form-control-sm" x-ref="logoInput">
                                <button class="btn btn-sm btn-outline-primary" @click="uploadLogo()">Upload</button>
                            </div>
                            <div x-show="customHeader.logo" class="mt-2">
                                <img :src="'/storage/' + customHeader.logo" style="height: 40px; border: 1px solid #ddd; border-radius: 4px;">
                                <div class="small text-success">Logo Set</div>
                            </div>
                        </div>
                        <!-- Save as New Profile Button -->
                         <button class="btn btn-outline-success btn-sm w-100 mt-2" @click="saveAsNewProfile()">
                            <i class="bi bi-hdd-fill me-1"></i> Save as New System Profile
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" @click="showCustomHeaderModal = false">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" @click="saveFinancialData(); showCustomHeaderModal = false;">Save & Apply</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Backdrop for manual modal -->
    <div class="modal-backdrop fade show" x-show="showCustomHeaderModal" style="z-index: 1040;"></div>

    <!-- Custom Customer Modal -->
    <div class="modal fade" id="customCustomerModal" tabindex="-1" x-show="showCustomCustomerModal" style="display: none;" :class="{ 'show d-block': showCustomCustomerModal }">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bill To (Customer) Settings</h5>
                    <button type="button" class="btn-close" @click="showCustomCustomerModal = false"></button>
                </div>
                <div class="modal-body">
                    <!-- Load from Agent -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Quick Load: From Agent</label>
                        <div class="input-group input-group-sm">
                            <select class="form-select" x-model="selectedAgentId" @change="loadAgentData()">
                                <option value="">-- Select Agent --</option>
                                @foreach(\App\Models\Agent::all() as $agent)
                                    <option value="{{ $agent->id }}"
                                            data-name="{{ $agent->agentNameEn }}"
                                            data-phone="{{ $agent->agentPhone ?? '' }}"
                                            >{{ $agent->agentNameEn }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline-secondary" type="button" @click="loadAgentData()">Load</button>
                        </div>
                    </div>

                    <hr>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="useCustomCustomerToggle" x-model="useCustomCustomer">
                        <label class="form-check-label fw-bold" for="useCustomCustomerToggle">Override Default (Employer)</label>
                    </div>

                    <div x-show="useCustomCustomer">
                        <div class="mb-2">
                            <label class="form-label small">Client Name</label>
                            <input type="text" class="form-control form-control-sm" x-model="customCustomerData.name">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Address</label>
                            <textarea class="form-control form-control-sm" rows="3" x-model="customCustomerData.address"></textarea>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small">Tax ID</label>
                                <input type="text" class="form-control form-control-sm" x-model="customCustomerData.tax_id">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Phone</label>
                                <input type="text" class="form-control form-control-sm" x-model="customCustomerData.phone">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" @click="showCustomCustomerModal = false">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" @click="saveFinancialData(); showCustomCustomerModal = false;">Save & Apply</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" x-show="showCustomCustomerModal" style="z-index: 1040;"></div>


    <!-- Add Transaction Modal -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" x-ref="addModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Add Installment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTransactionForm" @submit.prevent="addTransaction">
                        <div class="mb-2">
                            <label class="form-label small">Type</label>
                            <select class="form-select form-select-sm" x-model="newTransaction.type" required>
                                <option value="installment">Installment (งวดงาน)</option>
                                <option value="down_payment">Down Payment (มัดจำ)</option>
                                <option value="full_payment">Full Payment (จ่ายเต็ม)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Amount</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" x-model="newTransaction.amount" required>
                            <div class="form-text small">Remaining: <span x-text="formatCurrency(remainingSchedule)"></span></div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Due Date</label>
                            <input type="date" class="form-control form-control-sm" x-model="newTransaction.due_date">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Notes</label>
                            <textarea class="form-control form-control-sm" x-model="newTransaction.notes" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100" :disabled="isSaving">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Payment Modal -->
    <div class="modal fade" id="updatePaymentModal" tabindex="-1" x-ref="payModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Update Payment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updatePaymentForm" @submit.prevent="updateTransaction">
                        <div class="mb-2">
                            <label class="form-label small">Paid Amount</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" x-model="editingTransaction.paid_amount">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Status</label>
                            <select class="form-select form-select-sm" x-model="editingTransaction.status">
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Upload Slip</label>
                            <input type="file" class="form-control form-control-sm" @change="handleFileSelect">
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Selection Modal -->
    <div class="modal fade" id="documentSelectionModal" tabindex="-1" x-ref="docSelectionModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Select Items</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group list-group-flush mb-3">
                        <template x-for="t in filteredTransactions" :key="t.id">
                            <label class="list-group-item px-0 py-2 d-flex gap-2">
                                <input class="form-check-input" type="checkbox" :value="t.id" x-model="selectedTransactionIds">
                                <div class="small">
                                    <div class="fw-bold" x-text="formatType(t.type)"></div>
                                    <div class="text-muted" x-text="formatCurrency(t.amount)"></div>
                                </div>
                            </label>
                        </template>
                    </div>
                    <button class="btn btn-primary btn-sm w-100" @click="generateSelectedDocument()" :disabled="selectedTransactionIds.length === 0">
                        Generate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function financialManager() {
    return {
        // --- Groups (Tabs) ---
        financialGroups: @json($production->financialGroups),
        activeGroupId: null,

        // --- Data ---
        pricingMode: 'per_head',
        fixedTotal: 0,
        pricingTiers: [],
        employeeCount: {{ $production->items->count() }},
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
        transactions: @json($production->financialGroups->pluck('transactions')->flatten()),
        newTransaction: { type: 'installment', amount: '', due_date: '', notes: '' },
        editingTransaction: {},
        selectedFile: null,
        isSaving: false,
        selectedTransactionIds: [],
        documentTypeToGenerate: '',

        init() {
            if (this.financialGroups.length > 0) {
                this.switchGroup(this.financialGroups[0].id);
            } else {
                // Safety fallback
                this.addNewGroup('General');
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
                title: '{{ __('Add Tab') }}',
                input: 'text',
                inputLabel: '{{ __('Enter new tab name') }}',
                inputValue: 'New Tab',
                showCancelButton: true,
                confirmButtonText: '{{ __('Add') }}',
                cancelButtonText: '{{ __('Cancel') }}'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const name = result.value;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch('/production/{{ $production->id }}/financial-groups', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
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
                title: '{{ __('Are you sure you want to delete this tab?') }}',
                text: "All transactions in this group will be deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: '{{ __('Yes, delete it!') }}',
                cancelButtonText: '{{ __('Cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch(`/production/{{ $production->id }}/financial-groups/${groupId}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
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
                title: '{{ __('Rename Tab') }}',
                input: 'text',
                inputValue: currentName,
                showCancelButton: true,
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch(`/production/{{ $production->id }}/financial-groups/${groupId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
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

        // --- Save Logic (Updated to save to Group) ---
        saveFinancialData() {
            if (!this.activeGroupId) return;

            this.isSaving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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
                financial_group_id: this.activeGroupId, // Tell Controller which group
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
                // Update local group data to match saved
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
                this.isSaving = false;
                Swal.fire('Error', 'Error saving data', 'error');
            });
        },

        // --- Agent Logic ---
        loadAgentData() {
            if (!this.selectedAgentId) return;
            const select = this.$el.querySelector('select');
            const option = select.options[select.selectedIndex];
            const name = option.getAttribute('data-name');
            const phone = option.getAttribute('data-phone');

            this.customCustomerData.name = name;
            this.customCustomerData.phone = phone;
            this.customCustomerData.address = ''; // Agent address usually not in list, left blank
            this.customCustomerData.tax_id = '';

            this.useCustomCustomer = true;
            Swal.fire({
                icon: 'success',
                title: 'Agent Data Loaded',
                text: 'Don\'t forget to save!',
                timer: 1500,
                showConfirmButton: false
            });
        },

        // --- Profile Logic ---
        saveAsNewProfile() {
            if (!this.customHeader.name) {
                Swal.fire('Error', 'Please enter a Company Name', 'error');
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('{{ route("admin.settings.financial.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(this.customHeader)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', 'Profile Saved!', 'success').then(() => {
                        location.reload(); // Reload to refresh profile list
                    });
                } else {
                     Swal.fire('Error', 'Failed to save profile', 'error');
                }
            });
        },

        // --- Logo Upload ---
        uploadLogo() {
            const file = this.$refs.logoInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('logo', file);
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/production/{{ $production->id }}/upload-logo', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
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

        // --- Transactions & Documents (Standard) ---
        openAddModal() {
            if(typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getOrCreateInstance(this.$refs.addModal);
                modal.show();
            }
        },
        addTransaction() {
            if (!this.activeGroupId) return;

            this.isSaving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Append group ID to payload
            const payload = { ...this.newTransaction, financial_group_id: this.activeGroupId };

            fetch('/production/{{ $production->id }}/transactions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    this.transactions.push(data.transaction);
                    bootstrap.Modal.getOrCreateInstance(this.$refs.addModal).hide();
                    this.newTransaction = { type: 'installment', amount: '', due_date: '', notes: '' };
                }
            })
            .finally(() => this.isSaving = false);
        },
        openPayModal(t) {
            this.editingTransaction = { ...t };
            this.selectedFile = null;
            bootstrap.Modal.getOrCreateInstance(this.$refs.payModal).show();
        },
        handleFileSelect(e) { this.selectedFile = e.target.files[0]; },
        updateTransaction() {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('paid_amount', this.editingTransaction.paid_amount);
            formData.append('status', this.editingTransaction.status);
            if (this.selectedFile) formData.append('slip_file', this.selectedFile);

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
                    bootstrap.Modal.getInstance(this.$refs.payModal).hide();
                }
            });
        },
        deleteTransaction(id) {
            Swal.fire({
                title: '{{ __('Are you sure?') }}',
                text: '{{ __('This transaction will be permanently deleted.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: '{{ __('Yes, delete it!') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch('/production/transactions/' + id, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            this.transactions = this.transactions.filter(t => t.id !== id);
                            Swal.fire(
                                '{{ __('Deleted!') }}',
                                '{{ __('Transaction has been deleted.') }}',
                                'success'
                            );
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
            let url = `/production/{{ $production->id }}/documents/${type}?profile_id=${this.selectedProfileId}`;
            // Append Group ID
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
</script>
@else
    <div class="alert alert-danger">
        <i class="bi bi-lock-fill me-2"></i> {{ __('Access Denied: You do not have permission to view financial data.') }}
    </div>
@endcan
