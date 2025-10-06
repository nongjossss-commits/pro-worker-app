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
    // --- Dynamic Dropdown Logic (from user instructions) ---
    const addressTypes = ['registered', 'workplace'];
    let thaiAddressData = {};
    let isDataFetched = false;

    async function fetchDropdownData() {
        if (isDataFetched) return;
        const container = document.getElementById('addressListsContainer');
        if (!container) return; // Exit if the main container isn't on the page

        const dataUrl = container.dataset.url;
        if (!dataUrl) {
            console.error('Address data URL is missing.');
            return;
        }

        try {
            const response = await fetch(dataUrl);
            if (!response.ok) throw new Error('Network response was not ok');
            thaiAddressData = await response.json();
            isDataFetched = true;
            // Initialize dropdowns for any visible forms on page load
            addressTypes.forEach(type => initializeAddressType(type));
        } catch (error) {
            console.error('Failed to fetch address data:', error);
        }
    }

    function initializeAddressType(type) {
        const provinceSelect = document.getElementById(`${type}AddrProvince`);
        const districtSelect = document.getElementById(`${type}AddrDistrict`);
        const subDistrictSelect = document.getElementById(`${type}AddrSubDistrict`);
        const zipCodeInput = document.getElementById(`${type}AddrZipCode`);

        if (!provinceSelect) return; // If the form for this type isn't on the page, do nothing.

        populateProvinces(provinceSelect);

        provinceSelect.addEventListener('change', () => {
            populateDistricts(districtSelect, subDistrictSelect, zipCodeInput, provinceSelect.value);
        });

        districtSelect.addEventListener('change', () => {
            populateSubDistricts(subDistrictSelect, zipCodeInput, provinceSelect.value, districtSelect.value);
        });
    }

    function populateProvinces(selectElement) {
        if (selectElement.options.length > 1) return; // Avoid re-populating
        selectElement.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
        for (const province in thaiAddressData) {
            selectElement.add(new Option(province, province));
        }
    }

    function populateDistricts(districtSelect, subDistrictSelect, zipCodeInput, provinceName) {
        districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>';
        subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
        zipCodeInput.value = '';
        subDistrictSelect.disabled = true;

        if (provinceName && thaiAddressData[provinceName]) {
            districtSelect.disabled = false;
            for (const district in thaiAddressData[provinceName].districts) {
                districtSelect.add(new Option(district, district));
            }
        } else {
            districtSelect.disabled = true;
        }
    }

    function populateSubDistricts(subDistrictSelect, zipCodeInput, provinceName, districtName) {
        subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
        zipCodeInput.value = '';

        if (provinceName && districtName && thaiAddressData[provinceName]?.districts[districtName]) {
            subDistrictSelect.disabled = false;
            const subDistricts = thaiAddressData[provinceName].districts[districtName].sub_districts;
            for (const subDistrict in subDistricts) {
                subDistrictSelect.add(new Option(subDistrict, subDistrict));
            }
            const firstSubDistrict = Object.keys(subDistricts)[0];
            if (firstSubDistrict) {
                zipCodeInput.value = subDistricts[firstSubDistrict].zip_code;
            }
        } else {
            subDistrictSelect.disabled = true;
        }
    }

    fetchDropdownData();

    // --- Preserved Save/Modal Logic ---
    const addressModalEl = document.getElementById('addressModal');
    if (addressModalEl) {
        const addressForm = document.getElementById('addressForm');
        const addressModal = new bootstrap.Modal(addressModalEl);
        const addressErrors = document.getElementById('address-errors');
        const saveAddressButton = document.getElementById('saveAddress');

        saveAddressButton.addEventListener('click', async function() {
            const formData = new FormData(addressForm);
            const addressId = document.getElementById('addressId').value;
            let url = '{{ route('addresses.store') }}';
            if (addressId) {
                url = `/addresses/${addressId}`;
                formData.append('_method', 'PUT');
            }

            saveAddressButton.disabled = true;
            saveAddressButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึก...`;
            addressErrors.style.display = 'none';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });
                const result = await response.json();
                if (!response.ok) {
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
                    addressModal.hide();
                    if (typeof window.refreshAddressLists === 'function') {
                        window.refreshAddressLists();
                    } else {
                        window.location.reload();
                    }
                    showToast('บันทึกที่อยู่เรียบร้อยแล้ว');
                }
            } catch (error) {
                console.error('Save Address Error:', error);
                addressErrors.innerHTML = `เกิดข้อผิดพลาดในการเชื่อมต่อ: ${error.message}`;
                addressErrors.style.display = 'block';
            } finally {
                saveAddressButton.disabled = false;
                saveAddressButton.innerHTML = 'บันทึก';
            }
        });
    }
});
</script>
@endpush