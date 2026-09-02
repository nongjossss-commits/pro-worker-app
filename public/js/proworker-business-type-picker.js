// Populates every "ประเภทกิจการ" (Business Type) dropdown on a Pro Worker
// contract issuance/correction form from the same App\Models\BusinessType
// list already used on the Employer create/edit forms, and auto-fills the
// paired Thai/English hidden inputs when a selection is made — mirrors
// proworker-address-picker.js's role for the Address tool, just simpler
// since there's nothing to compose (one lookup row already has both
// languages). See resources/views/labor/_business_type_group.blade.php.
document.addEventListener('DOMContentLoaded', function () {
    const selects = document.querySelectorAll('.proworker-business-type-select');
    if (!selects.length) return;

    fetch(window.proworkerBusinessTypesUrl)
        .then(response => response.json())
        .then(data => {
            selects.forEach(select => {
                const groupId = select.closest('[data-group]')?.dataset.group;
                if (!groupId) return;

                const thInput = document.getElementById('bizTypeTh_' + groupId);
                const enInput = document.getElementById('bizTypeEn_' + groupId);
                const currentTh = thInput ? thInput.value : '';

                select.innerHTML = '<option value="">' + (window.proworkerSelectLabel || 'Select...') + '</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name_th + ' / ' + item.name_en;
                    option.dataset.th = item.name_th;
                    option.dataset.en = item.name_en;
                    // Restore the previous selection (edit form) by matching
                    // the already-submitted Thai value, since field_values
                    // only ever stores the composed text, not the business_types.id.
                    if (currentTh && item.name_th === currentTh) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });

                select.addEventListener('change', function () {
                    const opt = select.options[select.selectedIndex];
                    if (thInput) thInput.value = opt.dataset.th || '';
                    if (enInput) enInput.value = opt.dataset.en || '';
                });
            });
        });
});
