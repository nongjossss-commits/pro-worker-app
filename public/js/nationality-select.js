/**
 * Nationality picker (components/nationality-select.blade.php).
 *
 * Turns every <select class="js-nationality-select"> into a search box:
 *   - type to narrow the list (matches Thai / English / Myanmar / Chinese names and the country code)
 *   - the ▾ button (or clicking an empty box) shows the whole list, scroll and pick
 *   - with nothing typed, the 4 nationalities this browser picked most recently come first
 * The original <select> stays in the form (hidden) and is what gets submitted;
 * picking sets its value and fires input + change, so existing listeners and
 * Alpine x-model keep working. Recent picks live in this browser only (localStorage).
 */
(function () {
    'use strict';

    const RECENT_KEY = 'pw.nationality.recent';
    const RECENT_MAX = 4;
    const combos = [];

    function readRecent() {
        try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').filter(v => typeof v === 'string' && v); }
        catch (e) { return []; }
    }
    function pushRecent(value) {
        if (!value) return;
        try {
            const list = [value].concat(readRecent().filter(v => v !== value)).slice(0, RECENT_MAX);
            localStorage.setItem(RECENT_KEY, JSON.stringify(list));
        } catch (e) { /* storage blocked — picker still works without history */ }
    }
    function esc(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function enhance(select) {
        if (select.dataset.natReady) return;
        select.dataset.natReady = '1';

        // UI text: from the component's data-nat-* attributes, else the layout's window.NAT_I18N.
        const t = (k) => select.dataset['nat' + k] || (window.NAT_I18N || {})[k] || '';
        const small = select.classList.contains('form-select-sm');
        const wrap = document.createElement('div');
        wrap.className = 'nat-combo position-relative';
        const inlineStyle = select.getAttribute('style') || '';
        wrap.setAttribute('style', inlineStyle);
        if (/width\s*:\s*auto/i.test(inlineStyle)) wrap.style.width = '13rem';   // filter bars: compact, like the other selects

        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.className = 'form-control nat-combo-input' + (small ? ' form-control-sm' : '');
        if (select.classList.contains('is-invalid')) input.classList.add('is-invalid');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        const emptyOpt = [...select.options].find(o => o.value === '');
        input.placeholder = emptyOpt ? emptyOpt.textContent.trim() : (t('Placeholder'));
        if (select.id) { input.id = select.id + '__search'; }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.tabIndex = -1;
        btn.className = 'nat-combo-toggle';
        btn.setAttribute('aria-label', t('All'));
        btn.innerHTML = '<i class="bi bi-chevron-down"></i>';

        const list = document.createElement('div');
        list.className = 'nat-combo-list d-none';
        list.setAttribute('role', 'listbox');

        select.classList.add('d-none');
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(input);
        wrap.appendChild(btn);
        wrap.appendChild(list);
        wrap.appendChild(select);

        // Point existing <label for="select-id"> at the visible box.
        if (select.id) {
            document.querySelectorAll('label[for="' + CSS.escape(select.id) + '"]').forEach(l => l.setAttribute('for', input.id));
        }

        let items = [];      // rendered, selectable rows (in order)
        let active = -1;
        let lastValue = null;
        let lastDisabled = null;

        function options() {
            return [...select.options].map(o => ({
                value: o.value,
                label: o.textContent.trim(),
                search: (o.dataset.search || '') + ' ' + o.textContent.trim().toLowerCase(),
                main: o.dataset.main === '1',
            }));
        }
        function labelOf(value) {
            const o = [...select.options].find(x => x.value === value);
            return o && value !== '' ? o.textContent.trim() : '';
        }
        function syncFromSelect() {
            if (select.value !== lastValue && document.activeElement !== input) {
                input.value = labelOf(select.value);
            }
            lastValue = select.value;
            if (select.disabled !== lastDisabled) {
                input.disabled = btn.disabled = select.disabled;
                lastDisabled = select.disabled;
            }
        }

        function position() {
            const r = input.getBoundingClientRect();
            const below = window.innerHeight - r.bottom;
            const h = Math.min(320, Math.max(160, below - 12));
            list.style.left = r.left + 'px';
            list.style.width = Math.max(r.width, 220) + 'px';
            if (below < 180 && r.top > below) {
                list.style.top = '';
                list.style.bottom = (window.innerHeight - r.top + 2) + 'px';
                list.style.maxHeight = Math.min(320, r.top - 12) + 'px';
            } else {
                list.style.bottom = '';
                list.style.top = (r.bottom + 2) + 'px';
                list.style.maxHeight = h + 'px';
            }
        }

        function render(query) {
            const q = (query || '').trim().toLowerCase();
            const all = options();
            let html = '';
            items = [];
            const row = (it, extra) => {
                items.push(it);
                const isSel = it.value === select.value;
                return '<div class="nat-combo-item' + (isSel ? ' is-selected' : '') + (extra || '') + '" role="option" data-idx="' + (items.length - 1) + '">'
                    + esc(it.label) + (isSel ? '<i class="bi bi-check2 ms-auto"></i>' : '') + '</div>';
            };
            const head = (t) => '<div class="nat-combo-head">' + esc(t) + '</div>';

            if (!q) {
                const empty = all.find(o => o.value === '');
                if (empty) html += row(empty, ' text-muted');
                const recent = readRecent().map(v => all.find(o => o.value === v)).filter(Boolean);
                if (recent.length) {
                    html += head(t('Recent'));
                    recent.forEach(it => { html += row(it); });
                }
                const main = all.filter(o => o.main);
                if (main.length) {
                    html += head(t('Main'));
                    main.forEach(it => { html += row(it); });
                }
                html += head(t('All'));
                all.filter(o => o.value !== '' && !o.main).forEach(it => { html += row(it); });
            } else {
                const hits = all.filter(o => o.value !== '' && o.search.includes(q));
                hits.sort((a, b) => (b.label.toLowerCase().startsWith(q) - a.label.toLowerCase().startsWith(q)) || (b.main - a.main));
                hits.forEach(it => { html += row(it); });
                if (!hits.length) html = '<div class="nat-combo-none">' + esc(t('None')) + '</div>';
            }
            list.innerHTML = html;
            active = q && items.length ? 0 : items.findIndex(it => it.value === select.value);
            highlight(false);
        }

        function highlight(scroll) {
            list.querySelectorAll('.nat-combo-item.is-active').forEach(el => el.classList.remove('is-active'));
            const el = list.querySelector('[data-idx="' + active + '"]');
            if (el) {
                el.classList.add('is-active');
                if (scroll !== false) el.scrollIntoView({ block: 'nearest' });
            }
        }

        function open(query) {
            if (input.disabled) return;
            render(query);
            position();
            list.classList.remove('d-none');
            input.setAttribute('aria-expanded', 'true');
            const sel = list.querySelector('.is-active');
            if (sel && !query) sel.scrollIntoView({ block: 'nearest' });
        }
        function close() {
            list.classList.add('d-none');
            input.setAttribute('aria-expanded', 'false');
        }
        const isOpen = () => !list.classList.contains('d-none');

        function choose(it) {
            select.value = it.value;
            input.value = it.value === '' ? '' : it.label;
            lastValue = select.value;
            pushRecent(it.value);
            close();
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        input.addEventListener('focus', () => { input.select(); open(''); });
        input.addEventListener('click', () => { if (!isOpen()) open(''); });
        input.addEventListener('input', () => open(input.value));
        input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!isOpen()) { open(''); return; }
                if (!items.length) return;
                active = e.key === 'ArrowDown' ? Math.min(items.length - 1, active + 1) : Math.max(0, active - 1);
                highlight();
            } else if (e.key === 'Enter') {
                if (isOpen() && items[active]) { e.preventDefault(); choose(items[active]); }
            } else if (e.key === 'Escape') {
                if (isOpen()) { e.preventDefault(); close(); input.value = labelOf(select.value); }
            } else if (e.key === 'Tab') {
                if (isOpen() && input.value.trim() && items[active] && input.value !== labelOf(select.value)) choose(items[active]);
            }
        });
        input.addEventListener('blur', () => {
            setTimeout(() => {
                if (wrap.contains(document.activeElement)) return;
                close();
                const typed = input.value.trim();
                if (typed === '' && select.value !== '' && [...select.options].some(o => o.value === '')) {
                    choose({ value: '', label: '' });
                    return;
                }
                input.value = labelOf(select.value);   // typed text that wasn't picked → back to the current choice
            }, 120);
        });
        btn.addEventListener('mousedown', (e) => e.preventDefault());
        btn.addEventListener('click', () => {
            if (isOpen()) { close(); return; }
            input.focus();
            open('');
        });
        list.addEventListener('mousedown', (e) => e.preventDefault());
        list.addEventListener('click', (e) => {
            const el = e.target.closest('.nat-combo-item');
            if (el) choose(items[+el.dataset.idx]);
        });
        list.addEventListener('mousemove', (e) => {
            const el = e.target.closest('.nat-combo-item');
            if (el && +el.dataset.idx !== active) { active = +el.dataset.idx; highlight(false); }
        });
        window.addEventListener('scroll', () => { if (isOpen()) position(); }, true);
        window.addEventListener('resize', () => { if (isOpen()) position(); });
        select.addEventListener('change', () => { if (document.activeElement !== input) input.value = labelOf(select.value); lastValue = select.value; });

        syncFromSelect();
        combos.push(syncFromSelect);
    }

    function scan(root) {
        (root || document).querySelectorAll('select.js-nationality-select').forEach(enhance);
    }

    // Scripts and Alpine may set select.value directly (no event) — keep the visible box in step.
    setInterval(() => combos.forEach(fn => fn()), 400);

    function start() {
        scan();
        new MutationObserver((muts) => {
            for (const m of muts) {
                for (const n of m.addedNodes) {
                    if (n.nodeType !== 1) continue;
                    if (n.matches && n.matches('select.js-nationality-select')) enhance(n);
                    else if (n.querySelector && n.querySelector('select.js-nationality-select')) scan(n);
                }
            }
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
    window.initNationalitySelects = scan;
})();
