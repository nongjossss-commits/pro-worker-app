{{--
    Employee tiles — a grid of compact cards instead of full-width rows, for
    list pages without step ticking: Employees, the employer page's Employees
    tab, Groups & Teams, Employment History. Add class "emp-tile-grid" to the
    list container. Card markup (partials/_employee_card,
    employees/_history_card) is unchanged, so every button, the checkbox,
    drag & share keep working; only the layout changes:
      - tiles fill the whole row: as many as fit at the minimum width, then
        stretched equally to the row's width (zoom out / wider screen → one
        more tile as soon as it fits, each a bit narrower)
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
     * Tiles fill the row edge to edge: the grid fits as many tiles as the
     * minimum width allows (auto-fill), then shares the leftover space equally
     * (1fr) — no empty strip at the right. A wider screen or zooming out adds
     * a tile as soon as one more fits (each gets a bit narrower), zooming in
     * removes one (each gets wider). auto-fill (not auto-fit) keeps a short
     * list (1–2 results) at normal width instead of stretching it across the
     * row. All tiles keep the same height. Phones get one full-width column.
     */
    .emp-tile-grid {
        --emp-tile-w: 260px;   /* minimum width — still fits names, the passport line and 7 action buttons */
        --emp-tile-h: 445px;   /* room for 6 detail lines + 3 status badges without growing */
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(var(--emp-tile-w), 1fr));
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

    /* ── Tile look: a raised panel tinted by the work status. Colours come from
       partials/_employee_card: --emp-tone (+ --emp-tone-2) and, for the split
       pieces, --emp-tone-bar / --emp-tone-ring (hard colour stops, so an
       employee in two kinds of work — e.g. changing employer in Workflow AND in
       Renewal resolution — shows both colours side by side, not a blend).
       purple = registration, pink = renewal, yellow = workflow, cyan =
       pre-production; slate when no work. Pieces: colour bar on top, a glow
       per colour (left / right), a gradient hairline border carrying both
       colours, a ring around the photo, and on hover a lift, a coloured shadow
       and one light sweep. Light and dark mode. Card contents are untouched. ── */
    .emp-tile-grid .employee-card,
    .emp-tile-grid > .position-relative > .list-group-item,
    .emp-tile-grid > .notification-item {
        --emp-tone: #94a3b8;
        --emp-tone-2: var(--emp-tone);
        --emp-tone-bar: linear-gradient(90deg, var(--emp-tone), var(--emp-tone));
        --emp-tone-ring: conic-gradient(var(--emp-tone), var(--emp-tone));
        --emp-card-bg: var(--bs-body-bg);
        --emp-glow: 18%;
        --emp-sheen: rgba(255, 255, 255, .28);
        border: 1px solid transparent !important;
        border-radius: 1rem;
        background:
            radial-gradient(70% 46% at 18% 0%, color-mix(in srgb, var(--emp-tone) var(--emp-glow), transparent) 0%, transparent 72%) padding-box,
            radial-gradient(70% 46% at 82% 0%, color-mix(in srgb, var(--emp-tone-2) var(--emp-glow), transparent) 0%, transparent 72%) padding-box,
            linear-gradient(180deg, color-mix(in srgb, var(--emp-card-bg) 95%, var(--emp-tone)) 0%, var(--emp-card-bg) 58%) padding-box,
            linear-gradient(160deg, color-mix(in srgb, var(--emp-tone) 60%, var(--bs-border-color)) 0%,
                                    color-mix(in srgb, var(--bs-border-color) 75%, transparent) 48%,
                                    color-mix(in srgb, var(--emp-tone-2) 60%, var(--bs-border-color)) 100%) border-box !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .06), 0 10px 26px -14px rgba(15, 23, 42, .28);
        transition: transform .25s cubic-bezier(.2, .8, .2, 1), box-shadow .25s ease;
    }
    /* colour bar (split into equal parts when there are several kinds of work) + a soft shine */
    .emp-tile-grid .employee-card::before,
    .emp-tile-grid > .position-relative > .list-group-item::before,
    .emp-tile-grid > .notification-item::before {
        content: ''; position: absolute; inset: 0 0 auto 0; height: 5px; z-index: 1; pointer-events: none;
        background: linear-gradient(180deg, rgba(255, 255, 255, .45), rgba(255, 255, 255, 0)), var(--emp-tone-bar);
    }
    /* one light sweep across the tile on hover */
    .emp-tile-grid .employee-card::after,
    .emp-tile-grid > .position-relative > .list-group-item::after,
    .emp-tile-grid > .notification-item::after {
        content: ''; position: absolute; inset: 0; z-index: 7; pointer-events: none; border-radius: inherit;
        background: linear-gradient(115deg, transparent 38%, var(--emp-sheen) 50%, transparent 62%);
        transform: translateX(-130%); transition: transform 0s;
    }
    .emp-tile-grid .employee-card:hover::after,
    .emp-tile-grid > .position-relative > .list-group-item:hover::after,
    .emp-tile-grid > .notification-item:hover::after { transform: translateX(130%); transition: transform .9s ease; }
    .emp-tile-grid .employee-card:hover,
    .emp-tile-grid > .position-relative > .list-group-item:hover,
    .emp-tile-grid > .notification-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 2px 4px rgba(15, 23, 42, .06),
                    -12px 22px 38px -22px color-mix(in srgb, var(--emp-tone) 70%, transparent),
                    12px 22px 38px -22px color-mix(in srgb, var(--emp-tone-2) 70%, transparent),
                    0 26px 44px -26px rgba(15, 23, 42, .45);
    }
    /* ring around the photo (split like the bar) */
    .emp-tile-grid .employee-card .card-body > .position-relative.flex-shrink-0 {
        isolation: isolate; border-radius: 50%; width: 64px; height: 64px; line-height: 0; /* exactly the photo, so the ring is centred */
    }
    .emp-tile-grid .employee-card .card-body > .position-relative.flex-shrink-0::before {
        content: ''; position: absolute; inset: -5px; border-radius: 50%; z-index: -1; background: var(--emp-tone-ring);
        box-shadow: -6px 8px 18px -8px color-mix(in srgb, var(--emp-tone) 70%, transparent),
                    6px 8px 18px -8px color-mix(in srgb, var(--emp-tone-2) 70%, transparent);
    }
    .emp-tile-grid .employee-card .employee-photo-thumb { position: relative; box-shadow: 0 0 0 2px var(--emp-card-bg) !important; }
    .emp-tile-grid .employee-card .document-details {
        background: color-mix(in srgb, var(--emp-tone) 6%, var(--bs-tertiary-bg)) !important;
        border: 1px solid color-mix(in srgb, var(--emp-tone) 16%, transparent);
    }
    .emp-tile-grid .employee-card .emp-card-no {
        background: linear-gradient(135deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary) 65%, #000));
    }
    html[data-bs-theme="dark"] .emp-tile-grid .employee-card,
    html[data-bs-theme="dark"] .emp-tile-grid > .position-relative > .list-group-item,
    html[data-bs-theme="dark"] .emp-tile-grid > .notification-item {
        --emp-card-bg: color-mix(in srgb, var(--bs-body-bg) 90%, #fff);
        --emp-glow: 26%;
        --emp-sheen: rgba(255, 255, 255, .09);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06), 0 12px 30px -16px rgba(0, 0, 0, .8);
    }
    html[data-bs-theme="dark"] .emp-tile-grid .employee-card:hover,
    html[data-bs-theme="dark"] .emp-tile-grid > .position-relative > .list-group-item:hover,
    html[data-bs-theme="dark"] .emp-tile-grid > .notification-item:hover {
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .08),
                    -12px 22px 40px -20px color-mix(in srgb, var(--emp-tone) 60%, transparent),
                    12px 22px 40px -20px color-mix(in srgb, var(--emp-tone-2) 60%, transparent);
    }
    html[data-bs-theme="dark"] .emp-tile-grid .employee-card .document-details {
        background: color-mix(in srgb, var(--emp-tone) 8%, rgba(0, 0, 0, .25)) !important;
        border-color: color-mix(in srgb, var(--emp-tone) 22%, transparent);
    }
    /* ── The same look on the other tile pages: Employment History / Groups & Teams
       (employees/_history_card) and Notifications (card view). Tone: history =
       yellow while the employee has work in progress; notifications = how close
       the due date is (red / yellow / dark, as before). ── */
    .emp-tile-grid > .position-relative > .list-group-item:has(> .position-absolute.top-0.start-0) { --emp-tone: #ffc107; }
    .emp-tile-grid > .notification-item.alert-danger { --emp-tone: #ef4444; }
    .emp-tile-grid > .notification-item.alert-warning { --emp-tone: #f59e0b; }
    .emp-tile-grid > .notification-item.alert-dark { --emp-tone: #64748b; }
    .emp-tile-grid > .notification-item { color: var(--bs-body-color); }
    .emp-tile-grid > .position-relative > .list-group-item .employee-photo-thumb,
    .emp-tile-grid > .notification-item .notif-photo {
        box-shadow: 0 0 0 3px var(--emp-card-bg), 0 0 0 5px color-mix(in srgb, var(--emp-tone) 70%, transparent),
                    0 8px 18px -8px color-mix(in srgb, var(--emp-tone) 70%, rgba(0, 0, 0, .5)) !important;
    }
    /* "Jump to this employee" highlight (layouts/app .highlight) keeps its orange pulse on the new look. */
    .emp-tile-grid .employee-card.highlight, .emp-tile-grid .employee-card.highlighted {
        --emp-tone: #f97316 !important; --emp-tone-2: #f97316 !important;
        --emp-tone-bar: linear-gradient(90deg, #f97316, #f97316) !important; --emp-tone-ring: conic-gradient(#f97316, #f97316) !important;
    }
    @media (prefers-reduced-motion: reduce) {
        .emp-tile-grid .employee-card, .emp-tile-grid .employee-card:hover { transition: none; transform: none; }
        .emp-tile-grid .employee-card::after { display: none; }
    }
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
    .emp-tile-grid .emp-card-field {
        /* Long values wrap to a 2nd line (then "…"); the full text is in the tooltip. */
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        white-space: normal; overflow: hidden; overflow-wrap: anywhere;
    }
    /* Many wrapped lines scroll inside the box instead of pushing the buttons out of the tile. */
    .emp-tile-grid .employee-card .document-details { max-height: calc(9 * 1.45em + .6rem); overflow-y: auto; }
    /* Employer line: name shortens with "…", the preview magnifier always stays visible. */
    .emp-tile-grid .employee-card .employer-name { display: flex !important; align-items: center; min-width: 0; }
    .emp-tile-grid .employee-card .employer-name .emp-employer-text { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .emp-tile-grid .employee-card .employer-name .btn-preview { flex: none; margin-left: .3rem !important; }
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
        height: 100%; padding: 1.6rem .75rem .6rem; text-align: left; /* border / radius / background: tile look above */
        border-radius: 1rem !important;
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
        margin: 0; height: 100%; overflow: hidden; /* border / radius / shadow / hover: tile look above */
        padding: 2.2rem .75rem .6rem; display: flex; flex-direction: column; gap: .35rem; text-align: left;
    }
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
