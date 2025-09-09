{{-- DEFINITIVE MASTER FIX: This is the user's full original file with the final corrections. --}}
@extends('layouts.app')

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

{{-- Registered Address Section --}}
<div class="content-section mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">ที่อยู่ตามทะเบียน</h5>
        <button type="button" class="btn btn-sm btn-outline-success add-address-btn" data-bs-toggle="modal" data-bs-target="#addressModal" data-address-type="registered">
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
                    <button type="button" class="btn btn-outline-secondary edit-address-btn" data-id="{{ $address->id }}" data-bs-toggle="modal" data-bs-target="#addressModal"><i class="bi bi-pencil"></i></button>
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
        <button type="button" class="btn btn-sm btn-outline-success add-address-btn" data-bs-toggle="modal" data-bs-target="#addressModal" data-address-type="workplace">
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
                    <button type="button" class="btn btn-outline-secondary edit-address-btn" data-id="{{ $address->id }}" data-bs-toggle="modal" data-bs-target="#addressModal"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger delete-address-btn" data-id="{{ $address->id }}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีที่อยู่</p>
        @endforelse
    </div>
</div>


@php
$nationalityFlags = [
    'ลาว' => 'la',
    'กัมพูชา' => 'kh',
    'เมียนมา' => 'mm',
    'เวียดนาม' => 'vn',
];
@endphp
{{-- Employee List Section --}}
<div class="content-section mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
@php
    $totalEmployees = $employer->employees->count();
    $maleCount = 0;
    $femaleCount = 0;
    foreach ($employer->employees as $employee) {
        $title = $employee->employeeTitleTh ?? 'นาย'; // Default to male if not set
        if (in_array($title, ['นาย', 'Mr.'])) {
            $maleCount++;
        } elseif (in_array($title, ['นางสาว', 'นาง', 'Miss', 'Mrs.'])) {
            $femaleCount++;
        }
    }
@endphp
        <h5>ข้อมูลลูกจ้าง (รวม: {{ $totalEmployees }} | ชาย: {{ $maleCount }} | หญิง: {{ $femaleCount }})</h5>
        <div class="d-flex gap-2 flex-wrap">
            <input type="text" class="form-control form-control-sm" id="searchEmployeeInput" placeholder="ค้นหาพนักงาน..." style="width: 150px;">
            <select class="form-select form-select-sm" id="searchEmployeeNationality" style="width: 150px;">
                <option value="">-- ทุกสัญชาติ --</option>
                <option>ลาว</option>
                <option>กัมพูชา</option>
                <option>เมียนมา</option>
                <option>เวียดนาม</option>
            </select>
            <select class="form-select form-select-sm" id="searchEmployeeMOUGroup" style="width: 200px;">
                <option value="">-- ทุกประเภท มติ. --</option>
                <option>MOU</option>
                <option>มติต่ออายุในประเทศ</option>
                <option>มติขึ้นทะเบียน</option>
                <option>อื่นๆ</option>
            </select>
            <select class="form-select form-select-sm" id="searchEmployeePinkCard" style="width: 150px;">
                <option value="">-- บัตรชมพู --</option>
                <option value="has_card">มีบัตรชมพู</option>
                <option value="no_card">ไม่มีบัตรชมพู</option>
            </select>
            <a href="{{ route('employers.exportEmployees', $employer) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> ส่งออก</a>
            <a href="{{ route('employers.employees.create', $employer) }}" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> เพิ่มพนักงาน</a>
        </div>
    </div>
    <div id="employeeList" class="vstack gap-3">
        {{-- ========================================================================= --}}
        {{-- THE ONLY CRITICAL CHANGE IS HERE: Using the correct $employees variable --}}
        {{-- ========================================================================= --}}
        @forelse ($employees as $employee)
@php
    $flagCodes = [
        'เมียนมา' => 'mm', 'ลาว' => 'la', 'กัมพูชา' => 'kh', 'เวียดนาม' => 'vn',
    ];
    $nationality = $employee->employeeNationality ?? null;
    $flagCode = $nationality ? ($flagCodes[$nationality] ?? null) : null;
@endphp
        <div class="employee-card d-flex justify-content-between align-items-start gap-3" id="employee-card-{{ $employee->id }}">
            <div class="d-flex align-items-center flex-grow-1">
                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Employee Photo" style="width: 48px; height: 48px; object-fit: cover;">
                <div class="flex-grow-1">
                    <p class="mb-0">
                        <strong>{{ $loop->iteration }}. {{ $employee->employeeNameEn ?? 'No English Name' }}</strong>@if($nationality)
    <span class="text-muted small"> - {{ $nationality }}</span>
    @if($flagCode)
        <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" class="ms-1" style="width: 20px; vertical-align: middle;">
    @endif
@endif
                    </p>
                    <p class="mb-1 text-muted small">{{ $employee->employeeNameTh ?? 'ไม่มีชื่อภาษาไทย' }} ({{ $employee->employeePosition ?? 'ไม่ระบุตำแหน่ง' }})</p>
                    <p class="mb-1 text-muted small">Passport: {{ $employee->employeePassport ?? '-' }} (หมดอายุ: {{ $employee->passportExpiryDate ? \Carbon\Carbon::parse($employee->passportExpiryDate)->format('d M Y') : '-' }})</p>
                    <p class="mb-1 text-muted small">Work Permit: {{ $employee->employeeWorkPermit ?? '-' }} (หมดอายุ: {{ $employee->workPermitExpiryDate ? \Carbon\Carbon::parse($employee->workPermitExpiryDate)->format('d M Y') : '-' }})</p>
                    <p class="mb-0 text-muted small">Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) หมดอายุ: {{ $employee->visaExpiryDate ? \Carbon\Carbon::parse($employee->visaExpiryDate)->format('d M Y') : '-' }} | 90-Day: {{ $employee->ninetyDayReportDate ? \Carbon\Carbon::parse($employee->ninetyDayReportDate)->format('d M Y') : '-' }}</p>
                </div>
            </div>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('employers.employees.edit', ['employer' => $employer, 'employee' => $employee]) }}" class="btn btn-outline-primary" title="แก้ไข"><i class="bi bi-pencil-fill"></i></a>
                <button type="button" class="btn btn-outline-warning terminate-employee-btn" data-id="{{ $employee->id }}" title="แจ้งออก/เลิกจ้าง"><i class="bi bi-person-dash-fill"></i></button>
                <button type="button" class="btn btn-outline-danger delete-employee-btn" data-id="{{ $employee->id }}" title="ลบ"><i class="bi bi-trash-fill"></i></button>
            </div>
        </div>
        @empty
            <p class="text-muted">ไม่พบข้อมูลพนักงาน</p>
        @endforelse
    </div>
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

{{-- Add/Edit Address Modal --}}
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">เพิ่ม/แก้ไขที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="address-errors" class="alert alert-danger" style="display: none;"></div>
                <form id="addressForm">
                    @csrf
                    <input type="hidden" id="addressId" name="id">
                    <input type="hidden" id="addressType" name="type">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrNo" class="form-label">บ้านเลขที่ (ไทย)</label>
                            <input type="text" class="form-control" id="addrNo" name="addrNo">
                        </div>
                        <div class="col-md-6">
                            <label for="addrNoEn" class="form-label">Address No. (EN)</label>
                            <input type="text" class="form-control" id="addrNoEn" name="addrNoEn">
                        </div>
                    </div>
                     <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrMoo" class="form-label">หมู่ (ไทย)</label>
                            <input type="text" class="form-control" id="addrMoo" name="addrMoo">
                        </div>
                        <div class="col-md-6">
                            <label for="addrMooEn" class="form-label">Moo (EN)</label>
                            <input type="text" class="form-control" id="addrMooEn" name="addrMooEn">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrSoi" class="form-label">ซอย (ไทย)</label>
                            <input type="text" class="form-control" id="addrSoi" name="addrSoi">
                        </div>
                        <div class="col-md-6">
                            <label for="addrSoiEn" class="form-label">Soi (EN)</label>
                            <input type="text" class="form-control" id="addrSoiEn" name="addrSoiEn">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrRoad" class="form-label">ถนน (ไทย)</label>
                            <input type="text" class="form-control" id="addrRoad" name="addrRoad">
                        </div>
                        <div class="col-md-6">
                            <label for="addrRoadEn" class="form-label">Road (EN)</label>
                            <input type="text" class="form-control" id="addrRoadEn" name="addrRoadEn">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrProvince" class="form-label">จังหวัด (Thai)</label>
                            <select class="form-select" id="addrProvince" name="addrProvince">
                                <option selected disabled>--- เลือกจังหวัด ---</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="addrProvinceEn" class="form-label">Province (EN)</label>
                            <select class="form-select" id="addrProvinceEn" name="addrProvinceEn" disabled>
                                <option selected disabled>--- Province ---</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrDistrict" class="form-label">อำเภอ/เขต (Thai)</label>
                            <select class="form-select" id="addrDistrict" name="addrDistrict" disabled>
                                <option selected disabled>--- เลือกอำเภอ/เขต ---</option>
                            </select>
                        </div>
                         <div class="col-md-6">
                            <label for="addrDistrictEn" class="form-label">District (EN)</label>
                            <select class="form-select" id="addrDistrictEn" name="addrDistrictEn" disabled>
                                <option selected disabled>--- District ---</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrSubDistrict" class="form-label">ตำบล/แขวง (Thai)</label>
                            <select class="form-select" id="addrSubDistrict" name="addrSubDistrict" disabled>
                                <option selected disabled>--- เลือกตำบล/แขวง ---</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="addrSubDistrictEn" class="form-label">Sub-district (EN)</label>
                            <select class="form-select" id="addrSubDistrictEn" name="addrSubDistrictEn" disabled>
                                <option selected disabled>--- Sub-district ---</option>
                            </select>
                        </div>
                    </div>
                     <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrZipCode" class="form-label">รหัสไปรษณีย์</label>
                            <input type="text" class="form-control" id="addrZipCode" name="addrZipCode" readonly>
                            <input type="hidden" id="addrZipCodeEn" name="addrZipCodeEn">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" id="saveAddress">บันทึก</button>
            </div>
        </div>
    </div>
</div>

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const employerId = '{{ $employer->id }}';

    // --- Address Management ---
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const addressModalLabel = document.getElementById('addressModalLabel');

    // Thai dropdowns
    const provinceSelect = document.getElementById('addrProvince');
    const districtSelect = document.getElementById('addrDistrict');
    const subDistrictSelect = document.getElementById('addrSubDistrict');
    const zipCodeInput = document.getElementById('addrZipCode');

    // English dropdowns
    const provinceEnSelect = document.getElementById('addrProvinceEn');
    const districtEnSelect = document.getElementById('addrDistrictEn');
    const subDistrictEnSelect = document.getElementById('addrSubDistrictEn');
    const zipCodeEnInput = document.getElementById('addrZipCodeEn');

    let addressData = [];
    let isAddressDataLoaded = false;

    // Fetch Thai address data
    fetch('https://raw.githubusercontent.com/kongvut/thai-province-data/master/api_province_with_amphure_tambon.json')
        .then(response => response.json())
        .then(data => {
            addressData = data;
            isAddressDataLoaded = true;
            populateProvinces();
        });

    function populateProvinces() {
        provinceSelect.innerHTML = '<option selected disabled>--- เลือกจังหวัด ---</option>';
        addressData.forEach(province => {
            const option = new Option(province.name_th, province.name_th);
            option.dataset.name_en = province.name_en;
            provinceSelect.add(option);
        });
    }

    // Function to populate an English select
    function populateEnglishSelect(selectElement, enName, placeholder) {
        selectElement.innerHTML = ''; // Clear options
        if (enName) {
            const enOption = new Option(enName, enName);
            selectElement.add(enOption);
            selectElement.value = enName;
        } else {
            selectElement.innerHTML = `<option selected disabled>--- ${placeholder} ---</option>`;
        }
    }

    // Event listeners for dropdowns to cascade
    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';
        districtSelect.disabled = true;
        subDistrictSelect.disabled = true;
        populateEnglishSelect(districtEnSelect, '', 'District');
        populateEnglishSelect(subDistrictEnSelect, '', 'Sub-district');


        const selectedOption = this.options[this.selectedIndex];
        const provinceEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(provinceEnSelect, provinceEnName, 'Province');


        const selectedProvince = addressData.find(p => p.name_th === this.value);
        if (selectedProvince) {
            selectedProvince.amphure.forEach(district => {
                 const option = new Option(district.name_th, district.name_th);
                 option.dataset.name_en = district.name_en;
                 districtSelect.add(option);
            });
            districtSelect.disabled = false;
        }
    });

    districtSelect.addEventListener('change', function () {
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';
        subDistrictSelect.disabled = true;
        populateEnglishSelect(subDistrictEnSelect, '', 'Sub-district');

        const selectedOption = this.options[this.selectedIndex];
        const districtEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(districtEnSelect, districtEnName, 'District');

        const selectedProvince = addressData.find(p => p.name_th === provinceSelect.value);
        if (selectedProvince) {
            const selectedDistrict = selectedProvince.amphure.find(d => d.name_th === this.value);
            if (selectedDistrict) {
                selectedDistrict.tambon.forEach(subDistrict => {
                    const option = new Option(subDistrict.name_th, subDistrict.name_th);
                    option.dataset.name_en = subDistrict.name_en;
                    option.dataset.zip_code = subDistrict.zip_code;
                    subDistrictSelect.add(option);
                });
                subDistrictSelect.disabled = false;
            }
        }
    });

    subDistrictSelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const zipCode = selectedOption.dataset.zip_code || '';
        zipCodeInput.value = zipCode;
        if(zipCodeEnInput) zipCodeEnInput.value = zipCode; // Assuming we store zip in EN field too

        const subDistrictEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(subDistrictEnSelect, subDistrictEnName, 'Sub-district');
    });

    function resetAddressForm() {
        document.getElementById('address-errors').style.display = 'none';
        addressForm.reset();
        addressForm.querySelector('#addressId').value = '';
        provinceSelect.selectedIndex = 0;
        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        districtSelect.disabled = true;
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        subDistrictSelect.disabled = true;
        populateEnglishSelect(provinceEnSelect, '', 'Province');
        populateEnglishSelect(districtEnSelect, '', 'District');
        populateEnglishSelect(subDistrictEnSelect, '', 'Sub-district');
    }

    // Use event delegation for address buttons
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('.add-address-btn, .edit-address-btn');
        if (!target) return;

        resetAddressForm();
        const addressId = target.dataset.id;
        const addressType = target.dataset.addressType || target.closest('.content-section').querySelector('.add-address-btn').dataset.addressType;
        addressForm.querySelector('#addressType').value = addressType;

        if (addressId) { // Edit mode
            addressModalLabel.textContent = 'แก้ไขที่อยู่';
            const populateEditForm = (data) => {
                addressForm.querySelector('#addressId').value = data.id;
                // Populate regular inputs
                Object.keys(data).forEach(key => {
                    const field = addressForm.querySelector(`#${key}`);
                    if (field && field.tagName !== 'SELECT') {
                        // Check if the field is not one of the English dropdowns
                        if (!['addrProvinceEn', 'addrDistrictEn', 'addrSubDistrictEn'].includes(field.id)) {
                            field.value = data[key];
                        }
                    }
                });

                // Function to handle dropdown population once data is ready
                const populateDropdowns = () => {
                    // Thai Province
                    provinceSelect.value = data.addrProvince;
                    const selectedProvince = addressData.find(p => p.name_th === data.addrProvince);
                    if (selectedProvince) {
                        // English Province
                        populateEnglishSelect(provinceEnSelect, data.addrProvinceEn, 'Province');

                        // Thai District
                        districtSelect.innerHTML = ''; // Clear previous options
                        selectedProvince.amphure.forEach(d => {
                            const option = new Option(d.name_th, d.name_th);
                            option.dataset.name_en = d.name_en;
                            districtSelect.add(option);
                        });
                        districtSelect.disabled = false;
                        districtSelect.value = data.addrDistrict;
                        const selectedDistrict = selectedProvince.amphure.find(d => d.name_th === data.addrDistrict);

                        if (selectedDistrict) {
                            // English District
                            populateEnglishSelect(districtEnSelect, data.addrDistrictEn, 'District');

                            // Thai Sub-district
                            subDistrictSelect.innerHTML = ''; // Clear previous options
                            selectedDistrict.tambon.forEach(sd => {
                                const option = new Option(sd.name_th, sd.name_th);
                                option.dataset.name_en = sd.name_en;
                                option.dataset.zip_code = sd.zip_code;
                                subDistrictSelect.add(option);
                            });
                            subDistrictSelect.disabled = false;
                            subDistrictSelect.value = data.addrSubDistrict;
                            const selectedSubDistrict = selectedDistrict.tambon.find(sd => sd.name_th === data.addrSubDistrict);

                            if (selectedSubDistrict) {
                                // English Sub-district
                                populateEnglishSelect(subDistrictEnSelect, data.addrSubDistrictEn, 'Sub-district');
                                // Zipcode
                                zipCodeInput.value = selectedSubDistrict.zip_code || '';
                                if(zipCodeEnInput) zipCodeEnInput.value = selectedSubDistrict.zip_code || '';
                            }
                        }
                    }
                };

                // Check if address data from API is loaded
                if (isAddressDataLoaded) {
                    populateDropdowns();
                } else {
                    const waitInterval = setInterval(() => {
                        if (isAddressDataLoaded) {
                            clearInterval(waitInterval);
                            populateDropdowns();
                        }
                    }, 100);
                }
            };
            // Fetch the specific address details
            fetch(`/addresses/${addressId}/edit`)
                .then(response => response.json())
                .then(data => populateEditForm(data));

        } else { // Add mode
            addressModalLabel.textContent = 'เพิ่มที่อยู่';
        }
        addressModal.show();
    });

    // Save Address (Create/Update)
    document.getElementById('saveAddress').addEventListener('click', function() {
        const addressId = addressForm.querySelector('#addressId').value;
        const url = addressId ? `/addresses/${addressId}` : '/addresses';
        const method = addressId ? 'PUT' : 'POST';

        // Temporarily enable selects to include them in FormData
        provinceEnSelect.disabled = false;
        districtEnSelect.disabled = false;
        subDistrictEnSelect.disabled = false;

        const formData = new FormData(addressForm);

        // Re-disable them
        provinceEnSelect.disabled = true;
        districtEnSelect.disabled = true;
        subDistrictEnSelect.disabled = true;


        // Laravel needs _method field for PUT/PATCH requests sent via POST
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }
        formData.append('addressable_id', employerId);
        formData.append('addressable_type', 'Employer');

        fetch(url, {
            method: 'POST', // Always POST, with _method for spoofing
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            // Assuming the controller returns the saved address object with an 'id' and 'type'
            const address = data.address;
            const addressCardHtml = `
                <div class="address-card d-flex justify-content-between align-items-start" id="address-card-${address.id}">
                    <div>
                        <p class="mb-0">
                            เลขที่ ${address.addrNo || ''} หมู่ ${address.addrMoo || ''} ซอย${address.addrSoi || ''} ถนน${address.addrRoad || ''}
                            แขวง/ตำบล ${address.addrSubDistrict || ''} เขต/อำเภอ ${address.addrDistrict || ''}
                            ${address.addrProvince || ''} ${address.addrZipCode || ''}
                        </p>
                        <p class="mb-0 text-muted small">
                            Addr: ${address.addrNoEn || ''}, Moo: ${address.addrMooEn || ''}, Soi: ${address.addrSoiEn || ''}, Road: ${address.addrRoadEn || ''},
                            ${address.addrSubDistrictEn || ''}, ${address.addrDistrictEn || ''},
                            ${address.addrProvinceEn || ''} ${address.addrZipCodeEn || ''}
                        </p>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary edit-address-btn" data-id="${address.id}" data-bs-toggle="modal" data-bs-target="#addressModal"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-outline-danger delete-address-btn" data-id="${address.id}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>`;

            const listId = address.type === 'registered' ? 'registeredAddressList' : 'workplaceAddressList';
            const addressList = document.getElementById(listId);

            if (addressId) { // It was an update
                const oldCard = document.getElementById(`address-card-${addressId}`);
                if (oldCard) {
                    oldCard.outerHTML = addressCardHtml;
                }
            } else { // It was a new address
                // Remove the "no address" placeholder if it exists
                const placeholder = addressList.querySelector('.text-muted');
                if (placeholder) {
                    placeholder.remove();
                }
                addressList.insertAdjacentHTML('beforeend', addressCardHtml);
            }

            addressModal.hide();
        })
        .catch(error => {
            console.error('Save Address Error:', error);
            const errorDiv = document.getElementById('address-errors');
            errorDiv.innerHTML = '';
            errorDiv.style.display = 'none';

            if (error.errors) {
                let errorList = '<ul>';
                for (const key in error.errors) {
                    errorList += `<li>${error.errors[key][0]}</li>`;
                }
                errorList += '</ul>';
                errorDiv.innerHTML = errorList;
                errorDiv.style.display = 'block';
            } else {
                errorDiv.innerHTML = '<ul><li>เกิดข้อผิดพลาดในการบันทึกที่ไม่ทราบสาเหตุ</li></ul>';
                errorDiv.style.display = 'block';
            }
        });
    });

    // Delete Address
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('.delete-address-btn');
        if (!target) return;

        if (confirm('Are you sure you want to delete this address?')) {
            const addressId = target.dataset.id;
            fetch(`/addresses/${addressId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cardToRemove = document.getElementById(`address-card-${addressId}`);
                    if (cardToRemove) {
                        const parentList = cardToRemove.parentElement;
                        cardToRemove.remove();
                        // If it was the last card, show placeholder text
                        if (parentList.children.length === 0) {
                             parentList.innerHTML = '<p class="text-muted">ยังไม่มีที่อยู่</p>';
                        }
                    }
                } else {
                     alert(data.message || 'Error deleting address.');
                }
            })
            .catch(error => console.error('Delete Address Error:', error));
        }
    });


    // Terminate Employee
    const terminateModal = new bootstrap.Modal(document.getElementById('terminateEmployeeModal'));
    const terminateForm = document.getElementById('terminateEmployeeForm');
    const terminateEmployeeIdInput = document.getElementById('terminateEmployeeId');

    document.getElementById('employeeList').addEventListener('click', function (e) {
        if (e.target.closest('.terminate-employee-btn')) {
            const button = e.target.closest('.terminate-employee-btn');
            const employeeId = button.dataset.id;
            terminateEmployeeIdInput.value = employeeId;
            terminateModal.show();
        }
    });

    document.getElementById('confirmTerminateEmployeeButton').addEventListener('click', function () {
        const employeeId = terminateEmployeeIdInput.value;
        const terminateDate = document.getElementById('terminateDate').value;
        const terminationReason = document.getElementById('terminationReason').value;

        fetch(`/employees/${employeeId}/terminate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                terminateDate: terminateDate,
                terminationReason: terminationReason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Move employee card to history
                const employeeCard = document.querySelector(`.terminate-employee-btn[data-id='${employeeId}']`).closest('.employee-card');
                employeeCard.remove();
                // For simplicity, we just reload the page to see the history updated.
                // A more advanced implementation would dynamically create and append the history card.
                location.reload();
            } else {
                alert('Failed to terminate employee.');
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Filter Employees
    const searchInput = document.getElementById('searchEmployeeInput');
    const nationalitySelect = document.getElementById('searchEmployeeNationality');
    const mouGroupSelect = document.getElementById('searchEmployeeMOUGroup');
    const pinkCardSelect = document.getElementById('searchEmployeePinkCard');

    function filterEmployees() {
        const search = searchInput.value;
        const nationality = nationalitySelect.value;
        const mouGroup = mouGroupSelect.value;
        const pinkCard = pinkCardSelect.value;

        const url = new URL(`{{ route('employers.employees.filter', $employer->id) }}`);
        url.searchParams.append('search', search);
        url.searchParams.append('nationality', nationality);
        url.searchParams.append('mouGroup', mouGroup);
        url.searchParams.append('pinkCard', pinkCard);

        fetch(url)
            .then(response => response.json())
            .then(employees => {
                const employeeList = document.getElementById('employeeList');
                employeeList.innerHTML = '';
                if (employees.length > 0) {
                    employees.forEach(employee => {
                        const card = `
                        <div class="employee-card d-flex justify-content-between align-items-start gap-3">
                            <div class="d-flex align-items-center flex-grow-1">
                                <img src="${employee.employeePhoto ? '/storage/' + employee.employeePhoto : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC'}" class="employee-photo-thumb" alt="Employee Photo" style="width: 48px; height: 48px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <p class="mb-0"><strong>${employee.employeeNameEn ?? 'No English Name'}</strong></p>
                                    <p class="mb-1 text-muted small">${employee.employeeNameTh ?? 'ไม่มีชื่อภาษาไทย'} (${employee.employeePosition ?? 'ไม่ระบุตำแหน่ง'})</p>
                                    <p class="mb-1 text-muted small">Passport: ${employee.employeePassport ?? '-'} (หมดอายุ: ${employee.passportExpiryDate ? new Date(employee.passportExpiryDate).toLocaleDateString('en-GB') : '-'})</p>
                                    <p class="mb-1 text-muted small">Work Permit: ${employee.employeeWorkPermit ?? '-'} (หมดอายุ: ${employee.workPermitExpiryDate ? new Date(employee.workPermitExpiryDate).toLocaleDateString('en-GB') : '-'})</p>
                                    <p class="mb-0 text-muted small">Visa (${employee.workPermitMOUGroup ?? '-'}) หมดอายุ: ${employee.visaExpiryDate ? new Date(employee.visaExpiryDate).toLocaleDateString('en-GB') : '-'} | 90-Day: ${employee.ninetyDayReportDate ? new Date(employee.ninetyDayReportDate).toLocaleDateString('en-GB') : '-'}</p>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a href="/employees/${employee.id}/edit" class="btn btn-outline-primary" title="แก้ไข"><i class="bi bi-pencil-fill"></i></a>
                                <button type="button" class="btn btn-outline-warning terminate-employee-btn" data-id="${employee.id}" title="แจ้งออก/เลิกจ้าง"><i class="bi bi-person-dash-fill"></i></button>
                                <button type="button" class="btn btn-outline-danger delete-employee-btn" data-id="${employee.id}" title="ลบ"><i class="bi bi-trash-fill"></i></button>
                            </div>
                        </div>`;
                        employeeList.innerHTML += card;
                    });
                } else {
                    employeeList.innerHTML = '<p class="text-muted">ไม่พบข้อมูลพนักงานที่ตรงกับเงื่อนไข</p>';
                }
                 document.getElementById('employeeTotalCount').textContent = employees.length;
            });
    }

    searchInput.addEventListener('input', filterEmployees);
    nationalitySelect.addEventListener('change', filterEmployees);
    mouGroupSelect.addEventListener('change', filterEmployees);
    pinkCardSelect.addEventListener('change', filterEmployees);

    // Filter History
    const searchHistoryInput = document.getElementById('searchHistoryInput');

    function filterHistory() {
        const search = searchHistoryInput.value;
        const url = new URL(`{{ route('employers.history.filter', $employer->id) }}`);
        url.searchParams.append('search', search);

        fetch(url)
            .then(response => response.json())
            .then(employees => {
                const historyList = document.getElementById('employmentHistoryList');
                historyList.innerHTML = '';
                if (employees.length > 0) {
                    employees.forEach(employee => {
                        const card = `
                        <div class="employee-card bg-light d-flex justify-content-between align-items-start gap-3">
                             <div class="d-flex align-items-center flex-grow-1">
                                <img src="${employee.employeePhoto ? '/storage/' + employee.employeePhoto : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC'}" class="employee-photo-thumb" alt="Employee Photo" style="width: 48px; height: 48px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <p class="mb-0"><strong>${employee.employeeNameEn ?? 'No English Name'}</strong></p>
                                    <p class="mb-1 text-muted small">${employee.employeeNameTh ?? ''} (${employee.employeePosition ?? 'ไม่ระบุตำแหน่ง'})</p>
                                    <p class="mb-0 text-danger small"><strong>เลิกจ้างวันที่:</strong> ${new Date(employee.terminated_at).toLocaleDateString('en-GB')} - ${employee.termination_reason || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-success restore-employee-btn" data-id="${employee.id}" title="นำกลับ"><i class="bi bi-arrow-counterclockwise"></i></button>
                                <button type="button" class="btn btn-outline-danger permanent-delete-btn" data-id="${employee.id}" title="ลบถาวร"><i class="bi bi-trash3-fill"></i></button>
                            </div>
                        </div>`;
                        historyList.innerHTML += card;
                    });
                } else {
                    historyList.innerHTML = '<p class="text-muted">ไม่มีประวัติการจ้างงาน</p>';
                }
            });
    }

    searchHistoryInput.addEventListener('input', filterHistory);
    // Initial load of history
    filterHistory();

    // --- History Action Buttons ---
    const historyList = document.getElementById('employmentHistoryList');

    historyList.addEventListener('click', function(e) {
        const restoreBtn = e.target.closest('.restore-employee-btn');
        const deleteBtn = e.target.closest('.permanent-delete-btn');

        if (restoreBtn) {
            const employeeId = restoreBtn.dataset.id;
            const employeeCard = restoreBtn.closest('.employee-card');
            if (confirm('Are you sure you want to restore this employee?')) {
                fetch(`/employees/${employeeId}/restore`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        employeeCard.remove();
                        // For simplicity, reload the page to update both lists
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to restore employee.');
                    }
                })
                .catch(error => console.error('Restore Error:', error));
            }
        }

        if (deleteBtn) {
            const employeeId = deleteBtn.dataset.id;
            const employeeCard = deleteBtn.closest('.employee-card');
            if (confirm('This action is irreversible. Are you sure you want to permanently delete this employee?')) {
                fetch(`/employees/${employeeId}/force-delete`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        employeeCard.remove();
                    } else {
                        alert(data.message || 'Failed to delete employee.');
                    }
                })
                .catch(error => console.error('Delete Error:', error));
            }
        }
    });

    // Highlight employee card from URL hash
    if (window.location.hash) {
        try {
            const elementId = window.location.hash.substring(1);
            const element = document.getElementById(elementId);
            if (element && element.classList.contains('employee-card')) {
                element.classList.add('highlight');
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (e) {
            console.error("Error handling URL hash for highlighting:", e);
        }
    }
});
</script>
@endpush
@push('styles')
<style>
    .highlight {
        animation: highlight-bg 2s ease-out;
    }

    @keyframes highlight-bg {
        0% {
            background-color: #fceb92;
        }
        100% {
            background-color: transparent;
        }
    }
</style>
@endpush
