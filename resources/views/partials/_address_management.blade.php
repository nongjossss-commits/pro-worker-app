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
$(document).ready(function() {
    let addressData = [];
    let isDataFetched = false;

    async function fetchAddressData() {
        if (isDataFetched) return;
        try {
            const dataUrl = `{{ asset('storage/data/thai-address-data.json') }}?v=${Date.now()}`;
            const response = await $.getJSON(dataUrl);
            addressData = response;
            isDataFetched = true;
        } catch (error) {
            console.error("jQuery AJAX Error: Could not fetch address data.", error);
        }
    }

    function populateDropdown(selector, data, placeholder, valueKey, textKey) {
        const dropdown = $(selector);
        dropdown.empty().append($('<option>', {
            text: `--- ${placeholder} ---`,
            disabled: true,
            selected: true
        }));
        $.each(data, function(index, item) {
            dropdown.append($('<option>', {
                value: item[valueKey],
                text: item[textKey]
            }));
        });
    }

    // --- Main Logic using jQuery ---
    $('#addrProvince').on('change', function() {
        const selectedProvinceName = $(this).val();
        const provinceData = addressData.find(p => p.name_th === selectedProvinceName);

        // Reset and disable downstream
        $('#addrDistrict').prop('disabled', true).html('<option selected disabled>--- เลือกอำเภอ/เขต ---</option>');
        $('#addrSubDistrict').prop('disabled', true).html('<option selected disabled>--- เลือกตำบล/แขวง ---</option>');
        $('#addrZipCode').val('');

        if (provinceData && provinceData.amphoe) {
            populateDropdown('#addrDistrict', provinceData.amphoe, 'เลือกอำเภอ/เขต', 'name_th', 'name_th');
            $('#addrProvinceEn').val(provinceData.name_en);
            $('#addrDistrict').prop('disabled', false); // Enable District
        }
    });

    $('#addrDistrict').on('change', function() {
        const selectedProvinceName = $('#addrProvince').val();
        const provinceData = addressData.find(p => p.name_th === selectedProvinceName);
        if (!provinceData) return;

        const selectedDistrictName = $(this).val();
        const districtData = provinceData.amphoe.find(d => d.name_th === selectedDistrictName);

        // Reset sub-district
        $('#addrSubDistrict').prop('disabled', true).html('<option selected disabled>--- เลือกตำบล/แขวง ---</option>');
        $('#addrZipCode').val('');

        if (districtData && districtData.tambon) {
            populateDropdown('#addrSubDistrict', districtData.tambon, 'เลือกตำบล/แขวง', 'name_th', 'name_th');
            $('#addrDistrictEn').val(districtData.name_en);
            $('#addrSubDistrict').prop('disabled', false); // Enable Sub-district
        }
    });

    $('#addrSubDistrict').on('change', function() {
        const selectedProvinceName = $('#addrProvince').val();
        const provinceData = addressData.find(p => p.name_th === selectedProvinceName);
        if (!provinceData) return;

        const selectedDistrictName = $('#addrDistrict').val();
        const districtData = provinceData.amphoe.find(d => d.name_th === selectedDistrictName);
        if (!districtData) return;

        const selectedSubDistrictName = $(this).val();
        const subDistrictData = districtData.tambon.find(s => s.name_th === selectedSubDistrictName);

        if (subDistrictData) {
            $('#addrZipCode').val(subDistrictData.zip_code || '');
            $('#addrSubDistrictEn').val(subDistrictData.name_en);
        }
    });

    $('#addressModal').on('show.bs.modal', async function() {
        await fetchAddressData();
        if (addressData.length > 0) {
            populateDropdown('#addrProvince', addressData, 'เลือกจังหวัด', 'name_th', 'name_th');

            // Pre-populate English dropdowns
            populateDropdown('#addrProvinceEn', addressData, 'Province', 'name_en', 'name_en');
            const allDistricts = addressData.flatMap(p => p.amphoe || []);
            populateDropdown('#addrDistrictEn', allDistricts, 'District', 'name_en', 'name_en');
            const allSubDistricts = allDistricts.flatMap(d => d.tambon || []);
            populateDropdown('#addrSubDistrictEn', allSubDistricts, 'Sub-district', 'name_en', 'name_en');
        }
    });
});
</script>
@endpush