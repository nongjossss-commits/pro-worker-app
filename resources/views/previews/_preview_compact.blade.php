{{--
    Shared look of the employee + employer previews (universal preview modal):
      * all data on one tab, fields flowing into as many columns as the
        window holds (.pv-grid) — short / normal / long / full-row fields
      * frequently used numbers and dates highlighted (.pv-key)
      * a copy button after every filled value (.pv-copy, handler in layouts/_app_scripts)
      * attached files as compact chips (.pv-files)
      * a wider, nearly full-height window
    Scoped under .emp-preview / .empr-preview so nothing leaks to other modals.
--}}
<style>
    :is(.emp-preview, .empr-preview) .preview-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #f97316;
        border-left: 4px solid #f97316;
        padding-left: 8px;
        margin: 0 0 .5rem;
        letter-spacing: 0.2px;
    }
    :is(.emp-preview, .empr-preview) .preview-hr {
        border-top: 1px dashed #e5e7eb;
        margin: .65rem 0 .7rem;
    }
    :is(.emp-preview, .empr-preview) .preview-section-header { padding: 0; position: static; background: transparent; }
    /* Fields flow into as many columns as the window holds: every field takes
       2 small columns, short ones (age, height…) 1, long ones (names,
       hospital…) 3, a few the full row. Columns stretch to fill each row. */
    :is(.emp-preview, .empr-preview) .pv-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(5rem, 1fr));
        gap: .55rem .9rem;
    }
    :is(.emp-preview, .empr-preview) .pv-grid > .preview-field { grid-column: span 2; }
    :is(.emp-preview, .empr-preview) .pv-grid > .pv-s { grid-column: span 1; }
    :is(.emp-preview, .empr-preview) .pv-grid > .pv-l { grid-column: span 3; }
    :is(.emp-preview, .empr-preview) .pv-grid > .pv-full { grid-column: 1 / -1; }
    :is(.emp-preview, .empr-preview) .preview-field { min-width: 0; margin: 0; }
    :is(.emp-preview, .empr-preview) .preview-label {
        display: block;
        font-size: 0.75rem;
        color: #6b7280;
        line-height: 1.2;
        margin-bottom: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    :is(.emp-preview, .empr-preview) .preview-value {
        display: block;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.3;
        color: #111827;
        word-break: break-word;
        min-height: 1.25rem;
    }
    :is(.emp-preview, .empr-preview) .preview-value.text-muted-empty {
        color: #9ca3af;
        font-weight: 400;
    }
    /* Frequently used numbers / dates (passport, visa, work permit, ID, expiry…) */
    :is(.emp-preview, .empr-preview) .pv-key {
        background: rgba(var(--bs-primary-rgb), .07);
        border-left: 3px solid var(--bs-primary);
        border-radius: .375rem;
        padding: .2rem .45rem .25rem;
    }
    :is(.emp-preview, .empr-preview) .pv-key .preview-label { color: var(--bs-primary); font-weight: 600; }
    :is(.emp-preview, .empr-preview) .pv-key .preview-value:not(.text-muted-empty) { font-weight: 700; }
    /* Copy button after each filled value — one click copies the value. */
    :is(.emp-preview, .empr-preview) .pv-copy {
        display: inline-flex; align-items: center; justify-content: center;
        width: 1.45rem; height: 1.45rem; margin-left: .3rem; padding: 0;
        vertical-align: text-bottom;
        border: 1px solid var(--bs-border-color); border-radius: .35rem;
        background: var(--bs-body-bg); color: var(--bs-secondary-color);
        font-size: .78rem; line-height: 1; cursor: pointer;
        opacity: .55; transition: opacity .12s, color .12s, border-color .12s;
    }
    :is(.emp-preview, .empr-preview) .preview-field:hover .pv-copy,
    :is(.emp-preview, .empr-preview) .pv-copy:focus-visible { opacity: 1; }
    :is(.emp-preview, .empr-preview) .pv-copy:hover { color: var(--bs-primary); border-color: var(--bs-primary); }
    :is(.emp-preview, .empr-preview) .pv-copy.is-copied { opacity: 1; color: var(--bs-success); border-color: var(--bs-success); }
    @media (hover: none) {
        :is(.emp-preview, .empr-preview) .pv-copy { opacity: .9; width: 1.75rem; height: 1.75rem; }
    }
    @media (min-width: 992px) {
        :is(.emp-preview, .empr-preview) .preview-label { font-size: 0.82rem; color: #4b5563; }
        :is(.emp-preview, .empr-preview) .preview-value { font-size: 1.02rem; }
        :is(.emp-preview, .empr-preview) .pv-grid { grid-template-columns: repeat(auto-fill, minmax(5.5rem, 1fr)); gap: .7rem 1.1rem; }
    }
    /* Phones: two even columns; long fields take the whole row. */
    @media (max-width: 575.98px) {
        :is(.emp-preview, .empr-preview) .pv-grid { grid-template-columns: 1fr 1fr; }
        :is(.emp-preview, .empr-preview) .pv-grid > .preview-field { grid-column: span 1; }
        :is(.emp-preview, .empr-preview) .pv-grid > :is(.pv-l, .pv-full) { grid-column: 1 / -1; }
    }
    /* Small View / PDF buttons */
    :is(.emp-preview, .empr-preview) .preview-attach-btn {
        padding: 1px 8px;
        font-size: 0.78rem;
        line-height: 1.5;
    }
    /* Attached files: compact chips — name on the left, View / PDF on the right; empty slots faded. */
    :is(.emp-preview, .empr-preview) .pv-files {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr));
        gap: .45rem .6rem;
    }
    :is(.emp-preview, .empr-preview) .pv-file {
        display: flex; align-items: center; gap: .5rem; min-width: 0;
        padding: .4rem .55rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
        background: var(--bs-body-bg);
    }
    :is(.emp-preview, .empr-preview) .pv-file.has-file { border-color: rgba(var(--bs-success-rgb), .45); background: rgba(var(--bs-success-rgb), .06); }
    :is(.emp-preview, .empr-preview) .pv-file.is-empty { border-style: dashed; opacity: .7; }
    :is(.emp-preview, .empr-preview) .pv-file-icon { flex: none; font-size: 1.1rem; color: var(--bs-secondary-color); }
    :is(.emp-preview, .empr-preview) .pv-file.has-file .pv-file-icon { color: var(--bs-success); }
    :is(.emp-preview, .empr-preview) .pv-file-body { flex: 1 1 auto; min-width: 0; }
    :is(.emp-preview, .empr-preview) .pv-file-name {
        flex: 1 1 auto; min-width: 0; font-size: .86rem; font-weight: 600; line-height: 1.25;
        overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; word-break: break-word;
    }
    :is(.emp-preview, .empr-preview) .pv-file-meta { font-size: .75rem; color: var(--bs-secondary-color); }
    :is(.emp-preview, .empr-preview) .pv-file.is-empty .pv-file-name { font-weight: 400; }
    :is(.emp-preview, .empr-preview) .pv-file-actions { flex: none; display: flex; gap: .25rem; }
    :is(.emp-preview, .empr-preview) .pv-file-empty { flex: none; font-size: .75rem; color: var(--bs-secondary-color); }
    @media (max-width: 575.98px) {
        :is(.emp-preview, .empr-preview) .pv-files { grid-template-columns: 1fr; }
    }
    /* Less frame padding around the record inside the modal (was 2rem). */
    #universalPreviewModal .modal-body:has(> :is(.emp-preview, .empr-preview)) { padding: .75rem; }
    .emp-preview.content-section { padding: 1rem 1.25rem; }
    @media (max-width: 575.98px) {
        #universalPreviewModal .modal-body:has(> :is(.emp-preview, .empr-preview)) { padding: .5rem; }
        .emp-preview.content-section { padding: .75rem; }
    }
    /* Bigger window: wider, nearly the full screen height. */
    @media (min-width: 576px) {
        #universalPreviewModal .modal-dialog:has(:is(.emp-preview, .empr-preview)) { max-width: min(96vw, 1600px); margin-top: .75rem; margin-bottom: .75rem; }
        #universalPreviewModal .modal-content:has(:is(.emp-preview, .empr-preview)) { height: calc(100vh - 1.5rem); }
    }
</style>
