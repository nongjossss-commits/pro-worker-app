document.addEventListener('DOMContentLoaded', function () {
    // This script relies on a global `employerEditConfig` object defined in the Blade template.
    if (typeof window.employerEditConfig === 'undefined') {
        console.error('Configuration object `employerEditConfig` is missing.');
        alert('A critical configuration error occurred. Please refresh the page.');
        return;
    }
    const config = window.employerEditConfig;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- START: COMPLETE ADDRESS SCRIPT ---

    // === 1. GLOBAL ELEMENTS & VARIABLES ===
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveAddressButton = document.getElementById('saveAddress');
    const addressErrors = document.getElementById('address-errors');
    let thaiAddressData = []; // Cache for the address JSON data

    // === 2. EVENT LISTENERS (Using Event Delegation for robustness) ===

    document.body.addEventListener('click', function(e) {
        const addBtn = e.target.closest('.add-address-btn');
        const editBtn = e.target.closest('.edit-address-btn');
        const deleteBtn = e.target.closest('.delete-address-btn');

        if (addBtn) {
            e.preventDefault();
            openModalFor('add', addBtn);
        }
        if (editBtn) {
            e.preventDefault();
            openModalFor('edit', editBtn);
        }
        if (deleteBtn) {
            e.preventDefault();
            handleDelete(deleteBtn);
        }
    });

    saveAddressButton.addEventListener('click', saveAddress);

    // === 3. CORE MODAL & FORM FUNCTIONS ===

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
            const addressId = button.getAttribute('data-id');
            document.getElementById('addressId').value = addressId;

            fetch(`/addresses/${addressId}/edit`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    populateAddressForm(data);
                    addressModal.show();
                })
                .catch(error => {
                    console.error('Error fetching address for edit:', error);
                    showToast('ไม่สามารถโหลดข้อมูลที่อยู่ได้', 'danger');
                });
        }
    }

    function handleDelete(button) {
        const addressId = button.getAttribute('data-id');
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

    // === 4. HELPER FUNCTIONS ===

    async function saveAddress() {
        const formData = new FormData(addressForm);
        const addressId = document.getElementById('addressId').value;
        let url = addressId ? `/addresses/${addressId}` : config.urls.addressesStore;

        if (addressId) {
            formData.append('_method', 'PUT');
        }

        saveAddressButton.disabled = true;
        saveAddressButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...`;
        addressErrors.style.display = 'none';
        addressErrors.innerHTML = '';

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const result = await response.json();

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                    let errorHtml = '<ul>';
                    for (const key in result.errors) {
                        result.errors[key].forEach(error => { errorHtml += `<li>${error}</li>`; });
                    }
                    errorHtml += '</ul>';
                    addressErrors.innerHTML = errorHtml;
                    addressErrors.style.display = 'block';
                } else {
                    throw new Error(result.message || 'An unknown error occurred.');
                }
            } else {
                addressModal.hide();
                showToast('บันทึกที่อยู่เรียบร้อยแล้ว', 'success');
                refreshAddressLists();
            }
        } catch (error) {
            console.error('Save Error:', error);
            addressErrors.innerHTML = `เกิดข้อผิดพลาด: ${error.message}`;
            addressErrors.style.display = 'block';
        } finally {
            saveAddressButton.disabled = false;
            saveAddressButton.innerHTML = 'บันทึก';
        }
    }

    async function deleteAddress(id) {
        try {
            const response = await fetch(`/addresses/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (!response.ok) {
                 const errorData = await response.json();
                 throw new Error(errorData.message || 'Server responded with an error.');
            }
            const result = await response.json();
            if (result.success) {
                showToast('ลบที่อยู่เรียบร้อยแล้ว', 'success');
                refreshAddressLists();
            } else {
                throw new Error(result.message || 'Failed to delete address.');
            }
        } catch (error) {
            console.error('Delete Error:', error);
            showToast('เกิดข้อผิดพลาดในการลบ: ' + error.message, 'danger');
        }
    }

    async function refreshAddressLists() {
        try {
            const response = await fetch(config.urls.addressesList);
            if (!response.ok) throw new Error('Failed to fetch updated address list.');
            const html = await response.text();
            document.getElementById('addressListsContainer').innerHTML = html;
        } catch (error) {
            console.error('Refresh Error:', error);
            location.reload();
        }
    }

    function resetAddressForm() {
        addressForm.reset();
        document.getElementById('addressId').value = '';
        if(addressErrors) {
            addressErrors.style.display = 'none';
            addressErrors.innerHTML = '';
        }
        document.getElementById('addrDistrict').innerHTML = `<option selected disabled>--- เลือกอำเภอ/เขต ---</option>`;
        document.getElementById('addrDistrict').disabled = true;
        document.getElementById('addrSubDistrict').innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        document.getElementById('addrSubDistrict').disabled = true;
        document.getElementById('addrZipCode').value = '';
    }

    function populateAddressForm(data) {
        Object.keys(data).forEach(key => {
            const el = document.getElementById(key);
            if(el) el.value = data[key] || '';
        });

        const provinceEl = document.getElementById('addrProvince');
        provinceEl.value = data.addrProvince;
        provinceEl.dispatchEvent(new Event('change'));

        setTimeout(() => {
            const districtEl = document.getElementById('addrDistrict');
            districtEl.value = data.addrDistrict;
            districtEl.dispatchEvent(new Event('change'));
            setTimeout(() => {
                const subDistrictEl = document.getElementById('addrSubDistrict');
                subDistrictEl.value = data.addrSubDistrict;
                subDistrictEl.dispatchEvent(new Event('change'));
            }, 250);
        }, 250);
    }

    // === 5. THAI ADDRESS DROPDOWN LOGIC ===
    const thaiDropdowns = {
        province: document.getElementById('addrProvince'),
        district: document.getElementById('addrDistrict'),
        subDistrict: document.getElementById('addrSubDistrict'),
        zipCode: document.getElementById('addrZipCode'),
        provinceEn: document.getElementById('addrProvinceEn'),
        districtEn: document.getElementById('addrDistrictEn'),
        subDistrictEn: document.getElementById('addrSubDistrictEn'),
    };

    async function fetchThaiAddressData() {
        if (thaiAddressData.length > 0) return;
        try {
            const dataUrl = `${config.urls.addressDataJson}?v=${Date.now()}`;
            const response = await fetch(dataUrl);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            thaiAddressData = await response.json();
        } catch (error) {
            console.error("Fatal Error: Could not fetch address data.", error);
        }
    }

    function populateDropdown(selectEl, data, placeholder, nameKey) {
        selectEl.innerHTML = `<option selected disabled value="">--- ${placeholder} ---</option>`;
        data.forEach(item => selectEl.add(new Option(item[nameKey], item[nameKey])));
    }

    async function initializeThaiAddressDropdowns() {
        await fetchThaiAddressData();
        if (thaiAddressData.length > 0) {
            populateDropdown(thaiDropdowns.province, thaiAddressData, 'เลือกจังหวัด', 'name_th');
        }
    }

    addressModalEl.addEventListener('show.bs.modal', initializeThaiAddressDropdowns);

    thaiDropdowns.province.addEventListener('change', function () {
        thaiDropdowns.district.innerHTML = `<option selected disabled>--- เลือกอำเภอ/เขต ---</option>`;
        thaiDropdowns.subDistrict.innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        thaiDropdowns.district.disabled = true;
        thaiDropdowns.subDistrict.disabled = true;
        thaiDropdowns.zipCode.value = '';

        const selectedProvinceData = thaiAddressData.find(p => p.name_th === this.value);
        if (selectedProvinceData && selectedProvinceData.districts) {
            populateDropdown(thaiDropdowns.district, selectedProvinceData.districts, 'เลือกอำเภอ/เขต', 'name_th');
            thaiDropdowns.provinceEn.value = selectedProvinceData.name_en;
            thaiDropdowns.district.disabled = false;
        }
    });

    thaiDropdowns.district.addEventListener('change', function () {
        thaiDropdowns.subDistrict.innerHTML = `<option selected disabled>--- เลือกตำบล/แขวง ---</option>`;
        thaiDropdowns.subDistrict.disabled = true;
        thaiDropdowns.zipCode.value = '';

        const selectedProvinceData = thaiAddressData.find(p => p.name_th === thaiDropdowns.province.value);
        if (!selectedProvinceData) return;
        const selectedDistrictData = selectedProvinceData.districts.find(d => d.name_th === this.value);

        if (selectedDistrictData && selectedDistrictData.sub_districts) {
            populateDropdown(thaiDropdowns.subDistrict, selectedDistrictData.sub_districts, 'เลือกตำบล/แขวง', 'name_th');
            thaiDropdowns.districtEn.value = selectedDistrictData.name_en;
            thaiDropdowns.subDistrict.disabled = false;
        }
    });

    thaiDropdowns.subDistrict.addEventListener('change', function () {
        const selectedProvinceData = thaiAddressData.find(p => p.name_th === thaiDropdowns.province.value);
        if (!selectedProvinceData) return;
        const selectedDistrictData = selectedProvinceData.districts.find(d => d.name_th === thaiDropdowns.district.value);
        if (!selectedDistrictData) return;
        const selectedSubDistrictData = selectedDistrictData.sub_districts.find(s => s.name_th === this.value);

        if (selectedSubDistrictData) {
            thaiDropdowns.zipCode.value = selectedSubDistrictData.zip_code || '';
            thaiDropdowns.subDistrictEn.value = selectedSubDistrictData.name_en;
        }
    });
    // --- END: ADDRESS SCRIPT ---


    // --- OTHER SCRIPTS FOR THE PAGE ---

    // --- Terminate Employee Logic ---
    const terminateModalEl = document.getElementById('terminateEmployeeModal');
    if (terminateModalEl) {
        const terminateModal = new bootstrap.Modal(terminateModalEl);
        const terminateForm = document.getElementById('terminateEmployeeForm');
        const terminateEmployeeIdInput = document.getElementById('terminateEmployeeId');
        const employeeListContainer = document.getElementById('employeeList');

        employeeListContainer.addEventListener('click', function (e) {
            const terminateButton = e.target.closest('.terminate-employee-btn');
            if (terminateButton) {
                terminateEmployeeIdInput.value = terminateButton.dataset.id;
                terminateForm.reset();
                terminateModal.show();
            }
        });

        document.getElementById('confirmTerminateEmployeeButton').addEventListener('click', function () {
            const employeeId = terminateEmployeeIdInput.value;
            const terminateDate = document.getElementById('terminateDate').value;
            const terminationReason = document.getElementById('terminationReason').value;

            if (!terminateDate) {
                showToast('กรุณาเลือกวันที่แจ้งออก', 'danger');
                return;
            }

            fetch(`/employees/${employeeId}/terminate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ terminateDate, terminationReason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showToast(data.message || 'เกิดข้อผิดพลาดในการแจ้งออก', 'danger');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    // --- Employment History Logic ---
    const searchHistoryInput = document.getElementById('searchHistoryInput');
    const historyList = document.getElementById('employmentHistoryList');

    function filterHistory() {
        const search = searchHistoryInput.value;
        const url = new URL(config.urls.historyFilter);
        url.searchParams.append('search', search);

        fetch(url)
            .then(response => response.json())
            .then(employees => {
                historyList.innerHTML = '';
                if (employees.length > 0) {
                    employees.forEach(employee => {
                        const card = `
                        <div class="employee-card bg-light d-flex justify-content-between align-items-start gap-3 p-3">
                             <div class="d-flex align-items-center flex-grow-1">
                                <img src="${employee.employeePhoto ? '/storage/' + employee.employeePhoto : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC'}" class="employee-photo-thumb" alt="Employee Photo" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%;">
                                <div class="ms-3 flex-grow-1">
                                    <p class="mb-0"><strong>${employee.employeeNameEn ?? 'No English Name'}</strong></p>
                                    <p class="mb-1 text-muted small">${employee.employeeNameTh ?? ''} (${employee.employeePosition ?? 'ไม่ระบุตำแหน่ง'})</p>
                                    <p class="mb-0 text-danger small"><strong>เลิกจ้างวันที่:</strong> ${new Date(employee.terminated_at).toLocaleDateString('th-TH')} - ${employee.termination_reason || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-success restore-employee-btn" data-id="${employee.id}" title="นำกลับ"><i class="bi bi-arrow-counterclockwise"></i></button>
                                <button type="button" class="btn btn-outline-danger permanent-delete-btn" data-id="${employee.id}" title="ลบถาวร"><i class="bi bi-trash3-fill"></i></button>
                            </div>
                        </div>`;
                        historyList.insertAdjacentHTML('beforeend', card);
                    });
                } else {
                    historyList.innerHTML = '<p class="text-muted text-center py-3">ไม่มีประวัติการจ้างงาน</p>';
                }
            });
    }
    if (searchHistoryInput) {
        searchHistoryInput.addEventListener('input', filterHistory);
        const historyModalEl = document.getElementById('historyModal');
        historyModalEl.addEventListener('shown.bs.modal', filterHistory, { once: true });
    }

    // --- Highlight on Load ---
    if (window.location.hash) {
        const highlightId = window.location.hash.substring(1);
        const elementToHighlight = document.getElementById(highlightId);
        if (elementToHighlight) {
            elementToHighlight.scrollIntoView({ behavior: 'smooth', block: 'center' });
            elementToHighlight.classList.add('highlight');
        }
    }

    // NOTE: Other logic like bulk actions, history actions (restore/delete)
    // would be preserved here if they existed in the original file.
    // The provided file had placeholders for them.
});