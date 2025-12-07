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

@section('title', __('Edit Employer'))

@section('content')

{{-- Employer Info Form --}}
<div class="content-section" draggable="true"
     data-drag-payload="{{ json_encode([
        'id' => $employer->id,
        'title' => $employer->employerNameTh,
        'subtitle' => $employer->employerNameEn,
        'url' => request()->fullUrl()
     ]) }}"
     ondragstart="window.startDragGlobal(event, 'employer', JSON.parse(this.dataset.dragPayload))">
    <h2 class="mb-4">{{ __('Edit Employer') }}</h2>
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

        <h5>{{ __('Employer Info') }}</h5>
        <hr>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">{{ __('Employer Name (Thai)') }}</label>
                <input type="text" class="form-control" id="employerNameTh" name="employerNameTh" value="{{ old('employerNameTh', $employer->employerNameTh) }}">
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">{{ __('Employer Name (English)') }}</label>
                <input type="text" class="form-control" id="employerNameEn" name="employerNameEn" value="{{ old('employerNameEn', $employer->employerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerId" class="form-label">{{ __('Employer ID') }}</label>
                <input type="text" class="form-control" id="employerId" name="employerId" value="{{ old('employerId', $employer->employerId) }}" readonly required>
            </div>
            <div class="col-md-6">
                <label for="job_owner_id" class="form-label">{{ __('Job Owner') }}</label>
                <div class="input-group">
                    <select class="form-select" id="job_owner_id" name="job_owner_id">
                        <option selected disabled>{{ __('Select Job Owner') }}</option>
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
                <label for="assigned_staff_id" class="form-label">{{ __('Responsible Person') }}</label>
                <select class="form-select" id="assigned_staff_id" name="assigned_staff_id">
                    <option value="">{{ __('Select Responsible Person') }}</option>
                    @foreach($staffUsers as $staff)
                        <option value="{{ $staff->id }}" {{ $employer->assigned_staff_id == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">{{ __('Employer Tax ID') }}</label>
                <input type="text" class="form-control" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId', $employer->employerTaxId) }}">
            </div>
            <div class="col-md-6">
                <label for="businessType" class="form-label">{{ __('Business Type') }}</label>
                <input type="text" class="form-control" id="businessType" name="businessType" value="{{ old('businessType', $employer->businessType) }}">
            </div>
        </div>
        <div class="row mb-3">
 <div class="col-md-6">
 <label for="employerEmail" class="form-label">{{ __('Employer Email') }}</label>
 <input type="email" class="form-control @error('employerEmail') is-invalid @enderror" id="employerEmail" name="employerEmail" value="{{ old('employerEmail', $employer->employerEmail ?? '') }}">
 @error('employerEmail')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 <div class="col-md-6">
 <label for="employerPhone" class="form-label">{{ __('Phone Number') }}</label>
 <input type="text" class="form-control @error('employerPhone') is-invalid @enderror" id="employerPhone" name="employerPhone" value="{{ old('employerPhone', $employer->employerPhone ?? '') }}">
 @error('employerPhone')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 </div>
 <div class="row mb-3">
 <div class="col-md-6">
 <label for="employerPassword" class="form-label">{{ __('Password (for Employer)') }}</label>
 <input type="text" class="form-control @error('employerPassword') is-invalid @enderror" id="employerPassword" name="employerPassword" value="">
 @error('employerPassword')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 <div class="col-md-6">
 <label for="socialSecurityHospital" class="form-label">{{ __('Social Security Hospital') }}</label>
 <input type="text" class="form-control @error('socialSecurityHospital') is-invalid @enderror" id="socialSecurityHospital" name="socialSecurityHospital" value="{{ old('socialSecurityHospital', $employer->socialSecurityHospital ?? '') }}">
 @error('socialSecurityHospital')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">{{ __('Authorized Signatory (Thai)') }}</label>
                <input type="text" class="form-control" id="signerNameTh" name="signerNameTh" value="{{ old('signerNameTh', $employer->signerNameTh) }}">
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">{{ __('Authorized Signatory (English)') }}</label>
                <input type="text" class="form-control" id="signerNameEn" name="signerNameEn" value="{{ old('signerNameEn', $employer->signerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">{{ __('Type of Business') }}</label>
                <input type="text" class="form-control" id="businessTypeEn" name="businessTypeEn" value="{{ old('businessTypeEn', $employer->businessTypeEn) }}">
            </div>
            <div class="col-md-6">
                <label for="regCapital" class="form-label">{{ __('Registered Capital') }}</label>
                <input type="text" class="form-control" id="regCapital" name="regCapital" value="{{ old('regCapital', $employer->regCapital) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regDate" class="form-label">{{ __('Registration Date') }}</label>
                <input type="date" class="form-control" id="regDate" name="regDate" value="{{ old('regDate', $employer->regDate) }}">
            </div>
            <div class="col-md-6">
                <label for="minimum_wage" class="form-label">{{ __('Minimum Wage') }}</label>
                <input type="text" class="form-control" id="minimum_wage" name="minimum_wage" value="{{ old('minimum_wage') }}">
            </div>
        </div>

        <hr>
        <h5>{{ __('Employer Attachments') }}</h5>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employer_doc_company" class="form-label">1. {{ __('Company Certificate / ID Card') }}</label>
                <input type="file" class="form-control form-control-sm @error('employer_doc_company') is-invalid @enderror" id="employer_doc_company" name="employer_doc_company">
                @if($employer->employer_doc_company)
                    <div class="file-upload-display mt-2">
                        <a href="{{ asset('storage/' . $employer->employer_doc_company) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> {{ __('View current file') }}</a>
                    </div>
                @endif
                @error('employer_doc_company')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="employer_doc_company_expiry" class="form-label">{{ __('Expiry Date') }}</label>
                <input type="date" class="form-control form-control-sm @error('employer_doc_company_expiry') is-invalid @enderror" id="employer_doc_company_expiry" name="employer_doc_company_expiry" value="{{ old('employer_doc_company_expiry', $employer->employer_doc_company_expiry) }}">
                @error('employer_doc_company_expiry')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employer_doc_lease" class="form-label">2. {{ __('Lease Agreement / House Registration') }}</label>
                <input type="file" class="form-control form-control-sm @error('employer_doc_lease') is-invalid @enderror" id="employer_doc_lease" name="employer_doc_lease">
                @if($employer->employer_doc_lease)
                    <div class="file-upload-display mt-2">
                        <a href="{{ asset('storage/' . $employer->employer_doc_lease) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> {{ __('View current file') }}</a>
                    </div>
                @endif
                @error('employer_doc_lease')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="employer_doc_construction" class="form-label">3. {{ __('Construction Contract / Map') }}</label>
                <input type="file" class="form-control form-control-sm @error('employer_doc_construction') is-invalid @enderror" id="employer_doc_construction" name="employer_doc_construction">
                @if($employer->employer_doc_construction)
                    <div class="file-upload-display mt-2">
                        <a href="{{ asset('storage/' . $employer->employer_doc_construction) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> {{ __('View current file') }}</a>
                    </div>
                @endif
                @error('employer_doc_construction')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="employer_doc_other_1" class="form-label">4. {{ __('Other Document') }} 1</label>
                <input type="file" class="form-control form-control-sm @error('employer_doc_other_1') is-invalid @enderror" id="employer_doc_other_1" name="employer_doc_other_1">
                <input type="text" class="form-control form-control-sm mt-2 @error('employer_doc_other_1_desc') is-invalid @enderror" id="employer_doc_other_1_desc" name="employer_doc_other_1_desc" value="{{ old('employer_doc_other_1_desc', $employer->employer_doc_other_1_desc ?? '') }}" placeholder="{{ __('Specify description...') }}">
                @if($employer->employer_doc_other_1)
                    <div class="file-upload-display mt-2">
                        <a href="{{ asset('storage/' . $employer->employer_doc_other_1) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> {{ __('View current file') }}</a>
                    </div>
                @endif
                @error('employer_doc_other_1')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="employer_doc_other_2" class="form-label">5. {{ __('Other Document') }} 2</label>
                <input type="file" class="form-control form-control-sm @error('employer_doc_other_2') is-invalid @enderror" id="employer_doc_other_2" name="employer_doc_other_2">
                <input type="text" class="form-control form-control-sm mt-2 @error('employer_doc_other_2_desc') is-invalid @enderror" id="employer_doc_other_2_desc" name="employer_doc_other_2_desc" value="{{ old('employer_doc_other_2_desc', $employer->employer_doc_other_2_desc ?? '') }}" placeholder="{{ __('Specify description...') }}">
                @if($employer->employer_doc_other_2)
                    <div class="file-upload-display mt-2">
                        <a href="{{ asset('storage/' . $employer->employer_doc_other_2) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> {{ __('View current file') }}</a>
                    </div>
                @endif
                @error('employer_doc_other_2')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="employer_doc_other_3" class="form-label">6. {{ __('Other Document') }} 3</label>
                <input type="file" class="form-control form-control-sm @error('employer_doc_other_3') is-invalid @enderror" id="employer_doc_other_3" name="employer_doc_other_3">
                <input type="text" class="form-control form-control-sm mt-2 @error('employer_doc_other_3_desc') is-invalid @enderror" id="employer_doc_other_3_desc" name="employer_doc_other_3_desc" value="{{ old('employer_doc_other_3_desc', $employer->employer_doc_other_3_desc ?? '') }}" placeholder="{{ __('Specify description...') }}">
                @if($employer->employer_doc_other_3)
                    <div class="file-upload-display mt-2">
                        <a href="{{ asset('storage/' . $employer->employer_doc_other_3) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> {{ __('View current file') }}</a>
                    </div>
                @endif
                @error('employer_doc_other_3')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="mt-4">
            @can('edit-employers')
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> {{ __('Save Employer Info') }}</button>
            @endcan
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>

<div id="addressListsContainer" data-url="{{ route('addresses.thai_data') }}">
    {{-- Registered Address Section --}}
    <div class="content-section mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">{{ __('Registered Address') }}</h5>
            <button type="button" class="btn btn-sm btn-primary add-address-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#addressModal"
                    data-type="registered"
                    data-addressable-id="{{ $employer->id }}">
                {{ __('Add Address') }}
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
                        <button type="button" class="btn btn-sm btn-danger btn-delete-address" data-address-id="{{ $address->id }}">{{ __('Delete') }}</button>
                    </div>
                </div>
            @empty
                <p class="text-muted">{{ __('No address yet') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Workplace Address Section --}}
    <div class="content-section mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">{{ __('Workplace Address') }}</h5>
            <button type="button" class="btn btn-sm btn-primary add-address-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#addressModal"
                    data-type="workplace"
                    data-addressable-id="{{ $employer->id }}">
                {{ __('Add Address') }}
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
                        <button type="button" class="btn btn-sm btn-danger btn-delete-address" data-address-id="{{ $address->id }}">{{ __('Delete') }}</button>
                    </div>
                </div>
            @empty
                <p class="text-muted">{{ __('No address yet') }}</p>
            @endforelse
        </div>
    </div>
</div>


<hr class="my-4">

<div id="employee-list-container">
    @php
        $totalEmployees = $employees->total();
        // CORRECTED FATAL ERROR: Use 'employeeTitleTh' for both counts.
        $maleCount = $employer->employees()->whereIn('employeeTitleTh', ['นาย'])->count();
        $femaleCount = $employer->employees()->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            {{ __('Employee Info (Total: :total | Male: :male | Female: :female)', ['total' => $totalEmployees, 'male' => $maleCount, 'female' => $femaleCount]) }}
        </h5>
        @can('create-employees')
        <a href="{{ route('employees.create', ['employer_id' => $employer->id]) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> {{ __('Add Employee') }}
        </a>
        @endcan
    </div>

    <div class="card p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <form method="GET" action="{{ route('employers.edit', $employer->id) }}" class="d-flex flex-wrap align-items-center gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search') }}..." value="{{ request('search') }}" style="width: 200px;">
                <select name="nationality" class="form-select form-select-sm" style="width: auto;">
                     <option value="">-- {{ __('All Nationalities') }} --</option>
                    <option value="เมียนมา" @if(request('nationality') == 'เมียนมา') selected @endif>เมียนมา</option>
                    <option value="ลาว" @if(request('nationality') == 'ลาว') selected @endif>ลาว</option>
                    <option value="กัมพูชา" @if(request('nationality') == 'กัมพูชา') selected @endif>กัมพูชา</option>
                    <option value="เวียดนาม" @if(request('nationality') == 'เวียดนาม') selected @endif>เวียดนาม</option>
                </select>
                <select name="mou_group" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('All MOU Types') }} --</option>
                    <option value="MOU" @if(request('mou_group') == 'MOU') selected @endif>MOU</option>
                    <option value="มติต่ออายุในประเทศ" @if(request('mou_group') == 'มติต่ออายุในประเทศ') selected @endif>มติต่ออายุในประเทศ</option>
                    <option value="มติขึ้นทะเบียน" @if(request('mou_group') == 'มติขึ้นทะเบียน') selected @endif>มติขึ้นทะเบียน</option>
                    <option value="อื่นๆ" @if(request('mou_group') == 'อื่นๆ') selected @endif>อื่นๆ</option>
                </select>
                <select name="insurance_type" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('Insurance Type') }} --</option>
                    <option value="none" {{ request('insurance_type') == 'none' ? 'selected' : '' }}>{{ __('No Insurance') }}</option>
                    <option value="ประกันสังคม" {{ request('insurance_type') == 'ประกันสังคม' ? 'selected' : '' }}>{{ __('Social Security') }}</option>
                    <option value="ประกันโรงพยาบาล" {{ request('insurance_type') == 'ประกันโรงพยาบาล' ? 'selected' : '' }}>{{ __('Hospital Insurance') }}</option>
                    <option value="ประกันเอกชน" {{ request('insurance_type') == 'ประกันเอกชน' ? 'selected' : '' }}>{{ __('Private Insurance') }}</option>
                </select>
                <select name="pink_card" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('Pink Card') }} --</option>
                    <option value="yes" @if(request('pink_card') == 'yes') selected @endif>{{ __('Has Pink Card') }}</option>
                    <option value="no" @if(request('pink_card') == 'no') selected @endif>{{ __('No Pink Card') }}</option>
                </select>
                <select name="passport_type_myanmar" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('Passport Type (Myanmar)') }} --</option>
                    <option value="CI" {{ request('passport_type_myanmar') == 'CI' ? 'selected' : '' }}>เล่ม CI</option>
                    <option value="PJ" {{ request('passport_type_myanmar') == 'PJ' ? 'selected' : '' }}>เล่ม PJ</option>
                </select>
                <select name="passport_type_cambodia" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('Passport Type (Cambodia)') }} --</option>
                    <option value="เล่ม TD" {{ request('passport_type_cambodia') == 'เล่ม TD' ? 'selected' : '' }}>เล่ม TD</option>
                    <option value="เล่มอินเตอร์" {{ request('passport_type_cambodia') == 'เล่มอินเตอร์' ? 'selected' : '' }}>เล่มอินเตอร์</option>
                </select>
                <input type="date" name="work_permit_expiry_date" class="form-control form-control-sm" value="{{ request('work_permit_expiry_date') }}" title="{{ __('Search by work permit expiry date') }}">
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('employers.edit', $employer->id) }}" class="btn btn-sm btn-secondary">{{ __('Clear') }}</a>
            </form>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('employers.exportEmployees', ['employer' => $employer->id] + request()->query()) }}" class="btn btn-sm btn-outline-success">
                     <i class="bi bi-file-earmark-excel me-1"></i> Export
                </a>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('employers.edit', ['employer' => $employer->id] + array_merge(request()->query(), ['view' => 'card'])) }}" class="btn {{ $currentView == 'card' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Card') }}</a>
                    <a href="{{ route('employers.edit', ['employer' => $employer->id] + array_merge(request()->query(), ['view' => 'table'])) }}" class="btn {{ $currentView == 'table' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Table') }}</a>
                </div>
                <div class="btn-group btn-group-sm">
                    @foreach($perPageOptions as $option)
                        <a href="{{ route('employers.edit', ['employer' => $employer->id] + array_merge(request()->query(), ['per_page' => $option])) }}" class="btn {{ $currentPerPage == $option ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $option }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <x-bulk-action-bar id="employer-edit-bulk-bar">
        <li><a class="dropdown-item" href="#" id="employer-bulk-advanced-edit-btn"><i class="bi bi-pencil-square me-2"></i>{{ __('Advanced Edit') }}</a></li>
        <li><a class="dropdown-item" href="#" id="employer-bulk-advanced-export-btn"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Advanced Export') }}</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="#" id="employer-bulk-download-btn"><i class="bi bi-download me-2"></i>{{ __('Download Files') }}</a></li>
        <li><a class="dropdown-item" href="#" id="employer-bulk-send-data-btn"><i class="bi bi-send me-2"></i>{{ __('Send Data') }}</a></li>
    </x-bulk-action-bar>

    <div id="employeeList">
        @if($currentView === 'card')
            <div class="list-group">
            @forelse($employees as $employee)
                <div class="position-relative">
                    @include('partials._employee_card', [
                        'employee' => $employee,
                        'loop' => $loop,
                        'pagination' => $employees,
                        'showLocateButton' => false,
                        'elementId' => 'employee-card-' . $employee->id,
                        'dragUrl' => request()->fullUrl() . '#employee-card-' . $employee->id
                    ])
                </div>
            @empty
                <p class="text-center text-muted">{{ __('No employees found matching criteria') }}</p>
            @endforelse
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="table-select-all-checkbox"></th>
                            <th style="width: 5%;">#</th>
                            <th style="width: 10%;">Photo</th>
                            <th style="width: 25%;">Name (EN)</th>
                            <th style="width: 25%;">Name (TH)</th>
                            <th style="width: 15%;">Passport</th>
                            <th style="width: 10%;">{{ __('Status') }}</th>
                            <th style="width: 10%;">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr id="employee-row-{{ $employee->id }}" draggable="true"
                                data-drag-payload="{{ json_encode([
                                    'id' => $employee->id,
                                    'title' => $employee->employeeFullName,
                                    'subtitle' => $employer->employerNameTh,
                                    'photo_url' => $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC',
                                    'url' => request()->fullUrl() . '#employee-row-' . $employee->id,
                                    'employer_name' => $employer->employerNameTh,
                                    'nationality' => $employee->employeeNationality
                                ]) }}"
                                ondragstart="window.startDragGlobal(event, 'employee', JSON.parse(this.dataset.dragPayload))">
                                {{-- DEFINITIVE FIX: Add checkbox for bulk actions --}}
                                <td><input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-id="{{ $employee->id }}"></td>
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
                                <td>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? __('No English Name') }}</td>
                                <td>{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? __('No Thai Name') }}<br><small class="text-muted">{{ $employee->employeePosition ?? __('Unspecified Position') }}</small></td>
                                <td>{{ $employee->employeePassport ?? '-' }}</td>
                                <td>
                                    @php
                                        $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                                    @endphp
                                    @if($countryCode)
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" class="me-2" style="width: 20px;">
                                            <span>{{ $employee->employeeNationality }}</span>
                                        </div>
                                    @else
                                        {{ $employee->employeeNationality ?? '-' }}
                                    @endif
                                </td>
                                <td>
                                    <x-employee-action-buttons :employee="$employee" :show-locate-button="false" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">{{ __('No employees found matching criteria') }}</td>
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
</div>

{{-- Employment History Section --}}
<div class="d-flex justify-content-between align-items-center mt-5">
    <div>
        <h4 class="mb-0">{{ __('Employment History') }}</h4>
        <p class="text-muted small">{{ __('View all past employment history here') }}</p>
    </div>
    <button type="button" class="btn btn-outline-secondary"
            data-bs-toggle="modal"
            data-bs-target="#employmentHistoryModal"
            data-employer-id="{{ $employer->id }}">
        <i class="bi bi-clock-history me-2"></i>{{ __('View Employment History') }}
    </button>
</div>

    @include('partials._address_management')
    @include('partials._employee_action_modals')
    @include('employees.modals.advanced_export')
    @include('employees.modals.select_target_employer_modal')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle Advanced Edit
        const bulkEditBtn = document.getElementById('employer-bulk-advanced-edit-btn');
        if (bulkEditBtn) {
            bulkEditBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                // Create a form dynamically and submit POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('employees.bulk_edit.select_fields') }}';

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);

                const redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect_to';
                redirectInput.value = window.location.href;
                form.appendChild(redirectInput);

                selected.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employee_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            });
        }

        // Handle Download Files
        const bulkDownloadBtn = document.getElementById('employer-bulk-download-btn');
        if (bulkDownloadBtn) {
            bulkDownloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);
                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                if (window.openBulkDownloadModal) {
                    window.openBulkDownloadModal(selected);
                } else {
                    console.error('Download modal function not found.');
                    showToast('Download function not available.', 'danger');
                }
            });
        }

        const bulkExportBtn = document.getElementById('employer-bulk-advanced-export-btn');
        if (bulkExportBtn) {
            bulkExportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                document.getElementById('export_employee_ids').value = JSON.stringify(selected);
                const modalEl = document.getElementById('advancedExportModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        }

        const bulkSendDataBtn = document.getElementById('employer-bulk-send-data-btn');
        if (bulkSendDataBtn) {
            bulkSendDataBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
                const selected = Array.from(checkboxes).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                // Step 1: Check if all selected employees belong to the same employer (based on current view context)
                let employerIds = new Set();
                checkboxes.forEach(cb => {
                    const empId = cb.getAttribute('data-employer-id');
                    if (empId) employerIds.add(empId);
                });

                if (employerIds.size > 1) {
                     Swal.fire({
                        icon: 'warning',
                        title: '{{ __('Multiple Employers Selected') }}',
                        text: '{{ __('You selected employees from different employers. Please select employees from the same employer for one transaction.') }}'
                    });
                    return;
                }

                // Step 2: Store selected IDs
                window.pendingTicketEmployeeIds = selected;

                // Step 3: Open Modal to Select Target Employer
                const modalEl = document.getElementById('selectTargetEmployerModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        }

        @if (session('highlight_employee'))
            const employeeId = '{{ session('highlight_employee') }}';
            // Try both card ID (Card View) and row ID (Table View)
            const targetElement = document.getElementById('employee-card-' + employeeId) || document.getElementById('employee-row-' + employeeId);

            if (targetElement) {
                // Add a highlight class
                targetElement.classList.add('highlight');

                // Scroll to the element
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Optional: Remove the highlight after a few seconds
                setTimeout(() => {
                    targetElement.classList.remove('highlight');
                }, 5000); // Highlight for 5 seconds
            }
        @endif
    });
</script>
@endpush
