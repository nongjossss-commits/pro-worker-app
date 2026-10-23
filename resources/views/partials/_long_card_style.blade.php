{{--
    Look of the long (row) work cards — Workflow (workflow/partials/_item_card,
    .item-card-outer) and Pre-Production / Registration / Renewal resolution
    (production/registration/_employee_card, .employee-card-outer). Loaded in
    <head> by layouts/app. Decoration only — card markup, data and buttons are
    unchanged, and the existing signal colours stay: the resolution cards keep
    their renewal-progress background + border (blue / purple / yellow /
    green), only a glow, an accent bar and a shadow are added on top
    (background-image, never background-color).

    --lc-tone (accent colour):
      Workflow:   pending amber · completed green · cancelled slate · pre-production cyan
      Resolution: renewal progress (both blue · visa purple · work permit amber ·
                  completed green · cancelled slate), otherwise the brand colour
--}}
<style>
    :is(.item-card-outer, .employee-card-outer) > .card {
        --lc-tone: var(--bs-primary);
        --lc-glow: 11%;
        border-radius: 1rem !important;
        overflow: hidden;
        background-image:
            radial-gradient(55% 140% at 0% 0%, color-mix(in srgb, var(--lc-tone) var(--lc-glow), transparent) 0%, transparent 70%),
            linear-gradient(90deg, color-mix(in srgb, var(--lc-tone) 4%, transparent) 0%, transparent 35%);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 10px 28px -18px rgba(15, 23, 42, .3) !important;
        transition: transform .25s cubic-bezier(.2, .8, .2, 1), box-shadow .25s ease;
    }
    /* hairline border in the accent colour — not on cards whose border is a renewal-progress signal */
    :is(.item-card-outer, .employee-card-outer) > .card:not([class*="renewal-progress"]) {
        border: 1px solid color-mix(in srgb, var(--lc-tone) 32%, var(--bs-border-color)) !important;
    }
    /* accent bar down the left edge */
    :is(.item-card-outer, .employee-card-outer) > .card::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; z-index: 2; pointer-events: none;
        background: linear-gradient(180deg, var(--lc-tone), color-mix(in srgb, var(--lc-tone) 45%, #fff));
    }
    :is(.item-card-outer, .employee-card-outer) > .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 2px 4px rgba(15, 23, 42, .05),
                    0 18px 38px -20px color-mix(in srgb, var(--lc-tone) 65%, rgba(15, 23, 42, .5)) !important;
    }
    /* photo ring */
    :is(.item-card-outer, .employee-card-outer) .avatar-container > .rounded-circle {
        box-shadow: 0 0 0 3px var(--bs-body-bg), 0 0 0 5px color-mix(in srgb, var(--lc-tone) 65%, transparent),
                    0 8px 16px -8px color-mix(in srgb, var(--lc-tone) 70%, rgba(0, 0, 0, .5)) !important;
    }
    /* buttons: same rounded shape, a small lift on hover */
    :is(.item-card-outer, .employee-card-outer) .card-body .btn-sm:not(.btn-link):not(.rounded-circle):not(.rounded-pill) { border-radius: .6rem; }
    :is(.item-card-outer, .employee-card-outer) .card-body .btn-sm:not(.btn-link) { transition: transform .15s ease, box-shadow .15s ease; }
    :is(.item-card-outer, .employee-card-outer) .card-body .btn-sm:not(.btn-link):not(:disabled):hover {
        transform: translateY(-1px); box-shadow: 0 6px 14px -8px rgba(15, 23, 42, .45);
    }

    /* tones — Workflow */
    .item-card-outer > .card { --lc-tone: #f59e0b; }
    .item-card-outer > .card.border-info { --lc-tone: #0dcaf0; }
    .item-card-outer[data-status="completed"] > .card { --lc-tone: #10b981; }
    .item-card-outer[data-status="cancelled"] > .card { --lc-tone: #94a3b8; }
    /* tones — Pre-Production / Registration / Renewal resolution */
    .employee-card-outer[data-renewal-progress="both"] > .card { --lc-tone: #1d4ed8; }
    .employee-card-outer[data-renewal-progress="visa_only"] > .card { --lc-tone: #a855f7; }
    .employee-card-outer[data-renewal-progress="work_permit_only"] > .card { --lc-tone: #f59e0b; }
    .employee-card-outer[data-renewal-progress="completed"] > .card { --lc-tone: #10b981; }
    .employee-card-outer[data-renewal-progress="cancelled"] > .card { --lc-tone: #94a3b8; }

    html[data-bs-theme="dark"] :is(.item-card-outer, .employee-card-outer) > .card {
        --lc-glow: 20%;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05), 0 12px 30px -18px rgba(0, 0, 0, .85) !important;
    }
    html[data-bs-theme="dark"] :is(.item-card-outer, .employee-card-outer) > .card:hover {
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .07), 0 0 0 1px color-mix(in srgb, var(--lc-tone) 40%, transparent),
                    0 18px 40px -20px color-mix(in srgb, var(--lc-tone) 55%, transparent) !important;
    }
    @media (prefers-reduced-motion: reduce) {
        :is(.item-card-outer, .employee-card-outer) > .card,
        :is(.item-card-outer, .employee-card-outer) > .card:hover,
        :is(.item-card-outer, .employee-card-outer) .card-body .btn-sm:hover { transition: none; transform: none; }
    }
</style>
