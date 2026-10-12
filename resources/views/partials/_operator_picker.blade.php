{{--
  Shared "ผู้ดำเนินการ" (operator) picker for the operator button at the
  bottom-right of employee / job cards — Registration & Renewal Resolution,
  Pre-Production and Workflow all use this one, so every menu behaves the same:
  pick a user, or "ผู้ยื่นภายนอก" and type any name, or clear it.

  window.assignOperator({
      url:            POST endpoint (…/toggle-operator) — accepts operator_id / custom_operator_name,
      currentId:      current operator user id ('' if none),
      currentCustom:  current typed name ('' if none),
      button:         the operator button (updated in place when no card HTML comes back),
      onSaved(res):   optional — e.g. replace the whole card with res.html
  })

  The endpoint answers { success, message, operator: {id, name, custom, is_me}, html? }.
--}}
@php
    $opI18n = [
        'loading' => __('Loading...'),
        'title' => __('Assign Operator'),
        'select' => __('Select Operator'),
        'none' => __('None / Clear Operator'),
        'external' => __('Outside person (type a name)'),
        'externalLabel' => __('Operator name'),
        'externalPlaceholder' => __('Type the operator name...'),
        'externalRequired' => __('Please type the operator name.'),
        'save' => __('Save'),
        'cancel' => __('Cancel'),
        'error' => __('Error'),
        'loadFailed' => __('Could not load operators list.'),
        'outside' => __('outside'),
        'assignHint' => __('Click to Assign'),
    ];
@endphp
<script>
(function () {
    const T = @json($opI18n);
    const LIST_URL = @json(route('api-web.operators.list'));
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    // Show the saved operator on the button itself (same look as the cards render it).
    window.renderOperatorButton = function (btn, op) {
        if (!btn) return;
        op = op || {};
        const label = op.custom ? `${op.custom} (${T.outside})` : (op.name || '');
        btn.classList.remove('btn-outline-secondary', 'btn-primary', 'btn-secondary', 'btn-info', 'text-dark');
        if (op.custom) btn.classList.add('btn-info', 'text-dark');
        else if (op.id) btn.classList.add(op.is_me ? 'btn-primary' : 'btn-secondary');
        else btn.classList.add('btn-outline-secondary');
        btn.innerHTML = '<i class="bi bi-person-badge-fill"></i>' + (label ? ` <span class="ms-1 fw-bold">${esc(label)}</span>` : '');
        btn.title = label ? 'Operator: ' + label : T.assignHint;
        btn.dataset.operatorId = op.id || '';
        btn.dataset.operatorCustom = op.custom || '';
    };

    window.assignOperator = function (opts) {
        const btn = opts.button || null;
        // The button remembers the latest choice, so reopening shows it even before the card reloads.
        const currentId = String(btn && btn.dataset.operatorId !== undefined ? btn.dataset.operatorId : (opts.currentId || ''));
        const currentCustom = String(btn && btn.dataset.operatorCustom !== undefined ? btn.dataset.operatorCustom : (opts.currentCustom || ''));
        const uid = 'op' + Math.random().toString(36).slice(2, 8);

        Swal.fire({ title: T.loading, allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch(LIST_URL, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error('operators');
                let options = `<option value="">-- ${esc(T.none)} --</option>`;
                options += `<option value="external" ${currentCustom ? 'selected' : ''}>-- ${esc(T.external)} --</option>`;
                (data.data || []).forEach(op => {
                    const sel = (!currentCustom && String(op.id) === currentId) ? 'selected' : '';
                    options += `<option value="${esc(op.id)}" ${sel}>${esc(op.name)}</option>`;
                });

                Swal.fire({
                    title: T.title,
                    html: `<div class="text-start">
                             <label class="form-label mb-2" for="${uid}-select">${esc(T.select)}</label>
                             <select id="${uid}-select" class="form-select form-select-lg mb-3">${options}</select>
                             <div id="${uid}-custom" style="display:${currentCustom ? 'block' : 'none'}">
                               <label class="form-label mb-2" for="${uid}-name">${esc(T.externalLabel)}</label>
                               <input type="text" id="${uid}-name" class="form-control form-control-lg" maxlength="255"
                                      placeholder="${esc(T.externalPlaceholder)}" value="${esc(currentCustom)}">
                             </div>
                           </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: T.save,
                    cancelButtonText: T.cancel,
                    showLoaderOnConfirm: true,
                    didOpen: () => {
                        const sel = document.getElementById(uid + '-select');
                        const box = document.getElementById(uid + '-custom');
                        sel.addEventListener('change', () => {
                            box.style.display = sel.value === 'external' ? 'block' : 'none';
                            if (sel.value === 'external') document.getElementById(uid + '-name').focus();
                        });
                    },
                    preConfirm: () => {
                        const value = document.getElementById(uid + '-select').value;
                        let custom = null;
                        if (value === 'external') {
                            custom = document.getElementById(uid + '-name').value.trim();
                            if (!custom) { Swal.showValidationMessage(T.externalRequired); return false; }
                        }
                        return fetch(opts.url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ operator_id: value === 'external' ? null : value, custom_operator_name: custom }),
                        })
                            .then(r => r.ok ? r.json() : r.json().catch(() => ({})).then(d => Promise.reject(new Error(d.message || r.statusText))))
                            .catch(err => { Swal.showValidationMessage(err.message); });
                    },
                    allowOutsideClick: () => !Swal.isLoading(),
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const res = result.value;
                    if (!res || !res.success) { Swal.fire(T.error, (res && res.message) || 'Unknown error', 'error'); return; }
                    if (res.operator) window.renderOperatorButton(btn, res.operator);
                    if (typeof opts.onSaved === 'function') opts.onSaved(res);
                    if (typeof window.appToast === 'function') window.appToast(res.message);
                    else if (typeof window.showToast === 'function') window.showToast(res.message, 'success');
                });
            })
            .catch(() => Swal.fire(T.error, T.loadFailed, 'error'));
    };
})();
</script>
