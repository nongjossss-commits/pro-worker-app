{{--
    Employee tiles — a grid of compact cards instead of full-width rows, for
    list pages without step ticking: Employees, the employer page's Employees
    tab, Groups & Teams, Employment History. Add class "emp-tile-grid" to the
    list container. Card markup (partials/_employee_card,
    employees/_history_card) is unchanged, so every button, the checkbox,
    drag & share keep working; only the layout changes:
      - fixed-size tiles, as many per row as the screen fits (zoom out → more)
      - photo, names, employer, document dates stacked; buttons at the bottom
      - the work-status badges sit in their own box inside the tile instead
        of the full-card overlay used by the row layout
    Workflow / Registration / Renewal cards (production.registration._employee_card)
    are not affected.
--}}
{{-- layouts/app includes this twice: part=styles in <head> (no flash of the old layout before the CSS arrives), part=scripts at the end of <body>. --}}
@if(($part ?? 'all') !== 'scripts')
<style>
    /*
     * Fixed-size tiles: every tile is the same width and height; the grid fits
     * as many per row as the screen holds (auto-fill). A wider screen or
     * zooming out adds tiles to each row, zooming in removes some — tiles are
     * never stretched. Phones get one full-width column.
     */
    .emp-tile-grid {
        --emp-tile-w: 280px;   /* fits names, the passport line and 7 action buttons */
        --emp-tile-h: 445px;   /* room for 6 detail lines + 3 status badges without growing */
        display: grid !important;
        grid-template-columns: repeat(auto-fill, var(--emp-tile-w));
        justify-content: start;
        gap: .75rem;
        align-items: stretch;
    }
    .emp-tile-grid:has(.employee-card) { grid-auto-rows: var(--emp-tile-h); }
    @media (max-width: 575.98px) {
        .emp-tile-grid { grid-template-columns: 1fr; }
    }

    /* "No results" and similar messages span the whole row. */
    .emp-tile-grid > p, .emp-tile-grid > .text-center:not(.employee-card) { grid-column: 1 / -1; }
    /* Wrappers around each card stretch so tiles in a row are the same height. */
    .emp-tile-grid > * { min-width: 0; display: flex; flex-direction: column; }
    .emp-tile-grid > * > .employee-card, .emp-tile-grid > .employee-card { flex: 1 1 auto; }

    /* ── partials/_employee_card ── */
    .emp-tile-grid .employee-card {
        margin: 0 !important; height: 100%; max-height: 100%; border-radius: .75rem; overflow: hidden;
        transition: box-shadow .15s, transform .15s;
    }
    .emp-tile-grid .employee-card:hover { box-shadow: 0 4px 14px rgba(0, 0, 0, .12); transform: translateY(-1px); }
    .emp-tile-grid .employee-card .card-body {
        flex-direction: column !important; flex-wrap: nowrap !important; align-items: stretch !important;
        gap: .35rem !important; padding: 2.2rem .75rem .6rem; text-align: left; height: 100%;
    }
    /* Header row: number badge (top-left) · "last edited" label · checkbox (top-right). */
    .emp-tile-grid .employee-card .card-body > .me-3.flex-shrink-0:first-child {
        position: absolute; top: .45rem; right: .5rem; margin: 0 !important; z-index: 6;
    }
    /* Photo centred and a bit larger. */
    .emp-tile-grid .employee-card .card-body > .position-relative.flex-shrink-0 { margin: 0 auto !important; }
    .emp-tile-grid .employee-card .employee-photo-thumb { width: 64px !important; height: 64px !important; box-shadow: 0 1px 4px rgba(0, 0, 0, .15); }
    /* The row layout's full-card status overlay → hidden; the in-tile status box below is shown instead. */
    .emp-tile-grid .employee-card > .position-absolute.top-0.start-0.w-100.h-100 { display: none !important; }

    .emp-tile-grid .employee-card .employee-info {
        width: 100%; min-width: 0; font-size: .8rem; flex: 0 0 auto !important;
        position: static !important; /* lets the number badge sit in the card's corner */
    }
    .emp-tile-grid .emp-card-no {
        position: absolute; top: .45rem; left: .55rem; z-index: 6;
        min-width: 1.9rem; padding: .12rem .5rem; border-radius: 1rem; text-align: center;
        font-size: .78rem; font-weight: 700; line-height: 1.3;
        background: var(--bs-primary); color: #fff; box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
    }
    .emp-tile-grid .emp-card-no-dot { display: none; }
    .emp-tile-grid .employee-card > .last-edited-badge {
        left: auto; right: 2.4rem; top: .45rem; border-radius: .5rem; max-width: calc(100% - 5.5rem);
        overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
    }
    body.selection-mode .emp-tile-grid .employee-card > .last-edited-badge { right: 3rem; }
    .emp-tile-grid .employee-card .employee-info > .position-absolute {
        position: static !important; max-width: none !important; justify-content: flex-start !important;
        margin: 0 0 .25rem !important;
        flex-wrap: nowrap !important; overflow: hidden; max-height: 1.45rem; /* group tags: one line */
    }
    /* Name and the preview magnifier on the same line. */
    .emp-tile-grid .employee-card .employee-name-en { display: inline; font-weight: 600; font-size: .88rem; line-height: 1.3; }
    .emp-tile-grid .employee-card .employee-info > .btn-preview { margin: 0 0 0 .3rem !important; vertical-align: baseline; }
    .emp-tile-grid .employee-card .employee-info > .badge:not(.emp-card-nat) { margin: .1rem .15rem 0 !important; }
    /* Nationality beside the photo: flag at the card's left edge, the (translated)
       name right of the photo — a full-width row level with the photo, split around it.
       "html … .bg-light" outranks theme-dark.css's ".bg-light" background so the row
       never covers the photo in dark mode. */
    html .emp-tile-grid .emp-card-nat.bg-light,
    .emp-tile-grid .emp-card-nat {
        position: absolute !important; left: .75rem; right: .75rem; height: 64px; margin: 0 !important; padding: 0 !important;
        display: grid !important; grid-template-columns: minmax(0, 1fr) 64px minmax(0, 1fr); align-items: center; column-gap: .65rem;
        background: transparent !important; color: var(--bs-body-color) !important; pointer-events: none;
        font-size: .92rem; font-weight: 600; z-index: 2;
    }
    .emp-tile-grid .employee-card .emp-card-nat { top: 2.2rem; }
    .emp-tile-grid .list-group-item .emp-card-nat { top: 1.6rem; }
    .emp-tile-grid .notification-item .emp-card-nat { top: 2.2rem; }
    .emp-tile-grid .emp-card-nat img {
        grid-column: 1; justify-self: start; width: 34px !important; height: 24px !important; margin: 0 !important;
        object-fit: cover; border-radius: 3px; box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
    }
    .emp-tile-grid .emp-card-nat > :not(img), .emp-tile-grid .emp-card-nat { white-space: nowrap; }
    /* Thai marks above/below the line (ี ั ู …) need a tall line box, or overflow:hidden clips them.
       Long names (e.g. "Vietnamese") are shrunk to fit by the script below instead of ending in "…". */
    .emp-tile-grid .emp-card-nat > span {
        grid-column: 3; justify-self: start; max-width: 100%; overflow: hidden; text-overflow: ellipsis;
        line-height: 1.75; padding: .1em 0;
    }
    .emp-tile-grid .employee-card .employee-name-th,
    .emp-tile-grid .employee-card .employer-name {
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: .78rem;
    }
    .emp-tile-grid .employee-card .document-details {
        margin-top: .35rem !important; padding: .3rem .45rem; border-radius: .45rem; text-align: left;
        font-size: .7rem !important; line-height: 1.45; background: var(--bs-tertiary-bg); color: var(--bs-secondary-color);
    }
    /* One detail per line (Super Admin picks up to 6 — EmployeeCardFieldsService); long values end with … */
    .emp-tile-grid .emp-card-field { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .emp-tile-grid .emp-card-field-sep { display: none; }

    .emp-tile-grid .employee-card .employee-actions-unified { width: 100%; margin-top: auto; }
    /* Status box (the badges the row layout only showed on phones). */
    .emp-tile-grid .employee-card .employee-actions-unified > .d-sm-none {
        display: flex !important; flex-direction: column; gap: .25rem !important;
        padding: .3rem; margin-bottom: .45rem !important; border-radius: .5rem;
        background: var(--bs-tertiary-bg); border: 1px dashed var(--bs-border-color);
        /* up to 3 badges fit (3 × 1.55rem + gaps + padding); a 4th scrolls inside the box */
        max-height: calc(3 * 1.55rem + 2 * .25rem + .6rem + 2px); overflow-y: auto; flex: none;
    }
    .emp-tile-grid .employee-card .resolution-badge {
        display: flex; align-items: center; justify-content: center; gap: .3rem; text-align: center;
        width: 100%; max-width: 100% !important; min-height: 1.55rem; flex: none;
        font-size: .7rem; line-height: 1.25; padding: .3rem .45rem; border-radius: .4rem;
        white-space: nowrap; overflow: hidden; /* one line each; full text in the tooltip */
    }
    .emp-tile-grid .employee-card .resolution-badge .rb-text { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .emp-tile-grid .employee-card .employee-actions-unified > .d-flex:last-child {
        justify-content: center; gap: .25rem !important;
    }
    .emp-tile-grid .employee-card .employee-actions-unified .btn { padding: .2rem .38rem; font-size: .72rem; }

    /* ── employees/_history_card (Employment History) ── */
    .emp-tile-grid > .position-relative > .list-group-item {
        height: 100%; border-radius: .75rem !important; border: 1px solid var(--bs-border-color) !important;
        padding: 1.6rem .75rem .6rem; text-align: left;
    }
    .emp-tile-grid .list-group-item > .d-flex.w-100 { flex-direction: column; align-items: stretch; height: 100%; gap: .35rem; }
    .emp-tile-grid .list-group-item > .d-flex.w-100 > .me-3 { margin: 0 !important; justify-content: center; }
    .emp-tile-grid .list-group-item h5 { display: flex; flex-wrap: wrap; align-items: center; gap: .15rem .3rem; }
    /* checkbox + drag handle → top corners */
    .emp-tile-grid .list-group-item > .d-flex.w-100 > .me-3:nth-child(1) { position: absolute; top: .45rem; right: .5rem; }
    .emp-tile-grid .list-group-item > .d-flex.w-100 > .me-3:nth-child(2) { position: absolute; top: .4rem; left: .55rem; }
    .emp-tile-grid .list-group-item .employee-photo-thumb { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; }
    .emp-tile-grid .list-group-item h5 { font-size: .88rem; font-weight: 600; margin-bottom: .2rem !important; }
    .emp-tile-grid .list-group-item > .d-flex.w-100 > .flex-grow-1 { font-size: .78rem; flex: 0 0 auto !important; }
    .emp-tile-grid .list-group-item > .d-flex.w-100 > .flex-grow-1 > p { margin-bottom: .15rem !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .emp-tile-grid .list-group-item .row.gx-3 {
        margin: .35rem 0 0 !important; padding: .3rem .45rem; border-radius: .45rem; text-align: left;
        background: var(--bs-tertiary-bg);
    }
    .emp-tile-grid .list-group-item .row.gx-3 > [class*="col-"] { width: 100%; padding: 0; }
    .emp-tile-grid .list-group-item .row.gx-3 small { font-size: .7rem; }
    .emp-tile-grid .list-group-item > .d-flex.w-100 > .ms-auto { margin: auto 0 0 !important; padding: 0 !important; justify-content: center; }
    .emp-tile-grid .list-group-item > .d-flex.w-100 > .ms-auto > div { flex-direction: row !important; flex-wrap: wrap; justify-content: center; gap: .25rem !important; }
    .emp-tile-grid .list-group-item .btn-sm { padding: .2rem .38rem; font-size: .72rem; }
    /* history status overlay → a box at the top of the tile instead of covering it */
    .emp-tile-grid .list-group-item > .position-absolute.top-0.start-0.w-100.h-100 {
        position: static !important; height: auto !important; margin-bottom: .4rem; padding: .3rem; z-index: auto !important;
        border-width: 1px !important; border-style: dashed !important;
    }

    /* ── notifications/_notification_item (Notifications menu, card view) ──
       Same tile as the employee cards; the alert colour (red / yellow / dark)
       still shows how close the due date is. The two wrapper rows are
       flattened (display: contents) so photo, details, work-status box and
       the days badge + buttons stack in that order inside the tile. */
    .emp-tile-grid:has(.notification-item) { grid-auto-rows: var(--emp-notif-h, 400px); }
    .emp-tile-grid > .notification-item {
        margin: 0; height: 100%; overflow: hidden; border-radius: .75rem;
        padding: 2.2rem .75rem .6rem; display: flex; flex-direction: column; gap: .35rem; text-align: left;
        transition: box-shadow .15s, transform .15s;
    }
    .emp-tile-grid > .notification-item:hover { box-shadow: 0 4px 14px rgba(0, 0, 0, .12); transform: translateY(-1px); }
    .emp-tile-grid > .notification-item > .d-flex.align-items-start,
    .emp-tile-grid > .notification-item > .d-flex.align-items-start > .d-flex.flex-column { display: contents !important; }
    .emp-tile-grid > .notification-item .form-check-input { position: absolute; top: .45rem; right: .5rem; margin: 0; z-index: 6; }
    .emp-tile-grid > .notification-item .notif-photo {
        order: 1; width: 64px !important; height: 64px !important; margin: 0 auto; box-shadow: 0 1px 4px rgba(0, 0, 0, .15);
    }
    .emp-tile-grid > .notification-item .flex-grow-1 { order: 2; flex: 0 0 auto !important; min-width: 0; font-size: .8rem; }
    .emp-tile-grid > .notification-item h5.alert-heading { font-size: .88rem; font-weight: 600; line-height: 1.35; margin-bottom: .2rem !important; }
    .emp-tile-grid > .notification-item h5 .btn-preview,
    .emp-tile-grid > .notification-item .flex-grow-1 p .btn-preview { padding: 0 .2rem; border: 0; background: transparent; line-height: 1; }
    .emp-tile-grid > .notification-item .flex-grow-1 > p { margin-bottom: .15rem !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    /* work-status overlay → a box inside the tile (max 3 badges, like the employee tiles) */
    .emp-tile-grid > .notification-item > .position-absolute.top-0.start-0.w-100.h-100 {
        order: 3; position: static !important; width: auto !important; height: auto !important; z-index: auto !important;
        margin-top: auto; padding: .3rem; border-width: 1px !important; border-style: dashed !important; border-radius: .5rem;
        max-height: calc(3 * 1.55rem + 2 * .25rem + .6rem + 2px); overflow-y: auto; flex: none;
    }
    .emp-tile-grid > .notification-item > .position-absolute.top-0.start-0.w-100.h-100 > div { width: 100%; gap: .25rem !important; }
    .emp-tile-grid > .notification-item .resolution-badge {
        display: flex; align-items: center; justify-content: center; gap: .3rem; width: 100%; min-height: 1.55rem;
        font-size: .7rem; padding: .3rem .45rem; border-radius: .4rem; white-space: nowrap; overflow: hidden;
    }
    .emp-tile-grid > .notification-item .resolution-badge .rb-text { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* days badge + buttons at the bottom, centred */
    .emp-tile-grid > .notification-item .text-end.flex-shrink-0 { order: 4; margin: 0 !important; text-align: center !important; align-self: stretch !important; }
    .emp-tile-grid > .notification-item:not(:has(> .position-absolute.top-0)) .text-end.flex-shrink-0 { margin-top: auto !important; }
    .emp-tile-grid > .notification-item .text-end.flex-shrink-0 > .d-flex { align-items: center !important; }
    .emp-tile-grid > .notification-item .text-end.flex-shrink-0 .badge.fs-6 { font-size: .8rem !important; margin-bottom: .4rem !important; }
    .emp-tile-grid > .notification-item .text-end.flex-shrink-0 [role="group"] {
        flex-direction: row !important; flex-wrap: wrap; justify-content: center !important; gap: .25rem !important;
    }
    .emp-tile-grid > .notification-item .text-end.flex-shrink-0 .btn { padding: .2rem .38rem; font-size: .72rem; }

    /* ── Selection mode (program-wide): once any employee is ticked, every
       employee checkbox becomes a large highlighted target — clicking the
       card itself no longer selects (layouts/_app_scripts, "Selection Mode"). ── */
    body.selection-mode .employee-checkbox,
    body.selection-mode .history-employee-checkbox {
        width: 1.75rem; height: 1.75rem; margin-top: 0; cursor: pointer;
        border: 2px solid var(--bs-primary);
        box-shadow: 0 0 0 .3rem rgba(var(--bs-primary-rgb), .18);
        transition: width .15s, height .15s, box-shadow .15s;
    }
    body.selection-mode .employee-checkbox:hover,
    body.selection-mode .history-employee-checkbox:hover { box-shadow: 0 0 0 .4rem rgba(var(--bs-primary-rgb), .3); }
</style>
@endif
@if(($part ?? 'all') !== 'styles')
<script>
    /* Shrink a nationality name that is wider than its space beside the photo,
       step by step down to 0.68rem (still readable); only then would "…" show. */
    (function () {
        const MAX = 0.92, MIN = 0.68, STEP = 0.02;
        function fit(span) {
            span.style.fontSize = '';
            let size = MAX;
            while (span.scrollWidth > span.clientWidth + 1 && size > MIN) {
                size = Math.max(MIN, size - STEP);
                span.style.fontSize = size + 'rem';
            }
        }
        function fitAll(root) {
            (root || document).querySelectorAll('.emp-tile-grid .emp-card-nat > span').forEach(fit);
        }
        window.empTileFitNationality = fitAll;
        const run = () => fitAll();
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(run);
        let t;
        window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(run, 150); });
        // Cards added later (AJAX lists, tabs shown later) get fitted too.
        new MutationObserver((muts) => {
            if (muts.some(m => [...m.addedNodes].some(n => n.nodeType === 1 && (n.matches?.('.emp-card-nat, .emp-tile-grid, .employee-card, .list-group-item') || n.querySelector?.('.emp-card-nat'))))) {
                clearTimeout(t); t = setTimeout(run, 50);
            }
        }).observe(document.documentElement, { childList: true, subtree: true });
        // Hidden tabs measure 0 — refit when a Bootstrap tab is shown.
        document.addEventListener('shown.bs.tab', run);
        document.addEventListener('click', (e) => { if (e.target.closest && e.target.closest('.rec-tab')) setTimeout(run, 60); });
    })();
</script>
@endif
