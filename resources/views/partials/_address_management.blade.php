{{-- Add/Edit Address Modal --}}
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
    const isEditPage = @json(isset($employer));

    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return;

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const addressModalLabel = document.getElementById('addressModalLabel');
    const addressIdInput = document.getElementById('addressId');
    const addressTypeInput = document.getElementById('addressType');
    const addressErrors = document.getElementById('address-errors');

    let thaiAddressData = [];

    const provinceSelect = document.getElementById('addrProvince');
    const districtSelect = document.getElementById('addrDistrict');
    const subDistrictSelect = document.getElementById('addrSubDistrict');
    const zipCodeInput = document.getElementById('addrZipCode');
    const provinceEnInput = document.getElementById('addrProvinceEn');
    const districtEnInput = document.getElementById('addrDistrictEn');
    const subDistrictEnInput = document.getElementById('addrSubDistrictEn');
    const zipCodeEnInput = document.getElementById('addrZipCodeEn');

    // --- SHARED LOGIC ---

    async function fetchThaiAddressData() {
        if (thaiAddressData.length > 0) return;
        try {
            const response = await fetch('https://raw.githubusercontent.com/kongvut/thai-province-data/master/api_province_with_amphure_tambon.json');
            if (!response.ok) throw new Error('Network response was not ok');
            thaiAddressData = await response.json();
            populateProvinces();
        } catch (error) {
            console.error("Fatal Error: Could not fetch address data.", error);
        }
    }

    function populateProvinces() {
        provinceSelect.innerHTML = '<option selected disabled>--- เลือกจังหวัด ---</option>';
        thaiAddressData.forEach(province => {
            const option = new Option(province.name_th, province.name_th);
            option.dataset.name_en = province.name_en;
            provinceSelect.add(option);
        });
    }

    function populateEnglishSelect(selectElement, enName, placeholder) {
        selectElement.innerHTML = '';
        if (enName) {
            const enOption = new Option(enName, enName);
            selectElement.add(enOption);
            selectElement.value = enName;
        } else {
            selectElement.innerHTML = `<option selected disabled>--- ${placeholder} ---</option>`;
        }
    }

    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';
        districtSelect.disabled = true;
        subDistrictSelect.disabled = true;

        const selectedOption = this.options[this.selectedIndex];
        const provinceEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(provinceEnInput, provinceEnName, 'Province');

        const selectedProvince = thaiAddressData.find(p => p.name_th === this.value);
        if (selectedProvince) {
            selectedProvince.amphure.forEach(district => {
                 const option = new Option(district.name_th, district.name_th);
                 option.dataset.name_en = district.name_en;
                 districtSelect.add(option);
            });
            districtSelect.disabled = false;
        }
    });

    districtSelect.addEventListener('change', function () {
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';
        subDistrictSelect.disabled = true;

        const selectedOption = this.options[this.selectedIndex];
        const districtEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(districtEnInput, districtEnName, 'District');

        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        if (selectedProvince) {
            const selectedDistrict = selectedProvince.amphure.find(d => d.name_th === this.value);
            if (selectedDistrict) {
                selectedDistrict.tambon.forEach(subDistrict => {
                    const option = new Option(subDistrict.name_th, subDistrict.name_th);
                    option.dataset.name_en = subDistrict.name_en;
                    option.dataset.zip_code = subDistrict.zip_code;
                    subDistrictSelect.add(option);
                });
                subDistrictSelect.disabled = false;
            }
        }
    });

    subDistrictSelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const zipCode = selectedOption.dataset.zip_code || '';
        zipCodeInput.value = zipCode;
        if(zipCodeEnInput) zipCodeEnInput.value = zipCode;

        const subDistrictEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(subDistrictEnInput, subDistrictEnName, 'Sub-district');
    });

    function resetAddressForm() {
        addressForm.reset();
        addressErrors.style.display = 'none';
        addressIdInput.value = '';
        // Reset dropdowns to their initial disabled state
        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        provinceEnInput.innerHTML = '<option selected disabled>--- Province ---</option>';
        districtEnInput.innerHTML = '<option selected disabled>--- District ---</option>';
        subDistrictEnInput.innerHTML = '<option selected disabled>--- Sub-district ---</option>';
        districtSelect.disabled = true;
        subDistrictSelect.disabled = true;
    }

    // --- PAGE-SPECIFIC LOGIC ---

    if (isEditPage) {
        // --- EDIT PAGE LOGIC ---
        addressModalEl.addEventListener('show.bs.modal', async function (event) {
            resetAddressForm();
            const button = event.relatedTarget;
            const addressId = button.getAttribute('data-id');
            const addressType = button.getAttribute('data-address-type');
            addressTypeInput.value = addressType;

            if (addressId) { // Editing existing address
                addressModalLabel.textContent = 'แก้ไขที่อยู่';
                addressIdInput.value = addressId;
                const response = await fetch(`/addresses/${addressId}/edit`);
                const data = await response.json();
                for (const key in data) {
                    if (document.getElementById(key)) document.getElementById(key).value = data[key];
                }
                provinceSelect.value = data.addrProvince;
                provinceSelect.dispatchEvent(new Event('change'));
                await new Promise(r => setTimeout(r, 250));
                districtSelect.value = data.addrDistrict;
                districtSelect.dispatchEvent(new Event('change'));
                await new Promise(r => setTimeout(r, 250));
                subDistrictSelect.value = data.addrSubDistrict;
                subDistrictSelect.dispatchEvent(new Event('change'));
            } else { // Adding new address
                addressModalLabel.textContent = 'เพิ่มที่อยู่ใหม่';
            }
        });

        document.getElementById('saveAddress').addEventListener('click', async function() {
            const addressId = addressIdInput.value;
            const method = addressId ? 'PUT' : 'POST';
            const employerId = '{{ isset($employer) ? $employer->id : '' }}';
            const url = addressId ? `/addresses/${addressId}` : `/employers/${employerId}/addresses`;

            const formData = new FormData(addressForm);
            const data = Object.fromEntries(formData.entries());
            data.addrProvinceEn = provinceEnInput.value;
            data.addrDistrictEn = districtEnInput.value;
            data.addrSubDistrictEn = subDistrictEnInput.value;
            data.addrZipCodeEn = zipCodeEnInput.value;

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                if (!response.ok) throw await response.json();
                location.reload();
            } catch (error) {
                addressErrors.style.display = 'block';
                addressErrors.innerHTML = '<ul>' + Object.values(error.errors).map(e => `<li>${e[0]}</li>`).join('') + '</ul>';
            }
        });

    } else {
        // --- CREATE PAGE LOGIC ---
        let registeredAddresses = [];
        let workplaceAddresses = [];
        const registeredAddressesInput = document.getElementById('registered_addresses_json');
        const workplaceAddressesInput = document.getElementById('workplace_addresses_json');

        addressModalEl.addEventListener('show.bs.modal', function (event) {
            resetAddressForm();
            const button = event.relatedTarget;
            addressTypeInput.value = button.dataset.addressType;
            addressModalLabel.textContent = 'เพิ่มที่อยู่';
        });

        document.getElementById('saveAddress').addEventListener('click', function() {
            const addressType = addressTypeInput.value;
            const selects = addressForm.querySelectorAll('select:disabled');
            selects.forEach(s => s.disabled = false);
            const formData = new FormData(addressForm);
            const address = Object.fromEntries(formData.entries());
            selects.forEach(s => s.disabled = true);

            if (addressType === 'registered') {
                registeredAddresses.push(address);
                registeredAddressesInput.value = JSON.stringify(registeredAddresses);
            } else {
                workplaceAddresses.push(address);
                workplaceAddressesInput.value = JSON.stringify(workplaceAddresses);
            }
            renderAddressLists();
            addressModal.hide();
        });

        function renderAddressLists() {
            renderAddressList(registeredAddresses, 'registeredAddressList', 'registered');
            renderAddressList(workplaceAddresses, 'workplaceAddressList', 'workplace');
        }

        function renderAddressList(addresses, listId, type) {
            const listElement = document.getElementById(listId);
            if (!listElement) return;
            listElement.innerHTML = '';
            if (addresses.length === 0) {
                listElement.innerHTML = '<p class="text-muted">ยังไม่มีที่อยู่</p>';
                return;
            }
            addresses.forEach((address, index) => {
                const cardHtml = `
                    <div class="address-card d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-0">
                                เลขที่ ${address.addrNo || ''} หมู่ ${address.addrMoo || ''} ซอย${address.addrSoi || ''} ถนน${address.addrRoad || ''}
                                แขวง/ตำบล ${address.addrSubDistrict || ''} เขต/อำเภอ ${address.addrDistrict || ''}
                                ${address.addrProvince || ''} ${address.addrZipCode || ''}
                            </p>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-danger remove-address-btn" data-index="${index}" data-type="${type}"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>`;
                listElement.insertAdjacentHTML('beforeend', cardHtml);
            });
        }

        document.body.addEventListener('click', function (e) {
            const removeButton = e.target.closest('.remove-address-btn');
            if (removeButton) {
                const index = parseInt(removeButton.dataset.index, 10);
                const type = removeButton.dataset.type;
                if (type === 'registered') {
                    registeredAddresses.splice(index, 1);
                    registeredAddressesInput.value = JSON.stringify(registeredAddresses);
                } else {
                    workplaceAddresses.splice(index, 1);
                    workplaceAddressesInput.value = JSON.stringify(workplaceAddresses);
                }
                renderAddressLists();
            }
        });
    }

    // Initial fetch for all pages
    fetchThaiAddressData();
});
</script>
@endpush