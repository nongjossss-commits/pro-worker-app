{{--
    Shared popup theme (SweetAlert2 + Bootstrap modals), included by
    layouts/app.blade.php and labor/layout.blade.php right after their brand
    :root block. Uses only the brand CSS variables those layouts already
    define (--bs-primary, --bs-primary-rgb, --bs-primary-dark), so a colour
    change in Super Admin → Branding re-themes every popup.

    Explicit per-call options still win: a Swal.fire({ confirmButtonColor })
    sets an inline style, and a .modal-header that already has its own bg-*
    class or inline style is left exactly as it was designed.
--}}
<style>
    /* ---------- SweetAlert2 ---------- */
    .swal2-popup {
        font-family: var(--bs-body-font-family, 'Inter', 'Sarabun', sans-serif);
        border-radius: 18px;
        padding: 1.6rem 1.4rem 1.4rem;
        box-shadow: 0 24px 60px -18px rgba(15, 23, 42, .35);
    }
    .swal2-title { font-size: 1.25rem; font-weight: 700; color: #0f172a; padding-top: .4rem; }
    .swal2-html-container { font-size: .95rem; line-height: 1.6; color: #475569; }
    .swal2-actions { gap: .5rem; margin-top: 1.25rem; }
    .swal2-styled {
        margin: 0 !important;
        border-radius: 10px !important;
        font-weight: 600;
        padding: .6rem 1.35rem;
        min-width: 110px;
    }
    .swal2-styled.swal2-confirm { background-color: var(--bs-primary); }
    .swal2-styled.swal2-confirm:hover { background-color: var(--bs-primary-dark, var(--bs-primary)); background-image: none; }
    .swal2-styled.swal2-deny { background-color: #dc2626; }
    .swal2-styled.swal2-cancel { background-color: #fff; color: #475569; border: 1px solid #e2e8f0; }
    .swal2-styled.swal2-cancel:hover { background-color: #f8fafc; background-image: none; color: #0f172a; }
    .swal2-styled:focus, .swal2-styled:focus-visible { box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .3) !important; }
    /* Destructive confirm (appConfirm(..., { danger: true }) / data-confirm-danger) */
    .swal2-popup.app-swal-danger .swal2-styled.swal2-confirm { background-color: #dc2626; }
    .swal2-popup.app-swal-danger .swal2-styled.swal2-confirm:hover { background-color: #b91c1c; }
    .swal2-popup.app-swal-danger .swal2-styled.swal2-confirm:focus-visible { box-shadow: 0 0 0 3px rgba(220, 38, 38, .3) !important; }
    .swal2-icon { margin-top: .75rem; transform: scale(.85); }
    .swal2-icon.swal2-question, .swal2-icon.swal2-info { border-color: rgba(var(--bs-primary-rgb), .35); color: var(--bs-primary); }
    .swal2-input, .swal2-textarea, .swal2-select, .swal2-file {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: none;
        font-size: 1rem;
    }
    .swal2-input:focus, .swal2-textarea:focus, .swal2-select:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .15);
    }
    .swal2-validation-message { border-radius: 10px; background: #fef2f2; color: #b91c1c; font-size: .9rem; }
    .swal2-timer-progress-bar { background: rgba(var(--bs-primary-rgb), .55); }
    .swal2-popup.swal2-toast { border-radius: 12px; padding: .75rem 1rem; box-shadow: 0 12px 30px -10px rgba(15, 23, 42, .3); }
    .swal2-popup.swal2-toast .swal2-title { font-size: .95rem; padding-top: 0; }
    .swal2-popup.swal2-toast .swal2-icon { transform: none; margin-top: 0; }

    /* ---------- Bootstrap modals ---------- */
    .modal {
        --bs-modal-border-radius: 16px;
        --bs-modal-inner-border-radius: 15px;
        --bs-modal-border-color: transparent;
        --bs-modal-box-shadow: 0 24px 60px -18px rgba(15, 23, 42, .4);
    }
    .modal-content { box-shadow: var(--bs-modal-box-shadow); }
    .modal-backdrop.show { opacity: .45; }
    /* Plain (not yet designed) headers only: brand top bar + soft tint. */
    .modal-header:not([class*="bg-"]):not([style]):not(.border-0):not(.text-white) {
        background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), .07), rgba(var(--bs-primary-rgb), 0));
        box-shadow: inset 0 4px 0 var(--bs-primary);
        border-bottom-color: #eef2f7;
        padding-top: 1.1rem;
    }
    .modal-header:not([class*="bg-"]):not([style]):not(.border-0):not(.text-white) .modal-title { font-weight: 700; color: #0f172a; }
    .modal-footer { border-top-color: #eef2f7; }
</style>
