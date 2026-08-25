/**
 * Cascading Thai address picker for Pro Worker contract issuance —
 * parameterized variant of resources/js/address-handler.js (which is
 * hard-coded to one fixed set of element IDs and can't run twice on the
 * same page). A contract template may place the "Address" tool more than
 * once, so this wires up N independent .proworker-address-group blocks
 * from a single shared fetch of /thai-addresses (same endpoint, same
 * dataset — see routes/web.php AddressController@getThaiAddressData).
 *
 * Province/District/Subdistrict are typeable+searchable (HTML5 <input
 * list="..."> + <datalist>) instead of plain <select> — with 77 provinces
 * and thousands of districts/subdistricts, scrolling a long dropdown was
 * the exact complaint this replaces; typing filters natively in every
 * modern browser with zero extra dependency. Each visible text input pairs
 * with a HIDDEN input (fields[{groupId}_province] etc.) that only gets a
 * value once the typed text exactly matches a real option — the composed
 * address, and everything downstream, is only ever built from a confirmed
 * match, never from free-typed text.
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
        const provinceInput = document.getElementById('addrProvince_' + id);
        const provinceList = document.getElementById('addrProvinceList_' + id);
        const provinceValue = document.getElementById('addrProvinceValue_' + id);
        const districtInput = document.getElementById('addrDistrict_' + id);
        const districtList = document.getElementById('addrDistrictList_' + id);
        const districtValue = document.getElementById('addrDistrictValue_' + id);
        const subDistrictInput = document.getElementById('addrSubDistrict_' + id);
        const subDistrictList = document.getElementById('addrSubDistrictList_' + id);
        const subDistrictValue = document.getElementById('addrSubDistrictValue_' + id);
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

        const notMatchedMsg = document.documentElement.lang === 'en'
            ? 'Please pick a suggestion from the list.'
            : 'กรุณาเลือกจากรายการที่แนะนำ';

        function fillDatalist(datalistEl, values) {
            datalistEl.innerHTML = '';
            values.forEach((v) => {
                const opt = document.createElement('option');
                opt.value = v;
                datalistEl.appendChild(opt);
            });
        }

        function clearField(textInput, hiddenInput, datalistEl, disable) {
            textInput.value = '';
            hiddenInput.value = '';
            textInput.setCustomValidity('');
            if (datalistEl) datalistEl.innerHTML = '';
            textInput.disabled = !!disable;
        }

        fillDatalist(provinceList, uniqueSorted(thaiAddressData.map((d) => d.province_th.trim())));

        provinceInput.addEventListener('input', function () {
            clearField(districtInput, districtValue, districtList, true);
            clearField(subDistrictInput, subDistrictValue, subDistrictList, true);
            selectedRow = null;
            zipInput.value = '';
            compose();

            const typed = this.value.trim();
            const options = uniqueSorted(thaiAddressData.map((d) => d.province_th.trim()));
            if (!options.includes(typed)) {
                provinceValue.value = '';
                this.setCustomValidity(typed ? notMatchedMsg : '');
                return;
            }

            this.setCustomValidity('');
            provinceValue.value = typed;
            fillDatalist(districtList, uniqueSorted(
                thaiAddressData.filter((d) => d.province_th.trim() === typed).map((d) => d.district_th.trim())
            ));
            districtInput.disabled = false;
        });

        districtInput.addEventListener('input', function () {
            clearField(subDistrictInput, subDistrictValue, subDistrictList, true);
            selectedRow = null;
            zipInput.value = '';
            compose();

            const province = provinceValue.value;
            const typed = this.value.trim();
            const options = uniqueSorted(
                thaiAddressData.filter((d) => d.province_th.trim() === province).map((d) => d.district_th.trim())
            );
            if (!options.includes(typed)) {
                districtValue.value = '';
                this.setCustomValidity(typed ? notMatchedMsg : '');
                return;
            }

            this.setCustomValidity('');
            districtValue.value = typed;
            fillDatalist(subDistrictList, uniqueSorted(
                thaiAddressData
                    .filter((d) => d.province_th.trim() === province && d.district_th.trim() === typed)
                    .map((d) => d.subdistrict_th.trim())
            ));
            subDistrictInput.disabled = false;
        });

        subDistrictInput.addEventListener('input', function () {
            const province = provinceValue.value;
            const district = districtValue.value;
            const typed = this.value.trim();

            const match = thaiAddressData.find(
                (d) =>
                    d.province_th.trim() === province &&
                    d.district_th.trim() === district &&
                    d.subdistrict_th.trim() === typed
            ) || null;

            if (!match) {
                subDistrictValue.value = '';
                this.setCustomValidity(typed ? notMatchedMsg : '');
                selectedRow = null;
                zipInput.value = '';
                compose();
                return;
            }

            this.setCustomValidity('');
            subDistrictValue.value = typed;
            selectedRow = match;
            zipInput.value = match.zip_code;
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
         * Restores this group's inputs from a previously-issued contract's
         * stored address parts (see contracts/edit.blade.php +
         * _address_group.blade.php's $prefill prop). Unlike a live typed
         * match, prefill data is already known-good (it came from a
         * confirmed selection when the contract was first issued), so this
         * sets each level's visible+hidden values directly and rebuilds
         * its datalist options itself rather than replaying `input` events.
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
                provinceInput.value = data.province;
                provinceValue.value = data.province;
                districtInput.disabled = false;
                fillDatalist(districtList, uniqueSorted(
                    thaiAddressData.filter((d) => d.province_th.trim() === data.province).map((d) => d.district_th.trim())
                ));
            }
            if (data.district) {
                districtInput.value = data.district;
                districtValue.value = data.district;
                subDistrictInput.disabled = false;
                fillDatalist(subDistrictList, uniqueSorted(
                    thaiAddressData
                        .filter((d) => d.province_th.trim() === data.province && d.district_th.trim() === data.district)
                        .map((d) => d.subdistrict_th.trim())
                ));
            }
            if (data.subdistrict) {
                subDistrictInput.value = data.subdistrict;
                subDistrictValue.value = data.subdistrict;
                selectedRow = thaiAddressData.find(
                    (d) =>
                        d.province_th.trim() === data.province &&
                        d.district_th.trim() === data.district &&
                        d.subdistrict_th.trim() === data.subdistrict
                ) || null;
                zipInput.value = selectedRow ? selectedRow.zip_code : '';
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
