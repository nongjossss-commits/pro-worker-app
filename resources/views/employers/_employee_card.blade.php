@php
    $flagCodes = ['เมียนมา' => 'mm', 'ลาว' => 'la', 'กัมพูชา' => 'kh', 'เวียดนาม' => 'vn'];
    $nationality = $employee->employeeNationality ?? null;
    $flagCode = $nationality ? ($flagCodes[$nationality] ?? null) : null;
    $isDeleted = !is_null($employee->deleted_at);
@endphp
<div class="card mb-3 position-relative {{ $isDeleted ? 'border-danger' : '' }}" id="employee-card-{{ $employee->id }}">
    @if(isset($employee->active_workflows) && $employee->active_workflows->isNotEmpty())
        @php
            $isRegistration = $employee->active_workflows->contains('is_registration', true);
            $isRenewal = $employee->active_workflows->contains('is_renewal', true);
            $hasStandardWorkflow = $employee->active_workflows->contains(function ($value, $key) {
                return ($value->is_pre_production === false) && (!isset($value->is_registration) || !$value->is_registration) && (!isset($value->is_renewal) || !$value->is_renewal);
            });

            if ($isRegistration) {
                $overlayStyle = 'background-color: rgba(139, 92, 246, 0.15); border: 2px solid #8B5CF6;';
            } elseif ($isRenewal) {
                $overlayStyle = 'background-color: rgba(236, 72, 153, 0.15); border: 2px solid #EC4899;';
            } elseif ($hasStandardWorkflow) {
                $overlayStyle = 'background-color: rgba(255, 223, 0, 0.15); border: 2px solid #ffc107;';
            } else {
                $overlayStyle = 'background-color: rgba(13, 202, 240, 0.15); border: 2px solid #0dcaf0;';
            }
        @endphp
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center rounded"
             style="{{ $overlayStyle }} z-index: 10; pointer-events: none;">
             <div class="d-flex flex-column gap-2" style="pointer-events: auto;">
                @foreach($employee->active_workflows as $wf)
                    @php
                        if (isset($wf->is_registration) && $wf->is_registration) {
                            $style = 'background-color: #8B5CF6; color: white;';
                            $icon = 'bi-person-badge';
                            $badgeClass = '';
                        } elseif (isset($wf->is_renewal) && $wf->is_renewal) {
                            $style = 'background-color: #EC4899; color: white;';
                            $icon = 'bi-arrow-repeat';
                            $badgeClass = '';
                        } elseif (isset($wf->is_pre_production) && $wf->is_pre_production) {
                            $style = '';
                            $icon = 'bi-hourglass-split';
                            $badgeClass = 'bg-info text-dark';
                        } else {
                            $style = '';
                            $icon = 'bi-gear-fill';
                            $badgeClass = 'bg-warning text-dark';
                        }
                    @endphp
                    <a href="{{ $wf->url }}"
                       class="badge {{ $badgeClass }} text-decoration-none shadow-sm border border-dark fs-6 text-truncate"
                       style="max-width: 90%; {{ $style }}">
                       <i class="bi {{ $icon }} me-1"></i> {{ $wf->status_label }}: {{ $wf->name }}
                    </a>
                @endforeach
             </div>
        </div>
    @endif
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex align-items-center flex-grow-1">
                <div class="position-relative" style="margin-right: 1rem;">
                    <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
                         class="employee-photo-thumb"
                         style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;"
                         alt="Photo">
                    @if(isset($employee->financialStatus))
                        @if($employee->financialStatus === 'paid')
                            <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-success border border-white" title="{{ __('Fully Paid') }}" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
                        @elseif($employee->financialStatus === 'partial')
                            <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-primary border border-white" title="{{ __('Partial/Pending Payment') }}" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
                        @elseif($employee->financialStatus === 'installment_created')
                            <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border border-white" title="{{ __('Installment Created') }}" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
                        @elseif($employee->financialStatus === 'priced')
                            <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-secondary border border-white" title="{{ __('Priced') }}" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
                        @endif
                    @endif
                </div>
                <div class="flex-grow-1">
                    <p class="mb-0">
                        <strong>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'No English Name' }}</strong>
                        @if($nationality)
                            <span class="text-muted small"> - {{ $nationality }}</span>
                            @if($flagCode)
                                <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" class="ms-1" style="width: 20px; vertical-align: middle;">
                            @endif
                        @endif
                        @if($isDeleted)
                            <span class="badge bg-danger ms-2">ลบไปอยู่ในถังขยะ</span>
                        @endif
                    </p>
                    <p class="mb-1 text-muted small">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'ไม่มีชื่อภาษาไทย' }} ({{ $employee->job_title ?? 'ไม่ระบุตำแหน่ง' }})</p>
                    <p class="mb-1 text-muted small">Passport: {{ $employee->employeePassport ?? '-' }} (หมดอายุ: {{ $employee->passportExpiryDate ? \Carbon\Carbon::parse($employee->passportExpiryDate)->format('d/m/Y') : '-' }})</p>
                    <p class="mb-1 text-muted small">Work Permit: {{ $employee->employeeWorkPermit ?? '-' }} (หมดอายุ: {{ $employee->workPermitExpiryDate ? \Carbon\Carbon::parse($employee->workPermitExpiryDate)->format('d/m/Y') : '-' }})</p>
                    <p class="mb-0 text-muted small">Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) หมดอายุ: {{ $employee->visaExpiryDate ? \Carbon\Carbon::parse($employee->visaExpiryDate)->format('d/m/Y') : '-' }} | 90-Day: {{ $employee->ninetyDayReportDate ? \Carbon\Carbon::parse($employee->ninetyDayReportDate)->format('d/m/Y') : '-' }}</p>
                </div>
            </div>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-outline-primary" title="แก้ไข"><i class="bi bi-pencil-fill"></i></a>
                <button type="button" class="btn btn-outline-danger delete-employee-btn" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-url="{{ route('employees.destroy', $employee->id) }}" title="ลบ"><i class="bi bi-trash-fill"></i></button>
            </div>
        </div>
    </div>
</div>
