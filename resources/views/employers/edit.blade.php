{{-- DEFINITIVE MASTER FIX: This is the user's full original file with the final corrections. --}}
@extends('layouts.app')


@push('styles')
<style>
    .highlight {
        animation: highlight-fade 3s ease-out forwards;
        border: 2px solid #f97316 !important; /* An orange border */
        border-radius: 0.5rem; /* Match card/row radius */
        box-shadow: 0 0 15px rgba(249, 115, 22, 0.5);
    }
    @keyframes highlight-fade {
        from { background-color: #fef9c3; } /* A light yellow */
        to { background-color: transparent; }
    }
</style>
@endpush

@section('title', 'แก้ไขข้อมูลนายจ้าง')

@section('content')

{{-- Employer Info Form --}}
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลนายจ้าง</h2>
    <form id="employerForm" action="{{ route('employers.update', $employer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h5>ข้อมูลนายจ้าง</h5>
        <hr>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">ชื่อนายจ้าง (ไทย)</label>
                <input type="text" class="form-control" id="employerNameTh" name="employerNameTh" value="{{ old('employerNameTh', $employer->employerNameTh) }}">
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">ชื่อนายจ้าง (อังกฤษ)</label>
                <input type="text" class="form-control" id="employerNameEn" name="employerNameEn" value="{{ old('employerNameEn', $employer->employerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerId" class="form-label">รหัสนายจ้าง</label>
                <input type="text" class="form-control" id="employerId" name="employerId" value="{{ old('employerId', $employer->employerId) }}" readonly required>
            </div>
            <div class="col-md-6">
                <label for="job_owner_id" class="form-label">เจ้าของงาน</label>
                <div class="input-group">
                    <select class="form-select" id="job_owner_id" name="job_owner_id">
                        <option selected disabled>--- เลือกเจ้าของงาน ---</option>
                        @foreach($jobOwners as $owner)
                            <option value="{{ $owner->id }}" {{ $employer->job_owner_id == $owner->id ? 'selected' : '' }}>{{ $owner->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#jobOwnerModal">+</button>
                    <button class="btn btn-outline-danger" type="button" id="deleteJobOwnerBtn">-</button>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">เลขประจำตัวนายจ้าง</label>
                <input type="text" class="form-control" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId', $employer->employerTaxId) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">ผู้มีอำนาจลงนาม (ไทย)</label>
                <input type="text" class="form-control" id="signerNameTh" name="signerNameTh" value="{{ old('signerNameTh', $employer->signerNameTh) }}">
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">ผู้มีอำนาจลงนาม (อังกฤษ)</label>
                <input type="text" class="form-control" id="signerNameEn" name="signerNameEn" value="{{ old('signerNameEn', $employer->signerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessType" class="form-label">ประเภทกิจการ</label>
                <input type="text" class="form-control" id="businessType" name="businessType" value="{{ old('businessType', $employer->businessType) }}">
            </div>
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">Type of Business</label>
                <input type="text" class="form-control" id="businessTypeEn" name="businessTypeEn" value="{{ old('businessTypeEn', $employer->businessTypeEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regCapital" class="form-label">ทุนจดทะเบียน</label>
                <input type="text" class="form-control" id="regCapital" name="regCapital" value="{{ old('regCapital', $employer->regCapital) }}">
            </div>
            <div class="col-md-6">
                <label for="regDate" class="form-label">จดทะเบียนวันที่</label>
                <input type="date" class="form-control" id="regDate" name="regDate" value="{{ old('regDate', $employer->regDate) }}">
            </div>
        </div>

        <hr>
        <h5>เอกสารแนบของนายจ้าง</h5>
        <div class="row">
            <div class="col-md-4">
                <label for="document_company_registration" class="form-label">หนังสือรับรองบริษัท</label>
                <input type="file" class="form-control form-control-sm" id="document_company_registration" name="document_company_registration">
                @if($employer->document_company_registration)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employer->document_company_registration) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <label for="document_vat_registration" class="form-label">ภ.พ.20</label>
                <input type="file" class="form-control form-control-sm" id="document_vat_registration" name="document_vat_registration">
                @if($employer->document_vat_registration)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employer->document_vat_registration) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <label for="document_map" class="form-label">แผนที่</label>
                <input type="file" class="form-control form-control-sm" id="document_map" name="document_map">
                @if($employer->document_map)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employer->document_map) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
        </div>
        <div class="mt-4">
            @can('edit-employers')
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกข้อมูลนายจ้าง</button>
            @endcan
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>

<div id="addressListsContainer" data-url="{{ route('addresses.thai_data') }}">
    {{-- Registered Address Section --}}
    <div class="content-section mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">ที่อยู่ตามทะเบียน</h5>
            <button type="button" class="btn btn-sm btn-outline-primary add-address-btn" data-type="registered" data-addressable-id="{{ $employer->id }}" data-addressable-type="employer" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                <i class="bi bi-plus-lg"></i> เพิ่มที่อยู่
            </button>
        </div>
        <div id="registeredAddressList" class="vstack gap-3">
            @forelse ($employer->addresses->where('type', 'registered') as $address)
                <div class="address-card d-flex justify-content-between align-items-start" id="address-card-{{$address->id}}">
                    <div>
                        <p class="mb-0">
                            เลขที่ {{ $address->addrNo ?? '' }} หมู่ {{ $address->addrMoo ?? '' }} ซอย{{ $address->addrSoi ?? '' }} ถนน{{ $address->addrRoad ?? '' }}
                            แขวง/ตำบล {{ $address->addrSubDistrict ?? '' }} เขต/อำเภอ {{ $address->addrDistrict ?? '' }}
                            {{ $address->addrProvince ?? '' }} {{ $address->addrZipCode ?? '' }}
                        </p>
                        <p class="mb-0 text-muted small">
                            Addr: {{ $address->addrNoEn ?? '' }}, Moo: {{ $address->addrMooEn ?? '' }}, Soi: {{ $address->addrSoiEn ?? '' }}, Road: {{ $address->addrRoadEn ?? '' }},
                            {{ $address->addrSubDistrictEn ?? '' }}, {{ $address->addrDistrictEn ?? '' }},
                            {{ $address->addrProvinceEn ?? '' }} {{ $address->addrZipCodeEn ?? '' }}
                        </p>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-danger delete-address-btn" data-id="{{ $address->id }}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            @empty
                <p class="text-muted">ยังไม่มีที่อยู่</p>
            @endforelse
        </div>
    </div>

    {{-- Workplace Address Section --}}
    <div class="content-section mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">ที่อยู่สถานที่ทำงาน</h5>
            <button type="button" class="btn btn-sm btn-outline-primary add-address-btn" data-type="workplace" data-addressable-id="{{ $employer->id }}" data-addressable-type="employer" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                <i class="bi bi-plus-lg"></i> เพิ่มที่อยู่
            </button>
        </div>
        <div id="workplaceAddressList" class="vstack gap-3">
            @forelse ($employer->addresses->where('type', 'workplace') as $address)
                <div class="address-card d-flex justify-content-between align-items-start" id="address-card-{{$address->id}}">
                    <div>
                        <p class="mb-0">
                            เลขที่ {{ $address->addrNo ?? '' }} หมู่ {{ $address->addrMoo ?? '' }} ซอย{{ $address->addrSoi ?? '' }} ถนน{{ $address->addrRoad ?? '' }}
                            แขวง/ตำบล {{ $address->addrSubDistrict ?? '' }} เขต/อำเภอ {{ $address->addrDistrict ?? '' }}
                            {{ $address->addrProvince ?? '' }} {{ $address->addrZipCode ?? '' }}
                        </p>
                        <p class="mb-0 text-muted small">
                            Addr: {{ $address->addrNoEn ?? '' }}, Moo: {{ $address->addrMooEn ?? '' }}, Soi: {{ $address->addrSoiEn ?? '' }}, Road: {{ $address->addrRoadEn ?? '' }},
                            {{ $address->addrSubDistrictEn ?? '' }}, {{ $address->addrDistrictEn ?? '' }},
                            {{ $address->addrProvinceEn ?? '' }} {{ $address->addrZipCodeEn ?? '' }}
                        </p>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-danger delete-address-btn" data-id="{{ $address->id }}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            @empty
                <p class="text-muted">ยังไม่มีที่อยู่</p>
            @endforelse
        </div>
    </div>
</div>


<hr class="my-4">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    @php
        $totalEmployees = $employees->total();
        $maleCount = $employer->employees()->whereIn('employeeTitleTh', ['นาย'])->count();
        $femaleCount = $employer->employees()->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();
    @endphp
    <h5>ข้อมูลลูกจ้าง (รวม: {{ $totalEmployees }} | ชาย: {{ $maleCount }} | หญิง: {{ $femaleCount }})</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('employers.exportEmployees', ['employer' => $employer->id] + request()->query()) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Export</a>
    @can('create-employees')
    <a href="{{ route('employees.create', ['employer_id' => $employer->id]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-person-plus"></i> เพิ่มพนักงาน</a>
    @endcan
</div>
</div>

{{-- NEW: Bulk Action Bar for Employer's Employee List --}}
<div id="bulk-action-bar-employer" class="alert alert-info d-flex justify-content-between align-items-center my-3" style="display: none;">
    <div>
        <input class="form-check-input" type="checkbox" id="select-all-checkbox-employer">
        <label class="form-check-label ms-2" for="select-all-checkbox-employer">
            เลือกทั้งหมด (<span id="selected-count-employer">0</span>)
        </label>
    </div>
    <button class="btn btn-primary btn-sm" disabled>ดำเนินการกับรายการที่เลือก</button>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('employers.edit', $employer->id) }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="search_employee" class="form-control form-control-sm" placeholder="ค้นหาลูกจ้าง..." value="{{ request('search_employee') }}" style="width: 150px;">

            <select name="nationality" class="form-select form-select-sm" style="width: 150px;">
                <option value="">-- ทุกสัญชาติ --</option>
                <option value="เมียนมา" @selected(request('nationality') == 'เมียนมา')>เมียนมา</option>
                <option value="ลาว" @selected(request('nationality') == 'ลาว')>ลาว</option>
                <option value="กัมพูชา" @selected(request('nationality') == 'กัมพูชา')>กัมพูชา</option>
                <option value="เวียดนาม" @selected(request('nationality') == 'เวียดนาม')>เวียดนาม</option>
            </select>

            <select name="mou_type" class="form-select form-select-sm" style="width: 200px;">
                <option value="">-- ทุกประเภท มติ. --</option>
                <option value="MOU" @selected(request('mou_type') == 'MOU')>MOU</option>
                <option value="มติต่ออายุในประเทศ" @selected(request('mou_type') == 'มติต่ออายุในประเทศ')>มติต่ออายุในประเทศ</option>
                <option value="มติขึ้นทะเบียน" @selected(request('mou_type') == 'มติขึ้นทะเบียน')>มติขึ้นทะเบียน</option>
                <option value="อื่นๆ" @selected(request('mou_type') == 'อื่นๆ')>อื่นๆ</option>
            </select>

            <select name="pink_card" class="form-select form-select-sm" style="width: 150px;">
                <option value="">-- บัตรชมพู --</option>
                <option value="has_card" @selected(request('pink_card') == 'has_card')>มีบัตรชมพู</option>
                <option value="no_card" @selected(request('pink_card') == 'no_card')>ไม่มีบัตรชมพู</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">ค้นหา</button>
            <a href="{{ route('employers.edit', $employer->id) }}" class="btn btn-secondary btn-sm">ล้างการกรอง</a>

            <div class="btn-group btn-group-sm ms-md-auto">
                <input type="radio" class="btn-check" name="view" id="view-card" value="card" onchange="this.form.submit()" @checked($currentView === 'card')>
                <label class="btn btn-outline-secondary" for="view-card"><i class="bi bi-grid-3x3-gap-fill"></i></label>

                <input type="radio" class="btn-check" name="view" id="view-table" value="table" onchange="this.form.submit()" @checked($currentView === 'table')>
                <label class="btn btn-outline-secondary" for="view-table"><i class="bi bi-table"></i></label>
            </div>

            <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                @foreach($perPageOptions as $option)
                <option value="{{ $option }}" @selected(request('per_page', $perPageOptions[0]) == $option)>แสดง {{ $option }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div id="employeeList">
    @if($currentView === 'card')
        <div class="list-group">
        @forelse($employees as $employee)
            {{-- DEFINITIVE FIX: Use the single, unified partial --}}
            @include('partials._employee_card', ['employee' => $employee, 'loop' => $loop, 'pagination' => $employees, 'showLocateButton' => false])
        @empty
            <p class="text-center text-muted">ไม่พบข้อมูลลูกจ้างที่ตรงกับเงื่อนไข</p>
        @endforelse
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width: 1rem;"><!-- Checkbox --></th>
                        <th style="width: 5%;">#</th>
                        <th style="width: 10%;">Photo</th>
                        <th style="width: 25%;">Name (EN)</th>
                        <th style="width: 25%;">Name (TH)</th>
                        <th style="width: 15%;">Passport</th>
                        <th style="width: 10%;">Nationality</th>
                        <th style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr id="employee-row-{{ $employee->id }}">
                            {{-- DEFINITIVE FIX: Add checkbox for bulk actions --}}
                            <td><input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $employee->id }}"></td>
                            <td>{{ $employees->firstItem() + $loop->index }}</td>
                            <td class="align-middle text-center" style="width: 60px;">
                                @if($employee->employeePhoto)
                                    <img src="{{ asset('storage/' . $employee->employeePhoto) }}" alt="Photo" class="img-fluid rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center text-white" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'No English Name' }}</td>
                            <td>{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'ไม่มีชื่อภาษาไทย' }}<br><small class="text-muted">{{ $employee->employeePosition ?? 'ไม่ระบุตำแหน่ง' }}</small></td>
                            <td>{{ $employee->employeePassport ?? '-' }}</td>
                            <td>
                                @php
                                    $flagCodes = ['เมียนมา' => 'mm', 'ลาว' => 'la', 'กัมพูชา' => 'kh', 'เวียดนาม' => 'vn'];
                                    $nationality = $employee->employeeNationality ?? null;
                                    $flagCode = $nationality ? ($flagCodes[$nationality] ?? null) : null;
                                @endphp
                                @if($nationality)
                                    {{ $nationality }}
                                    @if($flagCode)
                                        <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" class="ms-1" style="width: 20px; vertical-align: middle;">
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <x-employee-action-buttons :employee="$employee" :show-locate-button="false" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">ไม่พบข้อมูลลูกจ้างที่ตรงกับเงื่อนไข</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mt-3">
    {{ $employees->links() }}
</div>

{{-- Employment History Section --}}
<div class="d-flex justify-content-between align-items-center mt-5">
    <div>
        <h4 class="mb-0">ประวัติการจ้างงาน</h4>
        <p class="text-muted small">ดูประวัติพนักงานที่เคยจ้างงานทั้งหมดได้ที่นี่</p>
    </div>
    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#employmentHistoryModal">
        <i class="bi bi-clock-history me-2"></i>ดูประวัติการจ้างงาน
    </button>
</div>

    @include('partials._address_management')
    @include('partials._employee_action_modals')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const historyModalEl = document.getElementById('employmentHistoryModal');
    const historyBody = document.getElementById('history-body');

    if (historyModalEl) {
        historyModalEl.addEventListener('show.bs.modal', function () {
            historyBody.innerHTML = '<tr><td colspan="4" class="text-center">กำลังโหลด...</td></tr>';

            fetch("{{ route('employers.history.filter', $employer) }}")
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    historyBody.innerHTML = '';
                    if (data.length === 0) {
                        historyBody.innerHTML = '<tr><td colspan="4" class="text-center">ไม่พบประวัติการจ้างงาน</td></tr>';
                    } else {
                        data.forEach(employee => {
                            // Use data-employee-id and js-* classes to work with the centralized SweetAlert script
                            const restoreButton = employee.can_restore ? `<button class="btn btn-sm btn-success js-restore-btn" data-employee-id="${employee.id}">กู้คืน</button>` : '';
                            const deleteButton = employee.can_force_delete ? `<button class="btn btn-sm btn-danger js-force-delete-btn" data-employee-id="${employee.id}">ลบถาวร</button>` : '';

                            const row = `
                                <tr id="history-row-${employee.id}">
                                    <td>${employee.employeeNameTh || 'N/A'}</td>
                                    <td>${new Date(employee.terminated_at).toLocaleDateString('th-TH')}</td>
                                    <td>${employee.termination_reason || '-'}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            ${restoreButton}
                                            ${deleteButton}
                                        </div>
                                    </td>
                                </tr>
                            `;
                            historyBody.insertAdjacentHTML('beforeend', row);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching employment history:', error);
                    historyBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
                });
        });
    }
});
</script>
@endpush
