// public/js/address-manager.js
// The Complete & Final Address Management Script, implemented as per the user's brief.

document.addEventListener('DOMContentLoaded', function () {
    // === 1. CONFIGURATION & GLOBAL ELEMENTS ===
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return; // Stop if modal doesn't exist on the page

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const elements = {
        province: document.getElementById('addrProvince'),
        district: document.getElementById('addrDistrict'),
        subDistrict: document.getElementById('addrSubDistrict'),
        zipCode: document.getElementById('addrZipCode'),
        provinceEn: document.getElementById('addrProvinceEn'),
        districtEn: document.getElementById('addrDistrictEn'),
        subDistrictEn: document.getElementById('addrSubDistrictEn'),
    };
    let addressDataCache = [];

    // === 2. DATA FETCHER FOR DROPDOWNS ===
    async function fetchAddressData() {
        if (addressDataCache.length > 0) return addressDataCache;
        try {
            const response = await fetch('/thai-addresses');
            if (!response.ok) throw new Error('Network response was not ok');
            addressDataCache = await response.json();
            return addressDataCache;
        } catch (error) {
            console.error('Failed to fetch address data:', error);
            showToast('ไม่สามารถโหลดข้อมูลที่อยู่ได้', 'danger');
            return [];
        }
    }

    // === 3. DROPDOWN LOGIC ===
    function populateDropdown(selectElement, data, defaultOption, valueKey) {
        selectElement.innerHTML = `<option value="">${defaultOption}</option>`;
        data.forEach(item => {
            const option = new Option(item[valueKey], item[valueKey]);
            selectElement.add(option);
        });
    }

    async function setupDropdowns() {
        const data = await fetchAddressData();
        if (data && data.length > 0) populateDropdown(elements.province, data, 'เลือกจังหวัด', 'name_th');

        elements.province.addEventListener('change', function () {
            const selectedProvinceData = data.find(p => p.name_th === this.value);
            if (selectedProvinceData) {
                populateDropdown(elements.district, selectedProvinceData.districts, 'เลือกอำเภอ/เขต', 'name_th');
                elements.district.disabled = false;
                elements.provinceEn.value = selectedProvinceData.name_en;
            } else {
                elements.district.innerHTML = '<option value="">เลือกอำเภอ/เขต</option>';
                elements.district.disabled = true;
            }
            elements.subDistrict.innerHTML = '<option value="">เลือกตำบล/แขวง</option>';
            elements.subDistrict.disabled = true;
            elements.zipCode.value = '';
        });

        elements.district.addEventListener('change', function () {
            const selectedProvinceData = data.find(p => p.name_th === elements.province.value);
            const selectedDistrictData = selectedProvinceData?.districts.find(d => d.name_th === this.value);
            if (selectedDistrictData) {
                populateDropdown(elements.subDistrict, selectedDistrictData.sub_districts, 'เลือกตำบล/แขวง', 'name_th');
                elements.subDistrict.disabled = false;
                elements.districtEn.value = selectedDistrictData.name_en;
            } else {
                elements.subDistrict.innerHTML = '<option value="">เลือกตำบล/แขวง</option>';
                elements.subDistrict.disabled = true;
            }
            elements.zipCode.value = '';
        });

        elements.subDistrict.addEventListener('change', function () {
            const selectedProvinceData = data.find(p => p.name_th === elements.province.value);
            const selectedDistrictData = selectedProvinceData?.districts.find(d => d.name_th === elements.district.value);
            const selectedSubDistrictData = selectedDistrictData?.sub_districts.find(s => s.name_th === this.value);
            if (selectedSubDistrictData) {
                elements.zipCode.value = selectedSubDistrictData.zip_code || '';
                elements.subDistrictEn.value = selectedSubDistrictData.name_en;
            }
        });
    }

    // === 4. EVENT LISTENERS (Using Event Delegation) ===
    document.body.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.add-address-btn');
        const editBtn = e.target.closest('.edit-address-btn');
        const deleteBtn = e.target.closest('.delete-address-btn');

        if (addBtn) openModalFor('add', addBtn);
        if (editBtn) openModalFor('edit', editBtn);
        if (deleteBtn) handleDelete(deleteBtn);
    });

    document.getElementById('saveAddress').addEventListener('click', saveAddress);

    // === 5. CORE MODAL & FORM FUNCTIONS ===
    function openModalFor(mode, button) {
        resetAddressForm();
        const addressableId = button.getAttribute('data-addressable-id');
        const addressableType = button.getAttribute('data-addressable-type');

        document.getElementById('addressableId').value = addressableId;
        document.getElementById('addressableType').value = addressableType;

        if (mode === 'add') {
            addressForm.querySelector('#addressModalLabel').textContent = 'เพิ่มที่อยู่';
            document.getElementById('addressType').value = button.getAttribute('data-type');
            addressModal.show();
        } else if (mode === 'edit') {
            addressForm.querySelector('#addressModalLabel').textContent = 'แก้ไขที่อยู่';
            const addressId = button.getAttribute('data-address-id');
            document.getElementById('addressId').value = addressId;

            fetch(`/addresses/${addressId}/edit`)
                .then(response => response.json())
                .then(data => {
                    populateAddressForm(data);
                    addressModal.show();
                }).catch(error => console.error('Error fetching address:', error));
        }
    }

    function handleDelete(button) {
        const addressId = button.getAttribute('data-address-id');
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: "คุณแน่ใจหรือไม่ว่าต้องการลบที่อยู่นี้?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteAddress(addressId);
            }
        });
    }

    // === 6. AJAX FUNCTIONS (Implementation) ===
    async function saveAddress() {
        const formData = new FormData(addressForm);
        const addressId = formData.get('addressId');
        const isUpdate = !!addressId;
        const url = isUpdate ? `/addresses/${addressId}` : `/addresses`;

        if (isUpdate) {
            formData.append('_method', 'PUT');
        }

        try {
            const response = await fetch(url, {
                method: 'POST', // Use POST for both create and update, with _method for PUT
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'An error occurred.');

            addressModal.hide();
            showToast(result.message || 'Address saved successfully.');
            reloadAddressLists();
        } catch (error) {
            console.error('Save Address Error:', error);
            showToast(error.message, 'danger');
        }
    }

    async function deleteAddress(id) {
        try {
            const response = await fetch(`/addresses/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'An error occurred.');

            showToast(result.message);
            reloadAddressLists();
        } catch (error) {
            console.error('Delete Address Error:', error);
            showToast(error.message, 'danger');
        }
    }

    // === 7. HELPER FUNCTIONS ===
    function resetAddressForm() {
        addressForm.reset();
        document.getElementById('addressId').value = '';
        elements.district.innerHTML = '<option value="">เลือกอำเภอ/เขต</option>';
        elements.district.disabled = true;
        elements.subDistrict.innerHTML = '<option value="">เลือกตำบล/แขวง</option>';
        elements.subDistrict.disabled = true;
    }

    function populateAddressForm(data) {
        for (const key in data) {
            const el = addressForm.querySelector(`[name="${key}"]`);
            if (el) el.value = data[key];
        }

        if (data.addrProvince) {
            elements.province.value = data.addrProvince;
            elements.province.dispatchEvent(new Event('change'));
            setTimeout(() => {
                elements.district.value = data.addrDistrict;
                elements.district.dispatchEvent(new Event('change'));
                setTimeout(() => {
                    elements.subDistrict.value = data.addrSubDistrict;
                    elements.subDistrict.dispatchEvent(new Event('change'));
                }, 300);
            }, 300);
        }
    }

    async function reloadAddressLists() {
        const container = document.getElementById('addressListsContainer');
        const employerId = container.querySelector('[data-employer-id]')?.getAttribute('data-employer-id');
        if (!employerId) {
             // This is the create page, no reload is possible. This is expected.
            return;
        }

        try {
            const response = await fetch(`/employers/${employerId}/addresses`);
            if (!response.ok) throw new Error('Failed to reload address list.');
            const html = await response.text();
            container.innerHTML = html;
        } catch (error) {
            console.error(error);
            showToast('Could not refresh address list.', 'danger');
        }
    }

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('liveToast');
        if (!toastEl) {
            alert(message); // Fallback
            return;
        }
        const toastBody = document.getElementById('toast-body-content');
        const toastIcon = toastEl.querySelector('.toast-header i');

        toastIcon.className = 'rounded me-2'; // Reset
        if (type === 'success') {
            toastIcon.classList.add('bi', 'bi-check-circle-fill', 'text-success');
        } else if (type === 'danger') {
            toastIcon.classList.add('bi', 'bi-exclamation-triangle-fill', 'text-danger');
        }
        toastBody.textContent = message;
        new bootstrap.Toast(toastEl).show();
    }

    // === 8. INITIALIZE ===
    setupDropdowns();
});