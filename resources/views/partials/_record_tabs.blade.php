{{--
    Record tabs — splits a long record page (employee edit / create /
    preview) into tabs so nothing needs scrolling to find. Included once in
    layouts/app.blade.php; works for pages and for content injected later
    (edit modal, preview modal) via a MutationObserver.

    Markup:
      <div class="rec-tabs" data-rec-tabs>
        <div class="rec-tabs-nav" role="tablist">
          <button type="button" class="rec-tab active" data-rec-tab="a"> <i class="bi …"></i> <span>Label</span> </button>
          …
        </div>
        <div class="rec-tab-pane active" data-rec-pane="a"> … </div>
        …
      </div>
    Optional on the root: data-error-fields='["fieldName", …]' (server
    validation errors) — those inputs get .is-invalid, their tab a red dot,
    and the first such tab opens.
    A pane holding a field the browser rejects (required, etc.) opens
    automatically on submit. Sticky save bar: wrap the buttons in .rec-savebar.
--}}
<style>
    .rec-tabs-nav {
        display: flex; gap: .25rem; overflow-x: auto; scrollbar-width: none;
        border-bottom: 1px solid var(--bs-border-color);
        position: sticky; top: 0; z-index: 5; background: var(--bs-body-bg);
        margin-bottom: 1rem; padding-top: .25rem;
    }
    .rec-tabs-nav::-webkit-scrollbar { display: none; }
    .rec-tab {
        flex: 0 0 auto; display: inline-flex; align-items: center; gap: .4rem;
        border: 0; background: transparent; color: var(--bs-secondary-color);
        padding: .6rem .9rem; font-weight: 500; white-space: nowrap;
        border-bottom: 3px solid transparent; margin-bottom: -1px;
        border-radius: .5rem .5rem 0 0; transition: color .15s, background-color .15s;
    }
    .rec-tab:hover { color: var(--bs-body-color); background: var(--bs-tertiary-bg); }
    .rec-tab.active { color: var(--bs-primary); border-bottom-color: var(--bs-primary); font-weight: 600; }
    .rec-tab .rec-tab-count {
        font-size: .72rem; line-height: 1; padding: .2rem .45rem; border-radius: 1rem;
        background: var(--bs-secondary-bg); color: var(--bs-secondary-color); font-weight: 600;
    }
    .rec-tab.active .rec-tab-count { background: var(--bs-primary); color: #fff; }
    .rec-tab .rec-tab-alert {
        display: none; width: .5rem; height: .5rem; border-radius: 50%;
        background: var(--bs-danger);
    }
    .rec-tab.has-error .rec-tab-alert { display: inline-block; }
    .rec-tab.has-error { color: var(--bs-danger); }
    .rec-tab .rec-tab-missing {
        display: none; font-size: .72rem; line-height: 1; padding: .2rem .4rem; border-radius: 1rem;
        background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); font-weight: 600;
    }
    .rec-tab.has-missing .rec-tab-missing { display: inline-block; }
    .rec-tab-pane { display: none; }
    .rec-tab-pane.active { display: block; animation: recTabIn .22s cubic-bezier(.2, .8, .2, 1); }
    @keyframes recTabIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

    /*
     * Fixed frame (previews): wrap header + tabs in .rec-fixed. Inside a
     * modal the window keeps one height, so switching between a short and
     * a long tab no longer resizes/re-centres it — the header and tab bar
     * stay put and only the content area under them scrolls.
     */
    .modal-content:has(.rec-fixed) { height: min(calc(100vh - 3.5rem), 900px); }
    .modal-content:has(.rec-fixed) > .modal-body { display: flex; flex-direction: column; overflow: hidden; }
    .rec-fixed { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
    .rec-fixed > .rec-profile { flex: none; }
    .rec-fixed > .rec-tabs { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
    .rec-fixed > .rec-tabs > .rec-tabs-nav { flex: none; position: static; margin-bottom: 0; }
    .rec-fixed > .rec-tabs > .rec-tab-pane.active {
        flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain;
        padding: 1rem .25rem .5rem 0;
    }
    @media (max-width: 575.98px) {
        .modal-fullscreen-sm-down .modal-content:has(.rec-fixed) { height: 100%; }
    }
    /* Section headings inside a pane: the tab already names the section. */
    .rec-tab-pane > h5:first-child { margin-top: 0 !important; }

    /* Save bar stays in view on every tab. */
    .rec-savebar {
        position: sticky; bottom: 0; z-index: 5;
        background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color);
        padding: .75rem 0; margin-top: 1rem !important;
        box-shadow: 0 -6px 12px -10px rgba(0, 0, 0, .35);
    }

    /* Profile header (employees/partials/_profile_header). */
    .rec-profile {
        display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;
        padding: 1rem; margin-bottom: .75rem; border-radius: .75rem;
        background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color);
    }
    .rec-profile-photo {
        width: 84px; height: 84px; border-radius: .75rem; object-fit: cover; flex: 0 0 auto;
        border: 2px solid var(--bs-body-bg); box-shadow: 0 1px 4px rgba(0, 0, 0, .15);
        background: var(--bs-secondary-bg);
    }
    .rec-profile-avatar {
        display: flex; align-items: center; justify-content: center; font-size: 2.2rem;
        color: var(--bs-primary); background: var(--bs-primary-bg-subtle);
    }
    .rec-profile-main { flex: 1 1 260px; min-width: 0; }
    .rec-profile-name { font-size: 1.25rem; font-weight: 700; line-height: 1.25; margin: 0; word-break: break-word; }
    .rec-profile-sub { color: var(--bs-secondary-color); font-size: .92rem; margin-top: .15rem; }
    .rec-profile-meta { display: flex; flex-wrap: wrap; gap: .35rem .9rem; margin-top: .35rem; font-size: .88rem; }
    .rec-profile-meta i { color: var(--bs-secondary-color); }
    .rec-profile-badges { display: flex; flex-wrap: wrap; gap: .4rem; flex: 1 1 100%; }
    .rec-expiry {
        display: inline-flex; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 600;
        padding: .3rem .6rem; border-radius: 2rem; border: 1px solid transparent;
    }
    .rec-expiry small { font-weight: 500; opacity: .85; }
    .rec-expiry.is-ok { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); border-color: var(--bs-success-border-subtle); }
    .rec-expiry.is-soon { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); border-color: var(--bs-warning-border-subtle); }
    .rec-expiry.is-expired { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); border-color: var(--bs-danger-border-subtle); }
    .rec-expiry.is-none { background: var(--bs-secondary-bg); color: var(--bs-secondary-color); }
    @media (min-width: 992px) {
        .rec-profile-badges { flex: 0 1 auto; justify-content: flex-end; max-width: 46%; }
    }
    @media (max-width: 575.98px) {
        .rec-profile { padding: .75rem; gap: .6rem .75rem; }
        .rec-profile-photo { width: 64px; height: 64px; }
        .rec-profile-main { flex: 1 1 0; }
        .rec-profile-name { font-size: 1.05rem; }
        .rec-profile-meta { font-size: .8rem; gap: .2rem .6rem; }
        /* Four badges in a 2×2 grid instead of four full-width lines. */
        .rec-profile-badges { display: grid; grid-template-columns: 1fr 1fr; gap: .35rem; }
        .rec-expiry { font-size: .72rem; padding: .25rem .45rem; flex-wrap: wrap; gap: .15rem .3rem; border-radius: .6rem; }
        .rec-expiry small { flex-basis: 100%; }
        .rec-tab { padding: .55rem .7rem; font-size: .92rem; }
    }
</style>
<script>
(function () {
    if (window.RecordTabs) return;

    function navOf(root) { return root.querySelector(':scope > .rec-tabs-nav'); }
    function panesOf(root) { return root.querySelectorAll(':scope > .rec-tab-pane'); }

    function activate(root, key) {
        const nav = navOf(root);
        if (!nav) return;
        nav.querySelectorAll('.rec-tab').forEach(function (btn) {
            const on = btn.dataset.recTab === key;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
            if (on && btn.scrollIntoView && nav.scrollWidth > nav.clientWidth) {
                btn.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        });
        panesOf(root).forEach(function (pane) {
            const on = pane.dataset.recPane === key;
            pane.classList.toggle('active', on);
            if (on) pane.scrollTop = 0; // fixed frame: each tab starts at its top
        });
        root.dispatchEvent(new CustomEvent('rec-tab-shown', { detail: { tab: key }, bubbles: true }));
    }

    function paneKey(el) {
        const pane = el.closest('.rec-tab-pane');
        return pane ? pane.dataset.recPane : null;
    }

    /** Red dot on tabs holding an .is-invalid field; warning count for "missing" markers. */
    function refreshMarks(root) {
        const nav = navOf(root);
        if (!nav) return null;
        let firstError = null;
        panesOf(root).forEach(function (pane) {
            const btn = nav.querySelector('.rec-tab[data-rec-tab="' + pane.dataset.recPane + '"]');
            if (!btn) return;
            const hasError = !!pane.querySelector('.is-invalid');
            btn.classList.toggle('has-error', hasError);
            if (hasError && !firstError) firstError = pane.dataset.recPane;
            const missing = pane.querySelectorAll('.bi-exclamation-circle-fill.text-warning').length;
            const badge = btn.querySelector('.rec-tab-missing');
            if (badge) {
                badge.textContent = missing;
                btn.classList.toggle('has-missing', missing > 0);
            }
        });
        return firstError;
    }

    /** Mark server-side validation errors by field name, then open the first tab with one. */
    function markErrors(root, fields) {
        (fields || []).forEach(function (name) {
            const base = String(name).split('.')[0];
            root.querySelectorAll('[name="' + base + '"], [name="' + base + '[]"]').forEach(function (input) {
                if (input.type === 'hidden') return;
                input.classList.add('is-invalid');
                input.addEventListener('input', function () { input.classList.remove('is-invalid'); refreshMarks(root); }, { once: true });
                input.addEventListener('change', function () { input.classList.remove('is-invalid'); refreshMarks(root); }, { once: true });
            });
        });
        const first = refreshMarks(root);
        if (first) activate(root, first);
    }

    function init(root) {
        if (root.dataset.recTabsReady) return;
        root.dataset.recTabsReady = '1';
        let errors = [];
        try { errors = JSON.parse(root.dataset.errorFields || '[]'); } catch (e) { errors = []; }
        if (errors.length) {
            markErrors(root, errors);
        } else {
            refreshMarks(root);
        }
        // Photo preview in the header follows the photo picker in the form.
        root.closest('form, .rec-scope, body').querySelectorAll('img[data-rec-mirror]').forEach(function (img) {
            const src = document.getElementById(img.dataset.recMirror);
            if (!src || img.dataset.recMirrorReady) return;
            img.dataset.recMirrorReady = '1';
            new MutationObserver(function () { img.src = src.src; }).observe(src, { attributes: true, attributeFilter: ['src'] });
        });
    }

    function initAll(scope) {
        (scope || document).querySelectorAll('[data-rec-tabs]').forEach(init);
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.rec-tabs-nav .rec-tab');
        if (!btn) return;
        const root = btn.closest('[data-rec-tabs]');
        if (root) activate(root, btn.dataset.recTab);
    });

    // A field the browser rejects on submit may sit in a hidden tab — open
    // that tab (first invalid field only) so the browser can show its message.
    let switching = false;
    document.addEventListener('invalid', function (e) {
        if (switching) return;
        const root = e.target.closest && e.target.closest('[data-rec-tabs]');
        const key = root && paneKey(e.target);
        if (!key) return;
        switching = true;
        setTimeout(function () { switching = false; }, 0);
        const pane = e.target.closest('.rec-tab-pane');
        if (!pane.classList.contains('active')) {
            activate(root, key);
            setTimeout(function () { if (e.target.reportValidity) e.target.reportValidity(); }, 30);
        }
    }, true);

    window.RecordTabs = { init: initAll, activate: activate, markErrors: markErrors, refresh: refreshMarks };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }
    new MutationObserver(function (records) {
        records.forEach(function (r) {
            r.addedNodes.forEach(function (n) {
                if (n.nodeType !== 1) return;
                if (n.matches && n.matches('[data-rec-tabs]')) init(n);
                if (n.querySelectorAll) initAll(n);
            });
        });
    }).observe(document.documentElement, { childList: true, subtree: true });
})();
</script>
