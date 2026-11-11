{{-- Layout of the Registration / Renewal Resolution employee card's info panel
     (production/registration/_employee_card .reg-panel) — the same idea as
     Workflow's .wf-panel (workflow/partials/_item_card_panel_style):
     appointment · outsource login in row 1, RA / request / reference no. in row 2,
     remarks · insurance in row 3, one label and value-box style everywhere.
     Wide: 3 columns · medium: 2 · narrow: 1. Layout only — every field keeps
     its own edit / save / copy behaviour (2026-11-11). --}}
@once
<style>
    .reg-panel {
        flex: 1 1 560px; min-width: 0; margin-left: .5rem; align-self: center; text-align: left;
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .55rem .8rem; align-items: start;
        padding: .65rem .85rem; border-radius: .9rem;
        background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color-translucent);
    }
    /* the employee details beside the panel get a fixed, readable width;
       the action buttons on the right stay a compact column */
    .emp-info-section:has(> .reg-panel) { align-items: center !important; min-width: 0; }
    .emp-info-section:has(> .reg-panel) > :first-child { flex: 0 0 310px; min-width: 0; }
    @media (min-width: 768px) {
        .emp-info-section:has(> .reg-panel) + .d-flex.flex-wrap.justify-content-end { flex: 0 1 330px; max-width: 330px; align-content: flex-start; }
    }
    .emp-info-section:has(> .reg-panel) .work-card-fields .wcf-item { flex-wrap: wrap; }
    .emp-info-section:has(> .reg-panel) .work-card-fields .wcf-value { white-space: normal; overflow-wrap: anywhere; }

    /* wrappers flatten into the grid (!important: they also carry Bootstrap's d-flex) */
    .reg-panel .rp-login-wrap, .reg-panel .rp-col, .reg-panel .rp-row, .reg-panel .rp-fields { display: contents !important; }
    .reg-panel .ms-md-2, .reg-panel .mt-2 { margin: 0 !important; }
    .reg-panel [style*="min-width"], .reg-panel [style*="max-width"] { min-width: 0 !important; max-width: none !important; }

    /* placement (order + span, so a field that is not shown never leaves a hole) */
    .reg-panel .rp-appt   { order: 1; }
    .reg-panel .rp-cred   { order: 2; grid-column: span 2; position: relative; }
    .reg-panel .rp-ra     { order: 3; }
    .reg-panel .rp-req    { order: 4; }
    .reg-panel .rp-ref    { order: 5; }
    .reg-panel .rp-remark { order: 6; grid-column: span 2; }
    .reg-panel .rp-ins    { order: 7; }
    .reg-panel > * , .reg-panel .rp-row > *, .reg-panel .rp-fields > *, .reg-panel .rp-login-wrap > * { min-width: 0; }
    .reg-panel .rp-ra > div:first-child { flex: 1 1 auto; min-width: 0; }

    /* what Alpine hides stays hidden even with Bootstrap's d-flex (!important) */
    .reg-panel [x-show][style*="display: none"] { display: none !important; }

    /* one label style */
    .reg-panel small.text-muted {
        font-size: .68rem !important; font-weight: 600 !important; letter-spacing: .01em; color: var(--bs-secondary-color) !important;
        margin-bottom: .2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    /* one value-box style */
    .reg-panel .border.rounded.bg-light, .reg-panel .border.rounded.bg-white {
        min-height: 31px !important; background: var(--bs-body-bg) !important; border-color: var(--bs-border-color) !important;
        box-shadow: none !important; border-radius: .5rem !important;
    }
    .reg-panel .mb-2 { margin-bottom: 0 !important; }

    /* outsource login: header line, then e-mail · password · code side by side */
    .reg-panel .rp-cred .shadow-sm.d-flex.flex-column, .reg-panel .rp-cred .border.rounded.bg-white.d-flex {
        display: grid !important; grid-template-columns: repeat(3, minmax(0, 1fr)); column-gap: .75rem; row-gap: .1rem;
    }
    .reg-panel .rp-cred .border.rounded.bg-white > :first-child { grid-column: 1 / -1; margin-bottom: 0 !important; }
    .reg-panel .rp-cred .border.rounded.bg-white > .mt-1 { margin-top: 0 !important; min-width: 0; }
    .reg-panel .rp-cred .text-truncate { max-width: none !important; min-width: 0; }

    /* appointment: one line, place below */
    .reg-panel .rp-appt [x-text="displayValue"] { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
    .reg-panel .rp-appt .bg-white { min-width: 0; overflow: hidden; }
    .reg-panel .rp-appt [x-show="locationValue"] { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* RA row: the edit pencil for the three numbers sits level with the box */
    .reg-panel .rp-ra .mt-3 { margin-top: 1.05rem !important; }

    /* remarks: at most two lines in the card (full text when editing) */
    .reg-panel .rp-remark .text-wrap {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    @media (max-width: 1399.98px) {
        .reg-panel { grid-template-columns: repeat(2, minmax(0, 1fr)); flex-basis: 400px; }
        .emp-info-section:has(> .reg-panel) > :first-child { flex: 0 0 290px; }
        .reg-panel .rp-cred { grid-column: span 1; }
        .reg-panel .rp-cred .shadow-sm.d-flex.flex-column, .reg-panel .rp-cred .border.rounded.bg-white.d-flex { grid-template-columns: minmax(0, 1fr); }
        .reg-panel .rp-remark { grid-column: span 1; }
    }
    @media (max-width: 991.98px) {
        .emp-info-section:has(> .reg-panel) { flex-wrap: wrap; }
        .emp-info-section:has(> .reg-panel) > :first-child { flex-basis: 100%; }
        .reg-panel { margin-left: 0; width: 100%; flex-basis: 100%; }
    }
    @media (max-width: 575.98px) {
        .reg-panel { grid-template-columns: minmax(0, 1fr); }
        .reg-panel > *, .reg-panel .rp-row > *, .reg-panel .rp-fields > *, .reg-panel .rp-login-wrap > * { grid-column: auto !important; }
    }
</style>
@endonce
