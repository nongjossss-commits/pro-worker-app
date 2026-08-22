/**
 * Soft duplicate-data warning — hooks any <form data-duplicate-check-url="...">
 * so its submit button first asks the server whether the identity fields
 * being saved (passport, work permit, pink card, labor ID, employer tax ID,
 * ...) already belong to another record, and if so shows a SweetAlert2
 * comparison popup before letting the save go through.
 *
 * Intercepts the submit BUTTON's click (delegated on `document`, capture
 * phase, preventDefault on the click itself) rather than the form's
 * 'submit' event, for two reasons: (1) it works transparently in front of
 * whatever a given page already does on submit — a plain native POST
 * (employees/employers create) or a custom AJAX fetch() handler
 * (employers/edit.blade.php, the edit-employee-modal's injected form) —
 * without racing another 'submit' listener on the same form; (2) the
 * employee edit form is also loaded dynamically into a modal via
 * innerHTML (see edit-employee-modal.blade.php), long after
 * DOMContentLoaded, so a one-time querySelectorAll scan would miss it —
 * delegation on `document` catches it regardless of when it's injected.
 * Once the check passes (or the user confirms "save anyway"), it calls
 * form.requestSubmit()/.submit(), which fires the real 'submit' event
 * exactly once and hands off to whatever that page normally does.
 */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('button[type="submit"], input[type="submit"]');
    if (!btn) return;

    const form = btn.closest('form[data-duplicate-check-url]');
    if (!form) return;

    if (form.dataset.dupCheckPassed === '1') {
        return;
    }
    e.preventDefault();
    e.stopPropagation();
    runDuplicateCheck(form);
}, true);

function runDuplicateCheck(form) {
    const fields = JSON.parse(form.dataset.duplicateFields || '[]');
    const checkUrl = form.dataset.duplicateCheckUrl;
    const excludeId = form.dataset.duplicateExcludeId || '';
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');

    const payload = new URLSearchParams();
    fields.forEach(function (name) {
        const input = form.querySelector('[name="' + name + '"]');
        payload.append(name, input ? input.value : '');
    });
    if (excludeId) {
        payload.append('exclude_id', excludeId);
    }

    fetch(checkUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.getAttribute('content') : '',
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: payload.toString(),
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            const duplicates = data.duplicates || [];
            if (duplicates.length === 0) {
                proceed(form);
                return;
            }
            showDuplicateWarning(form, duplicates);
        })
        .catch(function () {
            // A failed check (network hiccup, etc.) must never block a
            // real save — this is a UX safety net, not a hard gate.
            proceed(form);
        });
}

function proceed(form) {
    form.dataset.dupCheckPassed = '1';
    if (form.requestSubmit) {
        form.requestSubmit();
    } else {
        form.submit();
    }
    setTimeout(function () { form.dataset.dupCheckPassed = '0'; }, 3000);
}

function showDuplicateWarning(form, duplicates) {
    const modelType = form.dataset.duplicateModelType;
    const blocking = duplicates.some(function (d) { return d.blocking; });
    const labels = {
        title: form.dataset.duplicateLabelTitle || 'Duplicate data found',
        proceed: form.dataset.duplicateLabelProceed || 'Save anyway',
        fix: form.dataset.duplicateLabelFix || 'Cancel, fix data first',
        ok: form.dataset.duplicateLabelOk || 'OK',
        suffixHint: form.dataset.duplicateLabelSuffixHint || '',
        terminated: form.dataset.duplicateLabelTerminated || 'Terminated',
    };

    const rows = duplicates.map(function (d) {
        const r = d.record;
        const photo = r.photo_url
            ? '<img src="' + r.photo_url + '" style="width:48px;height:48px;object-fit:cover;border-radius:50%;flex-shrink:0;" class="me-2">'
            : '';
        const sub = r.employer_name || r.business_type || '';
        const terminatedBadge = r.terminated
            ? ' <span class="badge bg-secondary">' + escapeHtml(labels.terminated) + '</span>'
            : '';

        return '' +
            '<div class="d-flex align-items-center gap-2 border rounded p-2 mb-2 text-start">' +
                photo +
                '<div class="flex-grow-1 min-w-0">' +
                    '<div class="fw-bold">' + escapeHtml(r.name) + terminatedBadge + '</div>' +
                    (sub ? '<div class="small text-muted">' + escapeHtml(sub) + '</div>' : '') +
                    '<div class="small">' + escapeHtml(d.label) + ': <strong>' + escapeHtml(d.value) + '</strong></div>' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-info flex-shrink-0 btn-preview" data-model-type="' + modelType + '" data-model-id="' + r.id + '" title="' + escapeHtml(labels.ok) + '">' +
                    '<i class="bi bi-search"></i>' +
                '</button>' +
                '<a href="' + r.edit_url + '" target="_blank" class="btn btn-sm btn-outline-secondary flex-shrink-0"><i class="bi bi-pencil"></i></a>' +
            '</div>';
    }).join('');

    const suffixHint = (modelType === 'employer' && labels.suffixHint)
        ? '<p class="small text-muted text-start mt-2 mb-0">' + escapeHtml(labels.suffixHint) + '</p>'
        : '';

    Swal.fire({
        icon: blocking ? 'error' : 'warning',
        title: labels.title,
        html: '<div class="text-start">' + rows + '</div>' + suffixHint,
        showCancelButton: !blocking,
        confirmButtonText: blocking ? labels.ok : labels.proceed,
        cancelButtonText: labels.fix,
        focusCancel: !blocking,
        reverseButtons: true,
        width: 600,
    }).then(function (result) {
        if (!blocking && result.isConfirmed) {
            proceed(form);
        }
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str === null || str === undefined ? '' : String(str);
    return div.innerHTML;
}
