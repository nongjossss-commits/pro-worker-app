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
    // Determine if we are on the edit page (has an employer object) or create page.
    const isEditPage = @json(isset($employer));

    // --- Modal and Form Elements ---
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return;

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const addressModalLabel = document.getElementById('addressModalLabel');
    const addressIdInput = document.getElementById('addressId');
    const addressTypeInput = document.getElementById('addressType');
    const addressErrors = document.getElementById('address-errors');
    const saveAddressBtn = document.getElementById('saveAddress');

    // --- Form Field Elements ---
    const provinceSelect = document.getElementById('addrProvince');
    const districtSelect = document.getElementById('addrDistrict');
    const subDistrictSelect = document.getElementById('addrSubDistrict');
    const zipCodeInput = document.getElementById('addrZipCode');
    const provinceEnSelect = document.getElementById('addrProvinceEn');
    const districtEnSelect = document.getElementById('addrDistrictEn');
    const subDistrictEnSelect = document.getElementById('addrSubDistrictEn');
    const zipCodeEnInput = document.getElementById('addrZipCodeEn');

    // --- Data Store ---
    let thaiAddressData = [];
    let isAddressDataFetched = false;
    let currentAddressType = null; // 'registered' or 'workplace'

    // --- CREATE page specific elements and variables ---
    let registeredAddresses = [];
    let workplaceAddresses = [];
    const registeredAddressesInput = document.getElementById('registered_addresses_json');
    const workplaceAddressesInput = document.getElementById('workplace_addresses_json');

    // --- CORE FUNCTIONS ---

    /**
     * Fetches Thai address data from the backend.
     * Ensures data is fetched only once.
     */
    async function fetchThaiAddressData() {
        if (isAddressDataFetched) return;
        try {
            const response = await fetch("{{ route('addresses.thai_data') }}");
            if (!response.ok) throw new Error('Network response was not ok');
            thaiAddressData = await response.json();
            isAddressDataFetched = true;
        } catch (error) {
            console.error("Fatal Error: Could not fetch address data from backend.", error);
            addressErrors.innerHTML = 'ไม่สามารถโหลดข้อมูลที่อยู่ได้ กรุณาลองใหม่อีกครั้ง';
            addressErrors.style.display = 'block';
        }
    }

    /**
     * Generic dropdown population function.
     */
    function populateDropdown(selectElement, data, placeholder, valueField, textField, englishField = null) {
        selectElement.innerHTML = `<option selected disabled value="">${placeholder}</option>`;
        data.forEach(item => {
            const option = new Option(item[textField], item[valueField]);
            if (englishField && item[englishField]) {
                option.dataset.name_en = item[englishField];
            }
            if (item.zip_code) {
                option.dataset.zip_code = item.zip_code;
            }
            selectElement.add(option);
        });
    }

    /**
     * Populates the English counterpart select element.
     */
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

    /**
     * Resets the address form to its initial state.
     */
    function resetAddressForm() {
        addressForm.reset();
        addressErrors.style.display = 'none';
        addressIdInput.value = '';

        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        provinceEnSelect.innerHTML = '<option selected disabled>--- Province ---</option>';
        districtEnSelect.innerHTML = '<option selected disabled>--- District ---</option>';
        subDistrictEnSelect.innerHTML = '<option selected disabled>--- Sub-district ---</option>';
        districtSelect.disabled = true;
        subDistrictSelect.disabled = true;
    }

    // --- EVENT LISTENERS for Dropdowns ---

    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option selected disabled>--- เลือกอำเภอ/เขต ---</option>';
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';
        districtSelect.disabled = true;
        subDistrictSelect.disabled = true;

        const selectedOption = this.options[this.selectedIndex];
        const provinceEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(provinceEnSelect, provinceEnName, 'Province');

        const selectedProvince = thaiAddressData.find(p => p.name_th === this.value);
        if (selectedProvince && selectedProvince.amphure) {
            populateDropdown(districtSelect, selectedProvince.amphure, 'เลือกอำเภอ/เขต', 'name_th', 'name_th', 'name_en');
            districtSelect.disabled = false;
        }
    });

    districtSelect.addEventListener('change', function () {
        subDistrictSelect.innerHTML = '<option selected disabled>--- เลือกตำบล/แขวง ---</option>';
        zipCodeInput.value = '';
        subDistrictSelect.disabled = true;

        const selectedOption = this.options[this.selectedIndex];
        const districtEnName = selectedOption.dataset.name_en || '';
        populateEnglishSelect(districtEnSelect, districtEnName, 'District');

        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        if (selectedProvince) {
            const selectedDistrict = selectedProvince.amphure.find(d => d.name_th === this.value);
            if (selectedDistrict && selectedDistrict.tambon) {
                populateDropdown(subDistrictSelect, selectedDistrict.tambon, 'เลือกตำบล/แขวง', 'name_th', 'name_th', 'name_en');
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
        populateEnglishSelect(subDistrictEnSelect, subDistrictEnName, 'Sub-district');
    });


    // --- MODAL TRIGGER: THE CORE FIX ---
    // Make the event listener function ASYNC to allow use of AWAIT
    addressModalEl.addEventListener('show.bs.modal', async function (event) {
        resetAddressForm();

        // AWAIT the data fetching to ensure thaiAddressData is populated before continuing.
        // This is the main fix for the empty dropdown issue.
        await fetchThaiAddressData();

        // Now that we have the data, populate the province dropdown.
        populateDropdown(provinceSelect, thaiAddressData, '--- เลือกจังหวัด ---', 'name_th', 'name_th', 'name_en');

        const button = event.relatedTarget;
        const addressId = button.getAttribute('data-id');
        currentAddressType = button.getAttribute('data-address-type');
        addressTypeInput.value = currentAddressType;

        if (isEditPage && addressId) { // EDIT mode on Edit page
            addressModalLabel.textContent = 'แก้ไขที่อยู่';
            addressIdInput.value = addressId;
            try {
                const response = await fetch(`/addresses/${addressId}/edit`);
                if (!response.ok) throw new Error('Could not fetch address details.');
                const data = await response.json();

                for (const key in data) {
                    const el = document.getElementById(key);
                    if (el) el.value = data[key];
                }

                if (data.addrProvince) {
                    provinceSelect.value = data.addrProvince;
                    provinceSelect.dispatchEvent(new Event('change'));
                    // Use Promise to wait for the next UI update tick
                    await new Promise(resolve => setTimeout(resolve, 50));
                }
                if (data.addrDistrict) {
                    districtSelect.value = data.addrDistrict;
                    districtSelect.dispatchEvent(new Event('change'));
                    await new Promise(resolve => setTimeout(resolve, 50));
                }
                if (data.addrSubDistrict) {
                    subDistrictSelect.value = data.addrSubDistrict;
                    subDistrictSelect.dispatchEvent(new Event('change'));
                }

            } catch (error) {
                console.error("Error fetching address for editing:", error);
                addressErrors.innerHTML = 'เกิดข้อผิดพลาดในการดึงข้อมูลที่อยู่';
                addressErrors.style.display = 'block';
            }

        } else { // ADD mode (on both Create and Edit pages)
            addressModalLabel.textContent = 'เพิ่มที่อยู่ใหม่';
        }
    });

    // --- SAVE LOGIC ---
    saveAddressBtn.addEventListener('click', async function() {
        const formData = new FormData(addressForm);
        const data = Object.fromEntries(formData.entries());
        data.addrProvinceEn = provinceEnSelect.value;
        data.addrDistrictEn = districtEnSelect.value;
        data.addrSubDistrictEn = subDistrictEnSelect.value;
        data.addrZipCodeEn = zipCodeEnInput.value;

        if (isEditPage) {
            // --- EDIT PAGE SAVE LOGIC ---
            const addressId = addressIdInput.value;
            const method = addressId ? 'PUT' : 'POST';
            const employerId = '{{ isset($employer) ? $employer->id : '' }}';
            const url = addressId ? `/addresses/${addressId}` : `/employers/${employerId}/addresses`;

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw errorData;
                }
                location.reload();
            } catch (error) {
                addressErrors.style.display = 'block';
                if (error && error.errors) {
                    addressErrors.innerHTML = '<ul>' + Object.values(error.errors).map(e => `<li>${e[0]}</li>`).join('') + '</ul>';
                } else {
                    addressErrors.innerHTML = 'เกิดข้อผิดพลาดที่ไม่รู้จัก';
                }
            }
        } else {
            // --- CREATE PAGE SAVE LOGIC ---
            if (currentAddressType === 'registered') {
                registeredAddresses.push(data);
                if(registeredAddressesInput) registeredAddressesInput.value = JSON.stringify(registeredAddresses);
            } else if (currentAddressType === 'workplace') {
                workplaceAddresses.push(data);
                if(workplaceAddressesInput) workplaceAddressesInput.value = JSON.stringify(workplaceAddresses);
            }
            renderAddressLists();
            addressModal.hide();
        }
    });

    // --- CREATE PAGE SPECIFIC FUNCTIONS ---
    if (!isEditPage) {
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
                    if(registeredAddressesInput) registeredAddressesInput.value = JSON.stringify(registeredAddresses);
                } else {
                    workplaceAddresses.splice(index, 1);
                    if(workplaceAddressesInput) workplaceAddressesInput.value = JSON.stringify(workplaceAddresses);
                }
                renderAddressLists();
            }
        });
    }
});
</script>
@endpush