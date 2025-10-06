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
        // Configuration for the address modal
        const addressModal = document.getElementById('addAddressModal');
        if (!addressModal) return;

        const provinceSelect = document.getElementById('addrProvince');
        const districtSelect = document.getElementById('addrDistrict');
        const subDistrictSelect = document.getElementById('addrSubDistrict');
        const zipCodeInput = document.getElementById('addrZipCode');
        const form = addressModal.querySelector('form');

        let thaiAddressData = {};

        // Fetch data only once when the modal is first shown
        addressModal.addEventListener('show.bs.modal', function() {
            if (Object.keys(thaiAddressData).length === 0) {
                fetchAddressData();
            }
        }, { once: true });


        async function fetchAddressData() {
            const dataUrl = form.dataset.url; // We'll add this data-url to the form
            if (!dataUrl) {
                 console.error('Data URL is not set on the form.');
                 return;
            }
            try {
                const response = await fetch(dataUrl);
                if (!response.ok) throw new Error('Network response was not ok');
                thaiAddressData = await response.json();
                populateProvinces(provinceSelect);
            } catch (error) {
                console.error('Failed to fetch address data:', error);
            }
        }

        provinceSelect.addEventListener('change', () => {
            populateDistricts(districtSelect, subDistrictSelect, zipCodeInput, provinceSelect.value);
        });

        districtSelect.addEventListener('change', () => {
            populateSubDistricts(subDistrictSelect, zipCodeInput, provinceSelect.value, districtSelect.value);
        });

        subDistrictSelect.addEventListener('change', () => {
            autofillZipCode(zipCodeInput, provinceSelect.value, districtSelect.value, subDistrictSelect.value);
        });

        function populateProvinces(selectElement) {
            selectElement.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
            Object.keys(thaiAddressData).sort().forEach(province => {
                const option = new Option(province, province);
                selectElement.add(option);
            });
        }

        function populateDistricts(districtSelect, subDistrictSelect, zipCodeInput, provinceName) {
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>';
            subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
            zipCodeInput.value = '';
            if (provinceName && thaiAddressData[provinceName]) {
                Object.keys(thaiAddressData[provinceName].districts).sort().forEach(district => {
                    const option = new Option(district, district);
                    districtSelect.add(option);
                });
            }
        }

        function populateSubDistricts(subDistrictSelect, zipCodeInput, provinceName, districtName) {
            subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
            zipCodeInput.value = '';
            if (provinceName && districtName && thaiAddressData[provinceName]?.districts[districtName]) {
                const subDistricts = thaiAddressData[provinceName].districts[districtName].sub_districts;
                Object.keys(subDistricts).sort().forEach(subDistrict => {
                    const option = new Option(subDistrict, subDistrict);
                    subDistrictSelect.add(option);
                });
            }
        }

        function autofillZipCode(zipCodeInput, provinceName, districtName, subDistrictName) {
            zipCodeInput.value = '';
            if (provinceName && districtName && subDistrictName && thaiAddressData[provinceName]?.districts[districtName]?.sub_districts[subDistrictName]) {
                zipCodeInput.value = thaiAddressData[provinceName].districts[districtName].sub_districts[subDistrictName].zip_code;
            }
        }
    });
</script>
@endpush