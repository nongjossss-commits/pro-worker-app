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
});