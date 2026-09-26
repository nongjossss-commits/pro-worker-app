{{--
    Shared popup helpers — include AFTER sweetalert2 is loaded
    (layouts/app.blade.php, labor/layout.blade.php).

      appConfirm(message, { title, danger, confirmText, cancelText }) -> Promise<boolean>
      appAlert(message, { title, icon })                            -> Promise
      appToast(message, icon = 'success')

    Declarative confirm — replaces the old inline `return confirm('...')` handlers:
      <form data-confirm="Delete this?" data-confirm-danger> ... </form>
      <button type="submit" data-confirm="Issue this invoice?"> / <a data-confirm="...">

    window.alert() is routed to a themed popup too, so the old plain
    browser alert boxes across the app (and public/js) follow the brand.
    Everything falls back to the native browser dialog if SweetAlert2
    isn't available on the page.
--}}
@php
    // Built here, not inline in @json([...]) — Blade's @json can't parse a
    // multi-line array argument.
    $uiThemeLabels = [
        'ok' => __('OK'),
        'confirm' => __('Confirm'),
        'cancel' => __('Cancel'),
        'delete' => __('Delete'),
        'pleaseConfirm' => __('Please confirm'),
        'confirmDelete' => __('Confirm deletion'),
        'notice' => __('Notice'),
        'error' => __('Error'),
    ];
@endphp
<script>
(function () {
    if (window.appConfirm) return; // included once

    const T = @json($uiThemeLabels);
    const nativeAlert = window.alert.bind(window);
    const nativeConfirm = window.confirm.bind(window);
    const hasSwal = () => typeof window.Swal !== 'undefined' && typeof window.Swal.fire === 'function';

    window.appConfirm = function (message, opts) {
        opts = opts || {};
        if (!hasSwal()) return Promise.resolve(nativeConfirm(message));
        const danger = !!opts.danger;
        return Swal.fire({
            icon: danger ? 'warning' : 'question',
            title: opts.title || (danger ? T.confirmDelete : T.pleaseConfirm),
            text: message,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || (danger ? T.delete : T.confirm),
            cancelButtonText: opts.cancelText || T.cancel,
            reverseButtons: true,
            focusCancel: danger,
            customClass: { popup: danger ? 'app-swal-danger' : '' },
        }).then(function (r) { return !!r.isConfirmed; });
    };

    window.appAlert = function (message, opts) {
        opts = opts || {};
        if (!hasSwal()) { nativeAlert(message); return Promise.resolve(); }
        const text = message == null ? '' : String(message);
        let icon = opts.icon;
        if (!icon) {
            icon = /error|fail|ผิดพลาด|ไม่สามารถ|cannot|can't|missing|not loaded|ไม่รองรับ|ล้มเหลว/i.test(text) ? 'error'
                : /success|saved|สำเร็จ|เรียบร้อย/i.test(text) ? 'success'
                : 'info';
        }
        return Swal.fire({
            icon: icon,
            title: opts.title || (icon === 'error' ? T.error : ''),
            text: text,
            confirmButtonText: T.ok,
        });
    };

    window.appToast = function (message, icon) {
        if (!hasSwal()) { nativeAlert(message); return; }
        Swal.fire({
            toast: true, position: 'top-end', icon: icon || 'success', title: message,
            showConfirmButton: false, timer: 2500, timerProgressBar: true,
        });
    };

    // Route legacy alert() calls to the themed popup (non-blocking — every
    // call site was checked: none depends on alert() pausing execution).
    window.alert = function (message) {
        if (!hasSwal()) return nativeAlert(message);
        window.appAlert(message);
    };

    function isDanger(el) {
        return el.hasAttribute('data-confirm-danger');
    }

    // <form data-confirm="..."> — ask before submitting.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        const submitter = e.submitter || null;
        window.appConfirm(form.getAttribute('data-confirm'), { danger: isDanger(form) }).then(function (ok) {
            if (!ok) return;
            form.dataset.confirmed = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
            } else {
                form.submit();
            }
        });
    }, true);

    // <button|a data-confirm="..."> — ask before the click goes through
    // (keeps the clicked submit button's name/value, e.g. action_issue=1).
    document.addEventListener('click', function (e) {
        const el = e.target.closest('button[data-confirm], a[data-confirm], input[type="submit"][data-confirm]');
        if (!el) return;
        if (el.dataset.confirmed === '1') { delete el.dataset.confirmed; return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        window.appConfirm(el.getAttribute('data-confirm'), { danger: isDanger(el) }).then(function (ok) {
            if (!ok) return;
            el.dataset.confirmed = '1';
            // The button itself was confirmed — don't ask again for its form.
            if (el.form && el.form.hasAttribute('data-confirm')) el.form.dataset.confirmed = '1';
            el.click();
        });
    }, true);
})();
</script>
