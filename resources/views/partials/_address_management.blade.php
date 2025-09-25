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
    console.log('[DEBUG] Address script fully initialized.');

    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) {
        console.error('[DEBUG] FATAL: Modal element #addressModal not found!');
        return;
    }

    const elements = {
        province: document.getElementById('addrProvince'),
        district: document.getElementById('addrDistrict'),
        subDistrict: document.getElementById('addrSubDistrict'),
        provinceEn: document.getElementById('addrProvinceEn'),
        districtEn: document.getElementById('addrDistrictEn'),
        subDistrictEn: document.getElementById('addrSubDistrictEn'),
    };

    let thaiAddressData = [];

    async function fetchThaiAddressData() {
        try {
            const dataUrl = `{{ asset('storage/data/thai-address-data.json') }}?v=${new Date().getTime()}`;
            console.log(`[DEBUG] Step 1: Fetching data from ${dataUrl}`);
            const response = await fetch(dataUrl);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            thaiAddressData = await response.json();
            console.log('[DEBUG] Step 2: ✅ Data fetched successfully. Total provinces:', thaiAddressData.length);
        } catch (error) {
            console.error('[DEBUG] ❌ Step 2 FAILED: Could not fetch address data.', error);
        }
    }

    function populateDropdown(selectEl, data, placeholder) {
        selectEl.innerHTML = `<option selected disabled value="">--- ${placeholder} ---</option>`;
        data.forEach(item => {
            const option = new Option(item.name_th, item.name_th);
            selectEl.add(option);
        });
    }

    elements.province.addEventListener('change', function () {
        console.log('--- [DEBUG] Province Dropdown Changed ---');
        try {
            console.log(`[DEBUG] Step 4: User selected value: "${this.value}" (type: ${typeof this.value})`);

            elements.district.disabled = true;
            elements.subDistrict.disabled = true;
            console.log('[DEBUG] Step 5: Downstream dropdowns disabled.');

            if (!thaiAddressData || thaiAddressData.length === 0) {
                console.error('[DEBUG] ❌ ERROR: thaiAddressData array is empty or not available.');
                return;
            }

            console.log(`[DEBUG] Step 6: Searching for "${this.value.trim()}" in the data array...`);
            const selectedProvinceData = thaiAddressData.find(p => p.name_th.trim() === this.value.trim());

            if (selectedProvinceData) {
                console.log('[DEBUG] Step 7: ✅ Province object found:', selectedProvinceData);

                // Check for 'amphoe' property
                if (selectedProvinceData.amphoe) {
                    console.log('[DEBUG] Step 8: ✅ Found "amphoe" key with', selectedProvinceData.amphoe.length, 'districts.');
                    populateDropdown(elements.district, selectedProvinceData.amphoe, 'เลือกอำเภอ/เขต');
                    elements.district.disabled = false;
                    console.log('[DEBUG] Step 9: ✅ District dropdown populated and enabled.');
                    elements.provinceEn.value = selectedProvinceData.name_en;
                    console.log(`[DEBUG] Step 10: ✅ English Province synced to "${selectedProvinceData.name_en}".`);
                } else {
                    console.error('[DEBUG] ❌ ERROR: Province object found, but it is missing the "amphoe" key.', selectedProvinceData);
                }
            } else {
                console.error(`[DEBUG] ❌ ERROR: .find() method returned UNDEFINED. Could not find a province with name_th = "${this.value.trim()}".`);
            }
        } catch (e) {
            console.error('[DEBUG] ❌ A critical error occurred inside the change event handler:', e);
        }
        console.log('--- [DEBUG] Province Change Handler Finished ---');
    });

    addressModalEl.addEventListener('show.bs.modal', async function () {
        console.log('[DEBUG] Modal is opening...');
        await fetchThaiAddressData();

        if(thaiAddressData.length > 0) {
            populateDropdown(elements.province, thaiAddressData, 'เลือกจังหวัด');
            console.log('[DEBUG] Step 3: ✅ Initial province dropdown populated.');
        } else {
            console.error('[DEBUG] ❌ Step 3 FAILED: Cannot populate province dropdown because data is missing.');
        }
    });
});
</script>
@endpush