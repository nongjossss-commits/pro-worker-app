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

    addressModalEl.addEventListener('show.bs.modal', function (event) {
        if (thaiAddressData.length > 0) {
            populateProvinces();
        }
        resetSelect(districtSelect, '-- เลือกอำเภอ/เขต --');
        resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
        clearInputs(provinceEnInput, districtEnInput, subDistrictEnInput, zipCodeInput);
    });

    provinceSelect.addEventListener('change', function () {
        // First, reset the downstream dropdowns and clear related inputs.
        resetSelect(districtSelect, '-- เลือกอำเภอ/เขต --');
        resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
        clearInputs(provinceEnInput, districtEnInput, subDistrictEnInput, zipCodeInput);

        const selectedProvinceName = this.value;
        if (!selectedProvinceName) {
            return; // Exit if the user selected the placeholder option.
        }

        const selectedProvince = thaiAddressData.find(p => p.name_th === selectedProvinceName);
        if (selectedProvince) {
            provinceEnInput.value = selectedProvince.name_en;

            // Populate the district dropdown with a placeholder first.
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>';
            selectedProvince.amphoe.forEach(district => {
                const option = new Option(district.name_th, district.name_th);
                districtSelect.add(option);
            });

            // After populating, enable the district dropdown.
            districtSelect.disabled = false;
        }
    });

    districtSelect.addEventListener('change', function () {
        // Reset the sub-district dropdown and clear related inputs.
        resetSelect(subDistrictSelect, '-- เลือกตำบล/แขวง --');
        clearInputs(districtEnInput, subDistrictEnInput, zipCodeInput);

        const selectedProvince = thaiAddressData.find(p => p.name_th === provinceSelect.value);
        const selectedDistrictName = this.value;
        if (!selectedDistrictName || !selectedProvince) {
            return; // Exit if no district is selected or province data is missing.
        }

        const selectedDistrict = selectedProvince.amphoe.find(d => d.name_th === selectedDistrictName);
        if (selectedDistrict) {
            districtEnInput.value = selectedDistrict.name_en;

            // Populate the sub-district dropdown with a placeholder.
            subDistrictSelect.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
            selectedDistrict.tambon.forEach(sub => {
                const option = new Option(sub.name_th, sub.name_th);
                subDistrictSelect.add(option);
            });

            // After populating, enable the sub-district dropdown.
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
});