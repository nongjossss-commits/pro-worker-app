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
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return;

    // --- Element Cache ---
    const elements = {
        modal: new bootstrap.Modal(addressModalEl),
        form: document.getElementById('addressForm'),
        modalLabel: document.getElementById('addressModalLabel'),
        idInput: document.getElementById('addressId'),
        typeInput: document.getElementById('addressType'),
        errors: document.getElementById('address-errors'),
        saveBtn: document.getElementById('saveAddress'),
        province: document.getElementById('addrProvince'),
        district: document.getElementById('addrDistrict'),
        subDistrict: document.getElementById('addrSubDistrict'),
        zipCode: document.getElementById('addrZipCode'),
        provinceEn: document.getElementById('addrProvinceEn'),
        districtEn: document.getElementById('addrDistrictEn'),
        subDistrictEn: document.getElementById('addrSubDistrictEn'),
        zipCodeEn: document.getElementById('addrZipCodeEn'),
    };

    let thaiAddressData = [];
    let isDataFetched = false;

    // --- Data Fetching ---
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

    // --- UI Helper Functions ---
    function populateDropdown(selectEl, data, placeholder, valueField, textField) {
        selectEl.innerHTML = `<option selected disabled value="">--- ${placeholder} ---</option>`;
        data.forEach(item => {
            const option = new Option(item[textField], item[valueField]);
            // Store the entire object in a data attribute for easy access
            option.dataset.object = JSON.stringify(item);
            selectEl.add(option);
        });
    }

    function resetForm() {
        elements.form.reset();
        elements.errors.style.display = 'none';
        elements.idInput.value = '';

        // Reset and disable cascading dropdowns
        ['district', 'subDistrict', 'provinceEn', 'districtEn', 'subDistrictEn'].forEach(key => {
            elements[key].innerHTML = `<option selected disabled>--- ${elements[key].name.replace('addr', '').replace('En','')} ---</option>`;
            elements[key].disabled = true;
        });

        elements.province.innerHTML = `<option selected disabled>--- เลือกจังหวัด ---</option>`;
    }

    // --- Event Listeners ---
    elements.province.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const provinceData = JSON.parse(selectedOption.dataset.object);

        // Reset downstream selects
        elements.district.innerHTML = `<option selected disabled>--- เลือกอำเภอ/เขต ---</option>`;
        elements.subDistrict.innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        elements.subDistrict.disabled = true;
        elements.zipCode.value = '';

        if (provinceData && provinceData.amphure) {
            populateDropdown(elements.district, provinceData.amphure, 'เลือกอำเภอ/เขต', 'name_th', 'name_th');
            elements.provinceEn.value = provinceData.name_en; // Sync English province
            elements.district.disabled = false; // Enable Thai district
        }
    });

    elements.district.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const districtData = JSON.parse(selectedOption.dataset.object);

        elements.subDistrict.innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        elements.zipCode.value = '';

        if (districtData && districtData.tambon) {
            populateDropdown(elements.subDistrict, districtData.tambon, 'เลือกตำบล/แขวง', 'name_th', 'name_th');
            elements.districtEn.value = districtData.name_en; // Sync English district
            elements.subDistrict.disabled = false; // Enable Thai sub-district
        }
    });

    elements.subDistrict.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const subDistrictData = JSON.parse(selectedOption.dataset.object);

        if (subDistrictData) {
            elements.zipCode.value = subDistrictData.zip_code || '';
            elements.zipCodeEn.value = subDistrictData.zip_code || '';
            elements.subDistrictEn.value = subDistrictData.name_en; // Sync English sub-district
        }
    });

    // --- Modal Initialization ---
    addressModalEl.addEventListener('show.bs.modal', async function (event) {
        resetForm();
        await fetchThaiAddressData();

        populateDropdown(elements.province, thaiAddressData, 'เลือกจังหวัด', 'name_th', 'name_th');
        // Pre-populate all English dropdowns but keep them disabled
        populateDropdown(elements.provinceEn, thaiAddressData, 'Province', 'name_en', 'name_en');
        const allDistricts = thaiAddressData.flatMap(p => p.amphure || []);
        populateDropdown(elements.districtEn, allDistricts, 'District', 'name_en', 'name_en');
        const allSubDistricts = allDistricts.flatMap(d => d.tambon || []);
        populateDropdown(elements.subDistrictEn, allSubDistricts, 'Sub-district', 'name_en', 'name_en');

        const button = event.relatedTarget;
        elements.idInput.value = button.getAttribute('data-id') || '';
        elements.typeInput.value = button.getAttribute('data-address-type') || '';

        if (elements.idInput.value) { // Edit Mode
            elements.modalLabel.textContent = 'แก้ไขที่อยู่';
            // Fetch and populate logic for edit mode can be added here if needed
        } else { // Add Mode
            elements.modalLabel.textContent = 'เพิ่มที่อยู่ใหม่';
        }
    });
});
</script>
@endpush