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
        <div id="addressListsContainer">
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
        </div>

        <input type="hidden" name="registered_addresses" id="registered_addresses_json">
        <input type="hidden" name="workplace_addresses" id="workplace_addresses_json">

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">บันทึก</button>
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>

@include('partials._address_management')
@endsection

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
            listContainer.innerHTML = '<p class="text-muted fst-italic">ยังไม่มีที่อยู่</p>';
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
                    <button type="button" class="btn btn-outline-secondary temp-edit-address-btn" data-type="${type}" data-index="${index}" title="แก้ไข"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger temp-delete-address-btn" data-type="${type}" data-index="${index}" title="ลบ"><i class="bi bi-trash"></i></button>
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

        // Basic validation check
        if (!addressData.addrNo || !addressData.addrProvince || !addressData.addrDistrict || !addressData.addrSubDistrict) {
             showToast('กรุณากรอกข้อมูลที่อยู่ให้ครบถ้วน', 'danger');
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

            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบที่อยู่นี้?')) {
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
            const addressType = this.getAttribute('data-address-type');
            document.getElementById('addressType').value = addressType;
        });
    });
});
</script>
@endpush
