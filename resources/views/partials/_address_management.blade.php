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
    const addressModalEl = document.getElementById('addressModal');
    const form = document.getElementById('addressForm');
    if (!form) return; // Exit if no address form on the page

    // --- Form Input References ---
    const provinceSelect = document.getElementById('addrProvince');
    const districtSelect = document.getElementById('addrDistrict');
    const subDistrictSelect = document.getElementById('addrSubDistrict');
    const provinceEnInput = document.getElementById('addrProvinceEn');
    const districtEnInput = document.getElementById('addrDistrictEn');
    const subDistrictEnInput = document.getElementById('addrSubDistrictEn');
    const zipCodeInput = document.getElementById('addrZipCode');
    const saveBtn = document.getElementById('saveAddressBtn');

    // --- Helper Functions ---
    const resetSelect = (select, placeholder) => {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = true;
    };

    const clearInputs = (...inputs) => {
        inputs.forEach(input => input.value = '');
    };

    // 1. FETCH THAI ADDRESS DATA
    const fetchAddressData = () => {
        // Check if data is already fetched
        if (thaiAddressData.length > 0) {
            populateProvinces();
            return;
        }
        fetch("{{ route('addresses.thai_data') }}")
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                thaiAddressData = data;
                populateProvinces();
            }).catch(error => {
                console.error('Error fetching address data:', error);
                // Optionally disable the form or show an error message to the user
            });
    };

    // 2. POPULATE DROPDOWNS
    const populateProvinces = () => {
        resetSelect(provinceSelect, '-- เลือกจังหวัด --');
        thaiAddressData.forEach(province => {
            const option = new Option(province.name_th, province.name_th);
            provinceSelect.add(option);
        });
        provinceSelect.disabled = false;
    };

    provinceSelect.addEventListener('change', function () {
        resetSelect(districtSelect, '-- เลือกอำเภอ/เขต --');
        resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
        clearInputs(provinceEnInput, districtEnInput, subDistrictEnInput, zipCodeInput);

        const selectedProvinceName = this.value;
        if (!selectedProvinceName) return;

        const selectedProvince = thaiAddressData.find(p => p.name_th === selectedProvinceName);
        if (selectedProvince) {
            provinceEnInput.value = selectedProvince.name_en;
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>'; // Reset
            selectedProvince.amphoe.forEach(district => {
                const option = new Option(district.name_th, district.name_th);
                districtSelect.add(option);
            });
            districtSelect.disabled = false;
        }
    });

    districtSelect.addEventListener('change', function () {
        resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
        clearInputs(districtEnInput, subDistrictEnInput, zipCodeInput);

        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        const selectedDistrictName = this.value;
        if (!selectedDistrictName || !selectedProvince) return;

        const selectedDistrict = selectedProvince.amphoe.find(d => d.name_th === selectedDistrictName);
        if (selectedDistrict) {
            districtEnInput.value = selectedDistrict.name_en;
            subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>'; // Reset
            selectedDistrict.tambon.forEach(sub => {
                const option = new Option(sub.name_th, sub.name_th);
                subDistrictSelect.add(option);
            });
            subDistrictSelect.disabled = false;
        }
    });

    subDistrictSelect.addEventListener('change', function () {
        clearInputs(subDistrictEnInput, zipCodeInput);

        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        if (!selectedProvince) return;
        const selectedDistrict = selectedProvince.amphoe.find(d => d.name_th === districtSelect.value);
        if (!selectedDistrict) return;
        const selectedSubDistrictName = this.value;
        if (!selectedSubDistrictName) return;

        const selectedSubDistrict = selectedDistrict.tambon.find(s => s.name_th === selectedSubDistrictName);
        if (selectedSubDistrict) {
            subDistrictEnInput.value = selectedSubDistrict.name_en;
            zipCodeInput.value = selectedSubDistrict.zip_code;
        }
    });

    // 3. MODAL EVENT HANDLING
    if (addressModalEl) {
        addressModalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return; // Modal opened via JS, not a button click

            form.reset();
            clearInputs(provinceEnInput, districtEnInput, subDistrictEnInput, zipCodeInput);
            resetSelect(districtSelect, '-- เลือกอำเภอ/เขต --');
            resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
            fetchAddressData(); // Fetch data and populate provinces

            const modalType = button.classList.contains('add-address-btn') ? 'add' : 'edit';
            const addressableId = button.dataset.addressableId;
            const addressType = button.dataset.type;

            document.getElementById('addressModalLabel').textContent = 'เพิ่มที่อยู่ใหม่';
            document.getElementById('addressable_id').value = addressableId;
            document.getElementById('address_type').value = addressType;
            document.getElementById('address_id').value = ''; // Clear ID for 'add' mode
        });
    }

    // 4. SAVE ADDRESS LOGIC
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch("{{ route('addresses.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                const modalInstance = bootstrap.Modal.getInstance(addressModalEl);
                if (result.success) {
                    modalInstance.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: result.message || 'บันทึกที่อยู่เรียบร้อยแล้ว',
                        timer: 2000,
                        showConfirmButton: false,
                    }).then(() => location.reload()); // Reload to see changes
                } else {
                    let errorText = result.message || 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล';
                    if (result.errors) {
                        errorText = Object.values(result.errors).map(e => `<div>${e}</div>`).join('');
                    }
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด!', html: errorText });
                }
            }).catch(error => {
                console.error('Save Address Error:', error);
                Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: 'ไม่สามารถส่งข้อมูลไปยังเซิร์ฟเวอร์ได้' });
            });
        });
    }
});
</script>
@endpush