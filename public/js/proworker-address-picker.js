/**
 * Cascading Thai address picker for Pro Worker contract issuance —
 * parameterized variant of resources/js/address-handler.js (which is
 * hard-coded to one fixed set of element IDs and can't run twice on the
 * same page). A contract template may place the "Address" tool more than
 * once, so this wires up N independent .proworker-address-group blocks
 * from a single shared fetch of /thai-addresses (same endpoint, same
 * dataset — see routes/web.php AddressController@getThaiAddressData).
 *
 * Composition format mirrors PdfGeneratorService::formatAddress() exactly
 * so contracts read the same as every other generated document. Soi/Road
 * have separate English inputs (proper nouns can't be auto-translated
 * reliably) — if left blank, that part is simply OMITTED from the
 * composed English address rather than falling back to the untranslated
 * Thai text, since dropping Thai characters into an English legal
 * document is worse than leaving the detail out.
 *
 * Lives in public/js/ (loaded via a plain <script> tag, NOT the Vite
 * bundle) because the Pro Walker Labor module (resources/views/labor/*)
 * doesn't use @vite() at all — it loads Alpine/Bootstrap/etc. via CDN
 * <script> tags in labor/layout.blade.php, same convention as the other
 * standalone scripts in this folder (quick-capture.js, financial-manager.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    const groups = document.querySelectorAll('.proworker-address-group');
    if (!groups.length) return;

    fetch('/thai-addresses')
        .then((r) => r.json())
        .then((data) => {
            groups.forEach((group) => initAddressGroup(group, data));
        })
        .catch((err) => console.error('Failed to load Thai address data', err));

    function initAddressGroup(group, thaiAddressData) {
        const id = group.dataset.group;
        const provinceSelect = document.getElementById('addrProvince_' + id);
        const districtSelect = document.getElementById('addrDistrict_' + id);
        const subDistrictSelect = document.getElementById('addrSubDistrict_' + id);
        const noInput = document.getElementById('addrNo_' + id);
        const mooInput = document.getElementById('addrMoo_' + id);
        const soiInput = document.getElementById('addrSoi_' + id);
        const roadInput = document.getElementById('addrRoad_' + id);
        const soiEnInput = document.getElementById('addrSoiEn_' + id);
        const roadEnInput = document.getElementById('addrRoadEn_' + id);
        const zipInput = document.getElementById('addrZipCode_' + id);
        const enPreview = document.getElementById('addrEnPreview_' + id);
        const composedTh = document.getElementById('composed_th_' + id);
        const composedEn = document.getElementById('composed_en_' + id);

        let selectedRow = null; // matched thaiAddressData row (province+district+subdistrict)

        const uniqueSorted = (values) => [...new Set(values)].sort((a, b) => a.localeCompare(b, 'th'));

        uniqueSorted(thaiAddressData.map((d) => d.province_th.trim())).forEach((p) => {
            provinceSelect.add(new Option(p, p));
        });

        function resetSelect(select, placeholder) {
            select.innerHTML = '';
            select.add(new Option(placeholder, ''));
            select.disabled = true;
        }

        provinceSelect.addEventListener('change', function () {
            resetSelect(districtSelect, '-- ' + (document.documentElement.lang === 'en' ? 'Select' : 'เลือก') + ' --');
            resetSelect(subDistrictSelect, '-- -- --');
            selectedRow = null;
            compose();

            const province = this.value.trim();
            if (!province) return;

            uniqueSorted(
                thaiAddressData.filter((d) => d.province_th.trim() === province).map((d) => d.district_th.trim())
            ).forEach((d) => districtSelect.add(new Option(d, d)));
            districtSelect.disabled = false;
        });

        districtSelect.addEventListener('change', function () {
            resetSelect(subDistrictSelect, '-- -- --');
            selectedRow = null;
            compose();

            const province = provinceSelect.value.trim();
            const district = this.value.trim();
            if (!district) return;

            uniqueSorted(
                thaiAddressData
                    .filter((d) => d.province_th.trim() === province && d.district_th.trim() === district)
                    .map((d) => d.subdistrict_th.trim())
            ).forEach((sd) => subDistrictSelect.add(new Option(sd, sd)));
            subDistrictSelect.disabled = false;
        });

        subDistrictSelect.addEventListener('change', function () {
            const province = provinceSelect.value.trim();
            const district = districtSelect.value.trim();
            const subdistrict = this.value.trim();

            selectedRow = thaiAddressData.find(
                (d) =>
                    d.province_th.trim() === province &&
                    d.district_th.trim() === district &&
                    d.subdistrict_th.trim() === subdistrict
            ) || null;

            zipInput.value = selectedRow ? selectedRow.zip_code : '';
            compose();
        });

        [noInput, mooInput, soiInput, roadInput, soiEnInput, roadEnInput].forEach((input) => {
            input.addEventListener('input', compose);
        });

        // Once a Thai Soi/Road is typed, its English counterpart becomes
        // required — prevents silently submitting an English address with
        // that detail missing (see compose(): a blank EN input is now
        // OMITTED rather than falling back to untranslated Thai text, so
        // this is what stops the omission from happening unnoticed).
        soiInput.addEventListener('input', () => { soiEnInput.required = !!soiInput.value.trim(); });
        roadInput.addEventListener('input', () => { roadEnInput.required = !!roadInput.value.trim(); });

        function compose() {
            const thParts = [
                noInput.value.trim() || null,
                mooInput.value.trim() ? 'หมู่ ' + mooInput.value.trim() : null,
                soiInput.value.trim() ? 'ซอย ' + soiInput.value.trim() : null,
                roadInput.value.trim() ? 'ถนน ' + roadInput.value.trim() : null,
                selectedRow ? 'ต.' + selectedRow.subdistrict_th.trim() : null,
                selectedRow ? 'อ.' + selectedRow.district_th.trim() : null,
                selectedRow ? 'จ.' + selectedRow.province_th.trim() : null,
                selectedRow ? String(selectedRow.zip_code) : null,
            ].filter(Boolean);

            const soiEn = soiEnInput.value.trim();
            const roadEn = roadEnInput.value.trim();
            const enParts = [
                noInput.value.trim() || null,
                mooInput.value.trim() ? 'Moo ' + mooInput.value.trim() : null,
                soiEn ? 'Soi ' + soiEn : null,
                roadEn ? 'Road ' + roadEn : null,
                selectedRow ? selectedRow.subdistrict_en.trim() : null,
                selectedRow ? selectedRow.district_en.trim() : null,
                selectedRow ? selectedRow.province_en.trim() : null,
                selectedRow ? String(selectedRow.zip_code) : null,
            ].filter(Boolean);

            composedTh.value = thParts.join(' ');
            composedEn.value = enParts.join(', ');
            enPreview.textContent = composedEn.value;
        }

        prefillFromData();

        /**
         * Restores this group's dropdowns/inputs from a previously-issued
         * contract's stored address parts (see contracts/edit.blade.php +
         * _address_group.blade.php's $prefill prop) — replays the same
         * cascading change events a user triggers by hand, since province/
         * district/subdistrict options only exist after their parent
         * selection fires. Contracts issued before this field existed have
         * no stored parts, so this is a no-op and the picker just starts empty.
         */
        function prefillFromData() {
            let data;
            try {
                data = JSON.parse(group.dataset.prefill || 'null');
            } catch (e) {
                data = null;
            }
            if (!data) return;

            if (data.province) {
                provinceSelect.value = data.province;
                provinceSelect.dispatchEvent(new Event('change'));
            }
            if (data.district) {
                districtSelect.value = data.district;
                districtSelect.dispatchEvent(new Event('change'));
            }
            if (data.subdistrict) {
                subDistrictSelect.value = data.subdistrict;
                subDistrictSelect.dispatchEvent(new Event('change'));
            }
            noInput.value = data.no || '';
            mooInput.value = data.moo || '';
            soiInput.value = data.soi || '';
            roadInput.value = data.road || '';
            soiEnInput.value = data.soi_en || '';
            roadEnInput.value = data.road_en || '';
            soiEnInput.required = !!soiInput.value.trim();
            roadEnInput.required = !!roadInput.value.trim();
            compose();
        }
    }
});
