{{-- Layout of the Workflow item card's info panel (workflow/partials/_item_card .wf-panel):
     appointment · outsource login in row 1, RA / request / reference no. in row 2,
     the note across the bottom — same label and box style everywhere.
     Wide: 3 columns · medium: 2 · narrow: 1. Layout only — every field keeps its
     own edit / save / copy behaviour. --}}
@once
<style>
    .wf-panel {
        flex: 1 1 520px; min-width: 0; margin-left: .5rem; align-self: center; text-align: left;
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .55rem .8rem; align-items: start;
        padding: .6rem .8rem; border-radius: .8rem;
        background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color-translucent);
    }
    .wf-panel .ms-md-4 { margin-left: 0 !important; }
    /* employee details beside the panel: up to ~40% of the row, details wrap */
    .item-info-section:has(> .wf-panel) > [id^="info-container-"] { flex: 0 1 40%; min-width: 260px; }
    /* details beside the panel wrap instead of being cut with "…" */
    .item-info-section:has(> .wf-panel) .work-card-fields .wcf-item { flex-wrap: wrap; }
    .item-info-section:has(> .wf-panel) .work-card-fields .wcf-value { white-space: normal; overflow-wrap: anywhere; }
    /* (!important: these wrappers also carry Bootstrap's d-flex, which is !important) */
    .wf-panel > .wf-more { display: contents !important; }
    .wf-panel .wf-fields { display: contents !important; }
    .wf-panel > .wf-appt { grid-column: 1; grid-row: 1; min-width: 0; }
    .wf-panel > .wf-appt > div { min-width: 0 !important; }
    .wf-panel > .wf-cred { grid-column: 2 / 4; grid-row: 1; min-width: 0; }
    .wf-panel .wf-fields > :nth-child(1) { grid-column: 1; grid-row: 2; min-width: 0; }
    .wf-panel .wf-fields > :nth-child(2) { grid-column: 2; grid-row: 2; min-width: 0; }
    .wf-panel .wf-fields > :nth-child(3) { grid-column: 3; grid-row: 2; min-width: 0; }
    .wf-panel .wf-remark { grid-column: 1 / -1; grid-row: 3; min-width: 0; }

    /* what Alpine hides (x-show → display:none) stays hidden even with Bootstrap's d-flex (!important) —
       otherwise the value box stayed visible above its input in edit mode */
    .wf-panel [x-show][style*="display: none"] { display: none !important; }

    /* one label style */
    .wf-panel small.text-muted, .wf-panel .wf-label {
        font-size: .68rem !important; font-weight: 600; letter-spacing: .01em; color: var(--bs-secondary-color) !important;
        margin-bottom: .2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    /* one value-box style */
    .wf-panel .border.rounded.bg-light, .wf-panel .border.rounded.bg-white {
        min-height: 31px; background: var(--bs-body-bg) !important; border-color: var(--bs-border-color) !important; box-shadow: none !important;
    }
    /* outsource login: the two boxes side by side, edit pencil after them */
    .wf-panel > .wf-cred > .d-flex { width: 100%; }
    .wf-panel > .wf-cred > .d-flex > .d-flex.flex-column { flex: 1 1 auto; min-width: 0; flex-direction: row !important; }
    .wf-panel > .wf-cred > .d-flex > .d-flex.flex-column > div { flex: 1 1 0; min-width: 0 !important; }
    .wf-panel > .wf-cred > .d-flex > .d-flex.flex-column > div > span { max-width: none !important; flex: 1 1 auto; }
    /* RA cell: value + the edit pencil for the three numbers */
    .wf-panel .wf-fields > :nth-child(1) > div[style*="flex-grow"] { min-width: 0; }
    .wf-panel .wf-fields > :nth-child(1) > .d-flex.gap-1 { margin-bottom: 0 !important; }
    /* Every value in the panel stays on ONE line: longer text ends with "…" and
       shows in full when pointed at (title) — the card never grows taller
       because of a long appointment place, note or number. Editing still shows
       the whole text in the input. */
    .wf-panel .wf-oneline { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
    .wf-panel .wf-appt .bg-white { min-width: 0; overflow: hidden; }
    .wf-panel .wf-appt [x-text="displayValue"] { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
    .wf-panel .wf-remark .wf-remark-box {
        min-height: 31px; min-width: 0; white-space: nowrap !important; overflow: hidden; text-overflow: ellipsis; word-break: normal !important;
    }
    .wf-panel .wf-fields .text-nowrap.overflow-hidden { text-overflow: ellipsis; }
    .wf-panel [title]:not([title=""]) { cursor: default; }

    @media (max-width: 1399.98px) {
        .wf-panel { grid-template-columns: repeat(2, minmax(0, 1fr)); flex-basis: 380px; }
        .item-info-section:has(> .wf-panel) > [id^="info-container-"] { flex: 1 1 45%; min-width: 320px; }
        .wf-panel > .wf-appt { grid-column: 1; grid-row: 1; }
        .wf-panel > .wf-cred { grid-column: 2; grid-row: 1; }
        .wf-panel > .wf-cred > .d-flex > .d-flex.flex-column { flex-direction: column !important; }
        .wf-panel .wf-fields > :nth-child(1) { grid-column: 1; grid-row: 2; }
        .wf-panel .wf-fields > :nth-child(2) { grid-column: 2; grid-row: 2; }
        .wf-panel .wf-fields > :nth-child(3) { grid-column: 1; grid-row: 3; }
        .wf-panel .wf-remark { grid-column: 2; grid-row: 3; }
    }
    @media (max-width: 767.98px) {
        .wf-panel { grid-template-columns: minmax(0, 1fr); margin-left: 0; width: 100%; flex-basis: auto; }
        .item-info-section:has(> .wf-panel) > [id^="info-container-"] { flex-basis: auto; min-width: 0; }
        .wf-panel > *, .wf-panel .wf-fields > *, .wf-panel .wf-remark { grid-column: 1 !important; grid-row: auto !important; }
    }
</style>
@endonce
