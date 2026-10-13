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

    /* Attachment grid (x-file-input-group variant="card"). */
    .att-section-title {
        display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
        font-weight: 700; font-size: .95rem; padding: .5rem .8rem; margin: .25rem 0 .75rem;
        border-radius: .55rem; border-left: 4px solid var(--bs-primary);
        background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis);
    }
    .att-section-title .att-section-count {
        margin-left: auto; font-size: .78rem; font-weight: 600; padding: .15rem .55rem; border-radius: 1rem;
        background: var(--bs-body-bg); color: var(--bs-body-color);
    }
    .att-grid {
        display: grid; gap: .65rem; margin-bottom: 1.25rem;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
    .att-grid.att-grid-wide { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
    .att-card {
        display: flex; flex-direction: column; gap: .45rem; min-width: 0;
        padding: .6rem .7rem; border-radius: .65rem;
        border: 1px solid var(--bs-border-color); background: var(--bs-body-bg);
        transition: border-color .15s, box-shadow .15s;
    }
    .att-card:hover { box-shadow: 0 2px 10px rgba(0, 0, 0, .08); }
    .att-card.has-file { border-color: var(--bs-success-border-subtle); box-shadow: inset 3px 0 0 var(--bs-success); }
    .att-card.is-pending { border-color: var(--bs-primary-border-subtle); box-shadow: inset 3px 0 0 var(--bs-primary); }
    .att-card:has(.is-invalid) { border-color: var(--bs-danger); box-shadow: inset 3px 0 0 var(--bs-danger); }
    .att-card-head { display: flex; align-items: center; gap: .45rem; min-width: 0; }
    .att-num {
        flex: none; width: 1.55rem; height: 1.55rem; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700; background: var(--bs-secondary-bg); color: var(--bs-secondary-color);
    }
    .att-card.has-file .att-num { background: var(--bs-success); color: #fff; }
    .att-card.is-pending .att-num { background: var(--bs-primary); color: #fff; }
    .att-title {
        flex: 1 1 auto; min-width: 0; margin: 0; cursor: pointer;
        font-weight: 600; font-size: .88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .att-status { flex: none; font-size: .68rem; font-weight: 600; padding: .12rem .45rem; border-radius: 1rem; white-space: nowrap; }
    .att-status.ok { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
    .att-status.none { background: var(--bs-secondary-bg); color: var(--bs-secondary-color); }
    .att-card-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; }
    .att-spacer { flex: 1 1 auto; }
    .att-btn {
        --bs-btn-padding-y: .2rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .78rem;
        display: inline-flex; align-items: center; gap: .3rem; margin: 0;
        border: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); color: var(--bs-body-color);
    }
    .att-btn:hover { background: var(--bs-secondary-bg); color: var(--bs-body-color); }
    .att-btn-view { background: var(--bs-success-bg-subtle); border-color: var(--bs-success-border-subtle); color: var(--bs-success-text-emphasis); }
    .att-btn-pdf { background: var(--bs-danger-bg-subtle); border-color: var(--bs-danger-border-subtle); color: var(--bs-danger-text-emphasis); }
    .att-btn-edit { background: var(--bs-warning-bg-subtle); border-color: var(--bs-warning-border-subtle); color: var(--bs-warning-text-emphasis); }
    .att-btn-pick { background: var(--bs-primary-bg-subtle); border-color: var(--bs-primary-border-subtle); color: var(--bs-primary-text-emphasis); cursor: pointer; }
    /* Keyboard focus on the hidden input shows on its "choose file" button. */
    .att-card:has(.att-input:focus-visible) .att-btn-pick { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
    .att-pending {
        display: flex; gap: .4rem; align-items: flex-start; font-size: .75rem; line-height: 1.3;
        padding: .3rem .5rem; border-radius: .4rem; word-break: break-all;
        background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis);
    }
    .att-pending[hidden] { display: none; }
    .att-desc { font-size: .8rem; }
    .att-card .invalid-feedback { font-size: .75rem; margin-top: 0; }
    .att-card .att-extra { display: flex; align-items: center; gap: .4rem; font-size: .8rem; }
    .att-card .att-extra .form-control { font-size: .8rem; }
    /* Narrow cards: icon-only buttons so a card's actions stay on one line. */
    .att-card { container-type: inline-size; }
    .att-btn-view span { display: none; }
    @container (max-width: 270px) {
        .att-card.has-file .att-btn-pick span { display: none; }
    }
    /* Phones: one compact row per empty card — name on the left, buttons on the right. */
    @media (max-width: 575.98px) {
        .att-grid, .att-grid.att-grid-wide { grid-template-columns: 1fr; gap: .4rem; }
        .att-card { flex-direction: row; flex-wrap: wrap; align-items: center; padding: .45rem .55rem; gap: .35rem .5rem; }
        .att-card-head { flex: 1 1 140px; }
        .att-card-actions { flex: 0 0 auto; margin-left: auto; }
        .att-card .att-spacer { display: none; }
        .att-status.none { display: none; }
        .att-card > .att-pending, .att-card > .att-desc, .att-card > .att-extra,
        .att-card > .invalid-feedback { flex: 1 1 100%; }
    }

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
    .rec-profile-actions { flex: 1 1 100%; grid-column: 1 / -1; display: flex; justify-content: flex-end; }
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
    // Panes may sit directly under the root or inside a <form> within it
    // (employer edit: the form spans some tabs, lists outside it others).
    function panesOf(root) {
        return Array.prototype.filter.call(root.querySelectorAll('.rec-tab-pane'), function (p) {
            return p.closest('[data-rec-tabs]') === root;
        });
    }

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
        // Elements shown only on some tabs, e.g. the save bar of a form that
        // covers only part of the tabs: data-rec-only="info access".
        root.querySelectorAll('[data-rec-only]').forEach(function (el) {
            if (el.closest('[data-rec-tabs]') !== root) return;
            el.hidden = el.dataset.recOnly.split(/\s+/).indexOf(key) === -1;
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
        // Starting tab: server hint (data-rec-initial), or the pane holding the
        // element the URL hash points at (e.g. #employee-card-12 after saving
        // an employee from the employer page).
        let start = root.dataset.recInitial || null;
        let hashTarget = null;
        if (location.hash.length > 1) {
            try { hashTarget = root.querySelector(location.hash); } catch (e) { hashTarget = null; }
            if (hashTarget && paneKey(hashTarget)) start = paneKey(hashTarget);
        }
        const active = root.querySelector('.rec-tab-pane.active');
        if (start && (!active || active.dataset.recPane !== start)) {
            activate(root, start);
        } else if (active) {
            activate(root, active.dataset.recPane); // applies data-rec-only
        }
        if (hashTarget) setTimeout(function () { hashTarget.scrollIntoView({ block: 'center' }); }, 50);

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

    // Attachment cards: show the newly chosen file(s) — from the picker or the
    // scanner, which writes into the same input and fires "change".
    function showPending(input) {
        const card = input.closest('[data-att-card]');
        if (!card) return;
        const box = card.querySelector('.att-pending');
        const files = input.files ? Array.from(input.files) : [];
        card.classList.toggle('is-pending', files.length > 0);
        if (!box) return;
        box.hidden = files.length === 0;
        const names = box.querySelector('.att-pending-names');
        if (names) names.textContent = files.map(function (f) { return f.name; }).join(', ');
    }
    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('[data-att-card] input[type="file"]')) showPending(e.target);
    });
    document.addEventListener('reset', function (e) {
        setTimeout(function () {
            e.target.querySelectorAll && e.target.querySelectorAll('[data-att-card] input[type="file"]').forEach(showPending);
        }, 0);
    });

    // Preview → Edit → back: the Edit button in a preview header remembers
    // "reopen this preview on this page", goes to the edit page with
    // return_url = this page, and after saving (or cancelling back) the
    // preview opens again with the fresh data.
    const REOPEN_KEY = 'recReopenPreview';
    document.addEventListener('click', function (e) {
        const btn = e.target.closest && e.target.closest('[data-preview-edit]');
        if (!btn) return;
        e.preventDefault();
        const here = location.href.split('#')[0];
        try {
            sessionStorage.setItem(REOPEN_KEY, JSON.stringify({
                type: btn.dataset.type, id: btn.dataset.id,
                page: location.pathname + location.search, at: Date.now(),
            }));
        } catch (err) { /* storage unavailable: just go to the edit page */ }
        const url = btn.getAttribute('href');
        location.href = url + (url.indexOf('?') === -1 ? '?' : '&') + 'return_url=' + encodeURIComponent(here);
    });
    // pageshow (not load): also fires when Back restores the page from the browser cache.
    window.addEventListener('pageshow', function () {
        let saved = null;
        try { saved = JSON.parse(sessionStorage.getItem(REOPEN_KEY) || 'null'); } catch (err) { saved = null; }
        if (!saved) return;
        if (Date.now() - saved.at > 30 * 60 * 1000) { sessionStorage.removeItem(REOPEN_KEY); return; }
        if (saved.page !== location.pathname + location.search) return; // not back yet (e.g. still on the edit page)
        sessionStorage.removeItem(REOPEN_KEY);
        // Reuse the normal preview opener (.btn-preview handler in resources/js/app.js).
        setTimeout(function () {
            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'btn-preview d-none';
            trigger.dataset.modelType = saved.type;
            trigger.dataset.modelId = saved.id;
            document.body.appendChild(trigger);
            trigger.click();
            trigger.remove();
        }, 300);
    });

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
