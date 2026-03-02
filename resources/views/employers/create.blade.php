@extends('layouts.app')

@section('title', __('Add Employer'))

@section('content')
<div class="content-section">
    <h2 class="mb-4">{{ __('Add Employer') }}</h2>
    <form id="saveEmployerForm" action="{{ route('employers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">{{ __('Employer Name (Thai)') }}</label>
                <input type="text" class="form-control @error('employerNameTh') is-invalid @enderror" id="employerNameTh" name="employerNameTh" value="{{ old('employerNameTh') }}" required>
                @error('employerNameTh')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">{{ __('Employer Name (English)') }}</label>
                <input type="text" class="form-control @error('employerNameEn') is-invalid @enderror" id="employerNameEn" name="employerNameEn" value="{{ old('employerNameEn') }}">
                @error('employerNameEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="job_owner_id" class="form-label">{{ __('Job Owner') }}</label>
                <div class="input-group">
                    <select class="form-select" id="job_owner_id" name="job_owner_id">
                        <option selected disabled>{{ __('Select Job Owner') }}</option>
                        @foreach($jobOwners as $owner)
                            <option value="{{ $owner->id }}">{{ $owner->name }}</option>
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
                    selected: {{ json_encode(old('assigned_staff_ids', [])) }},
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
                <label for="employerId" class="form-label">{{ __('Employer ID') }}</label>
                <input type="text" class="form-control @error('employerId') is-invalid @enderror" id="employerId" name="employerId" value="" placeholder="สร้างอัตโนมัติเมื่อบันทึก" readonly>
                @error('employerId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">{{ __('Employer Tax ID') }}</label>
                <input type="text" class="form-control @error('employerTaxId') is-invalid @enderror" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId') }}">
                @error('employerTaxId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('Select Business Type') }}</label>
                <div class="input-group">
                    <select class="form-select" id="business_type_select">
                        <option value="">{{ __('Select...') }}</option>
                    </select>
                    @if(auth()->user()->hasAnyRole(['admin', 'super-admin']))
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
                <input type="text" class="form-control @error('businessType') is-invalid @enderror" id="businessType" name="businessType" value="{{ old('businessType') }}">
                @error('businessType')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
             <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">{{ __('Business Type (English)') }}</label>
                <input type="text" class="form-control @error('businessTypeEn') is-invalid @enderror" id="businessTypeEn" name="businessTypeEn" value="{{ old('businessTypeEn') }}">
                @error('businessTypeEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
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
 <input type="text" class="form-control @error('employerPassword') is-invalid @enderror" id="employerPassword" name="employerPassword" value="">
 @error('employerPassword')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 </div>
 <div class="row mb-3">
 <div class="col-md-6">
 <label for="outsource_re_code" class="form-label">{{ __('RE Code') }}</label>
 <input type="text" class="form-control @error('outsource_re_code') is-invalid @enderror" id="outsource_re_code" name="outsource_re_code" value="{{ old('outsource_re_code') }}">
 @error('outsource_re_code')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 <div class="col-md-6">
 <label for="outsource_password" class="form-label">{{ __('Outsource Password (รหัสสำหรับเข้าระบบ Outsource)') }}</label>
 <input type="text" class="form-control @error('outsource_password') is-invalid @enderror" id="outsource_password" name="outsource_password" value="{{ old('outsource_password') }}">
 @error('outsource_password')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror
 </div>
 </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">{{ __('Authorized Signatory 1 (Thai)') }}</label>
                <input type="text" class="form-control @error('signerNameTh') is-invalid @enderror" id="signerNameTh" name="signerNameTh" value="{{ old('signerNameTh') }}">
                @error('signerNameTh')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">{{ __('Authorized Signatory 1 (English)') }}</label>
                <input type="text" class="form-control @error('signerNameEn') is-invalid @enderror" id="signerNameEn" name="signerNameEn" value="{{ old('signerNameEn') }}">
                @error('signerNameEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh2" class="form-label">{{ __('Authorized Signatory 2 (Thai)') }}</label>
                <input type="text" class="form-control @error('signerNameTh2') is-invalid @enderror" id="signerNameTh2" name="signerNameTh2" value="{{ old('signerNameTh2') }}">
                @error('signerNameTh2')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="signerNameEn2" class="form-label">{{ __('Authorized Signatory 2 (English)') }}</label>
                <input type="text" class="form-control @error('signerNameEn2') is-invalid @enderror" id="signerNameEn2" name="signerNameEn2" value="{{ old('signerNameEn2') }}">
                @error('signerNameEn2')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regCapital" class="form-label">{{ __('Registered Capital') }}</label>
                <input type="text" class="form-control @error('regCapital') is-invalid @enderror" id="regCapital" name="regCapital" value="{{ old('regCapital') }}">
                @error('regCapital')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regDate" class="form-label">{{ __('Registration Date') }}</label>
                <input type="date" class="form-control @error('regDate') is-invalid @enderror" id="regDate" name="regDate" value="{{ old('regDate') }}">
                @error('regDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="minimum_wage" class="form-label">{{ __('Minimum Wage') }}</label>
                <input type="text" class="form-control @error('minimum_wage') is-invalid @enderror" id="minimum_wage" name="minimum_wage" value="{{ old('minimum_wage') }}">
                @error('minimum_wage')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <hr>
        <h5>{{ __('Employer Attachments') }}</h5>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employer_doc_company" class="form-label">1. {{ __('Company Certificate / ID Card') }} <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('employer_doc_company') is-invalid @enderror" id="employer_doc_company" name="employer_doc_company" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'employer_doc_company' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                @error('employer_doc_company')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="employer_doc_company_expiry" class="form-label">{{ __('Expiry Date') }}</label>
                <input type="date" class="form-control form-control-sm @error('employer_doc_company_expiry') is-invalid @enderror" id="employer_doc_company_expiry" name="employer_doc_company_expiry" value="{{ old('employer_doc_company_expiry') }}">
                @error('employer_doc_company_expiry')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employer_doc_lease" class="form-label">2. {{ __('Lease Agreement / House Registration') }} <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('employer_doc_lease') is-invalid @enderror" id="employer_doc_lease" name="employer_doc_lease" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'employer_doc_lease' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                @error('employer_doc_lease')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="employer_doc_construction" class="form-label">3. {{ __('Construction Contract / Map') }} <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('employer_doc_construction') is-invalid @enderror" id="employer_doc_construction" name="employer_doc_construction" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'employer_doc_construction' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                @error('employer_doc_construction')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="employer_doc_other_1" class="form-label">4. {{ __('Other Document') }} 1 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('employer_doc_other_1') is-invalid @enderror" id="employer_doc_other_1" name="employer_doc_other_1" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'employer_doc_other_1' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <input type="text" class="form-control form-control-sm mt-2 @error('employer_doc_other_1_desc') is-invalid @enderror" id="employer_doc_other_1_desc" name="employer_doc_other_1_desc" value="{{ old('employer_doc_other_1_desc') }}" placeholder="{{ __('Specify description...') }}">
                @error('employer_doc_other_1')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="employer_doc_other_2" class="form-label">5. {{ __('Other Document') }} 2 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('employer_doc_other_2') is-invalid @enderror" id="employer_doc_other_2" name="employer_doc_other_2" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'employer_doc_other_2' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <input type="text" class="form-control form-control-sm mt-2 @error('employer_doc_other_2_desc') is-invalid @enderror" id="employer_doc_other_2_desc" name="employer_doc_other_2_desc" value="{{ old('employer_doc_other_2_desc') }}" placeholder="{{ __('Specify description...') }}">
                @error('employer_doc_other_2')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="employer_doc_other_3" class="form-label">6. {{ __('Other Document') }} 3 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('employer_doc_other_3') is-invalid @enderror" id="employer_doc_other_3" name="employer_doc_other_3" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'employer_doc_other_3' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <input type="text" class="form-control form-control-sm mt-2 @error('employer_doc_other_3_desc') is-invalid @enderror" id="employer_doc_other_3_desc" name="employer_doc_other_3_desc" value="{{ old('employer_doc_other_3_desc') }}" placeholder="{{ __('Specify description...') }}">
                @error('employer_doc_other_3')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <hr>
        <div id="addressListsContainer">
            {{-- Registered Address Section --}}
            <div class="content-section mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ __('Registered Address') }}</h5>
                    <button type="button" class="btn btn-sm btn-primary add-address-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#addressModal"
                            data-type="registered"
                            disabled
                            title="You must save the employer first before adding an address.">
                        {{ __('Add Address') }}
                    </button>
                </div>
                <div id="registeredAddressList" class="vstack gap-3">
                    <p class="text-muted">{{ __('No address yet') }}</p>
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
                            disabled
                            title="You must save the employer first before adding an address.">
                        {{ __('Add Address') }}
                    </button>
                </div>
                <div id="workplaceAddressList" class="vstack gap-3">
                    <p class="text-muted">{{ __('No address yet') }}</p>
                </div>
            </div>
        </div>

        <input type="hidden" name="registered_addresses" id="registered_addresses_json">
        <input type="hidden" name="workplace_addresses" id="workplace_addresses_json">

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>

@include('partials._address_management')

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

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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
    // --- Temporary Address Storage ---
    let tempRegisteredAddresses = [];
    let tempWorkplaceAddresses = [];

    const registeredAddressList = document.getElementById('registeredAddressList');
    const workplaceAddressList = document.getElementById('workplaceAddressList');
    const mainForm = document.getElementById('saveEmployerForm');
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveAddressButton = document.getElementById('saveAddressBtn');
    const originalSaveButtonText = saveAddressButton.innerHTML;

    // This is the create page, so we override the default AJAX save behavior
    const isCreatePage = true;

    // --- START: NEW/UPDATED CODE FOR CREATE PAGE ---
    let currentlyEditing = null; // { type: 'registered', index: 0 }

    function getFullAddressStringFromObject(addr) {
        const th_parts = [
            addr.addrNo,
            addr.addrMoo ? `หมู่ ${addr.addrMoo}` : '',
            addr.addrSoi ? `ซอย${addr.addrSoi}` : '',
            addr.addrRoad ? `ถนน${addr.addrRoad}` : '',
            addr.addrSubDistrict,
            addr.addrDistrict,
            addr.addrProvince,
            addr.addrZipCode
        ].filter(Boolean).join(' ');

        const en_parts = [
            addr.addrNoEn,
            addr.addrMooEn ? `Moo ${addr.addrMooEn}` : '',
            addr.addrSoiEn ? `Soi ${addr.addrSoiEn}` : '',
            addr.addrRoadEn ? `Road ${addr.addrRoadEn}` : '',
            addr.addrSubDistrictEn,
            addr.addrDistrictEn,
            addr.addrProvinceEn,
            addr.addrZipCodeEn
        ].filter(Boolean).join(', ');

        return {
            th: th_parts || 'N/A',
            en: en_parts || 'N/A'
        };
    }


    function renderTempAddressList(type) {
        const listContainer = document.getElementById(type === 'registered' ? 'registeredAddressList' : 'workplaceAddressList');
        const addresses = type === 'registered' ? tempRegisteredAddresses : tempWorkplaceAddresses;

        listContainer.innerHTML = ''; // Clear previous list
        if (addresses.length === 0) {
            listContainer.innerHTML = '<p class="text-muted fst-italic">{{ __('No address yet') }}</p>';
            return;
        }

        addresses.forEach((addr, index) => {
            const fullAddress = getFullAddressStringFromObject(addr); // Function to format address
            const card = document.createElement('div');
            card.className = 'address-card d-flex justify-content-between align-items-start';
            card.innerHTML = `
                <div>
                    <p class="mb-0">${fullAddress.th}</p>
                    <p class="mb-0 text-muted small">${fullAddress.en}</p>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary temp-edit-address-btn" data-type="${type}" data-index="${index}" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger temp-delete-address-btn" data-type="${type}" data-index="${index}" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                </div>
            `;
            listContainer.appendChild(card);
        });
    }

    function openAddressModalForTemp(type, index, addressToEdit) {
        addressForm.reset();
        document.getElementById('addressType').value = type;

        // Populate the form with the address data
        for (const key in addressToEdit) {
            if (addressForm.elements[key]) {
                addressForm.elements[key].value = addressToEdit[key];
            }
        }
        // Specific handling for selects might be needed if they are dynamically populated
        // For now, assuming values are straightforward
        document.getElementById('addrProvince').value = addressToEdit.addrProvince;
        // You might need to re-enable and re-populate district/sub-district dropdowns here if they depend on the province
        document.getElementById('addrDistrict').disabled = false;
        document.getElementById('addrDistrict').value = addressToEdit.addrDistrict;
        document.getElementById('addrSubDistrict').disabled = false;
        document.getElementById('addrSubDistrict').value = addressToEdit.addrSubDistrict;


        currentlyEditing = { type, index };
        addressModal.show();
    }


    function handleSaveAddressTemporarily() {
        const formData = new FormData(addressForm);
        const addressData = Object.fromEntries(formData.entries());
        const addressType = document.getElementById('addressType').value;
        console.log('Saving address temporarily:', addressType);
        // Basic validation check
        if (!addressData.addrNo || !addressData.addrProvince || !addressData.addrDistrict || !addressData.addrSubDistrict) {
             showToast('{{ __('Please fill in complete address information') }}', 'danger');
             return;
        }

        if (currentlyEditing) {
            // Update existing address
            const { type, index } = currentlyEditing;
            if (type === 'registered') {
                tempRegisteredAddresses[index] = addressData;
            } else {
                tempWorkplaceAddresses[index] = addressData;
            }
        } else {
            // Add new address
            if (addressType === 'registered') {
                tempRegisteredAddresses.push(addressData);
            } else if (addressType === 'workplace') {
                tempWorkplaceAddresses.push(addressData);
            }
        }

        renderTempAddressList(currentlyEditing ? currentlyEditing.type : addressType);
        currentlyEditing = null; // Reset editing state
        addressModal.hide();
    }

    // Override the save button's click event ONLY on this page
    if (isCreatePage) {
        const newSaveAddressButton = saveAddressButton.cloneNode(true);
        saveAddressButton.parentNode.replaceChild(newSaveAddressButton, saveAddressButton);
        newSaveAddressButton.addEventListener('click', handleSaveAddressTemporarily);
    }

    // Add ONE event listener to handle all clicks on both address lists
    document.getElementById('addressListsContainer').addEventListener('click', function(e) {
        const editBtn = e.target.closest('.temp-edit-address-btn');
        const deleteBtn = e.target.closest('.temp-delete-address-btn');

        if (editBtn) {
            const type = editBtn.dataset.type;
            const index = parseInt(editBtn.dataset.index, 10);
            const addressToEdit = (type === 'registered' ? tempRegisteredAddresses : tempWorkplaceAddresses)[index];

            openAddressModalForTemp(type, index, addressToEdit);
        }

        if (deleteBtn) {
            const type = deleteBtn.dataset.type;
            const index = parseInt(deleteBtn.dataset.index, 10);

            if (confirm('{{ __('Are you sure you want to delete this address?') }}')) {
                if (type === 'registered') {
                    tempRegisteredAddresses.splice(index, 1);
                } else {
                    tempWorkplaceAddresses.splice(index, 1);
                }
                renderTempAddressList(type);
            }
        }
    });

    // Before submitting the main form, serialize the temp addresses into the hidden fields
    mainForm.addEventListener('submit', function (e) {
        document.getElementById('registered_addresses_json').value = JSON.stringify(tempRegisteredAddresses);
        document.getElementById('workplace_addresses_json').value = JSON.stringify(tempWorkplaceAddresses);
    });

    // Reset form on modal close
    addressModalEl.addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        // Reset dropdowns to their initial disabled state
        document.getElementById('addrDistrict').disabled = true;
        document.getElementById('addrSubDistrict').disabled = true;
    });

     // Set address type when "Add Address" is clicked
    document.querySelectorAll('.add-address-btn').forEach(button => {
        button.addEventListener('click', function() {
            const addressType = this.getAttribute('data-type');
            document.getElementById('address_type').value = addressType;
        });
    });
});
</script>
@endpush
