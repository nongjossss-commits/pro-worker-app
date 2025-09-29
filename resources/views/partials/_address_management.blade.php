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
                    <input type="hidden" id="addressableId" name="addressable_id">
                    <input type="hidden" id="addressableType" name="addressable_type">
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

    // --- 1. Get all elements we need to control ---
    const elements = {
        province: document.getElementById('addrProvince'),
        district: document.getElementById('addrDistrict'),
        subDistrict: document.getElementById('addrSubDistrict'),
        zipCode: document.getElementById('addrZipCode'),
        provinceEn: document.getElementById('addrProvinceEn'),
        districtEn: document.getElementById('addrDistrictEn'),
        subDistrictEn: document.getElementById('addrSubDistrictEn'),
    };

    let addressData = [];

    // --- 2. Function to fetch data (This part is working perfectly) ---
    async function fetchAddressData() {
        if (addressData.length > 0) return;
        try {
            const dataUrl = `{{ asset('storage/data/thai-address-data.json') }}?v=${Date.now()}`;
            const response = await fetch(dataUrl);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            addressData = await response.json();
        } catch (error) {
            console.error("Fatal Error: Could not fetch address data.", error);
        }
    }

    // --- 3. Function to populate dropdowns ---
    function populateDropdown(selectEl, data, placeholder, nameKey) {
        selectEl.innerHTML = `<option selected disabled value="">--- ${placeholder} ---</option>`;
        data.forEach(item => {
            selectEl.add(new Option(item[nameKey], item[nameKey]));
        });
    }

    // --- 4. The CORE LOGIC, based on your successful analysis ---

    // WHEN a Thai Province is selected...
    elements.province.addEventListener('change', function () {
        // Reset all dropdowns that come after it
        elements.district.innerHTML = `<option selected disabled>--- เลือกอำเภอ/เขต ---</option>`;
        elements.subDistrict.innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        elements.district.disabled = false;
        elements.subDistrict.disabled = true;
        elements.zipCode.value = '';

        // Find the selected province's data from the master list
        const selectedProvinceData = addressData.find(p => p.name_th === this.value);
        console.log(addressData);
        // If found...
        if (selectedProvinceData && selectedProvinceData.districts) {
            // Use its data to populate the next dropdown
            populateDropdown(elements.district, selectedProvinceData.districts, 'เลือกอำเภอ/เขต', 'name_th');
            // Sync the English province value
            elements.provinceEn.value = selectedProvinceData.name_en;
            // **Unlock the district dropdown**
            elements.district.disabled = false;
        }
    });

    // WHEN a Thai District is selected...
    elements.district.addEventListener('change', function () {
        // Reset the sub-district dropdown
        elements.subDistrict.innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        elements.subDistrict.disabled = true;
        elements.zipCode.value = '';

        const selectedProvinceData = addressData.find(p => p.name_th === elements.province.value);
        if (!selectedProvinceData) return;

        const selectedDistrictData = selectedProvinceData.districts.find(d => d.name_th === this.value);

        // If found...
        if (selectedDistrictData && selectedDistrictData.sub_districts) {
            // Use its data to populate the next dropdown
            populateDropdown(elements.subDistrict, selectedDistrictData.sub_districts, 'เลือกตำบล/แขวง', 'name_th');
            // Sync the English district value
            elements.districtEn.value = selectedDistrictData.name_en;
            // **Unlock the sub-district dropdown**
            elements.subDistrict.disabled = false;
        }
    });

    // WHEN a Thai Sub-district is selected...
    elements.subDistrict.addEventListener('change', function () {
        const selectedProvinceData = addressData.find(p => p.name_th === elements.province.value);
        if (!selectedProvinceData) return;
        const selectedDistrictData = selectedProvinceData.districts.find(d => d.name_th === elements.district.value);
        if (!selectedDistrictData) return;

        const selectedSubDistrictData = selectedDistrictData.sub_districts.find(s => s.name_th === this.value);

        // If found...
        if (selectedSubDistrictData) {
            // Set the zip code and sync the English sub-district value
            elements.zipCode.value = selectedSubDistrictData.zip_code || '';
            elements.subDistrictEn.value = selectedSubDistrictData.name_en;
        }
    });

    // --- 5. Modal Initialization ---
    addressModalEl.addEventListener('show.bs.modal', async function () {
        await fetchAddressData();

        if (addressData.length > 0) {
            // When modal opens, ONLY populate the first dropdown.
            populateDropdown(elements.province, addressData, 'เลือกจังหวัด', 'name_th');

            // Also populate the English dropdowns so their values can be set later
            populateDropdown(elements.provinceEn, addressData, 'Province', 'name_en');
            const allDistricts = addressData.flatMap(p => p.amphoe || []);
            populateDropdown(elements.districtEn, allDistricts, 'District', 'name_en');
            const allSubDistricts = allDistricts.flatMap(d => d.tambon || []);
            populateDropdown(elements.subDistrictEn, allSubDistricts, 'Sub-district', 'name_en');
        }
        // ... rest of modal logic for add/edit ...
    });

    // --- 6. Save Address Logic (NEW CODE) ---
    const saveAddressButton = document.getElementById('saveAddress');
    const addressForm = document.getElementById('addressForm');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressErrors = document.getElementById('address-errors');

    saveAddressButton.addEventListener('click', async function() {
        // Prepare form data
        const formData = new FormData(addressForm);
        const addressId = document.getElementById('addressId').value;

        // Determine URL and Method
        let url = '{{ route('addresses.store') }}';
        let method = 'POST';

        if (addressId) {
            url = `/addresses/${addressId}`;
            // For Laravel, PUT method is simulated via a hidden field
            formData.append('_method', 'PUT');
        }

        // Disable button to prevent double-click
        saveAddressButton.disabled = true;
        saveAddressButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึก...`;
        addressErrors.style.display = 'none';
        addressErrors.innerHTML = '';

        try {
            const response = await fetch(url, {
                method: 'POST', // Always POST for FormData, Laravel handles PUT/PATCH via _method field
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            });

            const result = await response.json();

            if (!response.ok) {
                // Handle validation errors from Laravel
                if (response.status === 422 && result.errors) {
                    let errorHtml = '<ul>';
                    for (const key in result.errors) {
                        result.errors[key].forEach(error => {
                            errorHtml += `<li>${error}</li>`;
                        });
                    }
                    errorHtml += '</ul>';
                    addressErrors.innerHTML = errorHtml;
                    addressErrors.style.display = 'block';
                } else {
                    throw new Error(result.message || 'An unknown error occurred.');
                }
            } else {
                // Success
                addressModal.hide();
                // We need a function to refresh the address lists on the main page
                if (typeof window.refreshAddressLists === 'function') {
                    window.refreshAddressLists();
                } else {
                    // Fallback if the function doesn't exist yet
                    window.location.reload();
                }
                // Use the new, global toast notification system
                showToast('บันทึกที่อยู่เรียบร้อยแล้ว');
            }

        } catch (error) {
            console.error('Save Address Error:', error);
            addressErrors.innerHTML = `เกิดข้อผิดพลาดในการเชื่อมต่อ: ${error.message}`;
            addressErrors.style.display = 'block';
        } finally {
            // Re-enable button
            saveAddressButton.disabled = false;
            saveAddressButton.innerHTML = 'บันทึก';
        }
    });
});



</script>
@endpush