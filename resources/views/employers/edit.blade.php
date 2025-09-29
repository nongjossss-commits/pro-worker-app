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
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกข้อมูลนายจ้าง</button>
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>

<div id="addressListsContainer">
    @include('employers._address_lists', ['employer' => $employer])
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
    <a href="{{ route('employees.create', ['employer_id' => $employer->id]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-person-plus"></i> เพิ่มพนักงาน</a>
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
                            <td>
                                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
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
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('jobs.create_from_employee', $employee) }}" class="btn btn-outline-success" title="สร้างงาน">
                                        <i class="bi bi-send-plus"></i>
                                    </a>
                                    <a href="{{ route('employees.edit', ['employer' => $employee->employer_id, 'employee' => $employee->id]) }}" class="btn btn-outline-primary" title="แก้ไข">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    @if(isset($showLocateButton) && $showLocateButton)
                                        <a href="{{ route('employees.locate', $employee) }}" class="btn btn-outline-info" title="ไปที่ข้อมูลนายจ้าง">
                                            <i class="bi bi-geo-alt-fill"></i>
                                        </a>
                                    @endif

                                    <button type="button" class="btn btn-outline-warning terminate-employee-btn" data-id="{{ $employee->id }}" title="แจ้งออก/เลิกจ้าง">
                                        <i class="bi bi-person-dash-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger delete-employee-btn" data-id="{{ $employee->id }}" title="ลบข้อมูล (ถาวร)">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
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
<div class="content-section mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">ประวัติการจ้างงาน</h5>
        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#historyModal">
            <i class="bi bi-clock-history"></i> ดูประวัติการจ้างงาน
        </button>
    </div>
    <p>ดูประวัติพนักงานที่เคยจ้างงานทั้งหมดได้ที่นี่</p>
</div>

@endsection

@include('partials._address_management')

{{-- Employment History Modal --}}
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">ประวัติการจ้างงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" id="searchHistoryInput" placeholder="ค้นหาในประวัติ..." style="width: 200px;">
                    </div>
                    <a href="{{ route('employers.exportHistory', $employer) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> ส่งออก</a>
                </div>
                <div id="employmentHistoryList" class="vstack gap-3">
                    {{-- Terminated employees will be loaded here via JavaScript --}}
                     <p class="text-muted">กำลังโหลด...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

{{-- Terminate Employee Modal --}}
<div class="modal fade" id="terminateEmployeeModal" tabindex="-1" aria-labelledby="terminateEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminateEmployeeModalLabel">แจ้งออก / เลิกจ้าง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="terminateEmployeeForm">
                    <input type="hidden" id="terminateEmployeeId">
                    <div class="mb-3">
                        <label for="terminateDate" class="form-label">วันที่แจ้งออก / เลิกจ้าง</label>
                        <input type="date" class="form-control" id="terminateDate" required>
                    </div>
                     <div class="mb-3">
                        <label for="terminationReason" class="form-label">เหตุผล</label>
                        <textarea class="form-control" id="terminationReason" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="confirmTerminateEmployeeButton">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
// Pass configuration data from Laravel to JavaScript
window.employerEditConfig = {
    employerId: {{ $employer->id }},
    urls: {
        addressesStore: "{{ route('addresses.store') }}",
        addressesList: "{{ route('employers.addresses.list', $employer->id) }}",
        addressDataJson: "{{ asset('storage/data/thai-address-data.json') }}",
        historyFilter: "{{ route('employers.history.filter', $employer->id) }}"
    }
};
</script>
<script src="{{ asset('js/employer-form-edit.js') }}"></script>
@endsection
