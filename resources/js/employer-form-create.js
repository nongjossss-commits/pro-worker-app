document.addEventListener('DOMContentLoaded', function () {
    // --- Temporary Address Storage ---
    let tempRegisteredAddresses = [];
    let tempWorkplaceAddresses = [];

    const registeredAddressList = document.getElementById('registeredAddressList');
    const workplaceAddressList = document.getElementById('workplaceAddressList');
    const mainForm = document.getElementById('saveEmployerForm');
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveAddressButton = document.getElementById('saveAddress');
    const addressErrors = document.getElementById('address-errors');

    // On the create page, we override the default AJAX save behavior.
    const isCreatePage = true;

    let currentlyEditing = null; // { type: 'registered' or 'workplace', index: 0 }

    /**
     * Creates a formatted address string from an address object.
     * @param {object} addr - The address object from the form.
     * @returns {{th: string, en: string}} - The formatted Thai and English addresses.
     */
    function getFullAddressStringFromObject(addr) {
        const th_parts = [
            addr.addrNo,
            addr.addrMoo ? `หมู่ ${addr.addrMoo}` : '',
            addr.addrSoi ? `ซอย${addr.addrSoi}` : '',
            addr.addrRoad ? `ถนน${addr.addrRoad}` : '',
            addr.addrSubDistrict,
            addr.addrDistrict,
            addr.addrProvince,
            addr.addrZipCode
        ].filter(Boolean).join(' ');

        const en_parts = [
            addr.addrNoEn,
            addr.addrMooEn ? `Moo ${addr.addrMooEn}` : '',
            addr.addrSoiEn ? `Soi ${addr.addrSoiEn}` : '',
            addr.addrRoadEn ? `Road ${addr.addrRoadEn}` : '',
            addr.addrSubDistrictEn,
            addr.addrDistrictEn,
            addr.addrProvinceEn,
            addr.addrZipCodeEn
        ].filter(Boolean).join(', ');

        return {
            th: th_parts || 'N/A',
            en: en_parts || 'N/A'
        };
    }

    /**
     * Renders the list of temporary addresses for a given type.
     * @param {string} type - 'registered' or 'workplace'.
     */
    function renderTempAddressList(type) {
        const listContainer = type === 'registered' ? registeredAddressList : workplaceAddressList;
        const addresses = type === 'registered' ? tempRegisteredAddresses : tempWorkplaceAddresses;

        listContainer.innerHTML = ''; // Clear previous list
        if (addresses.length === 0) {
            listContainer.innerHTML = '<p class="text-muted fst-italic">ยังไม่มีที่อยู่</p>';
            return;
        }

        addresses.forEach((addr, index) => {
            const fullAddress = getFullAddressStringFromObject(addr);
            const card = document.createElement('div');
            card.className = 'address-card d-flex justify-content-between align-items-start p-2 border-bottom';
            card.innerHTML = `
                <div>
                    <p class="mb-0">${fullAddress.th}</p>
                    <p class="mb-0 text-muted small">${fullAddress.en}</p>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary temp-edit-address-btn" data-type="${type}" data-index="${index}" title="แก้ไข"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger temp-delete-address-btn" data-type="${type}" data-index="${index}" title="ลบ"><i class="bi bi-trash"></i></button>
                </div>
            `;
            listContainer.appendChild(card);
        });
    }

    /**
     * Populates and opens the address modal for editing a temporary address.
     * @param {string} type - The address type ('registered' or 'workplace').
     * @param {number} index - The index of the address in its array.
     */
    function openAddressModalForTempEdit(type, index) {
        const addressToEdit = (type === 'registered' ? tempRegisteredAddresses : tempWorkplaceAddresses)[index];
        resetAddressForm();
        document.getElementById('addressType').value = type;

        // Populate the form with the address data
        for (const key in addressToEdit) {
            if (addressForm.elements[key]) {
                addressForm.elements[key].value = addressToEdit[key];
            }
        }

        // Manually trigger change events to populate dependent dropdowns
        const provinceEl = document.getElementById('addrProvince');
        provinceEl.dispatchEvent(new Event('change'));

        setTimeout(() => {
            const districtEl = document.getElementById('addrDistrict');
            districtEl.value = addressToEdit.addrDistrict;
            districtEl.disabled = false;
            districtEl.dispatchEvent(new Event('change'));
            setTimeout(() => {
                const subDistrictEl = document.getElementById('addrSubDistrict');
                subDistrictEl.value = addressToEdit.addrSubDistrict;
                subDistrictEl.disabled = false;
                subDistrictEl.dispatchEvent(new Event('change'));
            }, 250);
        }, 250);

        currentlyEditing = { type, index };
        addressModal.show();
    }

    /**
     * Saves address data from the modal form into the temporary client-side array.
     */
    function handleSaveAddressTemporarily() {
        const formData = new FormData(addressForm);
        const addressData = Object.fromEntries(formData.entries());
        const addressType = document.getElementById('addressType').value;

        // Basic validation
        if (!addressData.addrNo || !addressData.addrProvince || !addressData.addrDistrict || !addressData.addrSubDistrict) {
             showToast('กรุณากรอกข้อมูลที่อยู่ให้ครบถ้วน (เลขที่, จังหวัด, อำเภอ, ตำบล)', 'danger');
             return;
        }

        if (currentlyEditing) {
            // Update existing address
            const { type, index } = currentlyEditing;
            const targetArray = type === 'registered' ? tempRegisteredAddresses : tempWorkplaceAddresses;
            targetArray[index] = addressData;
        } else {
            // Add new address
            const targetArray = addressType === 'registered' ? tempRegisteredAddresses : tempWorkplaceAddresses;
            targetArray.push(addressData);
        }

        renderTempAddressList(currentlyEditing ? currentlyEditing.type : addressType);
        addressModal.hide();
    }

    // --- EVENT LISTENERS ---

    // Since this is the create page, we override the modal's save button to use our temporary storage function.
    // We clone the button to safely remove any existing event listeners from other scripts.
    if (isCreatePage) {
        const newSaveAddressButton = saveAddressButton.cloneNode(true);
        saveAddressButton.parentNode.replaceChild(newSaveAddressButton, saveAddressButton);
        newSaveAddressButton.addEventListener('click', handleSaveAddressTemporarily);
    }

    // Use event delegation on the container for edit/delete buttons
    document.getElementById('addressListsContainer').addEventListener('click', function(e) {
        const editBtn = e.target.closest('.temp-edit-address-btn');
        if (editBtn) {
            const type = editBtn.dataset.type;
            const index = parseInt(editBtn.dataset.index, 10);
            openAddressModalForTempEdit(type, index);
        }

        const deleteBtn = e.target.closest('.temp-delete-address-btn');
        if (deleteBtn) {
            const type = deleteBtn.dataset.type;
            const index = parseInt(deleteBtn.dataset.index, 10);

            Swal.fire({
                title: 'ยืนยันการลบ',
                text: "คุณแน่ใจหรือไม่ว่าต้องการลบที่อยู่นี้?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonText: 'ยกเลิก',
                confirmButtonText: 'ใช่, ลบเลย'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (type === 'registered') {
                        tempRegisteredAddresses.splice(index, 1);
                    } else {
                        tempWorkplaceAddresses.splice(index, 1);
                    }
                    renderTempAddressList(type);
                    showToast('ลบที่อยู่ชั่วคราวแล้ว', 'success');
                }
            });
        }
    });

    // Before submitting the main employer form, serialize the temp addresses into the hidden fields
    mainForm.addEventListener('submit', function (e) {
        document.getElementById('registered_addresses_json').value = JSON.stringify(tempRegisteredAddresses);
        document.getElementById('workplace_addresses_json').value = JSON.stringify(tempWorkplaceAddresses);
    });

    // Handle the "Add Address" buttons to set the correct type in the modal
    document.querySelectorAll('.add-address-btn').forEach(button => {
        button.addEventListener('click', function() {
            const addressType = this.getAttribute('data-address-type');
            resetAddressForm();
            document.getElementById('addressType').value = addressType;
        });
    });

    /**
     * Resets the address form and its dependent dropdowns.
     */
    function resetAddressForm() {
        addressForm.reset();
        currentlyEditing = null;
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

    // Reset form completely when modal is hidden
    addressModalEl.addEventListener('hidden.bs.modal', resetAddressForm);
});