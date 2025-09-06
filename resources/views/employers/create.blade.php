@extends('layouts.app')

@section('title', 'เพิ่มข้อมูลนายจ้าง')

@section('content')
<div class="content-section">
    <h2 class="mb-4">เพิ่มข้อมูลนายจ้าง</h2>
    <form action="{{ route('employers.store') }}" method="POST" enctype="multipart/form-data">
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

{{-- Add/Edit Address Modal --}}
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">เพิ่มที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Address Management on Create Page---
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const addressModalLabel = document.getElementById('addressModalLabel');

    const registeredAddressesInput = document.getElementById('registered_addresses_json');
    const workplaceAddressesInput = document.getElementById('workplace_addresses_json');

    let registeredAddresses = [];
    let workplaceAddresses = [];

    // Open modal logic
    document.querySelectorAll('.add-address-btn').forEach(button => {
        button.addEventListener('click', function () {
            resetAddressForm();
            const addressType = this.dataset.addressType;
            addressForm.querySelector('#addressType').value = addressType;
            addressModalLabel.textContent = 'เพิ่มที่อยู่';
        });
    });

    // Save Address (Client-side)
    document.getElementById('saveAddress').addEventListener('click', function() {
        const addressType = addressForm.querySelector('#addressType').value;

        // Temporarily enable selects to include them in FormData
        const selects = addressForm.querySelectorAll('select:disabled');
        selects.forEach(s => s.disabled = false);

        const formData = new FormData(addressForm);
        const address = Object.fromEntries(formData.entries());

        // Re-disable them
        selects.forEach(s => s.disabled = true);

        // Store address
        if (addressType === 'registered') {
            registeredAddresses.push(address);
            registeredAddressesInput.value = JSON.stringify(registeredAddresses);
        } else {
            workplaceAddresses.push(address);
            workplaceAddressesInput.value = JSON.stringify(workplaceAddresses);
        }

        renderAddressLists();
        addressModal.hide();
    });

    function renderAddressLists() {
        renderAddressList(registeredAddresses, 'registeredAddressList', 'registered');
        renderAddressList(workplaceAddresses, 'workplaceAddressList', 'workplace');
    }

    function renderAddressList(addresses, listId, type) {
        const listElement = document.getElementById(listId);
        listElement.innerHTML = ''; // Clear current list

        if (addresses.length === 0) {
            listElement.innerHTML = '<p class="text-muted">ยังไม่มีที่อยู่</p>';
            return;
        }

        addresses.forEach((address, index) => {
            const addressCardHtml = `
                <div class="address-card d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-0">
                            เลขที่ ${address.addrNo || ''} หมู่ ${address.addrMoo || ''} ซอย${address.addrSoi || ''} ถนน${address.addrRoad || ''}
                            แขวง/ตำบล ${address.addrSubDistrict || ''} เขต/อำเภอ ${address.addrDistrict || ''}
                            ${address.addrProvince || ''} ${address.addrZipCode || ''}
                        </p>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-danger remove-address-btn" data-index="${index}" data-type="${type}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>`;
            listElement.insertAdjacentHTML('beforeend', addressCardHtml);
        });
    }

    // Remove address from temporary list
    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-address-btn')) {
            const button = e.target.closest('.remove-address-btn');
            const index = parseInt(button.dataset.index, 10);
            const type = button.dataset.type;

            if (type === 'registered') {
                registeredAddresses.splice(index, 1);
                registeredAddressesInput.value = JSON.stringify(registeredAddresses);
            } else {
                workplaceAddresses.splice(index, 1);
                workplaceAddressesInput.value = JSON.stringify(workplaceAddresses);
            }
            renderAddressLists();
        }
    });

    function resetAddressForm() {
        addressForm.reset();
        // Reset dropdowns to their initial disabled state
        document.getElementById('addrDistrict').disabled = true;
        document.getElementById('addrSubDistrict').disabled = true;
        document.getElementById('addrProvinceEn').disabled = true;
        document.getElementById('addrDistrictEn').disabled = true;
        document.getElementById('addrSubDistrictEn').disabled = true;
    }

    // --- Thai Address Dropdown Logic (Copied from edit.blade.php) ---
    const provinceSelect = document.getElementById('addrProvince');
    const districtSelect = document.getElementById('addrDistrict');
    const subDistrictSelect = document.getElementById('addrSubDistrict');
    const zipCodeInput = document.getElementById('addrZipCode');
    const provinceEnSelect = document.getElementById('addrProvinceEn');
    const districtEnSelect = document.getElementById('addrDistrictEn');
    const subDistrictEnSelect = document.getElementById('addrSubDistrictEn');
    const zipCodeEnInput = document.getElementById('addrZipCodeEn');

    let addressData = [];
    fetch('https://raw.githubusercontent.com/kongvut/thai-province-data/master/api_province_with_amphure_tambon.json')
        .then(response => response.json())
        .then(data => {
            addressData = data;
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

    function populateEnglishSelect(selectElement, enName, placeholder) {
        selectElement.innerHTML = '';
        if (enName) {
            const enOption = new Option(enName, enName);
            selectElement.add(enOption);
            selectElement.value = enName;
        } else {
            selectElement.innerHTML = `<option selected disabled>--- ${placeholder} ---</option>`;
        }
    }

    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';
        districtSelect.disabled = true;
        subDistrictSelect.disabled = true;

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
        if(zipCodeEnInput) zipCodeEnInput.value = zipCode;

        const subDistrictEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(subDistrictEnSelect, subDistrictEnName, 'Sub-district');
    });
});
</script>
@endpush
