{{-- Add/Edit Address Modal HTML --}}
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isEditPage = @json(isset($employer));

    // Modal and Form Elements
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return;

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const addressModalLabel = document.getElementById('addressModalLabel');
    const addressIdInput = document.getElementById('addressId');
    const addressTypeInput = document.getElementById('addressType');
    const addressErrors = document.getElementById('address-errors');
    const saveAddressBtn = document.getElementById('saveAddress');

    // Form Field Elements
    const provinceSelect = document.getElementById('addrProvince');
    const districtSelect = document.getElementById('addrDistrict');
    const subDistrictSelect = document.getElementById('addrSubDistrict');
    const zipCodeInput = document.getElementById('addrZipCode');
    const provinceEnSelect = document.getElementById('addrProvinceEn');
    const districtEnSelect = document.getElementById('addrDistrictEn');
    const subDistrictEnSelect = document.getElementById('addrSubDistrictEn');
    const zipCodeEnInput = document.getElementById('addrZipCodeEn');

    let thaiAddressData = [];
    let isAddressDataFetched = false;

    async function fetchThaiAddressData() {
        if (isAddressDataFetched) return;
        try {
            // Use public_path helper to get the correct URL to the file in the public directory
            const dataUrl = "{{ asset('storage/data/thai-address-data.json') }}";
            const response = await fetch(dataUrl);
            if (!response.ok) throw new Error('Network response was not ok. Status: ' + response.status);
            thaiAddressData = await response.json();
            isAddressDataFetched = true;
        } catch (error) {
            console.error("Fatal Error: Could not fetch address data.", error);
            addressErrors.innerHTML = 'ไม่สามารถโหลดข้อมูลที่อยู่ได้ กรุณาตรวจสอบว่าไฟล์ข้อมูลอยู่ในระบบและลองอีกครั้ง';
            addressErrors.style.display = 'block';
        }
    }

    function populateDropdown(selectElement, data, placeholder, valueField, textField, englishField = null) {
        selectElement.innerHTML = `<option selected disabled value="">--- ${placeholder} ---</option>`;
        data.forEach(item => {
            const option = new Option(item[textField], item[valueField]);
            if (englishField && item[englishField]) {
                option.dataset.name_en = item[englishField];
            }
            if (item.zip_code) {
                option.dataset.zip_code = item.zip_code;
            }
            selectElement.add(option);
        });
    }

    function populateEnglishSelect(selectElement, data, placeholder, valueField, textField) {
         selectElement.innerHTML = `<option selected disabled value="">--- ${placeholder} ---</option>`;
        data.forEach(item => {
            const option = new Option(item[textField], item[valueField]);
            selectElement.add(option);
        });
    }

    function resetAddressForm() {
        addressForm.reset();
        addressErrors.style.display = 'none';
        addressIdInput.value = '';

        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        provinceEnSelect.innerHTML = '<option selected disabled>--- Province ---</option>';
        districtEnSelect.innerHTML = '<option selected disabled>--- District ---</option>';
        subDistrictEnSelect.innerHTML = '<option selected disabled>--- Sub-district ---</option>';
        districtSelect.disabled = true;
        subDistrictSelect.disabled = true;
    }

    // --- EVENT LISTENERS for Dropdowns ---

    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';
        subDistrictSelect.disabled = true;

        const selectedProvinceData = thaiAddressData.find(p => p.name_th === this.value);

        if (selectedProvinceData && selectedProvinceData.amphure) {
            populateDropdown(districtSelect, selectedProvinceData.amphure, 'เลือกอำเภอ/เขต', 'name_th', 'name_th', 'name_en');
            populateEnglishSelect(provinceEnSelect, selectedProvinceData.amphure, 'Province', 'name_en', 'name_en');

            // Sync English province dropdown
            provinceEnSelect.value = selectedProvinceData.name_en;
            districtSelect.disabled = false;
        } else {
            districtSelect.disabled = true;
        }
    });

    districtSelect.addEventListener('change', function () {
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';

        const selectedProvinceData = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        if (selectedProvinceData) {
            const selectedDistrictData = selectedProvinceData.amphure.find(d => d.name_th === this.value);

            if (selectedDistrictData && selectedDistrictData.tambon) {
                populateDropdown(subDistrictSelect, selectedDistrictData.tambon, 'เลือกตำบล/แขวง', 'name_th', 'name_th', 'name_en');

                // Sync English district dropdown
                districtEnSelect.value = selectedDistrictData.name_en;
                subDistrictSelect.disabled = false;
            } else {
                 subDistrictSelect.disabled = true;
            }
        }
    });

    subDistrictSelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const zipCode = selectedOption.dataset.zip_code || '';
        zipCodeInput.value = zipCode;
        if(zipCodeEnInput) zipCodeEnInput.value = zipCode;

        // Sync English sub-district dropdown
        subDistrictEnSelect.value = selectedOption.dataset.name_en || '';
    });

    addressModalEl.addEventListener('show.bs.modal', async function (event) {
        resetAddressForm();
        await fetchThaiAddressData();
        populateDropdown(provinceSelect, thaiAddressData, 'เลือกจังหวัด', 'name_th', 'name_th', 'name_en');
        populateEnglishSelect(provinceEnSelect, thaiAddressData, 'Province', 'name_en', 'name_en');

        const button = event.relatedTarget;
        const addressId = button.getAttribute('data-id');
        const addressType = button.getAttribute('data-address-type');
        addressTypeInput.value = addressType;

        if (addressId) { // Edit Mode
            addressModalLabel.textContent = 'แก้ไขที่อยู่';
            addressIdInput.value = addressId;
            try {
                const response = await fetch(`/addresses/${addressId}/edit`);
                if (!response.ok) throw new Error('Could not fetch address details.');
                const data = await response.json();

                for (const key in data) {
                    const el = document.getElementById(key);
                    if (el) el.value = data[key];
                }

                if (data.addrProvince) {
                    provinceSelect.value = data.addrProvince;
                    provinceSelect.dispatchEvent(new Event('change'));
                    await new Promise(r => setTimeout(r, 100)); // Wait for UI to update
                }
                if (data.addrDistrict) {
                    districtSelect.value = data.addrDistrict;
                    districtSelect.dispatchEvent(new Event('change'));
                    await new Promise(r => setTimeout(r, 100)); // Wait
                }
                if (data.addrSubDistrict) {
                    subDistrictSelect.value = data.addrSubDistrict;
                    subDistrictSelect.dispatchEvent(new Event('change'));
                }

            } catch (error) {
                console.error("Error fetching address for editing:", error);
                addressErrors.innerHTML = 'เกิดข้อผิดพลาดในการดึงข้อมูลที่อยู่';
                addressErrors.style.display = 'block';
            }
        } else { // Add Mode
            addressModalLabel.textContent = 'เพิ่มที่อยู่ใหม่';
        }
    });

    // Save Logic and other parts of the script remain the same
    // ... (The rest of your existing script for saving/deleting addresses) ...
});
</script>
@endpush