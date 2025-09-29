@extends('layouts.app')

@section('title', 'เพิ่มข้อมูลนายจ้าง')

@section('content')
<div class="content-section">
    <h2 class="mb-4">เพิ่มข้อมูลนายจ้าง</h2>
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
                <label for="employerNameTh" class="form-label">ชื่อนายจ้าง (ไทย)</label>
                <input type="text" class="form-control @error('employerNameTh') is-invalid @enderror" id="employerNameTh" name="employerNameTh" value="{{ old('employerNameTh') }}" required>
                @error('employerNameTh')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">ชื่อนายจ้าง (อังกฤษ)</label>
                <input type="text" class="form-control @error('employerNameEn') is-invalid @enderror" id="employerNameEn" name="employerNameEn" value="{{ old('employerNameEn') }}">
                @error('employerNameEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="job_owner_id" class="form-label">เจ้าของงาน</label>
                <div class="input-group">
                    <select class="form-select" id="job_owner_id" name="job_owner_id">
                        <option selected disabled>--- เลือกเจ้าของงาน ---</option>
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
                <label for="employerId" class="form-label">รหัสนายจ้าง</label>
                <input type="text" class="form-control @error('employerId') is-invalid @enderror" id="employerId" name="employerId" value="{{ $newEmployerId }}" readonly required>
                @error('employerId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">เลขประจำตัวนายจ้าง</label>
                <input type="text" class="form-control @error('employerTaxId') is-invalid @enderror" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId') }}">
                @error('employerTaxId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="businessType" class="form-label">ประเภทกิจการ</label>
                <input type="text" class="form-control @error('businessType') is-invalid @enderror" id="businessType" name="businessType" value="{{ old('businessType') }}">
                @error('businessType')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">ผู้มีอำนาจลงนาม (ไทย)</label>
                <input type="text" class="form-control @error('signerNameTh') is-invalid @enderror" id="signerNameTh" name="signerNameTh" value="{{ old('signerNameTh') }}">
                @error('signerNameTh')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">ผู้มีอำนาจลงนาม (อังกฤษ)</label>
                <input type="text" class="form-control @error('signerNameEn') is-invalid @enderror" id="signerNameEn" name="signerNameEn" value="{{ old('signerNameEn') }}">
                @error('signerNameEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">Type of Business</label>
                <input type="text" class="form-control @error('businessTypeEn') is-invalid @enderror" id="businessTypeEn" name="businessTypeEn" value="{{ old('businessTypeEn') }}">
                @error('businessTypeEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="regCapital" class="form-label">ทุนจดทะเบียน</label>
                <input type="text" class="form-control @error('regCapital') is-invalid @enderror" id="regCapital" name="regCapital" value="{{ old('regCapital') }}">
                @error('regCapital')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regDate" class="form-label">จดทะเบียนวันที่</label>
                <input type="date" class="form-control @error('regDate') is-invalid @enderror" id="regDate" name="regDate" value="{{ old('regDate') }}">
                @error('regDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="minimum_wage" class="form-label">ค่าแรงขั้นต่ำ</label>
                <input type="text" class="form-control @error('minimum_wage') is-invalid @enderror" id="minimum_wage" name="minimum_wage" value="{{ old('minimum_wage') }}">
                @error('minimum_wage')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <hr>
        <h5>เอกสารแนบ</h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="document_company_registration" class="form-label">หนังสือรับรองบริษัท</label>
                <input type="file" class="form-control @error('document_company_registration') is-invalid @enderror" id="document_company_registration" name="document_company_registration">
                @error('document_company_registration')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="document_vat_registration" class="form-label">ภ.พ.20</label>
                <input type="file" class="form-control @error('document_vat_registration') is-invalid @enderror" id="document_vat_registration" name="document_vat_registration">
                @error('document_vat_registration')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="document_map" class="form-label">แผนที่</label>
                <input type="file" class="form-control @error('document_map') is-invalid @enderror" id="document_map" name="document_map">
                @error('document_map')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <hr>
        {{-- Registered Address Section --}}
        <div class="content-section mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">ที่อยู่ตามทะเบียน</h5>
                <button type="button" class="btn btn-sm btn-outline-success add-address-btn" data-bs-toggle="modal" data-bs-target="#addressModal" data-address-type="registered">
                    <i class="bi bi-plus-lg"></i> เพิ่มที่อยู่
                </button>
            </div>
            <div id="registeredAddressList" class="vstack gap-3">
                <p class="text-muted">ยังไม่มีที่อยู่</p>
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
                <p class="text-muted">ยังไม่มีที่อยู่</p>
            </div>
        </div>

        <input type="hidden" name="registered_addresses" id="registered_addresses_json">
        <input type="hidden" name="workplace_addresses" id="workplace_addresses_json">

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">บันทึก</button>
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection

@include('partials._address_management')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Temporary Address Storage ---
    let tempRegisteredAddresses = [];
    let tempWorkplaceAddresses = [];

    const registeredAddressList = document.getElementById('registeredAddressList');
    const workplaceAddressList = document.getElementById('workplaceAddressList');
    const mainForm = document.getElementById('saveEmployerForm');
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveAddressButton = document.getElementById('saveAddress');
    const originalSaveButtonText = saveAddressButton.innerHTML;

    // This is the create page, so we override the default AJAX save behavior
    const isCreatePage = true; // Simple flag to confirm context

    function renderAddressList(container, addresses, type) {
        if (addresses.length === 0) {
            container.innerHTML = '<p class="text-muted">ยังไม่มีที่อยู่</p>';
            return;
        }

        container.innerHTML = ''; // Clear existing
        addresses.forEach((address, index) => {
            const card = document.createElement('div');
            card.className = 'address-card d-flex justify-content-between align-items-start';
            card.innerHTML = `
                <div>
                    <p class="mb-0">
                        เลขที่ ${address.addrNo ?? ''} หมู่ ${address.addrMoo ?? ''} ซอย${address.addrSoi ?? ''} ถนน${address.addrRoad ?? ''}
                        แขวง/ตำบล ${address.addrSubDistrict ?? ''} เขต/อำเภอ ${address.addrDistrict ?? ''}
                        ${address.addrProvince ?? ''} ${address.addrZipCode ?? ''}
                    </p>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-danger remove-temp-address-btn" data-type="${type}" data-index="${index}"><i class="bi bi-trash"></i></button>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function handleSaveAddressTemporarily() {
        const formData = new FormData(addressForm);
        const addressData = Object.fromEntries(formData.entries());
        const addressType = document.getElementById('addressType').value;

        // Basic validation check
        if (!addressData.addrNo || !addressData.addrProvince || !addressData.addrDistrict || !addressData.addrSubDistrict) {
             alert('กรุณากรอกข้อมูลที่อยู่ให้ครบถ้วน');
             return;
        }

        if (addressType === 'registered') {
            tempRegisteredAddresses.push(addressData);
            renderAddressList(registeredAddressList, tempRegisteredAddresses, 'registered');
        } else if (addressType === 'workplace') {
            tempWorkplaceAddresses.push(addressData);
            renderAddressList(workplaceAddressList, tempWorkplaceAddresses, 'workplace');
        }

        addressModal.hide();
    }

    // Override the save button's click event ONLY on this page
    if (isCreatePage) {
        // Clone and replace the button to remove existing event listeners from the partial
        const newSaveAddressButton = saveAddressButton.cloneNode(true);
        saveAddressButton.parentNode.replaceChild(newSaveAddressButton, saveAddressButton);
        newSaveAddressButton.addEventListener('click', handleSaveAddressTemporarily);
    }

    // Handle removal of a temporary address
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-temp-address-btn');
        if (removeBtn) {
            const type = removeBtn.dataset.type;
            const index = parseInt(removeBtn.dataset.index, 10);

            if (type === 'registered') {
                tempRegisteredAddresses.splice(index, 1);
                renderAddressList(registeredAddressList, tempRegisteredAddresses, 'registered');
            } else if (type === 'workplace') {
                tempWorkplaceAddresses.splice(index, 1);
                renderAddressList(workplaceAddressList, tempWorkplaceAddresses, 'workplace');
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
            const addressType = this.getAttribute('data-address-type');
            document.getElementById('addressType').value = addressType;
        });
    });
});
</script>
@endpush
