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
    canvas#signature-pad {
        touch-action: none; /* Prevent scrolling when drawing on touch devices */
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        cursor: crosshair;
    }
</style>
@endpush

@section('title', __('Edit Employer'))

@section('content')

{{-- Employer Info Form --}}
<div class="content-section">
    <h2 class="mb-4">{{ __('Edit Employer') }}</h2>
    <form id="employerForm" action="{{ route('employers.update', $employer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" id="return_url" name="return_url" value="{{ request('return_url', route('employers.index')) }}">

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
                <label class="form-label">{{ __('Responsible Person') }}</label>
                <div class="position-relative" x-data="{
                    open: false,
                    selected: {{ json_encode(old('assigned_staff_ids', $employer->caretakers->pluck('id')->toArray())) }},
                    options: {{ $staffUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toJson() }},
                    search: '',
                    get filteredOptions() {
                        if (this.search === '') return this.options;
                        return this.options.filter(option => option.name.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get buttonText() {
                        if (this.selected.length === 0) return '{{ __('Select Responsible Person') }}';
                        if (this.selected.length <= 2) {
                            return this.selected.map(id => this.options.find(o => o.id == id)?.name).filter(Boolean).join(', ');
                        }
                        return this.selected.length + ' {{ __('Selected') }}';
                    }
                }">
                    <button type="button" class="form-select text-start" @click="open = !open" x-text="buttonText"></button>

                    <div x-show="open" @click.outside="open = false" style="display: none; z-index: 1050; max-height: 300px; overflow-y: auto;" class="position-absolute w-100 bg-white border rounded shadow mt-1 p-2">
                        <input type="text" class="form-control form-control-sm mb-2" placeholder="{{ __('Search...') }}" x-model="search">
                        <template x-for="option in filteredOptions" :key="option.id">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" :id="'staff_' + option.id" :value="option.id" x-model="selected" name="assigned_staff_ids[]">
                                <label class="form-check-label w-100" :for="'staff_' + option.id" x-text="option.name" style="cursor: pointer;"></label>
                            </div>
                        </template>
                        <div x-show="filteredOptions.length === 0" class="text-muted small text-center">{{ __('No results found') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">{{ __('Employer Tax ID') }}</label>
                <input type="text" class="form-control" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId', $employer->employerTaxId) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('Select Business Type') }}</label>
                <div class="input-group">
                    <select class="form-select" id="business_type_select">
                        <option value="">{{ __('Select...') }}</option>
                    </select>
                    @if(auth()->user()->hasRole('admin'))
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#manageBusinessTypeModal">
                        <i class="bi bi-gear"></i> {{ __('Manage') }}
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessType" class="form-label">{{ __('Business Type (Thai)') }}</label>
                <input type="text" class="form-control" id="businessType" name="businessType" value="{{ old('businessType', $employer->businessType) }}">
            </div>
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">{{ __('Business Type (English)') }}</label>
                <input type="text" class="form-control" id="businessTypeEn" name="businessTypeEn" value="{{ old('businessTypeEn', $employer->businessTypeEn) }}">
            </div>
        </div>

 <div class="row mb-3">
 <div class="col-md-6">
 <label for="employerPhone" class="form-label">{{ __('Phone Number') }}</label>
 <input type="text" class="form-control @error('employerPhone') is-invalid @enderror" id="employerPhone" name="employerPhone" value="{{ old('employerPhone', $employer->employerPhone ?? '') }}">
 @error('employerPhone')
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

 <!-- System Access Group -->
 <h5 class="mb-3 text-primary mt-4">System Access & Outsource</h5>
        <div class="row mb-3">
 <div class="col-md-6">
 <label for="employerEmail" class="form-label">{{ __('Employer Email') }}</label>
 <input type="text" class="form-control @error('employerEmail') is-invalid @enderror" id="employerEmail" name="employerEmail" value="{{ old('employerEmail', $employer->employerEmail ?? '') }}">
 @error('employerEmail')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 <div class="col-md-6">
 <label for="employerPassword" class="form-label">{{ __('Email Password (รหัสสำหรับอีเมล)') }}</label>
 <input type="text" class="form-control @error('employerPassword') is-invalid @enderror" id="employerPassword" name="employerPassword" value="{{ old('employerPassword', $employer->employerPassword) }}">
 @error('employerPassword')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 </div>
 <div class="row mb-3">
 <div class="col-md-6">
 <label for="outsource_re_code" class="form-label">{{ __('RE Code') }}</label>
 <input type="text" class="form-control @error('outsource_re_code') is-invalid @enderror" id="outsource_re_code" name="outsource_re_code" value="{{ old('outsource_re_code', $employer->outsource_re_code) }}">
 @error('outsource_re_code')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 <div class="col-md-6">
 <label for="outsource_password" class="form-label">{{ __('Outsource Password (รหัสสำหรับเข้าระบบ Outsource)') }}</label>
 <input type="text" class="form-control @error('outsource_password') is-invalid @enderror" id="outsource_password" name="outsource_password" value="{{ old('outsource_password', $employer->outsource_password) }}">
 @error('outsource_password')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 </div>

        <h5 class="mb-3 text-primary mt-4">{{ __('Authorized Signatories') }}</h5>
        <!-- Signer 1 -->
        <div class="card bg-light mb-3" x-data="signatureField('signature_1', '{{ $employer->signature_1_path ? Storage::url($employer->signature_1_path) : '' }}')">
            <div class="card-body">
                <h6 class="card-title text-muted fw-bold">Signer 1</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="signerNameTh" class="form-label">{{ __('Name (Thai)') }}</label>
                        <input type="text" class="form-control" id="signerNameTh" name="signerNameTh" value="{{ old('signerNameTh', $employer->signerNameTh) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="signerNameEn" class="form-label">{{ __('Name (English)') }}</label>
                        <input type="text" class="form-control" id="signerNameEn" name="signerNameEn" value="{{ old('signerNameEn', $employer->signerNameEn) }}">
                    </div>
                </div>

                <!-- Hidden Inputs for State Persistence -->
                <input type="hidden" name="signature_1_action" x-model="action">
                <input type="hidden" name="signature_1_base64" x-model="base64">
                <div x-ref="fileInputContainer" class="d-none"></div>

                <!-- Preview and Trigger -->
                <div class="d-flex flex-column flex-md-row align-items-start gap-3 mt-2">
                     <div class="d-flex flex-column">
                        <label class="form-label small text-muted mb-1">{{ __('Signature Preview') }}</label>
                        <div class="border rounded bg-white d-flex justify-content-center align-items-center overflow-hidden position-relative" style="width: 180px; height: 100px;">
                             <template x-if="previewUrl">
                                 <img :src="previewUrl" class="img-fluid" style="max-height: 100%;">
                             </template>
                             <template x-if="!previewUrl">
                                 <span class="text-muted small italic">{{ __('No Signature') }}</span>
                             </template>
                             <template x-if="action !== 'keep' && action !== ''">
                                 <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-1" style="font-size: 0.6rem;">{{ __('Pending Save') }}</span>
                             </template>
                        </div>
                     </div>
                     <div class="mt-md-4">
                        <button type="button" class="btn btn-outline-primary" @click="$dispatch('open-signature-modal', { field: 'signature_1', currentAction: action, currentUrl: previewUrl, currentBase64: base64 })">
                            <i class="bi bi-pen me-2"></i> {{ __('Signature Settings') }}
                        </button>
                        <div class="form-text mt-1">{{ __('Click to edit, upload, or draw signature.') }}</div>
                     </div>
                </div>
            </div>
        </div>

        <!-- Signer 2 -->
        <div class="card bg-light mb-3" x-data="signatureField('signature_2', '{{ $employer->signature_2_path ? Storage::url($employer->signature_2_path) : '' }}')">
            <div class="card-body">
                <h6 class="card-title text-muted fw-bold">Signer 2 (Optional)</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="signer_2_name_th" class="form-label">{{ __('Name (Thai)') }}</label>
                        <input type="text" class="form-control" id="signer_2_name_th" name="signer_2_name_th" value="{{ old('signer_2_name_th', $employer->signer_2_name_th) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="signer_2_name_en" class="form-label">{{ __('Name (English)') }}</label>
                        <input type="text" class="form-control" id="signer_2_name_en" name="signer_2_name_en" value="{{ old('signer_2_name_en', $employer->signer_2_name_en) }}">
                    </div>
                </div>

                 <!-- Hidden Inputs for State Persistence -->
                 <input type="hidden" name="signature_2_action" x-model="action">
                 <input type="hidden" name="signature_2_base64" x-model="base64">
                 <div x-ref="fileInputContainer" class="d-none"></div>

                 <!-- Preview and Trigger -->
                 <div class="d-flex flex-column flex-md-row align-items-start gap-3 mt-2">
                      <div class="d-flex flex-column">
                         <label class="form-label small text-muted mb-1">{{ __('Signature Preview') }}</label>
                         <div class="border rounded bg-white d-flex justify-content-center align-items-center overflow-hidden position-relative" style="width: 180px; height: 100px;">
                              <template x-if="previewUrl">
                                  <img :src="previewUrl" class="img-fluid" style="max-height: 100%;">
                              </template>
                              <template x-if="!previewUrl">
                                  <span class="text-muted small italic">{{ __('No Signature') }}</span>
                              </template>
                              <template x-if="action !== 'keep' && action !== ''">
                                <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-1" style="font-size: 0.6rem;">{{ __('Pending Save') }}</span>
                            </template>
                         </div>
                      </div>
                      <div class="mt-md-4">
                         <button type="button" class="btn btn-outline-primary" @click="$dispatch('open-signature-modal', { field: 'signature_2', currentAction: action, currentUrl: previewUrl, currentBase64: base64 })">
                             <i class="bi bi-pen me-2"></i> {{ __('Signature Settings') }}
                         </button>
                         <div class="form-text mt-1">{{ __('Click to edit, upload, or draw signature.') }}</div>
                      </div>
                 </div>
            </div>
        </div>

        <div class="row mb-3">
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
                <x-file-input-group
                    id="employer_doc_company"
                    name="employer_doc_company"
                    label="1. {{ __('Company Certificate / ID Card') }}"
                    :value="$employer->employer_doc_company"
                    :pdfRoute="route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_company'])"
                />
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
                <x-file-input-group
                    id="employer_doc_lease"
                    name="employer_doc_lease"
                    label="2. {{ __('Lease Agreement / House Registration') }}"
                    :value="$employer->employer_doc_lease"
                    :pdfRoute="route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_lease'])"
                />
            </div>
            <div class="col-md-6">
                <x-file-input-group
                    id="employer_doc_construction"
                    name="employer_doc_construction"
                    label="3. {{ __('Construction Contract / Map') }}"
                    :value="$employer->employer_doc_construction"
                    :pdfRoute="route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_construction'])"
                />
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <x-file-input-group
                    id="employer_doc_other_1"
                    name="employer_doc_other_1"
                    label="4. {{ __('Other Document') }} 1"
                    :value="$employer->employer_doc_other_1"
                    :pdfRoute="route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_other_1'])"
                    description="employer_doc_other_1_desc"
                    :descriptionValue="$employer->employer_doc_other_1_desc"
                />
            </div>
            <div class="col-md-4">
                <x-file-input-group
                    id="employer_doc_other_2"
                    name="employer_doc_other_2"
                    label="5. {{ __('Other Document') }} 2"
                    :value="$employer->employer_doc_other_2"
                    :pdfRoute="route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_other_2'])"
                    description="employer_doc_other_2_desc"
                    :descriptionValue="$employer->employer_doc_other_2_desc"
                />
            </div>
            <div class="col-md-4">
                <x-file-input-group
                    id="employer_doc_other_3"
                    name="employer_doc_other_3"
                    label="6. {{ __('Other Document') }} 3"
                    :value="$employer->employer_doc_other_3"
                    :pdfRoute="route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_other_3'])"
                    description="employer_doc_other_3_desc"
                    :descriptionValue="$employer->employer_doc_other_3_desc"
                />
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

<!-- ... Address Lists ... -->
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
                <div class="address-card d-flex gap-3 align-items-start" id="address-card-{{$address->id}}">
                    <div class="pt-1">
                        <input type="radio"
                               class="form-check-input"
                               name="document_address_radio"
                               value="{{ $address->id }}"
                               {{ $address->is_document_address ? 'checked' : '' }}
                               onchange="setDocumentAddress({{ $address->id }})"
                               title="{{ __('Use this address for documents') }}"
                               style="cursor: pointer;">
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-0"><strong>TH:</strong> {{ $address->full_address_th }}</p>
                        <p class="mb-0"><strong>EN:</strong> {{ $address->full_address_en }}</p>
                        @if($address->is_document_address)
                            <span class="badge bg-success text-white" style="font-size: 0.7rem;">{{ __('Default for Documents') }}</span>
                        @endif
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
                <div class="address-card d-flex gap-3 align-items-start" id="address-card-{{$address->id}}">
                    <div class="pt-1">
                        <input type="radio"
                               class="form-check-input"
                               name="document_address_radio"
                               value="{{ $address->id }}"
                               {{ $address->is_document_address ? 'checked' : '' }}
                               onchange="setDocumentAddress({{ $address->id }})"
                               title="{{ __('Use this address for documents') }}"
                               style="cursor: pointer;">
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-0"><strong>TH:</strong> {{ $address->full_address_th }}</p>
                        <p class="mb-0"><strong>EN:</strong> {{ $address->full_address_en }}</p>
                        @if($address->is_document_address)
                            <span class="badge bg-success text-white" style="font-size: 0.7rem;">{{ __('Default for Documents') }}</span>
                        @endif
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
                <select name="passport_status" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('Passport Status') }} --</option>
                    <option value="has_passport" {{ request('passport_status') == 'has_passport' ? 'selected' : '' }}>{{ __('Has Passport') }}</option>
                    <option value="no_passport" {{ request('passport_status') == 'no_passport' ? 'selected' : '' }}>{{ __('No Passport') }}</option>
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
        <li><a class="dropdown-item" href="#" id="employer-bulk-send-production-btn"><i class="bi bi-diagram-3 me-2"></i>{{ __('Send to P Production') }}</a></li>
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
                            <th style="width: 1%;"></th>
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
                            <tr id="employee-row-{{ $employee->id }}">
                                <td><input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employer-id="{{ $employer->id }}"></td>
                                <td>
                                    <span class="cursor-grab"
                                          draggable="true"
                                          data-drag-payload="{{ json_encode([
                                              'id' => $employee->id,
                                              'title' => $employee->employeeNameEn ?? $employee->employeeNameTh,
                                              'title_th' => $employee->employeeNameTh,
                                              'title_en' => $employee->employeeNameEn,
                                              'subtitle' => $employee->job_title ?? 'N/A',
                                              'photo_url' => $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-profile.png'),
                                              'url' => request()->fullUrl() . '#employee-row-' . $employee->id,
                                              'source_menu' => 'Employer Info',
                                              'employer_name' => $employer->employerNameTh,
                                              'nationality' => $employee->employeeNationality
                                          ]) }}"
                                          ondragstart="window.startDragGlobal(event, 'employee', JSON.parse(this.dataset.dragPayload))"
                                         title="{{ __('Drag') }}">
                                        <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                                    </span>
                                </td>
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
                                <td colspan="9" class="text-center text-muted py-3">{{ __('No employees found matching criteria') }}</td>
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

<!-- Business Type Management Modal -->
<div class="modal fade" id="manageBusinessTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Manage Business Types') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addBusinessTypeForm" class="mb-4">
                    <div class="mb-2">
                        <label class="form-label">{{ __('Thai Name') }}</label>
                        <input type="text" class="form-control form-control-sm" name="name_th" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('English Name') }}</label>
                        <input type="text" class="form-control form-control-sm" name="name_en" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100">{{ __('Add Type') }}</button>
                </form>
                <hr>
                <h6>{{ __('Existing Types') }}</h6>
                <ul class="list-group" id="businessTypeList">
                    <!-- Loaded via JS -->
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Signature Settings Modal -->
<div class="modal fade" id="signatureSettingsModal" tabindex="-1" aria-labelledby="signatureSettingsModalLabel" aria-hidden="true" x-data="signatureModalController">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="signatureSettingsModalLabel">{{ __('Signature Settings') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'generate' }" @click="activeTab = 'generate'" type="button">{{ __('Auto Generate') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'upload' }" @click="activeTab = 'upload'" type="button">{{ __('Upload File') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" :class="{ 'active': activeTab === 'draw' }" @click="activeTab = 'draw'" type="button">{{ __('Draw Signature') }}</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Auto Generate Tab -->
                    <div class="tab-pane fade" :class="{ 'show active': activeTab === 'generate' }">
                        <div class="text-center p-4 border rounded bg-light">
                             <p class="mb-3">{{ __('A signature will be automatically generated and saved for this user.') }}</p>
                             <div class="alert alert-info small mb-0">
                                <i class="bi bi-info-circle me-1"></i> {{ __('If a generated signature already exists, it will be kept unless you choose to regenerate.') }}
                             </div>
                        </div>
                    </div>

                    <!-- Upload Tab -->
                    <div class="tab-pane fade" :class="{ 'show active': activeTab === 'upload' }">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Upload Signature Image') }}</label>
                            <input type="file" class="form-control" accept="image/png, image/jpeg" @change="handleFileSelect" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                            <div class="form-text">{{ __('Max size: 2MB. Allowed formats: PNG, JPG.') }}</div>
                        </div>
                    </div>

                    <!-- Draw Tab -->
                    <div class="tab-pane fade" :class="{ 'show active': activeTab === 'draw' }">
                        <div class="mb-2">
                             <label class="form-label">{{ __('Draw Signature below') }}</label>
                             <div class="position-relative">
                                 <canvas id="signature-pad" width="450" height="200" class="bg-white w-100"></canvas>
                             </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearCanvas">
                                <i class="bi bi-eraser"></i> {{ __('Clear') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" @click="saveSettings">{{ __('Save Temporary') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('signatureField', (fieldName, initialUrl) => ({
            action: 'keep', // keep, generate, upload, draw
            base64: '',
            previewUrl: initialUrl || '',

            init() {
                // Listen for save event from the modal
                window.addEventListener('signature-saved', (event) => {
                    if (event.detail.field === fieldName) {
                        this.action = event.detail.action;
                        this.base64 = event.detail.base64 || '';

                        // Update preview
                        if (this.action === 'draw' && this.base64) {
                            this.previewUrl = this.base64;
                        } else if (this.action === 'upload' && event.detail.file) {
                             // Create a local preview for the uploaded file
                             const reader = new FileReader();
                             reader.onload = (e) => { this.previewUrl = e.target.result; };
                             reader.readAsDataURL(event.detail.file);

                             // Move the file input to this component's container so it gets submitted with the form
                             const container = this.$refs.fileInputContainer;
                             container.innerHTML = ''; // clear previous
                             // We need to clone the file input from the modal, but we can't easily clone file inputs with value.
                             // Instead, we will grab the actual element from the modal and move it here.
                             if (event.detail.fileInputEl) {
                                 // Rename it to match the controller expectation
                                 event.detail.fileInputEl.name = fieldName + '_file';
                                 event.detail.fileInputEl.classList.add('d-none'); // hide it
                                 container.appendChild(event.detail.fileInputEl);
                             }
                        } else if (this.action === 'generate') {
                            // We can't easily show a preview of server-generated signature before saving
                            // Show a placeholder
                            this.previewUrl = ''; // Clear it or show a placeholder icon
                            // Maybe set a placeholder image via JS? For now, the text "No Signature" or "Pending" will show.
                        }
                    }
                });
            }
        }));

        Alpine.data('signatureModalController', () => ({
            activeTab: 'generate',
            targetField: '',
            canvas: null,
            ctx: null,
            isDrawing: false,
            uploadedFile: null,
            uploadedFileInput: null,
            modalInstance: null,

            init() {
                this.modalInstance = new bootstrap.Modal(document.getElementById('signatureSettingsModal'));

                window.addEventListener('open-signature-modal', (event) => {
                    this.targetField = event.detail.field;
                    const currentAction = event.detail.currentAction;

                    // Set initial tab based on current action
                    if (currentAction === 'keep') this.activeTab = 'generate'; // Default to generate if keeping
                    else if (currentAction === 'draw') this.activeTab = 'draw';
                    else if (currentAction === 'upload') this.activeTab = 'upload';
                    else this.activeTab = currentAction; // fallback

                    this.modalInstance.show();

                    // Init canvas after modal is shown
                    setTimeout(() => {
                        this.initCanvas();
                        if (currentAction === 'draw' && event.detail.currentBase64) {
                            this.loadCanvas(event.detail.currentBase64);
                        }
                    }, 500);
                });
            },

            initCanvas() {
                this.canvas = document.getElementById('signature-pad');
                this.ctx = this.canvas.getContext('2d');
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.strokeStyle = '#000';

                const getPos = (e) => {
                    const rect = this.canvas.getBoundingClientRect();
                    const scaleX = this.canvas.width / rect.width;
                    const scaleY = this.canvas.height / rect.height;

                    let clientX, clientY;
                    if (e.changedTouches) {
                         clientX = e.changedTouches[0].clientX;
                         clientY = e.changedTouches[0].clientY;
                    } else {
                         clientX = e.clientX;
                         clientY = e.clientY;
                    }

                    return {
                        x: (clientX - rect.left) * scaleX,
                        y: (clientY - rect.top) * scaleY
                    };
                };

                // Mouse Events
                this.canvas.onmousedown = (e) => {
                    this.isDrawing = true;
                    this.ctx.beginPath();
                    const pos = getPos(e);
                    this.ctx.moveTo(pos.x, pos.y);
                };
                this.canvas.onmousemove = (e) => {
                    if (this.isDrawing) {
                        const pos = getPos(e);
                        this.ctx.lineTo(pos.x, pos.y);
                        this.ctx.stroke();
                    }
                };
                this.canvas.onmouseup = () => { this.isDrawing = false; };
                this.canvas.onmouseout = () => { this.isDrawing = false; };

                // Touch Events
                this.canvas.ontouchstart = (e) => {
                    e.preventDefault();
                    this.isDrawing = true;
                    this.ctx.beginPath();
                    const pos = getPos(e);
                    this.ctx.moveTo(pos.x, pos.y);
                };
                this.canvas.ontouchmove = (e) => {
                    if (this.isDrawing) {
                        e.preventDefault();
                        const pos = getPos(e);
                        this.ctx.lineTo(pos.x, pos.y);
                        this.ctx.stroke();
                    }
                };
                this.canvas.ontouchend = () => { this.isDrawing = false; };
            },

            clearCanvas() {
                if(this.ctx) this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            },

            loadCanvas(base64) {
                 const img = new Image();
                 img.onload = () => {
                     this.clearCanvas();
                     this.ctx.drawImage(img, 0, 0);
                 };
                 img.src = base64;
            },

            handleFileSelect(e) {
                if (e.target.files.length > 0) {
                    this.uploadedFile = e.target.files[0];
                    this.uploadedFileInput = e.target; // Keep reference to move it later
                }
            },

            saveSettings() {
                let eventData = {
                    field: this.targetField,
                    action: this.activeTab,
                };

                let replacementInput = null;
                let originalParent = null;

                if (this.activeTab === 'draw') {
                    eventData.base64 = this.canvas.toDataURL('image/png');
                } else if (this.activeTab === 'upload') {
                    if (this.uploadedFile && this.uploadedFileInput) {
                        eventData.file = this.uploadedFile;
                        eventData.fileInputEl = this.uploadedFileInput; // Send the element itself

                        // Prepare replacement BEFORE dispatching event (which moves the original)
                        originalParent = this.uploadedFileInput.parentElement;
                        if (originalParent) {
                             replacementInput = this.uploadedFileInput.cloneNode(true);
                             replacementInput.value = ''; // clear value
                             replacementInput.addEventListener('change', (e) => this.handleFileSelect(e));
                        }
                    }
                }

                window.dispatchEvent(new CustomEvent('signature-saved', { detail: eventData }));
                this.modalInstance.hide();

                // Clear state
                this.clearCanvas();
                this.uploadedFile = null;

                // If we moved the input, put the replacement back
                if (replacementInput && originalParent) {
                     originalParent.appendChild(replacementInput);
                }
                this.uploadedFileInput = null;
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', function () {
        // --- Employer Form AJAX Submission ---
        const employerForm = document.getElementById('employerForm');
        if (employerForm) {
            employerForm.addEventListener('submit', function (e) {
                e.preventDefault();

                // Clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                if (!submitBtn) return; // Guard clause

                const originalBtnText = submitBtn.innerHTML;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __("Saving...") }}';

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    return response.json().then(data => {
                        if (!response.ok) {
                            throw { status: response.status, data: data };
                        }
                        return data;
                    });
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Success") }}',
                            text: data.message || '{{ __("Employer updated successfully.") }}',
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        }).then((result) => {
                             if (result.isConfirmed || result.isDismissed) {
                                 const returnUrl = document.getElementById('return_url').value;
                                 window.location.href = returnUrl;
                             }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (error.status === 422) {
                        // Validation Errors
                        const errors = error.data.errors;
                        let errorMsg = '<div class="text-start"><ul>';
                        for (const field in errors) {
                            errorMsg += `<li>${errors[field][0]}</li>`;
                            const input = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                // Check if feedback div already exists (it shouldn't because we removed them, but for safety)
                                let feedbackDiv = input.parentElement.querySelector('.invalid-feedback');
                                if (!feedbackDiv) {
                                     feedbackDiv = document.createElement('div');
                                     feedbackDiv.className = 'invalid-feedback';
                                     input.parentElement.appendChild(feedbackDiv);
                                }
                                feedbackDiv.innerText = errors[field][0];
                            }
                        }
                        errorMsg += '</ul></div>';

                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("Validation Error") }}',
                            html: errorMsg
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.data?.message || '{{ __("An error occurred while saving.") }}'
                        });
                    }
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });
        }

        // --- Business Type Logic ---
        const businessTypeSelect = document.getElementById('business_type_select');
        const businessTypeThInput = document.getElementById('businessType');
        const businessTypeEnInput = document.getElementById('businessTypeEn');
        const businessTypeList = document.getElementById('businessTypeList');

        function fetchBusinessTypes() {
            fetch('{{ route('admin.business-types.index') }}')
                .then(response => response.json())
                .then(data => {
                    // Populate Select
                    businessTypeSelect.innerHTML = '<option value="">{{ __('Select...') }}</option>';
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = `${item.name_th} / ${item.name_en}`;
                        option.dataset.th = item.name_th;
                        option.dataset.en = item.name_en;
                        businessTypeSelect.appendChild(option);
                    });

                    // Populate List in Modal (if exists)
                    if (businessTypeList) {
                        businessTypeList.innerHTML = '';
                        data.forEach(item => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item d-flex justify-content-between align-items-center';
                            li.innerHTML = `
                                <span>${item.name_th} / ${item.name_en}</span>
                                <button class="btn btn-sm btn-outline-danger delete-business-type" data-id="${item.id}"><i class="bi bi-trash"></i></button>
                            `;
                            businessTypeList.appendChild(li);
                        });
                    }
                });
        }

        // Initial Fetch
        fetchBusinessTypes();

        // Handle Select Change
        if (businessTypeSelect) {
            businessTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    businessTypeThInput.value = selectedOption.dataset.th;
                    businessTypeEnInput.value = selectedOption.dataset.en;
                }
            });
        }

        // Handle Add New Type
        const addBusinessTypeForm = document.getElementById('addBusinessTypeForm');
        if (addBusinessTypeForm) {
            addBusinessTypeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = {
                    name_th: formData.get('name_th'),
                    name_en: formData.get('name_en')
                };

                fetch('{{ route('admin.business-types.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        fetchBusinessTypes();
                        this.reset();
                        showToast('Business Type added', 'success');
                    }
                });
            });
        }

        // Handle Delete Type
        if (businessTypeList) {
            businessTypeList.addEventListener('click', function(e) {
                if (e.target.closest('.delete-business-type')) {
                    const btn = e.target.closest('.delete-business-type');
                    const id = btn.dataset.id;
                    if (confirm('Delete this type?')) {
                        fetch(`{{ url('admin/business-types') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                fetchBusinessTypes();
                            }
                        });
                    }
                }
            });
        }

        // --- End Business Type Logic ---
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

        const bulkSendProductionBtn = document.getElementById('employer-bulk-send-production-btn');
        if (bulkSendProductionBtn) {
            bulkSendProductionBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Get selected employees
                const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
                const selected = Array.from(checkboxes).map(cb => cb.value);

                // Prepare URL params
                const employerId = '{{ $employer->id }}';
                let url = '{{ route("production.create") }}?employer_id=' + employerId;

                if (selected.length > 0) {
                     const idsJson = encodeURIComponent(JSON.stringify(selected));
                     url += '&employee_ids_json=' + idsJson;
                }

                window.location.href = url;
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

    window.setDocumentAddress = function(addressId) {
        // Show loading state if desired (optional)
        const radio = document.querySelector(`input[name="document_address_radio"][value="${addressId}"]`);

        fetch(`/addresses/${addressId}/set-document`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh page to update badges? Or just toast.
                // Since we added badges via PHP, they won't update without reload.
                // However, the radio button state is correct.
                // We can reload to show the badge correctly if we want.
                // For now, a toast + reload is safest to ensure visual consistency.
                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error', 'Failed to update document address', 'error');
                // Revert radio?
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred', 'error');
        });
    }
</script>
@endpush
