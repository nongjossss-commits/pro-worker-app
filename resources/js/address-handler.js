document.addEventListener('DOMContentLoaded', function () {
    let thaiAddressData = [];
    // Ensure we are on a page that has the address modal
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) {
        return;
    }

    const provinceSelect = document.getElementById('addrProvinceTh');
    const districtSelect = document.getElementById('addrDistrictTh');
    const subDistrictSelect = document.getElementById('addrSubDistrictTh');
    const provinceEnInput = document.getElementById('addrProvinceEn');
    const districtEnInput = document.getElementById('addrDistrictEn');
    const subDistrictEnInput = document.getElementById('addrSubDistrictEn');
    const zipCodeInput = document.getElementById('addrZipCode');

    const resetSelect = (select, placeholder) => {
        select.innerHTML = `<option value="" selected disabled>${placeholder}</option>`;
        select.disabled = true;
    };

    const clearInputs = (...inputs) => {
        inputs.forEach(input => input.value = '');
    };

    const populateProvinces = () => {
        resetSelect(provinceSelect, '-- เลือกจังหวัด --');
        thaiAddressData.forEach(province => {
            const option = new Option(province.name_th, province.name_th);
            provinceSelect.add(option);
        });
        provinceSelect.disabled = false;
    };

    // Fetch data on initial load
    fetch('/thai-addresses')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            thaiAddressData = data;
            // The modal's show event will trigger the population
        })
        .catch(error => {
            console.error('Failed to fetch Thai address data:', error);
            // Maybe disable the address functionality or show an error
        });

// START: Corrected 'show.bs.modal' listener
addressModalEl.addEventListener('show.bs.modal', function (event) {
    // Button that triggered the modal
    const button = event.relatedTarget;
    const addressForm = document.getElementById('addressForm');
    const modalTitle = document.getElementById('addressModalLabel'); // Assuming your modal title has this ID, add it if not.

    // Reset the form for both create and edit
    addressForm.reset();
    document.getElementById('address_id').value = '';
    document.getElementById('addressable_id').value = '';
    document.getElementById('address_type').value = '';

    // Clear dropdowns
    resetSelect(districtSelect, '-- เลือกอำเภอ/เขต --');
    resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
    clearInputs(provinceEnInput, districtEnInput, subDistrictEnInput, zipCodeInput);

    // Repopulate provinces if data is ready
    if (thaiAddressData.length > 0) {
        populateProvinces();
    }

    if (button) {
        const addressId = button.dataset.addressId;
        const addressableId = button.dataset.addressableId;
        const addressType = button.dataset.type;

        if (addressId) {
            // ----- WE ARE EDITING -----
            if(modalTitle) modalTitle.textContent = 'แก้ไขที่อยู่';
            document.getElementById('address_id').value = addressId;

            // Show loading state (optional, but good practice)
            // saveButton.disabled = true;

            // Fetch the existing address data
            fetch(`/addresses/${addressId}/edit`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch address data.');
                    }
                    return response.json();
                })
                .then(data => {
                    // Populate the form with existing data
                    document.getElementById('addrNo').value = data.addrNo || '';
                    document.getElementById('addrMoo').value = data.addrMoo || '';
                    document.getElementById('addrSoi').value = data.addrSoi || '';
                    document.getElementById('addrRoad').value = data.addrRoad || '';
                    document.getElementById('addrZipCode').value = data.addrZipCode || '';
                    document.getElementById('addrNoEn').value = data.addrNoEn || '';
                    document.getElementById('addrMooEn').value = data.addrMooEn || '';
                    document.getElementById('addrSoiEn').value = data.addrSoiEn || '';
                    document.getElementById('addrRoadEn').value = data.addrRoadEn || '';
                    document.getElementById('addrSubDistrictEn').value = data.addrSubDistrictEn || '';
                    document.getElementById('addrDistrictEn').value = data.addrDistrictEn || '';
                    document.getElementById('addrProvinceEn').value = data.addrProvinceEn || '';

                    // Set dropdowns (This is complex, simplified version: set values)
                    // Note: This won't auto-trigger district/sub-district loading,
                    // but it will save the correct data.
                    if (data.addrProvince) {
                        provinceSelect.value = data.addrProvince;
                    }
                    // A full solution would require triggering 'change' events and waiting
                    // but for saving, setting the EN values is most important.

                    // saveButton.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching address data:', error);
                    alert('Could not load address data for editing. Please close and try again.');
                });

        } else if (addressableId) {
            // ----- WE ARE CREATING -----
            if(modalTitle) modalTitle.textContent = 'เพิ่มที่อยู่ใหม่';
            document.getElementById('addressable_id').value = addressableId;
            document.getElementById('address_type').value = addressType;
        }
    }
});
// END: Corrected 'show.bs.modal' listener

    provinceSelect.addEventListener('change', function () {
        // Always start by resetting downstream dependencies. This disables them.
        resetSelect(districtSelect, '-- เลือกอำเภอ/เขต --');
        resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
        clearInputs(provinceEnInput, districtEnInput, subDistrictEnInput, zipCodeInput);
        // districtSelect.disabled = false;
        const selectedProvinceName = this.value;
        if (!selectedProvinceName) {
            return; // A province is not selected, so leave dropdowns disabled as they are.
        }

        const selectedProvince = thaiAddressData.find(p => p.name_th === selectedProvinceName);
        // console.log('Selected Province:', selectedProvince);
        if (selectedProvince) {
            provinceEnInput.value = selectedProvince.name_en;

            // Populate districts
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>'; // Add placeholder
            selectedProvince.districts.forEach(district => {
                districtSelect.add(new Option(district.name_th, district.name_th));
            });

            // As the final step, enable the dropdown now that it's populated.
            districtSelect.disabled = false;
        }
    });

    districtSelect.addEventListener('change', function () {
        // Always start by resetting downstream dependencies.
        resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
        clearInputs(districtEnInput, subDistrictEnInput, zipCodeInput);

        const selectedDistrictName = this.value;
        if (!selectedDistrictName) {
            return; // A district is not selected, leave sub-district disabled.
        }

        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        if (!selectedProvince) {
            return; // Should not happen if UI is working correctly
        }

        const selectedDistrict = selectedProvince.districts.find(d => d.name_th === selectedDistrictName);
        if (selectedDistrict) {
            districtEnInput.value = selectedDistrict.name_en;

            // Populate sub-districts
            subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>'; // Add placeholder
            selectedDistrict.sub_districts.forEach(sub => {
                subDistrictSelect.add(new Option(sub.name_th, sub.name_th));
            });

            // As the final step, enable the dropdown.
            subDistrictSelect.disabled = false;
        }
    });

    subDistrictSelect.addEventListener('change', function () {
        clearInputs(subDistrictEnInput, zipCodeInput);

        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        if (!selectedProvince) return;
        const selectedDistrict = selectedProvince.districts.find(d => d.name_th === districtSelect.value);
        if (!selectedDistrict) return;
        const selectedSubDistrictName = this.value;
        if (!selectedSubDistrictName) return;

        const selectedSubDistrict = selectedDistrict.sub_districts.find(s => s.name_th === selectedSubDistrictName);
        if (selectedSubDistrict) {
            subDistrictEnInput.value = selectedSubDistrict.name_en;
            zipCodeInput.value = selectedSubDistrict.zip_code;
        }
    });

    const addressForm = document.getElementById('addressForm');
    addressForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const addressId = document.getElementById('address_id').value;
        const method = addressId ? 'PUT' : 'POST';
        const url = addressId ? `/addresses/${addressId}` : '/addresses';

        // Update the hidden method input for Laravel
        document.getElementById('addressFormMethod').value = method;

        const formData = new FormData(addressForm);
        const saveButton = document.getElementById('saveAddressBtn');

        // Disable button to prevent multiple submissions
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        fetch(url, {
            method: 'POST', // Always POST, Laravel handles PUT/PATCH via _method field
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(response => {
            if (response.status === 422) {
                return response.json().then(data => {
                    // Handle validation errors
                    handleValidationErrors(data.errors);
                    // Re-enable the button
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'บันทึก';
                });
            }
            if (!response.ok) {
                 return response.json().then(data => {
                    throw new Error(data.message || 'An unexpected error occurred.');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data) {
                // It was a success
                const addressModal = bootstrap.Modal.getInstance(addressModalEl);
                addressModal.hide();
                // Show a success toast
                if (window.showToast) {
                    window.showToast(data.message || 'Address saved successfully!', 'success');
                }
                // Optionally, refresh the part of the page that lists addresses
                // For now, we will just reload the page
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
             if (window.showToast) {
                window.showToast(error.message, 'danger');
            }
            // Re-enable the button
            saveButton.disabled = false;
            saveButton.innerHTML = 'บันทึก';
        });
    });

    function handleValidationErrors(errors) {
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

        for (const field in errors) {
            const input = document.getElementById(field);
            const errorContainer = document.getElementById(`${field}Error`);
            if (input) {
                input.classList.add('is-invalid');
            }
            if (errorContainer) {
                errorContainer.textContent = errors[field][0];
            }
        }
    }

     addressModalEl.addEventListener('hidden.bs.modal', function (event) {
        // Clear form and errors when modal is closed
        addressForm.reset();
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        document.getElementById('address_id').value = '';
        document.getElementById('addressFormMethod').value = '';
        const saveButton = document.getElementById('saveAddressBtn');
        saveButton.disabled = false;
        saveButton.innerHTML = 'บันทึก';
    });

// START: Add Delete Functionality (Bootstrap Modal Version)
const deleteModalEl = document.getElementById('deleteConfirmModal');
const deleteModal = new bootstrap.Modal(deleteModalEl);
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

document.addEventListener('click', function(e) {
    // Check if the clicked element is a delete button
    if (e.target && (e.target.classList.contains('btn-delete-address') || e.target.closest('.btn-delete-address'))) {

        e.preventDefault();

        const button = e.target.closest('.btn-delete-address');
        const addressId = button.dataset.addressId;

        if (!addressId) {
            console.error('Delete button is missing address ID.');
            alert('An error occurred. Please refresh the page.');
            return;
        }

        // Store the ID on the confirm button and show the modal
        confirmDeleteBtn.dataset.addressId = addressId;
        deleteModal.show();
    }
});

// Add listener for the *actual* delete confirmation
confirmDeleteBtn.addEventListener('click', function() {
    const addressId = this.dataset.addressId;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    if (!addressId || !token) {
        console.error('Delete confirmation is missing address ID or CSRF token.');
        alert('An error occurred. Please refresh the page.');
        return;
    }

    // Disable button to prevent double-click
    this.disabled = true;
    this.innerHTML = 'Deleting...';

    fetch(`/addresses/${addressId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok.');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            deleteModal.hide();
            if (window.showToast) {
                window.showToast('Address deleted successfully!', 'success');
            }
            window.location.reload();
        } else {
            throw new Error('Server reported an error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deleting address:', error);
        alert('Failed to delete address. Please check the console for details.');
        // Re-enable button on failure
        this.disabled = false;
        this.innerHTML = 'Confirm Delete';
    });
});
// END: Add Delete Functionality (Bootstrap Modal Version)
});