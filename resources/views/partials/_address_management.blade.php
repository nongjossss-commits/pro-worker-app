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
    //  --- START: Variable Definitions ---
    const isEditPage = @json(isset($employer));
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return;

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const addressModalLabel = document.getElementById('addressModalLabel');
    const addressIdInput = document.getElementById('addressId');
    const addressTypeInput = document.getElementById('addressType');
    const addressErrors = document.getElementById('address-errors');
    const saveAddressBtn = document.getElementById('saveAddress');

    const provinceSelect = document.getElementById('addrProvince');
    const districtSelect = document.getElementById('addrDistrict');
    const subDistrictSelect = document.getElementById('addrSubDistrict');
    const zipCodeInput = document.getElementById('addrZipCode');

    const provinceEnSelect = document.getElementById('addrProvinceEn');
    const districtEnSelect = document.getElementById('addrDistrictEn');
    const subDistrictEnSelect = document.getElementById('addrSubDistrictEn');
    const zipCodeEnInput = document.getElementById('addrZipCodeEn');

    let thaiAddressData = [];
    let isDataFetched = false;
    // --- END: Variable Definitions ---

    // --- START: Core Functions ---
    async function fetchThaiAddressData() {
        if (isDataFetched) return;
        try {
            const dataUrl = `{{ asset('storage/data/thai-address-data.json') }}?v=${new Date().getTime()}`;
            const response = await fetch(dataUrl);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            thaiAddressData = await response.json();
            isDataFetched = true;
        } catch (error) {
            console.error("Fatal Error: Could not fetch address data.", error);
            elements.errors.innerHTML = 'ไม่สามารถโหลดข้อมูลที่อยู่ได้ กรุณาตรวจสอบไฟล์และลองอีกครั้ง';
            elements.errors.style.display = 'block';
        }
    }

    function populateDropdown(selectEl, data, placeholder, valueField, textField) {
        selectEl.innerHTML = `<option selected disabled value="">--- ${placeholder} ---</option>`;
        data.forEach(item => {
            const option = new Option(item[textField], item[valueField]);
            option.dataset.object = JSON.stringify(item);
            selectEl.add(option);
        });
    }

    function resetForm() {
        addressForm.reset();
        addressErrors.style.display = 'none';
        addressIdInput.value = '';

        ['district', 'subDistrict', 'provinceEn', 'districtEn', 'subDistrictEn'].forEach(key => {
            const el = document.getElementById(`addr${key.charAt(0).toUpperCase() + key.slice(1)}`);
            if (el) {
                el.innerHTML = `<option selected disabled>--- ${key.includes('En') ? key.replace('En', ' (EN)') : `เลือก ${key}`} ---</option>`;
                el.disabled = true;
            }
        });

        provinceSelect.innerHTML = `<option selected disabled>--- เลือกจังหวัด ---</option>`;
    }
    // --- END: Core Functions ---

    // --- START: Event Listeners for Dropdowns ---
    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = `<option selected disabled>--- เลือกอำเภอ/เขต ---</option>`;
        subDistrictSelect.innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        zipCodeInput.value = '';
        subDistrictSelect.disabled = true;

        const selectedProvinceData = thaiAddressData.find(p => p.name_th.trim() === this.value.trim());

        // FIX: Corrected property name from 'amphure' to 'amphoe'
        if (selectedProvinceData && selectedProvinceData.amphoe) {
            populateDropdown(districtSelect, selectedProvinceData.amphoe, 'เลือกอำเภอ/เขต', 'name_th', 'name_th');
            provinceEnSelect.value = selectedProvinceData.name_en;
            districtSelect.disabled = false;
        } else {
            districtSelect.disabled = true;
        }
    });

    districtSelect.addEventListener('change', function () {
        subDistrictSelect.innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        zipCodeInput.value = '';

        const selectedProvinceData = thaiAddressData.find(p => p.name_th.trim() === provinceSelect.value.trim());
        // FIX: Corrected property name from 'amphure' to 'amphoe'
        if (selectedProvinceData && selectedProvinceData.amphoe) {
            const selectedDistrictData = selectedProvinceData.amphoe.find(d => d.name_th.trim() === this.value.trim());

            if (selectedDistrictData && selectedDistrictData.tambon) {
                populateDropdown(subDistrictSelect, selectedDistrictData.tambon, 'เลือกตำบล/แขวง', 'name_th', 'name_th');
                districtEnSelect.value = selectedDistrictData.name_en;
                subDistrictSelect.disabled = false;
            } else {
                 subDistrictSelect.disabled = true;
            }
        }
    });

    subDistrictSelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const subDistrictData = JSON.parse(selectedOption.dataset.object);

        if (subDistrictData) {
            zipCodeInput.value = subDistrictData.zip_code || '';
            zipCodeEnInput.value = subDistrictData.zip_code || '';
            subDistrictEnSelect.value = subDistrictData.name_en;
        }
    });
    // --- END: Event Listeners for Dropdowns ---

    // --- START: Modal Initialization ---
    addressModalEl.addEventListener('show.bs.modal', async function (event) {
        resetForm();
        await fetchThaiAddressData();

        populateDropdown(provinceSelect, thaiAddressData, 'เลือกจังหวัด', 'name_th', 'name_th');
        populateDropdown(provinceEnSelect, thaiAddressData, 'Province', 'name_en', 'name_en');

        const allDistricts = thaiAddressData.flatMap(p => p.amphoe || []);
        populateDropdown(districtEnSelect, allDistricts, 'District', 'name_en', 'name_en');

        const allSubDistricts = allDistricts.flatMap(d => d.tambon || []);
        populateDropdown(subDistrictEnSelect, allSubDistricts, 'Sub-district', 'name_en', 'name_en');

        const button = event.relatedTarget;
        const addressId = button.getAttribute('data-id');
        const addressType = button.getAttribute('data-address-type');
        addressTypeInput.value = addressType;

        if (addressId) { // Edit Mode
            // ... (Edit mode logic remains the same)
        } else { // Add Mode
            addressModalLabel.textContent = 'เพิ่มที่อยู่ใหม่';
        }
    });
    // --- END: Modal Initialization ---
});
</script>
@endpush