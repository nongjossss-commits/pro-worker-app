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
const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
const addressForm = document.getElementById('addressForm');
let thaiAddressData = [];

async function fetchAddressData() {
    try {
        const response = await fetch("{{ route('addresses.thai_data') }}");
        if (!response.ok) throw new Error('Network response was not ok');
        thaiAddressData = await response.json();
        populateProvinces();
    } catch (error) {
        console.error('Error fetching address data:', error);
        alert('ไม่สามารถโหลดข้อมูลที่อยู่ได้ กรุณาลองอีกครั้ง');
    }
}

function populateDropdown(selectElement, items, defaultOptionText) {
    selectElement.innerHTML = `<option value="">-- ${defaultOptionText} --</option>`;
    items.forEach(item => {
        selectElement.add(new Option(item.name_th, item.name_th));
    });
    selectElement.disabled = false;
}

const provinceSelect = document.getElementById('addrProvince');
const districtSelect = document.getElementById('addrDistrict');
const subDistrictSelect = document.getElementById('addrSubDistrict');
const zipCodeInput = document.getElementById('addrZipCode');
const provinceEnInput = document.getElementById('addrProvinceEn');
const districtEnInput = document.getElementById('addrDistrictEn');
const subDistrictEnInput = document.getElementById('addrSubDistrictEn');

function populateProvinces() {
    populateDropdown(provinceSelect, thaiAddressData, 'เลือกจังหวัด');
}

provinceSelect.addEventListener('change', function() {
    const selectedProvince = thaiAddressData.find(p => p.name_th === this.value);
    districtSelect.innerHTML = '<option value="">-- เลือกเขต/อำเภอ --</option>';
    subDistrictSelect.innerHTML = '<option value="">-- เลือกแขวง/ตำบล --</option>';
    zipCodeInput.value = '';
    districtSelect.disabled = true;
    subDistrictSelect.disabled = true;
    provinceEnInput.value = selectedProvince ? selectedProvince.name_en : '';
    districtEnInput.value = '';
    subDistrictEnInput.value = '';
    if (selectedProvince) {
        populateDropdown(districtSelect, selectedProvince.amphure, 'เลือกเขต/อำเภอ');
    }
});

districtSelect.addEventListener('change', function() {
    const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
    const selectedDistrict = selectedProvince?.amphure.find(d => d.name_th === this.value);
    subDistrictSelect.innerHTML = '<option value="">-- เลือกแขวง/ตำบล --</option>';
    zipCodeInput.value = '';
    subDistrictSelect.disabled = true;
    districtEnInput.value = selectedDistrict ? selectedDistrict.name_en : '';
    subDistrictEnInput.value = '';
    if (selectedDistrict) {
        populateDropdown(subDistrictSelect, selectedDistrict.tambon, 'เลือกแขวง/ตำบล');
    }
});

subDistrictSelect.addEventListener('change', function() {
    const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
    const selectedDistrict = selectedProvince?.amphure.find(d => d.name_th === districtSelect.value);
    const selectedSubDistrict = selectedDistrict?.tambon.find(sd => sd.name_th === this.value);
    zipCodeInput.value = selectedSubDistrict ? selectedSubDistrict.zip_code : '';
    subDistrictEnInput.value = selectedSubDistrict ? selectedSubDistrict.name_en : '';
});

// Initial fetch of address data
fetchAddressData();


    // --- Terminate Employee Logic ---
    const terminateModalEl = document.getElementById('terminateEmployeeModal');
    if (terminateModalEl) {
        const terminateModal = new bootstrap.Modal(terminateModalEl);
        const terminateForm = document.getElementById('terminateEmployeeForm');
        const terminateEmployeeIdInput = document.getElementById('terminateEmployeeId');
        const employeeListContainer = document.getElementById('employeeList');

        employeeListContainer.addEventListener('click', function (e) {
            const terminateButton = e.target.closest('.terminate-employee-btn');
            if (terminateButton) {
                const employeeId = terminateButton.dataset.id;
                terminateEmployeeIdInput.value = employeeId;
                terminateForm.reset();
                terminateModal.show();
            }
        });

        document.getElementById('confirmTerminateEmployeeButton').addEventListener('click', function () {
            const employeeId = terminateEmployeeIdInput.value;
            const terminateDate = document.getElementById('terminateDate').value;
            const terminationReason = document.getElementById('terminationReason').value;

            if (!terminateDate) {
                alert('กรุณาเลือกวันที่แจ้งออก');
                return;
            }

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
                    const employeeCard = document.getElementById(`employee-card-${employeeId}`);
                    if(employeeCard) {
                         employeeCard.remove();
                    }
                    // Simple refresh to update history list automatically
                    location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาดในการแจ้งออก');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    // Filter Employees
    const searchInput = document.getElementById('searchEmployeeInput');
    const nationalitySelect = document.getElementById('searchEmployeeNationality');
    const mouGroupSelect = document.getElementById('searchEmployeeMOUGroup');
    const pinkCardSelect = document.getElementById('searchEmployeePinkCard');

    // function filterEmployees() {
    //     const search = searchInput.value;
    //     const nationality = nationalitySelect.value;
    //     const mouGroup = mouGroupSelect.value;
    //     const pinkCard = pinkCardSelect.value;

    //     const url = new URL(`{{ route('employers.employees.filter', $employer->id) }}`);
    //     url.searchParams.append('search', search);
    //     url.searchParams.append('nationality', nationality);
    //     url.searchParams.append('mouGroup', mouGroup);
    //     url.searchParams.append('pinkCard', pinkCard);

    //     fetch(url)
    //         .then(response => response.json())
    //         .then(employees => {
    //             const employeeList = document.getElementById('employeeList');
    //             employeeList.innerHTML = '';
    //             if (employees.length > 0) {
    //                 employees.forEach(employee => {
    //                     const card = `
    //                     <div class="employee-card d-flex justify-content-between align-items-start gap-3">
    //                         <div class="d-flex align-items-center flex-grow-1">
    //                             <img src="${employee.employeePhoto ? '/storage/' + employee.employeePhoto : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC'}" class="employee-photo-thumb" alt="Employee Photo" style="width: 48px; height: 48px; object-fit: cover;">
    //                             <div class="flex-grow-1">
    //                                 <p class="mb-0"><strong>${employee.employeeNameEn ?? 'No English Name'}</strong></p>
    //                                 <p class="mb-1 text-muted small">${employee.employeeNameTh ?? 'ไม่มีชื่อภาษาไทย'} (${employee.employeePosition ?? 'ไม่ระบุตำแหน่ง'})</p>
    //                                 <p class="mb-1 text-muted small">Passport: ${employee.employeePassport ?? '-'} (หมดอายุ: ${employee.passportExpiryDate ? new Date(employee.passportExpiryDate).toLocaleDateString('en-GB') : '-'})</p>
    //                                 <p class="mb-1 text-muted small">Work Permit: ${employee.employeeWorkPermit ?? '-'} (หมดอายุ: ${employee.workPermitExpiryDate ? new Date(employee.workPermitExpiryDate).toLocaleDateString('en-GB') : '-'})</p>
    //                                 <p class="mb-0 text-muted small">Visa (${employee.workPermitMOUGroup ?? '-'}) หมดอายุ: ${employee.visaExpiryDate ? new Date(employee.visaExpiryDate).toLocaleDateString('en-GB') : '-'} | 90-Day: ${employee.ninetyDayReportDate ? new Date(employee.ninetyDayReportDate).toLocaleDateString('en-GB') : '-'}</p>
    //                             </div>
    //                         </div>
    //                         <div class="btn-group btn-group-sm">
    //                             <a href="/employees/${employee.id}/edit" class="btn btn-outline-primary" title="แก้ไข"><i class="bi bi-pencil-fill"></i></a>
    //                             <button type="button" class="btn btn-outline-warning terminate-employee-btn" data-id="${employee.id}" title="แจ้งออก/เลิกจ้าง"><i class="bi bi-person-dash-fill"></i></button>
    //                             <button type="button" class="btn btn-outline-danger delete-employee-btn" data-id="${employee.id}" title="ลบ"><i class="bi bi-trash-fill"></i></button>
    //                         </div>
    //                     </div>`;
    //                     employeeList.innerHTML += card;
    //                 });
    //             } else {
    //                 employeeList.innerHTML = '<p class="text-muted">ไม่พบข้อมูลพนักงานที่ตรงกับเงื่อนไข</p>';
    //             }
    //              document.getElementById('employeeTotalCount').textContent = employees.length;
    //         });
    // }

    // searchInput.addEventListener('input', filterEmployees);
    // nationalitySelect.addEventListener('change', filterEmployees);
    // mouGroupSelect.addEventListener('change', filterEmployees);
    // pinkCardSelect.addEventListener('change', filterEmployees);

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
        // The hash will be #employee-card-XX or #employee-row-XX
        const highlightId = window.location.hash.substring(1);
        console.log("TEST " + highlightId)
        const elementToHighlight = document.getElementById(highlightId);

        if (elementToHighlight) {
            // Scroll the element into the middle of the view
            elementToHighlight.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Add the highlight class
            elementToHighlight.classList.add('highlight');
            elementToHighlight.style.border = "2px solid #f97316";
            elementToHighlight.style.borderRadius = "0.5rem";
            elementToHighlight.style.boxShadow = "0 0 15px rgba(249, 115, 22, 0.5)";
            // Optional: Remove the class after the animation to clean up styles
            // setTimeout(() => {
            //     elementToHighlight.classList.remove('highlight');
            //     // Also clear the hash from the URL for a cleaner experience
            //     if (history.pushState) {
            //         history.pushState(null, null, window.location.pathname + window.location.search);
            //     } else {
            //         window.location.hash = '';
            //     }
            // }, 3100); // Slightly longer than the animation
        }
    }
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('employeeList');
        const actionBar = document.getElementById('bulk-action-bar-employer');
        if (!container || !actionBar) return;

        const selectAllCheckbox = document.getElementById('select-all-checkbox-employer');
        const selectedCountSpan = document.getElementById('selected-count-employer');
        const actionButton = actionBar.querySelector('button');

        function updateActionBar() {
            const itemCheckboxes = container.querySelectorAll('.bulk-action-checkbox');
            const selectedCheckboxes = container.querySelectorAll('.bulk-action-checkbox:checked');
            const count = selectedCheckboxes.length;

            if (count > 0) {
                actionBar.style.display = 'flex';
                selectedCountSpan.textContent = count;
                actionButton.disabled = false;
            } else {
                actionBar.style.display = 'none';
                selectedCountSpan.textContent = 0;
                actionButton.disabled = true;
            }
            if(selectAllCheckbox){
                 selectAllCheckbox.checked = itemCheckboxes.length > 0 && count === itemCheckboxes.length;
            }
        }

        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('bulk-action-checkbox')) {
                updateActionBar();
            }
        });

        if(selectAllCheckbox){
            selectAllCheckbox.addEventListener('change', function() {
                const itemCheckboxes = container.querySelectorAll('.bulk-action-checkbox');
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateActionBar();
            });
        }
        updateActionBar();
});
</script>
@endpush
