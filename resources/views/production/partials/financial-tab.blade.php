@can('view-finance')
@php
    // ตรวจให้แน่ใจว่า relations ที่ x-data ต้องใช้โหลดครบ
    // สำคัญสำหรับ Workflow ที่ controller ไม่ได้ eager load financialGroups
    // ใช้ loadMissing เพื่อ no-op ถ้า controller โหลดมาแล้ว (Pre-Prod, Sales, Registration, Renewal)
    $production->loadMissing([
        'financialGroups.transactions.items',
        'financialGroups.transactions.payments',
        'financialGroups.advanceItems',
    ]);
@endphp
<div x-data="financialManager({
    financialGroups: {{ json_encode($production->financialGroups) }},
    transactions: {{ json_encode($production->financialGroups->pluck('transactions')->flatten()) }},
    productionItems: {{ json_encode($production->items->map(function($item) {
        $lastStepName = null;
        $emp = $item->employee;
        $tempData = $item->new_employee_data;

        if ($emp) {
            if (request()->is('production/registration*')) {
                $lastStepName = $emp->registrationSteps->sortByDesc('order')->first()?->name;
            } elseif (request()->is('production/renewal*')) {
                $lastStepName = $emp->registrationSteps->sortByDesc('order')->first()?->name;
            } else {
                $lastStepName = $item->completedWorkTypeSteps?->sortByDesc('order')->first()?->name;
            }
        }

        return [
            'id' => $item->id,
            'name' => $emp ? ($emp->employeeNameTh ?? $emp->employeeNameEn) : ($tempData['name_th'] ?? $tempData['name_en'] ?? 'ลูกจ้างชั่วคราว'),
            'name_en' => $emp ? $emp->employeeNameEn : ($tempData['name_en'] ?? ''),
            'title_en' => $emp ? $emp->employeeTitleEn : ($tempData['title_en'] ?? ''),
            'photo' => $emp ? ($emp->employeePhoto ? asset('storage/' . $emp->employeePhoto) : '') : '',
            'nationality' => $emp ? $emp->employeeNationality : ($tempData['nationality'] ?? ''),
            'insurance_type' => $emp ? $emp->insurance_type : '',
            'passport' => $emp ? $emp->employeePassport : ($tempData['passport'] ?? ''),
            'has_visa' => $emp ? !empty($emp->visaExpiryDate) : false,
            'employee_id' => $item->employee_id,
            'last_step_name' => $lastStepName,
            'status' => $item->status,
            // Extra searchable identifiers — see matchesEmployeeSearch() in
            // financial-manager.js. Temp (not-yet-created) employees have
            // none of these yet, so they simply won't match on them.
            'work_permit' => $emp ? $emp->employeeWorkPermit : '',
            'id_number' => $emp ? $emp->employee_id_number : '',
            'ra_number' => $emp ? $emp->name_list_number : '',
            'reference_id' => $emp ? $emp->employee_reference_id : ''
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
            'photo' => $emp->employeePhoto ? asset('storage/' . $emp->employeePhoto) : '',
            'nationality' => $emp->employeeNationality,
            'insurance_type' => $emp->insurance_type,
            'passport' => $emp->employeePassport,
            'has_visa' => !empty($emp->visaExpiryDate),
            'last_step_name' => $lastStepName,
            'status' => $emp->status,
            'work_permit' => $emp->employeeWorkPermit,
            'id_number' => $emp->employee_id_number,
            'ra_number' => $emp->name_list_number,
            'reference_id' => $emp->employee_reference_id
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
            <ul class="nav nav-tabs border-0 flex-wrap" id="financialTabs" role="tablist">
                <template x-for="(group, index) in financialGroups" :key="group.id">
                    <li class="nav-item d-flex align-items-stretch" role="presentation">
                        {{-- Tab switch button --}}
                        <button class="nav-link pe-2"
                                :class="{ 'active fw-bold': activeGroupId === group.id }"
                                @click="switchGroup(group.id)"
                                type="button"
                                role="tab"
                                @dblclick.prevent="renameGroup(group.id, group.name)">
                            <span x-text="group.name || '{{ __('Untitled') }}'"></span>
                        </button>
                        {{-- Inline action toolbar (sibling of tab button — reliable click, not nested) --}}
                        <div class="d-flex align-items-center gap-1 ps-1 pe-2"
                             :style="activeGroupId === group.id ? '' : 'border-bottom: 1px solid #dee2e6;'">
                            <button type="button"
                                    class="btn btn-link btn-sm p-1 text-muted"
                                    @click.prevent="renameGroup(group.id, group.name)"
                                    :title="'{{ __('Rename Tab') }}'"
                                    style="line-height: 1;">
                                <i class="bi bi-pencil-square" style="font-size: 0.85rem;"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-link btn-sm p-1 text-danger"
                                    x-show="index > 0"
                                    @click.prevent="deleteGroup(group.id)"
                                    :title="'{{ __('Delete Tab') }}'"
                                    style="line-height: 1;">
                                <i class="bi bi-x-circle-fill" style="font-size: 0.85rem;"></i>
                            </button>
                        </div>
                    </li>
                </template>
                <li class="nav-item ms-2 align-self-center">
                    <button class="btn btn-sm btn-outline-success" @click="addNewGroup()">
                        <i class="bi bi-plus-circle-fill me-1"></i>{{ __('Add Tab') }}
                    </button>
                </li>
            </ul>
        </div>
        <div class="small text-muted mt-1 ps-1" style="font-size: 0.75rem;">
            <i class="bi bi-info-circle me-1"></i>{{ __('คลิกที่ไอคอน ดินสอ เพื่อตั้งชื่อแท็บ (เช่น "ค่ามัดจำ", "ค่าวีซ่า", "งวดเปลี่ยนนายจ้าง") — หรือดับเบิ้ลคลิกที่ชื่อแท็บก็ได้') }}
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
                    <label class="form-label">{{ __('Total Project Value') }} <span x-show="vatEnabled && !vatIncluded">{{ __('(Excl. VAT)') }}</span><span x-show="vatEnabled && vatIncluded">{{ __('(Incl. VAT)') }}</span></label>
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

                    {{-- Stacked card layout — each tier is a card with controls on top + full-width note below --}}
                    <div class="border rounded p-2 mb-2 bg-light">
                        <template x-for="(tier, index) in pricingTiers" :key="index">
                            <div class="card border mb-2 shadow-sm">
                                <div class="card-body p-2">
                                    {{-- Row 1: Price | Assigned | Delete --}}
                                    <div class="d-flex align-items-end gap-2 flex-wrap mb-2">
                                        <div style="min-width: 130px; flex: 0 0 auto;">
                                            <label class="form-label text-muted small mb-1" style="font-size: 0.7rem;">{{ __('Price (฿)') }}</label>
                                            <input type="number" class="form-control form-control-sm" x-model="tier.price" @input="updateTotal()" placeholder="Price">
                                        </div>
                                        <div style="min-width: 130px; flex: 0 0 auto;">
                                            <label class="form-label text-muted small mb-1" style="font-size: 0.7rem;">{{ __('Assigned') }}</label>
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
                                        </div>
                                        <div class="ms-auto">
                                            <button type="button"
                                                    class="btn btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    @click="removeTier(index)"
                                                    :title="'{{ __('ลบงวดราคานี้') }}'"
                                                    style="width: 38px; height: 38px; padding: 0;">
                                                <i class="bi bi-trash" style="font-size: 1rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Row 2: Note (full-width) + Edit pencil --}}
                                    <div>
                                        <label class="form-label text-muted small mb-1" style="font-size: 0.7rem;">
                                            {{ __('Note') }}
                                            <i class="bi bi-info-circle ms-1" title="{{ __('หมายเหตุจะแสดงในใบแจ้งหนี้/ใบเสร็จด้วย') }}" style="font-size: 0.7rem;"></i>
                                        </label>
                                        <div class="d-flex align-items-stretch gap-2">
                                            <div class="flex-grow-1 small bg-white border rounded px-2 py-1"
                                                 style="word-break: break-word; word-wrap: break-word; overflow-wrap: anywhere; white-space: pre-wrap; min-height: 38px; max-height: 120px; overflow-y: auto; cursor: pointer;"
                                                 @click="editTierNote(index)"
                                                 :title="'{{ __('คลิกเพื่อแก้ไขหมายเหตุ') }}'">
                                                <template x-if="tier.note && tier.note.trim()">
                                                    <span x-text="tier.note"></span>
                                                </template>
                                                <template x-if="!tier.note || !tier.note.trim()">
                                                    <span class="text-muted fst-italic">{{ __('— คลิกเพื่อเพิ่มหมายเหตุ —') }}</span>
                                                </template>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary d-flex align-items-center justify-content-center flex-shrink-0"
                                                    @click.stop="editTierNote(index)"
                                                    :title="'{{ __('แก้ไขหมายเหตุ') }}'"
                                                    style="width: 38px; min-height: 38px; padding: 0;">
                                                <i class="bi bi-pencil-square" style="font-size: 1rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="pricingTiers.length === 0">
                            <div class="text-center text-muted small fst-italic py-3">— {{ __('ยังไม่มีงวดราคา — กดปุ่ม "Add Tier" ด้านบนเพื่อเพิ่ม') }} —</div>
                        </template>
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

                <!-- Discount: ย้ายไปอยู่ในแต่ละ Installment/Transaction แล้ว -->
                <div class="mb-3">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>{{ __('ส่วนลด: ตั้งค่าได้ในแต่ละรายการ Installment ที่สร้างขึ้น') }}</small>
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

                    <!-- VAT Toggle -->
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2" :class="vatEnabled ? 'border-bottom' : ''">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" :id="'vatEnabled_' + productionId" x-model="vatEnabled" @change="updateTotal()">
                            <label class="form-check-label small fw-bold" :for="'vatEnabled_' + productionId">{{ __('Enable VAT') }}</label>
                        </div>
                    </div>

                    <!-- VAT Settings -->
                    <div class="d-flex align-items-center justify-content-between mb-3" x-show="vatEnabled">
                        <div class="form-check form-switch ps-4">
                            <input class="form-check-input" type="checkbox" :id="'vatIncluded_' + productionId" x-model="vatIncluded" @change="updateTotal()">
                            <label class="form-check-label small text-muted" :for="'vatIncluded_' + productionId">{{ __('Price Includes VAT') }}</label>
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
                                        <template x-if="t.discount_amount > 0">
                                            <div class="small text-danger mt-1">
                                                <i class="bi bi-tag-fill me-1"></i>ส่วนลด: <span x-text="formatCurrency(t.discount_amount)"></span>
                                                <span x-show="t.discount_description" class="text-muted ms-1">(<span x-text="t.discount_description"></span>)</span>
                                            </div>
                                        </template>
                                        <div x-show="t.slip_path" class="mt-1">
                                            <a href="#" @click.prevent="viewPDF('/storage/' + t.slip_path, 'View Slip')" class="badge bg-info text-decoration-none">View Slip</a>
                                        </div>
                                    </td>
                                    <td x-text="formatDate(t.due_date)"></td>
                                    <td class="text-end" x-text="formatCurrency(t.amount)"></td>
                                    <td class="text-end">
                                        <span x-text="formatCurrency(t.paid_amount || 0)"></span><br>
                                        <small class="text-muted" x-show="t.amount - (parseFloat(t.discount_amount) || 0) - (t.paid_amount || 0) > 0">Remaining: <span x-text="formatCurrency(Math.max(0, t.amount - (parseFloat(t.discount_amount) || 0) - (t.paid_amount || 0)))"></span></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" :class="statusClass(t.status)" x-text="formatStatus(t.status)"></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-success py-0" @click="openPayModal(t)" title="Update Payment">
                                                <i class="bi bi-cash"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary py-0" @click="openCreateInvoiceModal(t)" title="สร้างใบแจ้งหนี้ / Create Invoice">
                                                <i class="bi bi-receipt"></i>
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
                                        <template x-if="t.discount_amount > 0">
                                            <div class="small text-danger mt-1">
                                                <i class="bi bi-tag-fill me-1"></i>ส่วนลด: <span x-text="formatCurrency(t.discount_amount)"></span>
                                                <span x-show="t.discount_description" class="text-muted ms-1">(<span x-text="t.discount_description"></span>)</span>
                                            </div>
                                        </template>
                                        <div x-show="t.slip_path" class="mt-1">
                                            <a href="#" @click.prevent="viewPDF('/storage/' + t.slip_path, 'View Slip')" class="badge bg-info text-decoration-none">View Slip</a>
                                        </div>
                                    </td>
                                    <td x-text="formatDate(t.due_date)"></td>
                                    <td class="text-end text-primary" x-text="formatCurrency(t.amount)"></td>
                                    <td class="text-end">
                                        <span x-text="formatCurrency(t.paid_amount)"></span><br>
                                        <small class="text-muted" x-show="t.amount - (parseFloat(t.discount_amount) || 0) - t.paid_amount > 0">Remaining: <span x-text="formatCurrency(Math.max(0, t.amount - (parseFloat(t.discount_amount) || 0) - t.paid_amount))"></span></small>
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
                            <input type="text" class="form-control form-control-sm" placeholder="{{ __('Search by name, passport, work permit, ID no., RA no., reference no....') }}" x-model="modalSearch">
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
                                   style="cursor: pointer;">
                                <input class="form-check-input me-1 mt-0" type="checkbox" :value="item.id" x-model="modalSelectedIds">

                                <div class="d-flex align-items-center gap-2 overflow-hidden w-100">
                                    <img :src="item.photo || 'https://ui-avatars.com/api/?name=User&background=random'" class="rounded-circle flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                                    <div class="d-flex flex-column" style="line-height: 1.2; min-width: 0;">
                                        <div class="fw-bold text-wrap" style="font-size: 0.85rem;">
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
                                            <!-- Visa Badge -->
                                            <template x-if="item.has_visa">
                                                <span class="badge bg-success rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('Visa') }}</span>
                                            </template>
                                            <template x-if="!item.has_visa">
                                                <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('None') }}</span>
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
                                @foreach($production->employer?->addresses ?? [] as $address)
                                    <option value="{{ $address->id }}"
                                            data-name-th="{{ $production->employer?->employerNameTh }}"
                                            data-name-en="{{ $production->employer?->employerNameEn }}"
                                            data-tax-id="{{ $production->employer?->employerTaxId }}"
                                            data-phone="{{ $production->employer?->employerPhone }}"
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
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
                                <div class="mb-2 border-top pt-2">
                                    <label class="form-label small fw-bold text-danger"><i class="bi bi-tag me-1"></i>{{ __('Discount / ส่วนลด') }}</label>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text">฿</span>
                                        <input type="number" class="form-control" x-model="newTransaction.discount_amount" placeholder="0.00" step="0.01" min="0">
                                    </div>
                                    <input type="text" class="form-control form-control-sm" x-model="newTransaction.discount_description" placeholder="{{ __('รายละเอียดส่วนลด เช่น ลดราคาพิเศษ') }}">
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
                                <input type="text" class="form-control form-control-sm mb-2" placeholder="{{ __('Search by name, passport, work permit, ID no., RA no., reference no....') }}" x-model="newTransactionSearch">
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
                                                        <div class="fw-bold text-wrap">
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
                                                            <!-- Visa Badge -->
                                                            <template x-if="item.has_visa">
                                                                <span class="badge bg-success rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('Visa') }}</span>
                                                            </template>
                                                            <template x-if="!item.has_visa">
                                                                <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('Not Visa') }}</span>
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
                                        <div class="small text-muted mb-1 d-flex justify-content-between align-items-center">
                                            <span x-text="pay.bank_account ? pay.bank_account.bank_name : 'No Account'"></span>
                                            <div class="d-flex gap-1 align-items-center">
                                                <template x-if="pay.receipt_generated_at">
                                                    <span class="badge bg-success" style="font-size: 0.6rem;" title="Document Generated"><i class="bi bi-check-circle"></i> Generated</span>
                                                </template>
                                                <template x-if="!pay.receipt_generated_at">
                                                    <span class="badge bg-secondary" style="font-size: 0.6rem;" title="Not Generated"><i class="bi bi-dash-circle"></i> Not Generated</span>
                                                </template>
                                                <a x-show="pay.slip_path" :href="'/storage/' + pay.slip_path" target="_blank" class="badge bg-success text-decoration-none" title="ดูไฟล์"><i class="bi bi-eye-fill"></i></a>
                                                <a x-show="pay.slip_path" href="#" @click.prevent="viewPDF('/storage/' + pay.slip_path, 'View Slip')" class="badge bg-danger text-decoration-none" title="PDF"><i class="bi bi-file-earmark-pdf-fill"></i></a>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="small fst-italic text-muted text-truncate" style="max-width: 40%;" x-text="pay.notes"></div>
                                            <div class="d-flex gap-1">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Doc
                                                    </button>
                                                    <ul class="dropdown-menu shadow" style="font-size: 0.8rem;">
                                                        <li><a class="dropdown-item py-1" href="#" @click.prevent="generatePaymentDocument(pay.id, 'receipt')"><i class="bi bi-receipt me-1"></i> Receipt</a></li>
                                                        <li><a class="dropdown-item py-1" href="#" @click.prevent="generatePaymentDocument(pay.id, 'tax_invoice')"><i class="bi bi-file-earmark-text me-1"></i> Tax Invoice</a></li>
                                                    </ul>
                                                </div>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" @click="editPayment(pay)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" @click="deletePayment(pay.id)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="!editingTransaction.payments || editingTransaction.payments.length === 0" class="text-center text-muted p-3 small">
                                    No payments recorded yet.
                                </div>
                            </div>

                            <!-- Add New Payment Form -->
                            <div class="card shadow-sm border-success" :class="{'border-primary': editingPaymentId, 'border-success': !editingPaymentId}">
                                <div class="card-header text-white py-1 small fw-bold d-flex justify-content-between align-items-center" :class="{'bg-primary': editingPaymentId, 'bg-success': !editingPaymentId}">
                                    <span x-text="editingPaymentId ? 'Edit Payment' : 'Add Payment'"></span>
                                    <button type="button" class="btn-close btn-close-white" style="font-size: 0.5rem;" x-show="editingPaymentId" @click="cancelEditPayment()"></button>
                                </div>
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
                                        <label class="form-label small mb-0">Slip / หลักฐานการจ่ายเงิน</label>
                                        <div class="input-group input-group-sm">
                                            <input type="file" id="paymentSlipInput" class="form-control form-control-sm" onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)" @change="handlePaymentSlipSelect" accept="image/*,application/pdf" multiple>
                                            <button type="button" class="btn btn-outline-secondary" title="สแกน/ถ่ายรูป" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'paymentSlipInput' } }))">
                                                <i class="bi bi-camera"></i>
                                            </button>
                                        </div>
                                        <div x-show="editingPaymentId && newPayment.slip_path && !paymentSlipFile" class="mt-1 d-flex gap-1">
                                            <a :href="'/storage/' + newPayment.slip_path" target="_blank" class="btn btn-success btn-sm py-0 px-2" title="ดูไฟล์">
                                                <i class="bi bi-eye-fill"></i> ดูไฟล์
                                            </a>
                                            <a href="#" @click.prevent="viewPDF('/storage/' + newPayment.slip_path, 'View Slip')" class="btn btn-danger btn-sm py-0 px-2" title="ดู PDF">
                                                <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                            </a>
                                        </div>
                                        <div x-show="paymentSlipFile" class="mt-1 small text-warning">
                                            <i class="bi bi-info-circle"></i> เลือกไฟล์ใหม่แล้ว
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">Notes</label>
                                        <input type="text" class="form-control form-control-sm" x-model="newPayment.notes">
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm w-100" :class="editingPaymentId ? 'btn-primary' : 'btn-success'" @click="editingPaymentId ? updatePayment() : addPayment()" :disabled="isSavingPayment">
                                            <span x-show="isSavingPayment" class="spinner-border spinner-border-sm me-1"></span>
                                            <span x-text="editingPaymentId ? 'Save Changes' : 'Save Payment'"></span>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm w-50" x-show="editingPaymentId" @click="cancelEditPayment()">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Transaction Options & Items -->
                        <div class="col-md-7 border-start ps-3">
                            <form @submit.prevent="updateTransaction" :id="'updateTransactionForm-' + productionId">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Transaction Options</h6>

                                <!-- Summary Display -->
                                <div class="d-flex justify-content-between small mb-3 bg-light p-2 rounded border flex-wrap gap-1">
                                    <div><span class="text-muted">Total:</span> <span class="fw-bold" x-text="formatCurrency(editingTransaction.amount)"></span></div>
                                    <div x-show="editingTransaction.discount_amount > 0"><span class="text-muted">Discount:</span> <span class="fw-bold text-warning" x-text="'-' + formatCurrency(editingTransaction.discount_amount)"></span></div>
                                    <div><span class="text-muted">Paid:</span> <span class="fw-bold text-success" x-text="formatCurrency(editingTransaction.paid_amount || 0)"></span></div>
                                    <div><span class="text-muted">Remaining:</span> <span class="fw-bold text-danger" x-text="formatCurrency(Math.max(0, editingTransaction.amount - (parseFloat(editingTransaction.discount_amount) || 0) - (editingTransaction.paid_amount || 0)))"></span></div>
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

                                <!-- Discount / ส่วนลด -->
                                <div class="card border-warning mb-2">
                                    <div class="card-header py-1 px-2 bg-warning bg-opacity-10">
                                        <label class="form-label small mb-0 text-warning fw-bold"><i class="bi bi-tag-fill me-1"></i>Discount / ส่วนลด</label>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="row g-2">
                                            <div class="col-5">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">฿</span>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" x-model="editingTransaction.discount_amount" placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="col-7">
                                                <input type="text" class="form-control form-control-sm" x-model="editingTransaction.discount_description" placeholder="รายละเอียดส่วนลด">
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-1" x-show="editingTransaction.discount_amount > 0">
                                            ยอดหลังหักส่วนลด: <span class="fw-bold text-success" x-text="formatCurrency(editingTransaction.amount - (parseFloat(editingTransaction.discount_amount) || 0))"></span>
                                        </div>
                                    </div>
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
                                <input type="text" class="form-control form-control-sm mb-2" placeholder="{{ __('Search by name, passport, work permit, ID no., RA no., reference no....') }}" x-model="editTransactionSearch">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small text-muted" style="font-size: 0.75rem;">
                                        Selected: <span x-text="selectedTransactionItems.length"></span>
                                        <span x-show="pricingMode === 'per_head'">(Auto-calc active)</span>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary py-0" style="font-size: 0.7rem;" @click="selectedTransactionItems = [...new Set([...selectedTransactionItems, ...editModalItems.map(i => i.id)])]; recalcEditAmount()">All</button>
                                        <button type="button" class="btn btn-outline-secondary py-0" style="font-size: 0.7rem;" @click="selectedTransactionItems = []; recalcEditAmount()">None</button>
                                    </div>
                                </div>
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
                                                        <div class="fw-bold text-wrap">
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
                                                            <!-- Visa Badge -->
                                                            <template x-if="item.has_visa">
                                                                <span class="badge bg-success rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('Visa') }}</span>
                                                            </template>
                                                            <template x-if="!item.has_visa">
                                                                <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.6rem;">{{ __('Not Visa') }}</span>
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
                        <button type="submit" :form="'updateTransactionForm-' + productionId" class="btn btn-success btn-sm w-100 mt-3">Update</button>
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

                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-2">{{ __('Document Variant') }}</label>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" :id="'docVariantInvoice-' + productionId" value="invoice" x-model="docVariant">
                            <label class="form-check-label" :for="'docVariantInvoice-' + productionId">
                                {{ __('Document only (no list)') }}
                            </label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" :id="'docVariantWithList-' + productionId" value="with_list" x-model="docVariant">
                            <label class="form-check-label" :for="'docVariantWithList-' + productionId">
                                {{ __('Document + Employee List') }}
                            </label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" :id="'docVariantListOnly-' + productionId" value="list_only" x-model="docVariant">
                            <label class="form-check-label" :for="'docVariantListOnly-' + productionId">
                                {{ __('Employee List only (no document)') }}
                            </label>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-sm w-100" @click="generateSelectedDocument()" :disabled="selectedTransactionIds.length === 0">
                        Generate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Invoice Modal (per-row from Income transactions) -->
    <div class="modal fade" :id="'createInvoiceModal-' + productionId" tabindex="-1" x-ref="createInvoiceModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">
                        <i class="bi bi-receipt me-1 text-primary"></i> สร้างใบแจ้งหนี้ / Create Invoice
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 small text-muted">
                        งวดงาน: <span class="fw-bold text-dark" x-text="invoiceModalLabel"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-2">{{ __('Document Variant') }}</label>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" :id="'invVariantInvoice-' + productionId" value="invoice" x-model="invoiceModalVariant">
                            <label class="form-check-label" :for="'invVariantInvoice-' + productionId">
                                {{ __('Document only (no list)') }}
                            </label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" :id="'invVariantWithList-' + productionId" value="with_list" x-model="invoiceModalVariant">
                            <label class="form-check-label" :for="'invVariantWithList-' + productionId">
                                {{ __('Document + Employee List') }}
                            </label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" :id="'invVariantListOnly-' + productionId" value="list_only" x-model="invoiceModalVariant">
                            <label class="form-check-label" :for="'invVariantListOnly-' + productionId">
                                {{ __('Employee List only (no document)') }}
                            </label>
                        </div>
                    </div>

                    {{-- Payment Methods (ช่องทางการชำระเงิน) — appears at bottom-left of generated PDF --}}
                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="small fw-bold mb-2">
                            <i class="bi bi-credit-card-2-front me-1"></i> {{ __('Payment Methods') }}
                            <span class="text-muted fw-normal">— {{ __('Tick all channels the customer may use to pay this invoice.') }}</span>
                        </div>

                        {{-- Cash --}}
                        <div class="form-check form-check-inline small mb-1">
                            <input type="checkbox" class="form-check-input" :id="'invPmCash-' + productionId" x-model="invoiceModalUsingCash">
                            <label class="form-check-label" :for="'invPmCash-' + productionId">
                                <i class="bi bi-cash-stack me-1"></i> {{ __('Cash') }}
                            </label>
                        </div>

                        {{-- PromptPay --}}
                        <div class="form-check form-check-inline small mb-1">
                            <input type="checkbox" class="form-check-input" :id="'invPmPP-' + productionId" x-model="invoiceModalUsingPromptPay">
                            <label class="form-check-label" :for="'invPmPP-' + productionId">
                                <i class="bi bi-qr-code me-1"></i> {{ __('PromptPay') }}
                            </label>
                        </div>
                        <div x-show="invoiceModalUsingPromptPay" class="mb-2">
                            <input type="text" class="form-control form-control-sm" x-model="invoiceModalPromptPayId" placeholder="{{ __('Phone (10 digits) or Tax ID (13 digits)') }}">
                        </div>

                        {{-- Bank Transfer --}}
                        <div class="form-check small mb-1">
                            <input type="checkbox" class="form-check-input" :id="'invPmTr-' + productionId" x-model="invoiceModalUsingTransfer" @change="onInvoiceUsingTransferChange">
                            <label class="form-check-label fw-bold" :for="'invPmTr-' + productionId">
                                <i class="bi bi-bank2 me-1"></i> {{ __('Bank Transfer') }}
                            </label>
                        </div>

                        <div x-show="invoiceModalUsingTransfer" class="ms-1">
                            {{-- Warn if biller profile isn't selected --}}
                            <div x-show="!invoiceModalBillerProfileId" class="alert alert-warning small py-1 px-2 mb-2">
                                {{ __('No biller profile attached. You can still add a bank manually with Custom.') }}
                            </div>

                            {{-- Selected accounts list --}}
                            <template x-for="(t, idx) in invoiceModalTransferList" :key="idx">
                                <div class="d-flex align-items-center gap-2 border rounded p-1 mb-1 bg-white">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                          :style="`background:${invoiceBankBadge(t).color}; width:26px; height:26px; font-size:10px;`"
                                          x-text="invoiceBankBadge(t).initial"></span>
                                    <div class="small flex-grow-1" style="min-width:0;">
                                        <div class="fw-bold text-truncate" x-text="t.bank_name || '—'"></div>
                                        <div class="text-muted text-truncate" style="font-size:11px;">
                                            <span x-text="t.account_name || ''"></span>
                                            <span x-show="t.account_name && t.account_number"> · </span>
                                            <span x-text="t.account_number || ''"></span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" @click="removeInvoiceTransfer(idx)"><i class="bi bi-x"></i></button>
                                </div>
                            </template>

                            {{-- "Add another" button when picker closed --}}
                            <div x-show="!invoiceModalBankPickerOpen && !invoiceModalShowCustomBank && invoiceModalTransferList.length > 0" class="mt-1">
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" @click="openInvoiceBankPicker">
                                    <i class="bi bi-plus-circle"></i> {{ __('Add another bank account') }}
                                </button>
                            </div>

                            {{-- Picker dropdown --}}
                            <div x-show="invoiceModalBankPickerOpen" class="border rounded p-1 mt-1 bg-white">
                                <div class="d-flex gap-1 align-items-center mb-1">
                                    <input type="text" class="form-control form-control-sm flex-grow-1" x-model="invoiceModalBankSearch" placeholder="{{ __('Search bank...') }}">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="openInvoiceCustomBank" title="{{ __('Custom') }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0" @click="closeInvoiceBankPicker" title="{{ __('Close') }}">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <div style="max-height:200px; overflow-y:auto;">
                                    <template x-if="filteredInvoiceProfileBanks.length > 0">
                                        <div>
                                            <div class="px-1 py-1 text-muted bg-light fw-bold" style="font-size:11px;">{{ __('From this profile') }}</div>
                                            <template x-for="acc in filteredInvoiceProfileBanks" :key="'p'+acc.id">
                                                <div class="d-flex align-items-center gap-2 p-1 bank-pick" style="cursor:pointer;" @click="addInvoiceProfileTransfer(acc)">
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold"
                                                          :style="`background:${invoiceBankBadge(acc).color}; width:22px; height:22px; font-size:10px;`"
                                                          x-text="invoiceBankBadge(acc).initial"></span>
                                                    <div class="small flex-grow-1" style="min-width:0;">
                                                        <div class="fw-bold text-truncate" x-text="acc.bank_name"></div>
                                                        <div class="text-muted text-truncate" style="font-size:11px;" x-text="(acc.account_name || '') + ' · ' + (acc.account_number || '')"></div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <div x-show="invoiceModalBillerProfileId && invoiceProfileTransferBanks.length === 0" class="px-2 py-1 small text-muted">
                                        {{ __('No bank accounts on this profile. Use Custom or pick a preset bank below.') }}
                                    </div>

                                    <template x-if="invoiceModalThaiBanks.length > 0">
                                        <div>
                                            <div class="px-1 py-1 text-muted bg-light fw-bold mt-1" style="font-size:11px;">{{ __('Other Thai banks') }}</div>
                                            <template x-for="b in filteredInvoiceThaiBanks" :key="'b'+b.code">
                                                <div class="d-flex align-items-center gap-2 p-1 bank-pick" style="cursor:pointer;" @click="addInvoicePresetTransfer(b)">
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold"
                                                          :style="`background:${b.color}; width:22px; height:22px; font-size:10px;`"
                                                          x-text="b.initial"></span>
                                                    <span class="small flex-grow-1" x-text="b.name_th"></span>
                                                    <span class="text-muted" style="font-size:10px;" x-text="b.name_en"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Custom inline form --}}
                            <div x-show="invoiceModalShowCustomBank" class="border rounded p-1 mt-1 bg-white">
                                <div class="small fw-bold mb-1">{{ __('Custom bank account') }}</div>
                                <div class="row g-1">
                                    <div class="col-12">
                                        <input type="text" class="form-control form-control-sm" x-model="invoiceModalCustomBank.bank_name" placeholder="{{ __('Bank name') }} *">
                                    </div>
                                    <div class="col-12">
                                        <input type="text" class="form-control form-control-sm" x-model="invoiceModalCustomBank.account_name" placeholder="{{ __('Account name') }}">
                                    </div>
                                    <div class="col-8">
                                        <input type="text" class="form-control form-control-sm" x-model="invoiceModalCustomBank.account_number" placeholder="{{ __('Account number') }}">
                                    </div>
                                    <div class="col-4 d-grid">
                                        <button type="button" class="btn btn-sm btn-success" @click="addInvoiceCustomBank">
                                            <i class="bi bi-check-lg"></i> {{ __('Add') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm flex-grow-1" data-bs-dismiss="modal">
                            ยกเลิก
                        </button>
                        <button class="btn btn-primary btn-sm flex-grow-1" @click="generateInvoiceFromButton()">
                            <i class="bi bi-file-earmark-text me-1"></i> สร้าง
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Load the external script (ensure it's loaded only once) -->
<script src="{{ asset('js/financial-manager.js') }}?v={{ @filemtime(public_path('js/financial-manager.js')) }}"></script>
@else
    <div class="alert alert-danger">
        <i class="bi bi-lock-fill me-2"></i> {{ __('Access Denied: You do not have permission to view financial data.') }}
    </div>
@endcan
