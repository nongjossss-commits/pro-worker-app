{{-- Add/Edit Address Modal HTML --}}
<div x-ignore class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">เพิ่ม/แก้ไขที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="address-errors" class="alert alert-danger" style="display: none;"></div>
                <form id="addressForm" data-url="{{ route('addresses.thai_data') }}">
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
        const addressModalEl = document.getElementById('addAddressModal');
        if (!addressModalEl) return;

        // --- Element Declarations ---
        const provinceSelect = document.getElementById('addrProvince');
        const districtSelect = document.getElementById('addrDistrict');
        const subDistrictSelect = document.getElementById('addrSubDistrict');
        const zipCodeInput = document.getElementById('addrZipCode');
        const provinceEnInput = document.getElementById('addrProvinceEn');
        const districtEnInput = document.getElementById('addrDistrictEn');
        const subDistrictEnInput = document.getElementById('addrSubDistrictEn');
        const form = addressModalEl.querySelector('form');
        let thaiAddressData = [];

        // --- Event Listeners ---

        // 1. DEFINITIVE BACKDROP FIX: On modal show, find the backdrop and force it to the back.
        addressModalEl.addEventListener('shown.bs.modal', function () {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.zIndex = '1050'; // Force backdrop z-index
            }
            addressModalEl.style.zIndex = '1070'; // Ensure modal is on top
        });

        // 2. Fetch data only once when the modal is first shown
        addressModalEl.addEventListener('show.bs.modal', function() {
            if (thaiAddressData.length === 0) {
                fetchAddressData();
            }
        }, { once: true });

        // 3. Handle dropdown changes
        provinceSelect.addEventListener('change', handleProvinceChange);
        districtSelect.addEventListener('change', handleDistrictChange);
        subDistrictSelect.addEventListener('change', handleSubDistrictChange);

        // --- Main Functions ---

        async function fetchAddressData() {
            const dataUrl = form.dataset.url;
            try {
                const response = await fetch(dataUrl);
                if (!response.ok) throw new Error('Network response was not ok');
                thaiAddressData = await response.json();
                populateProvinces();
            } catch (error) {
                console.error('Failed to fetch address data:', error);
            }
        }

        function handleProvinceChange() {
            const selectedProvinceData = thaiAddressData.find(p => p.name_th === provinceSelect.value);
            resetDistricts();
            resetSubDistricts();
            if (selectedProvinceData) {
                provinceEnInput.value = selectedProvinceData.name_en;
                populateDistricts(selectedProvinceData.districts);
                districtSelect.disabled = false; // <-- RE-ADDED THIS FIX
            }
        }

        function handleDistrictChange() {
            const selectedProvinceData = thaiAddressData.find(p => p.name_th === provinceSelect.value);
            const selectedDistrictData = selectedProvinceData?.districts.find(d => d.name_th === districtSelect.value);
            resetSubDistricts();
            if (selectedDistrictData) {
                districtEnInput.value = selectedDistrictData.name_en;
                populateSubDistricts(selectedDistrictData.sub_districts);
                subDistrictSelect.disabled = false; // <-- RE-ADDED THIS FIX
            }
        }

        function handleSubDistrictChange() {
            const selectedProvinceData = thaiAddressData.find(p => p.name_th === provinceSelect.value);
            const selectedDistrictData = selectedProvinceData?.districts.find(d => d.name_th === districtSelect.value);
            const selectedSubDistrictData = selectedDistrictData?.sub_districts.find(s => s.name_th === subDistrictSelect.value);
            zipCodeInput.value = '';
            subDistrictEnInput.value = '';
            if (selectedSubDistrictData) {
                subDistrictEnInput.value = selectedSubDistrictData.name_en;
                zipCodeInput.value = selectedSubDistrictData.zip_code;
            }
        }

        // --- Helper Functions (No changes needed here) ---

        function populateProvinces() {
            provinceSelect.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
            thaiAddressData.forEach(province => {
                const option = new Option(province.name_th, province.name_th);
                provinceSelect.add(option);
            });
        }

        function populateDistricts(districts) {
            districts.forEach(district => {
                const option = new Option(district.name_th, district.name_th);
                districtSelect.add(option);
            });
        }

        function populateSubDistricts(subDistricts) {
            subDistricts.forEach(subDistrict => {
                const option = new Option(subDistrict.name_th, subDistrict.name_th);
                subDistrictSelect.add(option);
            });
        }

        function resetDistricts() {
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>';
            districtSelect.disabled = false;
            districtEnInput.value = '';
        }

        function resetSubDistricts() {
            subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
            subDistrictSelect.disabled = true;
            subDistrictEnInput.value = '';
            zipCodeInput.value = '';
        }
    });
</script>
@endpush