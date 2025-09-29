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
                            <input type="text" class="form-control" id="addrProvinceEn" name="addrProvinceEn" disabled>
                            <!-- <select class="form-select" id="addrProvinceEn" name="addrProvinceEn" disabled>
                                <option selected disabled>--- Province ---</option>
                            </select> -->
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
                            <input type="text" class="form-control" id="addrDistrictEn" name="addrDistrictEn" disabled>
                            <!-- <select class="form-select" id="addrDistrictEn" name="addrDistrictEn" disabled>
                                <option selected disabled>--- District ---</option>
                            </select> -->
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
                            <input type="text" class="form-control" id="addrSubDistrictEn" name="addrSubDistrictEn" disabled>
                            <!-- <select class="form-select" id="addrSubDistrictEn" name="addrSubDistrictEn" disabled>
                                <option selected disabled>--- Sub-district ---</option>
                            </select> -->
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

    const employerId = '{{ $employer->id ?? null }}';

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
    };

    let addressData = [];
    let isDataFetched = false;

    // --- Data Fetching ---
    async function fetchAddressData() {
        if (isDataFetched) return;
        try {
            const dataUrl = `{{ asset('storage/data/thai-address-data.json') }}?v=${Date.now()}`;
            const response = await fetch(dataUrl);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            addressData = await response.json();
            isDataFetched = true;
        } catch (error) {
            console.error("Fatal Error: Could not fetch address data.", error);
        }
    }

    // --- UI Helpers ---
    function populateDropdown(selectEl, data, placeholder, valueKey, textKey) {
        selectEl.innerHTML = `<option selected disabled value="">--- ${placeholder} ---</option>`;
        data.forEach(item => {
            selectEl.add(new Option(item[textKey], item[valueKey]));
        });
    }

    function resetCascadingDropdowns() {
        elements.district.innerHTML = `<option selected disabled value="">--- เลือกอำเภอ/เขต ---</option>`;
        elements.subDistrict.innerHTML = `<option selected disabled value="">--- เลือกตำบล/แขวง ---</option>`;
        elements.district.disabled = true;
        elements.subDistrict.disabled = true;
        elements.zipCode.value = '';
        if (elements.provinceEn.options.length > 0) elements.provinceEn.selectedIndex = 0;
        if (elements.districtEn.options.length > 0) elements.districtEn.selectedIndex = 0;
        if (elements.subDistrictEn.options.length > 0) elements.subDistrictEn.selectedIndex = 0;
    }

    // --- Event Listeners for Dropdowns ---
    elements.province.addEventListener('change', function () {
        resetCascadingDropdowns();
        const selectedProvinceData = addressData.find(p => p.name_th === this.value);
        if (selectedProvinceData && selectedProvinceData.amphoe) {
            populateDropdown(elements.district, selectedProvinceData.amphoe, 'เลือกอำเภอ/เขต', 'name_th', 'name_th');
            elements.provinceEn.value = selectedProvinceData.name_en;
            elements.district.disabled = false;
        }
    });

    elements.district.addEventListener('change', function () {
        const selectedProvinceData = addressData.find(p => p.name_th === elements.province.value);
        if (!selectedProvinceData) return;
        const selectedDistrictData = selectedProvinceData.amphoe.find(d => d.name_th === this.value);
        elements.subDistrict.innerHTML = `<option selected disabled value="">--- เลือกตำบล/แขวง ---</option>`;
        elements.subDistrict.disabled = true;
        elements.zipCode.value = '';
        if (selectedDistrictData && selectedDistrictData.tambon) {
            populateDropdown(elements.subDistrict, selectedDistrictData.tambon, 'เลือกตำบล/แขวง', 'name_th', 'name_th');
            elements.districtEn.value = selectedDistrictData.name_en;
            elements.subDistrict.disabled = false;
        }
    });

    elements.subDistrict.addEventListener('change', function () {
        const selectedProvinceData = addressData.find(p => p.name_th === elements.province.value);
        if (!selectedProvinceData) return;
        const selectedDistrictData = selectedProvinceData.amphoe.find(d => d.name_th === elements.district.value);
        if (!selectedDistrictData) return;
        const selectedSubDistrictData = selectedDistrictData.tambon.find(s => s.name_th === this.value);
        if (selectedSubDistrictData) {
            elements.zipCode.value = selectedSubDistrictData.zip_code || '';
            elements.subDistrictEn.value = selectedSubDistrictData.name_en;
        }
    });

    // --- Event Listener for Modal Opening ---
    addressModalEl.addEventListener('show.bs.modal', async function () {
        await fetchAddressData();
        if (addressData.length > 0) {
            populateDropdown(elements.province, addressData, 'เลือกจังหวัด', 'name_th', 'name_th');
            populateDropdown(elements.provinceEn, addressData, 'Province', 'name_en', 'name_en');
            const allDistricts = addressData.flatMap(p => p.amphoe || []);
            populateDropdown(elements.districtEn, allDistricts, 'District', 'name_en', 'name_en');
            const allSubDistricts = allDistricts.flatMap(d => d.tambon || []);
            populateDropdown(elements.subDistrictEn, allSubDistricts, 'Sub-district', 'name_en', 'name_en');
        }
        resetCascadingDropdowns();
        elements.form.reset();
    });

    // --- START: NEW CODE FOR SAVING ADDRESS ---
    elements.saveBtn.addEventListener('click', async function() {
        const formData = new FormData(elements.form);

        if(employerId) {
             formData.append('employer_id', employerId);
        }

        const addressId = elements.idInput.value;
        let url = addressId ? `/addresses/${addressId}` : "{{ route('addresses.store') }}";

        // For handling PUT method in Laravel with FormData
        if(addressId){
            formData.append('_method', 'PUT');
        }

        try {
            const response = await fetch(url, {
                method: 'POST', // HTML forms with FormData only support GET/POST
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                // Handle validation errors from Laravel
                let errorHtml = '<ul>';
                for (const key in result.errors) {
                    errorHtml += `<li>${result.errors[key][0]}</li>`;
                }
                errorHtml += '</ul>';
                elements.errors.innerHTML = errorHtml;
                elements.errors.style.display = 'block';
            } else {
                elements.modal.hide();
                alert(result.message); // Show success message
                location.reload(); // Reload the page to show the new address
            }
        } catch (error) {
            console.error('Save Error:', error);
            elements.errors.innerHTML = 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์';
            elements.errors.style.display = 'block';
        }
    });
    // --- END: NEW CODE FOR SAVING ADDRESS ---
});
</script>
@endpush