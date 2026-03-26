@can('view-finance')
<div x-data="financialManager({
    financialGroups: {{ json_encode($production->financialGroups) }},
    transactions: {{ json_encode($production->financialGroups->pluck('transactions')->flatten()) }},
    productionItems: {{ json_encode($production->items->map(function($item) {
        $lastStepName = null;
        if (request()->is('production/registration*') && $item->employee) {
            $lastStepName = $item->employee->registrationSteps->sortByDesc('order')->first()?->name;
        } elseif (request()->is('production/renewal*') && $item->employee) {
            $lastStepName = $item->employee->registrationSteps->sortByDesc('order')->first()?->name; // Renewal uses registrationSteps relation logic
        } else {
            $lastStepName = $item->completedWorkTypeSteps->sortByDesc('order')->first()?->name;
        }

        return [
            'id' => $item->id,
            'name' => $item->employee ? ($item->employee->name_th ?? $item->employee->name_en) : 'New Employee',
            'name_en' => $item->employee ? $item->employee->employeeNameEn : '',
            'title_en' => $item->employee ? $item->employee->employeeTitleEn : '',
            'photo' => $item->employee ? $item->employee->photo_url : '',
            'nationality' => $item->employee ? $item->employee->employeeNationality : '',
            'insurance_type' => $item->employee ? $item->employee->insurance_type : '',
            'passport' => $item->employee ? $item->employee->employeePassport : '',
            'employee_id' => $item->employee_id,
            'last_step_name' => $lastStepName,
            'status' => $item->status
        ];
    })) }},
    employees: {{ json_encode(($employees ?? collect())->map(function($emp) {
        $lastStepName = null;
        if (request()->is('production/registration*') || request()->is('production/renewal*')) {
            $lastStepName = $emp->registrationSteps->sortByDesc('order')->first()?->name;
        }

        return [
            'id' => $emp->id,
            'name' => $emp->employeeNameTh ?? $emp->employeeNameEn,
            'name_en' => $emp->employeeNameEn,
            'title_en' => $emp->employeeTitleEn,
            'photo' => $emp->photo_url,
            'nationality' => $emp->employeeNationality,
            'insurance_type' => $emp->insurance_type,
            'passport' => $emp->employeePassport,
            'last_step_name' => $lastStepName,
            'status' => $emp->status
        ];
    })) }},
    productionId: {{ $production->id }},
    employeeCount: {{ $employeeCount ?? $production->items->count() }},
    csrfToken: '{{ csrf_token() }}'
})"
@insurance-updated.window="updateEmployeeInsurance($event.detail.employeeId, $event.detail.insuranceType)"
class="row">

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
                        <input type="radio" class="btn-check" name="pricing_mode" :id="'mode_fixed_' + productionId" value="fixed" x-model="pricingMode" @change="updateTotal()">
                        <label class="btn btn-outline-primary btn-sm" :for="'mode_fixed_' + productionId">{{ __('Fixed Total') }}</label>

                        <input type="radio" class="btn-check" name="pricing_mode" :id="'mode_per_head_' + productionId" value="per_head" x-model="pricingMode" @change="updateTotal()">
                        <label class="btn btn-outline-primary btn-sm" :for="'mode_per_head_' + productionId">{{ __('Per Head (Tiered)') }}</label>
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
                                    <th style="width: 120px;">{{ __('Assigned') }}</th>
                                    <th>{{ __('Note') }}</th>
                                    <th style="width: 30px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(tier, index) in pricingTiers" :key="index">
                                    <tr>
                                        <td><input type="number" class="form-control form-control-sm" x-model="tier.price" @input="updateTotal()" placeholder="Price"></td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-text p-0 overflow-hidden" style="width: 60px;">
                                                    <div class="d-flex flex-column w-100" style="font-size: 0.65rem; line-height: 1;">
                                                        <span class="text-primary fw-bold" title="Remaining to Bill" x-text="getTierRemainingCount(index)"></span>
                                                        <hr class="my-0">
                                                        <span class="text-muted" title="Total Assigned" x-text="tier.item_ids ? tier.item_ids.length : 0"></span>
                                                    </div>
                                                </div>
                                                <button class="btn btn-outline-secondary" type="button" @click="openManageEmployeesModal(index)" title="Manage Employees">
                                                    <i class="bi bi-people-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" x-model="tier.note" placeholder="Opt."></td>
                                        <td class="text-center align-middle">
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
                    <div class="d-flex flex-column gap-1 small mt-2">
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Total Employees') }}: <strong x-text="employeeCount"></strong></span>
                            <span>{{ __('Assigned') }}: <strong x-text="tierCountSum"></strong></span>
                        </div>
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                            <span>{{ __('Unassigned') }}: <strong class="text-warning" x-text="unassignedEmployeeCount"></strong></span>
                            <span>{{ __('Billed') }}: <strong class="text-info" x-text="billedItemIds.size"></strong></span>
                        </div>
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

                <!-- Advance Payments Section (NEW) -->
                <div class="mb-3 border-top pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0 fw-bold small text-primary">{{ __('Advance Payments / Expenses') }}</label>
                        <button class="btn btn-sm btn-outline-primary py-0" style="font-size: 10px;" @click="addAdvanceItem()">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="table-responsive border rounded p-2 mb-2 bg-light">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr class="text-muted small">
                                    <th>{{ __('Description') }}</th>
                                    <th style="width: 60px;">{{ __('Qty') }}</th>
                                    <th style="width: 140px;">{{ __('Price') }}</th>
                                    <th style="width: 20px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in advanceItems" :key="index">
                                    <tr>
                                        <td><input type="text" class="form-control form-control-sm" x-model="item.description" placeholder="Visa, Medical..."></td>
                                        <td><input type="number" class="form-control form-control-sm px-1" x-model="item.quantity" @input="updateTotal()"></td>
                                        <td><input type="number" class="form-control form-control-sm px-1" x-model="item.unit_price" @input="updateTotal()"></td>
                                        <td>
                                            <button class="btn btn-sm btn-link text-danger p-0" @click="removeAdvanceItem(index)">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="advanceItems.length === 0">
                                    <td colspan="4" class="text-center text-muted small fst-italic py-2">No advance payments added.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end align-items-center gap-3 small fw-bold">
                         <div class="text-muted">Total: <span x-text="formatCurrency(advanceTotal)"></span></div>
                         <div class="text-success">Paid: <span x-text="formatCurrency(advancePaid)"></span></div>
                         <div :class="{'text-danger': advanceRemaining > 0, 'text-success': advanceRemaining <= 0}">
                            Balance: <span x-text="formatCurrency(advanceRemaining)"></span>
                         </div>
                    </div>
                </div>

                <!-- VAT & WHT Settings -->
                <div class="mb-3 border-top pt-3">
                    <label class="form-label small text-muted">{{ __('Tax Settings (Service Fee Only)') }}</label>

                    <!-- VAT -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" :id="'vatIncluded_' + productionId" x-model="vatIncluded" @change="updateTotal()">
                            <label class="form-check-label small" :for="'vatIncluded_' + productionId">{{ __('Price Includes VAT') }}</label>
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
                            <input class="form-check-input" type="checkbox" :id="'whtEnabled_' + productionId" x-model="whtEnabled" @change="updateTotal()">
                            <label class="form-check-label small" :for="'whtEnabled_' + productionId">{{ __('Withholding Tax (WHT)') }}</label>
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
                <button class="btn btn-primary btn-sm w-100" @click="saveFinancialData()" :disabled="isSavingSettings">
                    <i class="bi bi-save me-1"></i> {{ __('Save Pricing & Advance Items') }}
                </button>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold">{{ __('Financial Summary') }}</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">{{ __('Service Fee (Gross)') }}:</span>
                    <span x-text="formatCurrency(baseTotal)"></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small text-success" x-show="discount > 0">
                    <span>Discount:</span>
                    <span>- <span x-text="formatCurrency(discount)"></span></span>
                </div>

                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Service Base (Excl. VAT)') }}:</span>
                    <span x-text="formatCurrency(subtotalAmount)"></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">VAT (<span x-text="vatRate"></span>%):</span>
                    <span x-text="formatCurrency(vatAmount)"></span>
                </div>

                <div class="d-flex justify-content-between mb-2 fw-bold bg-light p-1 rounded">
                    <span>Service Total (Inc. VAT):</span>
                    <span class="text-primary" x-text="formatCurrency(totalAmount)"></span>
                </div>

                 <!-- Advance Section in Summary -->
                <div class="d-flex justify-content-between mb-1 text-info small" x-show="advanceTotal > 0">
                    <span>+ Advance Payments (No VAT):</span>
                    <span x-text="formatCurrency(advanceTotal)"></span>
                </div>

                <div x-show="whtEnabled" class="d-flex justify-content-between mb-1 text-danger small">
                    <span>Less WHT (<span x-text="whtRate"></span>%):</span>
                    <span>- <span x-text="formatCurrency(whtAmount)"></span></span>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                    <span class="fw-bold fs-6">{{ __('Grand Total Receivable') }}:</span>
                    <span class="fw-bold fs-6 text-success" x-text="formatCurrency(grandTotalReceivable)"></span>
                </div>

                <div class="d-flex justify-content-between mb-1 small text-success">
                    <span class="text-muted">{{ __('Total Paid') }}:</span>
                    <span x-text="formatCurrency(totalPaidAmount)"></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small text-danger fw-bold">
                    <span class="text-muted">{{ __('Balance Due') }}:</span>
                    <span x-text="formatCurrency(remainingBalance)"></span>
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
                <span><i class="bi bi-printer me-2"></i>{{ __('Document Center') }}</span>
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
                     <!-- Quotation Dropdown (Updated) -->
                     <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle text-start" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-text me-2"></i>{{ __('Quotation (ใบเสนอราคา)') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('quotation', null, 'combined'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Combined (Service + Advance)') }}</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('quotation', null, 'service_only'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Service Fee Only') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openSelectionModal('quotation'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Select Installment(s)...') }}</a></li>
                        </ul>
                    </div>

                     <!-- Invoice Dropdown (New) -->
                     <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle text-start" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-text me-2"></i>{{ __('Invoice (ใบแจ้งหนี้)') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('invoice', null, 'combined'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Combined (Service + Advance)') }}</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('invoice', null, 'service_only'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Service Fee Only') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openSelectionModal('invoice'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Select Installment(s)...') }}</a></li>
                        </ul>
                    </div>

                    <!-- Advanced Generation Dropdown -->
                     <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle text-start" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Tax Invoice (ใบกำกับภาษี)') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('tax_invoice', null, 'combined'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Combined (Service + Advance)') }}</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('tax_invoice', null, 'service_only'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Service Fee Only') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openSelectionModal('tax_invoice'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Select Installment(s)...') }}</a></li>
                        </ul>
                    </div>

                     <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle text-start" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-receipt me-2"></i>{{ __('Receipt / Invoice') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('receipt', null, 'combined'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Combined (Receipt)') }}</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('receipt', null, 'service_only'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Receipt (Service Fee)') }}</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openSelectionModal('receipt'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Select Installment(s)...') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openDocument('advance_receipt', null, 'advance_only'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Advance Receipt (Total List)') }}</a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="openSelectionModal('advance_receipt'); if(typeof bootstrap !== 'undefined') { bootstrap.Dropdown.getOrCreateInstance($el.closest('.btn-group').querySelector('.dropdown-toggle')).hide(); }">{{ __('Advance Receipt (Select Transaction)') }}</a></li>
                        </ul>
                    </div>
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
                    <!-- SECTION 1: Service Fees / Income -->
                    <div class="bg-light px-3 py-2 fw-bold text-muted border-bottom text-xs text-uppercase">
                        {{ __('Income / Service Fees') }}
                    </div>
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
                            <template x-for="t in incomeTransactions" :key="t.id">
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold" x-text="formatType(t.type)"></div>
                                        <div class="small text-muted" x-text="t.notes || '-'"></div>
                                        <div x-show="t.slip_path" class="mt-1">
                                            <a href="#" @click.prevent="viewPDF('/storage/' + t.slip_path, 'View Slip')" class="badge bg-info text-decoration-none">View Slip</a>
                                        </div>
                                    </td>
                                    <td x-text="formatDate(t.due_date)"></td>
                                    <td class="text-end" x-text="formatCurrency(t.amount)"></td>
                                    <td class="text-end">
                                        <span x-text="formatCurrency(t.paid_amount || 0)"></span><br>
                                        <small class="text-muted" x-show="t.amount - (t.paid_amount || 0) > 0">Remaining: <span x-text="formatCurrency(Math.max(0, t.amount - (t.paid_amount || 0)))"></span></small>
                                    </td>
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
                            <tr x-show="incomeTransactions.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">No income transactions recorded.</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- SECTION 2: Reserve Fund / Advance Payments -->
                    <div class="bg-light px-3 py-2 fw-bold text-primary border-top border-bottom text-xs text-uppercase mt-2">
                        {{ __('Reserve Fund / Advance Payments') }} (เงินสำรองจ่าย)
                    </div>
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
                            <template x-for="t in advanceTransactions" :key="t.id">
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-primary" x-text="formatType(t.type)"></div>
                                        <div class="small text-muted" x-text="t.notes || '-'"></div>
                                        <div x-show="t.slip_path" class="mt-1">
                                            <a href="#" @click.prevent="viewPDF('/storage/' + t.slip_path, 'View Slip')" class="badge bg-info text-decoration-none">View Slip</a>
                                        </div>
                                    </td>
                                    <td x-text="formatDate(t.due_date)"></td>
                                    <td class="text-end text-primary" x-text="formatCurrency(t.amount)"></td>
                                    <td class="text-end">
                                        <span x-text="formatCurrency(t.paid_amount)"></span><br>
                                        <small class="text-muted" x-show="t.amount - t.paid_amount > 0">Remaining: <span x-text="formatCurrency(Math.max(0, t.amount - t.paid_amount))"></span></small>
                                    </td>
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
                            <tr x-show="advanceTransactions.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">No reserve fund transactions.</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Custom Header Modal -->
    <div class="modal fade" :id="'customHeaderModal-' + productionId" tabindex="-1" x-show="showCustomHeaderModal" style="display: none;" :class="{ 'show d-block': showCustomHeaderModal }">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Document Header Settings</h5>
                    <button type="button" class="btn-close" @click="showCustomHeaderModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 small border-0 bg-opacity-10 d-flex align-items-center mb-4 rounded-3">
                        <i class="bi bi-info-circle-fill me-2 fs-6"></i>
                        <div>
                            Select a saved profile to automatically apply logos, signatures, and stamps.
                            <a href="{{ route('finance.profiles.builder') }}" target="_blank" class="alert-link fw-bold ms-1">Manage Profiles</a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Select Biller Profile (Issuer)</label>
                        <select class="form-select form-select-sm" x-model="customHeader.biller_profile_id">
                            <option value="">-- Use Default System Profile --</option>
                            @foreach(\App\Models\FinancialProfile::where('type', 'biller')->latest()->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Select Customer Profile (Client)</label>
                        <select class="form-select form-select-sm" x-model="customHeader.customer_profile_id">
                            <option value="">-- Use Default Client Data --</option>
                            @foreach(\App\Models\FinancialProfile::where('type', 'customer')->latest()->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" :id="'useCustomHeaderToggle-' + productionId" x-model="useCustomHeader">
                        <label class="form-check-label fw-bold text-danger" :for="'useCustomHeaderToggle-' + productionId">Manual Override Data (Without Profile)</label>
                    </div>

                    <div x-show="useCustomHeader" class="bg-light p-3 border rounded">
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

    <!-- Manage Employees Modal (Tier Assignment) -->
    <div class="modal fade" :id="'manageEmployeesModal-' + productionId" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6 fw-bold">Assign Employees to Price Tier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="d-flex justify-content-between mb-2 gap-2">
                        <ul class="nav nav-pills nav-pills-sm custom-nav-pills w-50" style="font-size: 0.8rem;">
                            <li class="nav-item flex-fill text-center">
                                <button class="nav-link w-100 py-1 px-2" :class="{ 'active': tierEmployeeFilter === 'active' }" @click="tierEmployeeFilter = 'active'" type="button">Active</button>
                            </li>
                            <li class="nav-item flex-fill text-center">
                                <button class="nav-link w-100 py-1 px-2" :class="{ 'active': tierEmployeeFilter === 'cancelled' }" @click="tierEmployeeFilter = 'cancelled'" type="button">Cancelled</button>
                            </li>
                        </ul>
                        <div class="d-flex gap-1 w-50">
                            <input type="text" class="form-control form-control-sm" placeholder="Search..." x-model="modalSearch">
                            <button class="btn btn-sm btn-outline-secondary" @click="selectAllForModal()" title="Select All Visible">
                                <i class="bi bi-check-all"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="deselectAllForModal()" title="Clear Selection">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="list-group list-group-flush border rounded small" style="max-height: 300px; overflow-y: auto;">
                        <template x-for="item in allEmployeesForTier" :key="item.id">
                            <label class="list-group-item list-group-item-action d-flex gap-2 align-items-center py-2"
                                   x-show="!modalSearch || item.name.toLowerCase().includes(modalSearch.toLowerCase())"
                                   style="cursor: pointer;">
                                <input class="form-check-input me-1 mt-0" type="checkbox" :value="item.id" x-model="modalSelectedIds">

                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                    <img :src="item.photo || 'https://ui-avatars.com/api/?name=User&background=random'" class="rounded-circle flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                                    <div class="d-flex flex-column" style="line-height: 1.2; min-width: 0;">
                                        <div class="fw-bold text-truncate" style="font-size: 0.85rem;">
                                            <span x-text="item.name_en ? (item.title_en + ' ' + item.name_en) : item.name"></span>
                                            <!-- Insurance Badge -->
                                            <template x-if="item.insurance_type && item.insurance_type !== '-' && item.insurance_type !== 'No' && item.insurance_type !== 'ไม่มี'">
                                                <span class="badge rounded-pill ms-1" style="font-size: 0.6rem;"
                                                      :class="{
                                                          'bg-primary': item.insurance_type === 'ประกันสังคม',
                                                          'bg-warning text-dark': item.insurance_type === 'ประกันเอกชน',
                                                          'bg-success': item.insurance_type === 'ประกันโรงพยาบาล',
                                                          'bg-secondary': item.insurance_type !== 'ประกันสังคม' && item.insurance_type !== 'ประกันเอกชน' && item.insurance_type !== 'ประกันโรงพยาบาล'
                                                      }"
                                                      x-text="item.insurance_type"></span>
                                            </template>
                                            <template x-if="!item.insurance_type || item.insurance_type === '-' || item.insurance_type === 'No' || item.insurance_type === 'ไม่มี'">
                                                <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.6rem;">None</span>
                                            </template>
                                            <!-- Passport Badge -->
                                            <template x-if="item.passport && item.passport !== '-' && item.passport !== 'No' && item.passport !== 'ไม่มี'">
                                                <span class="badge bg-info text-dark rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('Passport') }}</span>
                                            </template>
                                            <template x-if="!item.passport || item.passport === '-' || item.passport === 'No' || item.passport === 'ไม่มี'">
                                                <span class="badge bg-light text-dark border rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('No Passport') }}</span>
                                            </template>
                                            <!-- Last Step Badge -->
                                            <template x-if="item.last_step_name">
                                                <span class="badge border border-primary text-primary rounded-pill ms-1" style="font-size: 0.6rem;" x-text="item.last_step_name"></span>
                                            </template>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.75rem;">
                                             <span class="text-truncate" x-text="item.nationality"></span>
                                             <template x-if="item.nationality === 'Myanmar' || item.nationality === 'เมียนมา' || item.nationality === 'พม่า'"><span style="font-size: 1rem;">🇲🇲</span></template>
                                             <template x-if="item.nationality === 'Laos' || item.nationality === 'ลาว'"><span style="font-size: 1rem;">🇱🇦</span></template>
                                             <template x-if="item.nationality === 'Cambodia' || item.nationality === 'กัมพูชา'"><span style="font-size: 1rem;">🇰🇭</span></template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Indicator if assigned to another tier -->
                                <template x-if="getTierForItem(item.id) && activeTierIndex !== null && pricingTiers.indexOf(getTierForItem(item.id)) !== activeTierIndex">
                                    <span class="badge bg-warning text-dark ms-auto fw-normal" style="font-size: 0.7em;">
                                        In <span x-text="getTierForItem(item.id).price"></span>
                                    </span>
                                </template>

                                <!-- Indicator if already billed -->
                                <template x-if="billedItemIds.has(item.id)">
                                    <span class="badge bg-info ms-1 fw-normal" style="font-size: 0.7em;">Billed</span>
                                </template>
                            </label>
                        </template>
                        <div x-show="allEmployeesForTier.length === 0" class="p-3 text-center text-muted">No employees found.</div>
                    </div>
                </div>
                <div class="modal-footer py-1 bg-light">
                     <div class="d-flex w-100 justify-content-between align-items-center">
                        <div class="small text-muted">
                            Selected: <strong x-text="modalSelectedIds.length"></strong>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary btn-sm" @click="saveTierSelection()">Save Changes</button>
                        </div>
                     </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Customer Modal -->
    <div class="modal fade" :id="'customCustomerModal-' + productionId" tabindex="-1" x-show="showCustomCustomerModal" style="display: none;" :class="{ 'show d-block': showCustomCustomerModal }">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bill To (Customer) Settings</h5>
                    <button type="button" class="btn-close" @click="showCustomCustomerModal = false"></button>
                </div>
                <div class="modal-body">
                    <!-- Load from Employer -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Quick Load: From Employer Addresses</label>
                        <div class="input-group input-group-sm">
                            <select class="form-select" x-model="selectedEmployerAddressId" @change="loadEmployerData()">
                                <option value="">-- Select Address --</option>
                                @foreach($production->employer->addresses ?? [] as $address)
                                    <option value="{{ $address->id }}"
                                            data-name-th="{{ $production->employer->employerNameTh }}"
                                            data-name-en="{{ $production->employer->employerNameEn }}"
                                            data-tax-id="{{ $production->employer->employerTaxId }}"
                                            data-phone="{{ $production->employer->employerPhone }}"
                                            data-address-th="{{ $address->full_address_th }}"
                                            data-address-en="{{ $address->full_address_en }}"
                                            >
                                        {{ $address->type === 'registered' ? '(Registered) ' : '' }} {{ Str::limit($address->full_address_th, 40) }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline-secondary" type="button" @click="loadEmployerData()">Load</button>
                        </div>
                    </div>

                    <hr>

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
                        <input class="form-check-input" type="checkbox" :id="'useCustomCustomerToggle-' + productionId" x-model="useCustomCustomer">
                        <label class="form-check-label fw-bold" :for="'useCustomCustomerToggle-' + productionId">Override Default (Employer)</label>
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
    <div class="modal fade" :id="'addTransactionModal-' + productionId" tabindex="-1" x-ref="addModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Add Installment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="addTransaction(false)">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label small">Type</label>
                                    <select class="form-select form-select-sm" x-model="newTransaction.type" required>
                                        <option value="installment">Installment (งวดงาน)</option>
                                        <option value="down_payment">Down Payment (มัดจำ)</option>
                                        <option value="full_payment">Full Payment (จ่ายเต็ม)</option>
                                        <option value="advance_payment">Advance Payment (เงินสำรองจ่าย)</option>
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
                            </div>
                            <div class="col-md-6 border-start">
                                <label class="form-label small fw-bold mb-1">Select Employees</label>
                                <ul class="nav nav-pills nav-pills-sm custom-nav-pills mb-2" style="font-size: 0.75rem;">
                                    <li class="nav-item flex-fill text-center">
                                        <button class="nav-link w-100 py-0" :class="{ 'active': newTransactionEmployeeFilter === 'active' }" @click="newTransactionEmployeeFilter = 'active'" type="button">Active</button>
                                    </li>
                                    <li class="nav-item flex-fill text-center">
                                        <button class="nav-link w-100 py-0" :class="{ 'active': newTransactionEmployeeFilter === 'cancelled' }" @click="newTransactionEmployeeFilter = 'cancelled'" type="button">Cancelled</button>
                                    </li>
                                </ul>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small text-muted" style="font-size: 0.75rem;">
                                        Selected: <span x-text="selectedTransactionItems.length"></span>
                                        <span x-show="pricingMode === 'per_head'">(Auto-calc active)</span>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary py-0" style="font-size: 0.7rem;" @click="selectAllAvailable()">All</button>
                                        <button type="button" class="btn btn-outline-secondary py-0" style="font-size: 0.7rem;" @click="deselectAllTransactionItems()">None</button>
                                    </div>
                                </div>
                                <div class="border rounded bg-light" style="max-height: 250px; overflow-y: auto;">
                                    <div class="list-group list-group-flush">
                                        <!-- Available Items -->
                                        <template x-for="item in availableItems" :key="item.id">
                                            <label class="list-group-item py-1 px-2 d-flex gap-2 align-items-center bg-white" style="font-size: 0.8rem;">
                                                <input class="form-check-input mt-0" type="checkbox" :value="item.id" x-model="selectedTransactionItems" @change="recalcAmount()">
                                                <span class="badge bg-light text-dark border ms-auto" x-text="getItemPrice(item.id)" style="font-size: 0.65rem;"></span>
                                                <div class="d-flex align-items-center gap-2 overflow-hidden w-100">
                                                    <img :src="item.photo || 'https://ui-avatars.com/api/?name=User&background=random'" class="rounded-circle flex-shrink-0" style="width: 24px; height: 24px; object-fit: cover;">
                                                    <div class="d-flex flex-column" style="line-height: 1.1; min-width: 0;">
                                                        <div class="fw-bold text-truncate">
                                                            <span x-text="item.name_en ? (item.title_en + ' ' + item.name_en) : item.name"></span>
                                                            <template x-if="item.insurance_type && item.insurance_type !== '-' && item.insurance_type !== 'No' && item.insurance_type !== 'ไม่มี'">
                                                                <span class="badge rounded-pill ms-1" style="font-size: 0.6rem;"
                                                                    :class="{
                                                                        'bg-primary': item.insurance_type === 'ประกันสังคม',
                                                                        'bg-warning text-dark': item.insurance_type === 'ประกันเอกชน',
                                                                        'bg-success': item.insurance_type === 'ประกันโรงพยาบาล',
                                                                        'bg-secondary': item.insurance_type !== 'ประกันสังคม' && item.insurance_type !== 'ประกันเอกชน' && item.insurance_type !== 'ประกันโรงพยาบาล'
                                                                    }"
                                                                    x-text="item.insurance_type"></span>
                                                            </template>
                                                            <template x-if="!item.insurance_type || item.insurance_type === '-' || item.insurance_type === 'No' || item.insurance_type === 'ไม่มี'">
                                                                <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.6rem;">None</span>
                                                            </template>
                                                            <!-- Passport Badge -->
                                                            <template x-if="item.passport && item.passport !== '-' && item.passport !== 'No' && item.passport !== 'ไม่มี'">
                                                                <span class="badge bg-info text-dark rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('Passport') }}</span>
                                                            </template>
                                                            <template x-if="!item.passport || item.passport === '-' || item.passport === 'No' || item.passport === 'ไม่มี'">
                                                                <span class="badge bg-light text-dark border rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('No Passport') }}</span>
                                                            </template>
                                                            <!-- Last Step Badge -->
                                                            <template x-if="item.last_step_name">
                                                                <span class="badge border border-primary text-primary rounded-pill ms-1" style="font-size: 0.6rem;" x-text="item.last_step_name"></span>
                                                            </template>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.7rem;">
                                                             <span class="text-truncate" x-text="item.nationality"></span>
                                                             <template x-if="item.nationality === 'Myanmar' || item.nationality === 'เมียนมา' || item.nationality === 'พม่า'"><span style="font-size: 0.8rem;">🇲🇲</span></template>
                                                             <template x-if="item.nationality === 'Laos' || item.nationality === 'ลาว'"><span style="font-size: 0.8rem;">🇱🇦</span></template>
                                                             <template x-if="item.nationality === 'Cambodia' || item.nationality === 'กัมพูชา'"><span style="font-size: 0.8rem;">🇰🇭</span></template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        </template>
                                        <div x-show="availableItems.length === 0" class="p-2 text-center text-muted small">
                                            No available employees.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                             <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" @click="if($el.closest('form').reportValidity()) { addTransaction(true); }" :disabled="isSavingTransaction">Save & Close</button>
                             <button type="submit" class="btn btn-primary btn-sm flex-grow-1" :disabled="isSavingTransaction">Save & Add Another</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Payment Modal -->
    <div class="modal fade" :id="'updatePaymentModal-' + productionId" tabindex="-1" x-ref="payModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Payment History & Details</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Left Column: Payments History -->
                        <div class="col-md-5">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Payments</h6>

                            <!-- Payment List -->
                            <div class="list-group list-group-flush mb-3 border rounded shadow-sm" style="max-height: 250px; overflow-y: auto;">
                                <template x-for="pay in editingTransaction.payments" :key="pay.id">
                                    <div class="list-group-item py-2 px-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="fw-bold text-success" x-text="formatCurrency(pay.amount)"></div>
                                            <div class="text-muted small" x-text="formatDate(pay.paid_at)"></div>
                                        </div>
                                        <div class="small text-muted mb-1 d-flex justify-content-between">
                                            <span x-text="pay.bank_account ? pay.bank_account.bank_name : 'No Account'"></span>
                                            <span>
                                                <a x-show="pay.slip_path" href="#" @click.prevent="viewPDF('/storage/' + pay.slip_path, 'View Slip')" class="badge bg-info text-decoration-none">View Slip</a>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="small fst-italic text-muted" x-text="pay.notes"></div>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" @click="deletePayment(pay.id)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="!editingTransaction.payments || editingTransaction.payments.length === 0" class="text-center text-muted p-3 small">
                                    No payments recorded yet.
                                </div>
                            </div>

                            <!-- Add New Payment Form -->
                            <div class="card shadow-sm border-success">
                                <div class="card-header bg-success text-white py-1 small fw-bold">Add Payment</div>
                                <div class="card-body p-2">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small mb-0">Amount</label>
                                            <input type="number" step="0.01" class="form-control form-control-sm" x-model="newPayment.amount">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small mb-0">Date</label>
                                            <input type="date" class="form-control form-control-sm" x-model="newPayment.paid_at">
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Account</label>
                                        <select class="form-select form-select-sm" x-model="newPayment.bank_account_id">
                                            <option value="">-- Select Account --</option>
                                            @foreach(\App\Models\BankAccount::where('is_active', true)->get() as $bank)
                                                <option value="{{ $bank->id }}">{{ $bank->bank_name }} {{ $bank->account_number ? '('.$bank->account_number.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Slip</label>
                                        <input type="file" class="form-control form-control-sm" @change="handlePaymentSlipSelect" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Notes</label>
                                        <input type="text" class="form-control form-control-sm" x-model="newPayment.notes">
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm w-100" @click="addPayment" :disabled="isSavingPayment">
                                        <span x-show="isSavingPayment" class="spinner-border spinner-border-sm me-1"></span>
                                        Save Payment
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Transaction Options & Items -->
                        <div class="col-md-7 border-start ps-3">
                            <form @submit.prevent="updateTransaction">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Transaction Options</h6>

                                <!-- Summary Display -->
                                <div class="d-flex justify-content-between small mb-3 bg-light p-2 rounded border">
                                    <div><span class="text-muted">Total:</span> <span class="fw-bold" x-text="formatCurrency(editingTransaction.amount)"></span></div>
                                    <div><span class="text-muted">Paid:</span> <span class="fw-bold text-success" x-text="formatCurrency(editingTransaction.paid_amount || 0)"></span></div>
                                    <div><span class="text-muted">Remaining:</span> <span class="fw-bold text-danger" x-text="formatCurrency(Math.max(0, editingTransaction.amount - (editingTransaction.paid_amount || 0)))"></span></div>
                                </div>

                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <label class="form-label small">Status Override</label>
                                        <select class="form-select form-select-sm" x-model="editingTransaction.status">
                                            <option value="pending">Pending</option>
                                            <option value="partial">Partial</option>
                                            <option value="paid">Paid</option>
                                        </select>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label small">Receive to Account (Legacy)</label>
                                        <select class="form-select form-select-sm" x-model="editingTransaction.bank_account_id" disabled title="Please update account via the Payments tab below.">
                                            <option value="">-- Legacy Field --</option>
                                            @foreach(\App\Models\BankAccount::where('is_active', true)->get() as $bank)
                                                <option value="{{ $bank->id }}">{{ $bank->bank_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-2" x-show="editingTransaction.type !== 'advance_payment' && editingTransaction.type !== 'advance_receipt'">
                                    <label class="form-label small">WHT (หัก ณ ที่จ่าย 3%)</label>
                                    <select class="form-select form-select-sm mb-1" x-model="editingTransaction.wht_status">
                                        <option value="not_required">Not Required</option>
                                        <option value="pending">Pending</option>
                                        <option value="received">Received</option>
                                    </select>
                                    <input type="number" step="0.01" class="form-control form-control-sm mt-1" x-model="editingTransaction.withholding_tax_amount" placeholder="WHT Amount" x-show="editingTransaction.wht_status !== 'not_required'">

                                    <!-- WHT Upload Area -->
                                    <div class="mt-2" x-show="editingTransaction.wht_status !== 'not_required'">
                                        <label class="form-label small text-muted">WHT Document (50 ทวิ)</label>
                                        <input type="file" class="d-none" :id="'whtInput-' + editingTransaction.id" @change="handleWhtFileSelect" accept=".pdf,image/*">
                                        <div class="btn-group w-100">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="document.getElementById('whtInput-' + editingTransaction.id).click()">
                                                <i class="bi bi-file-earmark-text me-1"></i> <span x-text="editingTransaction.wht_file ? 'Change WHT File' : 'Upload WHT'"></span>
                                            </button>
                                            <a x-show="editingTransaction.wht_document_path && !editingTransaction.wht_file" :href="'/storage/' + editingTransaction.wht_document_path" target="_blank" class="btn btn-outline-info btn-sm">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Notes</label>
                                    <textarea class="form-control form-control-sm" x-model="editingTransaction.notes" rows="2"></textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Proof of Payment / Slip</label>
                                    <!-- Hidden Input -->
                                    <input type="file" class="d-none" :id="'slipInput-' + editingTransaction.id" @change="handleFileSelect" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">

                                    <div class="d-flex flex-column gap-2 mt-1">
                                        <div class="btn-group w-100">
                                            <!-- Scan/Edit -->
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                    @click="$dispatch('open-document-scanner', {
                                                        inputId: 'slipInput-' + editingTransaction.id,
                                                        initialUrl: editingTransaction.slip_path ? '/storage/' + editingTransaction.slip_path : null
                                                    })">
                                                <i class="bi" :class="editingTransaction.slip_path ? 'bi-pencil-square' : 'bi-camera-fill'"></i>
                                                <span x-text="editingTransaction.slip_path ? 'Edit / Scan' : 'Scan Document'"></span>
                                            </button>

                                            <!-- View -->
                                            <template x-if="editingTransaction.slip_path">
                                                <a href="#" @click.prevent="viewPDF('/storage/' + editingTransaction.slip_path, 'View Slip')" class="btn btn-outline-secondary btn-sm" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </template>

                                            <!-- Download -->
                                            <template x-if="editingTransaction.slip_path">
                                                <a :href="'/storage/' + editingTransaction.slip_path" download class="btn btn-outline-secondary btn-sm" title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            </template>
                                        </div>

                                        <!-- Selected File Name -->
                                        <div x-show="selectedFile" class="small text-success">
                                            <i class="bi bi-check-circle-fill me-1"></i>
                                            <span x-text="selectedFile ? selectedFile.name : ''"></span>
                                        </div>
                                    </div>
                                </div>
                             </div>
                             <div class="col-md-6 border-start">
                                <label class="form-label small fw-bold mb-1">Edit Employees</label>
                                <ul class="nav nav-pills nav-pills-sm custom-nav-pills mb-2" style="font-size: 0.75rem;">
                                    <li class="nav-item flex-fill text-center">
                                        <button class="nav-link w-100 py-0" :class="{ 'active': editTransactionEmployeeFilter === 'active' }" @click="editTransactionEmployeeFilter = 'active'" type="button">Active</button>
                                    </li>
                                    <li class="nav-item flex-fill text-center">
                                        <button class="nav-link w-100 py-0" :class="{ 'active': editTransactionEmployeeFilter === 'cancelled' }" @click="editTransactionEmployeeFilter = 'cancelled'" type="button">Cancelled</button>
                                    </li>
                                </ul>
                                <div class="border rounded bg-light" style="max-height: 250px; overflow-y: auto;">
                                    <div class="list-group list-group-flush">
                                        <!-- Show ALL items for edit (Available + Currently Attached) -->
                                        <template x-for="item in editModalItems" :key="item.id">
                                            <label class="list-group-item py-1 px-2 d-flex gap-2 align-items-center bg-white" style="font-size: 0.8rem;">
                                                <input class="form-check-input mt-0" type="checkbox" :value="item.id" x-model="selectedTransactionItems" @change="recalcEditAmount()">
                                                <span class="badge bg-light text-dark border ms-auto" x-text="getItemPrice(item.id)" style="font-size: 0.65rem;"></span>
                                                <div class="d-flex align-items-center gap-2 overflow-hidden w-100">
                                                    <img :src="item.photo || 'https://ui-avatars.com/api/?name=User&background=random'" class="rounded-circle flex-shrink-0" style="width: 24px; height: 24px; object-fit: cover;">
                                                    <div class="d-flex flex-column" style="line-height: 1.1; min-width: 0;">
                                                        <div class="fw-bold text-truncate">
                                                            <span x-text="item.name_en ? (item.title_en + ' ' + item.name_en) : item.name"></span>
                                                            <template x-if="item.insurance_type && item.insurance_type !== '-' && item.insurance_type !== 'No' && item.insurance_type !== 'ไม่มี'">
                                                                <span class="badge rounded-pill ms-1" style="font-size: 0.6rem;"
                                                                    :class="{
                                                                        'bg-primary': item.insurance_type === 'ประกันสังคม',
                                                                        'bg-warning text-dark': item.insurance_type === 'ประกันเอกชน',
                                                                        'bg-success': item.insurance_type === 'ประกันโรงพยาบาล',
                                                                        'bg-secondary': item.insurance_type !== 'ประกันสังคม' && item.insurance_type !== 'ประกันเอกชน' && item.insurance_type !== 'ประกันโรงพยาบาล'
                                                                    }"
                                                                    x-text="item.insurance_type"></span>
                                                            </template>
                                                            <template x-if="!item.insurance_type || item.insurance_type === '-' || item.insurance_type === 'No' || item.insurance_type === 'ไม่มี'">
                                                                <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.6rem;">None</span>
                                                            </template>
                                                            <!-- Passport Badge -->
                                                            <template x-if="item.passport && item.passport !== '-' && item.passport !== 'No' && item.passport !== 'ไม่มี'">
                                                                <span class="badge bg-info text-dark rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('Passport') }}</span>
                                                            </template>
                                                            <template x-if="!item.passport || item.passport === '-' || item.passport === 'No' || item.passport === 'ไม่มี'">
                                                                <span class="badge bg-light text-dark border rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('No Passport') }}</span>
                                                            </template>
                                                            <!-- Last Step Badge -->
                                                            <template x-if="item.last_step_name">
                                                                <span class="badge border border-primary text-primary rounded-pill ms-1" style="font-size: 0.6rem;" x-text="item.last_step_name"></span>
                                                            </template>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.7rem;">
                                                             <span class="text-truncate" x-text="item.nationality"></span>
                                                             <template x-if="item.nationality === 'Myanmar' || item.nationality === 'เมียนมา' || item.nationality === 'พม่า'"><span style="font-size: 0.8rem;">🇲🇲</span></template>
                                                             <template x-if="item.nationality === 'Laos' || item.nationality === 'ลาว'"><span style="font-size: 0.8rem;">🇱🇦</span></template>
                                                             <template x-if="item.nationality === 'Cambodia' || item.nationality === 'กัมพูชา'"><span style="font-size: 0.8rem;">🇰🇭</span></template>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span x-show="isItemAttached(item.id)" class="badge bg-success ms-auto flex-shrink-0" style="font-size: 0.6em;">Linked</span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                             </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100 mt-3">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Selection Modal -->
    <div class="modal fade" :id="'documentSelectionModal-' + productionId" tabindex="-1" x-ref="docSelectionModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Select Items</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group list-group-flush mb-3">
                        <template x-for="t in modalFilteredTransactions" :key="t.id">
                            <label class="list-group-item px-0 py-2 d-flex gap-2">
                                <input class="form-check-input" type="checkbox" :value="t.id" x-model="selectedTransactionIds">
                                <div class="small">
                                    <div class="fw-bold" x-text="formatType(t.type)"></div>
                                    <div class="text-muted" x-text="formatCurrency(t.amount)"></div>
                                    <div class="text-muted" style="font-size: 0.75rem;" x-text="t.notes"></div>
                                </div>
                            </label>
                        </template>
                        <div x-show="modalFilteredTransactions.length === 0" class="text-center text-muted py-3">
                            No transactions available for this type.
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" :id="'includeEmployeeList-' + productionId" x-model="includeEmployeeList">
                        <label class="form-check-label small fw-bold" :for="'includeEmployeeList-' + productionId">
                            แนบตารางรายชื่อพนักงาน / Include Employee List
                        </label>
                    </div>

                    <button class="btn btn-primary btn-sm w-100" @click="generateSelectedDocument()" :disabled="selectedTransactionIds.length === 0">
                        Generate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Load the external script (ensure it's loaded only once) -->
<script src="{{ asset('js/financial-manager.js') }}"></script>
@else
    <div class="alert alert-danger">
        <i class="bi bi-lock-fill me-2"></i> {{ __('Access Denied: You do not have permission to view financial data.') }}
    </div>
@endcan
