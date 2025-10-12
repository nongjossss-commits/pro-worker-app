<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">เพิ่ม/แก้ไขที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    @csrf
                    <input type="hidden" id="address_id" name="id">
                    <input type="hidden" id="addressable_id" name="addressable_id">
                    <input type="hidden" id="addressable_type" name="addressable_type" value="App\Models\Employer">
                    <input type="hidden" id="address_type" name="type">

                    {{-- Row 1: No --}}
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
                    {{-- Row 2: Moo --}}
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
                    {{-- Row 3: Soi --}}
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
                    {{-- Row 4: Road --}}
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
                    {{-- Row 5: Province --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrProvince" class="form-label">จังหวัด (Thai)</label>
                            <select class="form-select" id="addrProvince" name="addrProvince">
                                <option selected disabled>-- เลือกจังหวัด --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="addrProvinceEn" class="form-label">Province (EN)</label>
                            <input type="text" class="form-control" id="addrProvinceEn" name="addrProvinceEn" readonly>
                        </div>
                    </div>
                    {{-- Row 6: District --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrDistrict" class="form-label">อำเภอ/เขต (Thai)</label>
                            <select class="form-select" id="addrDistrict" name="addrDistrict">
                                <option selected disabled>-- เลือกอำเภอ/เขต --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="addrDistrictEn" class="form-label">District (EN)</label>
                            <input type="text" class="form-control" id="addrDistrictEn" name="addrDistrictEn" readonly>
                        </div>
                    </div>
                    {{-- Row 7: SubDistrict --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrSubDistrict" class="form-label">ตำบล/แขวง (Thai)</label>
                            <select class="form-select" id="addrSubDistrict" name="addrSubDistrict">
                                <option selected disabled>-- เลือกตำบล/แขวง --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="addrSubDistrictEn" class="form-label">Sub-district (EN)</label>
                            <input type="text" class="form-control" id="addrSubDistrictEn" name="addrSubDistrictEn" readonly>
                        </div>
                    </div>
                     <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="addrZipCode" class="form-label">รหัสไปรษณีย์</label>
                            <input type="text" class="form-control" id="addrZipCode" name="addrZipCode" readonly>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" id="saveAddressBtn">บันทึก</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let thaiAddressData = [];
    const addressModal = document.getElementById('addressModal');
    const form = document.getElementById('addressForm');
    // Get all form elements by ID
    const provinceSelect = document.getElementById('addrProvince');
    const districtSelect = document.getElementById('addrDistrict');
    const subDistrictSelect = document.getElementById('addrSubDistrict');
    const provinceEnInput = document.getElementById('addrProvinceEn');
    const districtEnInput = document.getElementById('addrDistrictEn');
    const subDistrictEnInput = document.getElementById('addrSubDistrictEn');
    const zipCodeInput = document.getElementById('addrZipCode');

    // 1. Fetch address data on page load
    fetch("{{ route('addresses.thai_data') }}")
        .then(response => response.json())
        .then(data => {
            thaiAddressData = data;
            populateProvinces();
        });

    function populateProvinces() {
        thaiAddressData.forEach(province => {
            const option = new Option(province.name_th, province.name_th);
            provinceSelect.add(option);
        });
    }

    // 2. Cascading Dropdown Logic
    provinceSelect.addEventListener('change', function () {
        // Clear subsequent fields
        districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>';
        subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
        [districtSelect, subDistrictSelect].forEach(el => el.disabled = true);
        [provinceEnInput, districtEnInput, subDistrictEnInput, zipCodeInput].forEach(el => el.value = '');

        const selectedProvinceName = this.value;
        if (!selectedProvinceName) return;

        const selectedProvince = thaiAddressData.find(p => p.name_th === selectedProvinceName);
        if (selectedProvince) {
            provinceEnInput.value = selectedProvince.name_en;
            selectedProvince.amphoe.forEach(district => {
                const option = new Option(district.name_th, district.name_th);
                districtSelect.add(option);
            });
            districtSelect.disabled = false;
        }
    });

    districtSelect.addEventListener('change', function () {
        // Similar logic for district -> sub-district
        subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
        [subDistrictEnInput, zipCodeInput].forEach(el => el.value = '');

        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        const selectedDistrictName = this.value;
         if (!selectedDistrictName || !selectedProvince) return;

        const selectedDistrict = selectedProvince.amphoe.find(d => d.name_th === selectedDistrictName);
        if(selectedDistrict) {
            districtEnInput.value = selectedDistrict.name_en;
            selectedDistrict.tambon.forEach(sub => {
                const option = new Option(sub.name_th, sub.name_th);
                subDistrictSelect.add(option);
            });
            subDistrictSelect.disabled = false;
        }
    });

    subDistrictSelect.addEventListener('change', function () {
        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        const selectedDistrict = selectedProvince.amphoe.find(d => d.name_th === districtSelect.value);
        const selectedSubDistrictName = this.value;
        if(!selectedSubDistrictName || !selectedDistrict) return;

        const selectedSubDistrict = selectedDistrict.tambon.find(s => s.name_th === selectedSubDistrictName);
        if(selectedSubDistrict){
            subDistrictEnInput.value = selectedSubDistrict.name_en;
            zipCodeInput.value = selectedSubDistrict.zip_code;
        }
    });

    // 3. Modal Opening and Data Handling Logic
    addressModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const modalType = button.classList.contains('add-address-btn') ? 'add' : 'edit';

        form.reset(); // Reset form on open

        if (modalType === 'add') {
            document.getElementById('addressModalLabel').textContent = 'เพิ่มที่อยู่';
            document.getElementById('addressable_id').value = button.dataset.addressableId;
            document.getElementById('address_type').value = button.dataset.type;
        } else {
            // Logic for edit will be added later if needed
        }
    });

    // 4. Save Logic
    document.getElementById('saveAddressBtn').addEventListener('click', function() {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        fetch("{{ route('addresses.store') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data._token },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.address) {
                // Close modal and refresh address list without full page reload
                const modal = bootstrap.Modal.getInstance(addressModal);
                modal.hide();
                // You can add a function here to dynamically refresh the address list on the page
                location.reload(); // Simple reload for now
            } else {
                // Handle errors
            }
        });
    });
});
</script>
@endpush