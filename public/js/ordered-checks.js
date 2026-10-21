/*
 * Ordered checkboxes — the order you tick is the order used.
 *
 *   OrderedChecks.attach(container, { selector, name, form, summary, labels })
 *
 * Used by Advanced Export (column order), Advanced Edit (field order) and
 * Advanced Download (document / page order).
 *
 *  - Ticking gives the box the next number (1, 2, 3 …), shown as a badge.
 *  - Clicking a ticked box asks "Remove no. N to choose it again?". On yes the
 *    box is unticked and its number N is left EMPTY — the others keep their
 *    numbers (nothing shifts down). The next box ticked takes N (the lowest
 *    empty number first), then numbering carries on after the highest.
 *  - values() returns the ticked values in number order (empty numbers skipped).
 *  - With `form` + `name`, on submit the ticked boxes are sent as hidden
 *    `name` inputs in number order (the boxes themselves are not sent).
 *  - Boxes ticked in the HTML (defaults) are numbered in page order.
 *  - Changes fire a normal `change` event on the box, so existing counters /
 *    max-selection logic keep working.
 */
(function () {
    'use strict';

    const T = Object.assign({
        confirmTitle: 'Change order no. :n?',
        confirmText: 'Remove ":label" from no. :n — the next item you tick becomes no. :n.',
        confirmOk: 'Yes, choose no. :n again',
        pending: 'Tick the item for no. :n',
        order: 'Order',
        none: 'Tick items in the order you want them.',
    }, window.OrderedChecksText || {});

    const fill = (s, n, label) => String(s).split(':n').join(n).split(':label').join(label || '');

    function labelOf(cb) {
        const l = (cb.id && document.querySelector('label[for="' + cb.id + '"]')) || cb.closest('label') || (cb.parentElement && cb.parentElement.querySelector('label'));
        if (!l) return cb.value;
        const copy = l.cloneNode(true);
        copy.querySelectorAll('.oc-badge').forEach(b => b.remove());
        return copy.textContent.trim();
    }

    function attach(container, opts) {
        if (!container || container.dataset.ocBound === '1') return container && container._oc;
        container.dataset.ocBound = '1';
        opts = opts || {};
        const selector = opts.selector || 'input[type="checkbox"]';
        const boxes = () => Array.from(container.querySelectorAll(selector));
        let slots = []; // slots[i] = checkbox at number i+1, or null (empty number)
        let busy = false;

        function badge(cb) {
            let b = cb._ocBadge;
            if (!b) {
                b = document.createElement('span');
                b.className = 'oc-badge';
                const l = (cb.id && container.querySelector('label[for="' + cb.id + '"]')) || cb.nextElementSibling;
                (l && l.parentNode ? l : cb).insertAdjacentElement(l ? 'afterbegin' : 'afterend', b);
                cb._ocBadge = b;
            }
            return b;
        }

        function render() {
            boxes().forEach(cb => {
                const n = slots.indexOf(cb) + 1;
                const b = badge(cb);
                b.textContent = n > 0 ? n : '';
                b.classList.toggle('is-on', n > 0);
                cb.closest('.form-check')?.classList.toggle('oc-on', n > 0);
            });
            const hole = slots.indexOf(null);
            container.classList.toggle('oc-has-hole', hole >= 0);
            if (opts.summary) {
                const el = typeof opts.summary === 'string' ? container.querySelector(opts.summary) || document.querySelector(opts.summary) : opts.summary;
                if (el) {
                    const parts = slots.map((cb, i) => cb
                        ? '<span class="oc-chip"><b>' + (i + 1) + '</b> ' + escapeHtml(labelOf(cb)) + '</span>'
                        : '<span class="oc-chip is-hole"><b>' + (i + 1) + '</b> ' + escapeHtml(fill(T.pending, i + 1)) + '</span>');
                    el.innerHTML = parts.length ? '<span class="oc-chip-title">' + escapeHtml(T.order) + ':</span>' + parts.join('<i class="bi bi-chevron-right oc-sep"></i>') : '<span class="text-muted">' + escapeHtml(T.none) + '</span>';
                }
            }
        }

        function trim() { while (slots.length && slots[slots.length - 1] === null) slots.pop(); }

        function place(cb) {
            if (slots.includes(cb)) return;
            const hole = slots.indexOf(null);
            if (hole >= 0) slots[hole] = cb; else slots.push(cb);
        }

        function vacate(cb) {
            const i = slots.indexOf(cb);
            if (i >= 0) slots[i] = null;
            trim();
        }

        function fire(cb) { cb.dispatchEvent(new Event('change', { bubbles: true })); }

        // Clicking a ticked box: confirm first, then untick and leave its number empty.
        container.addEventListener('click', function (e) {
            const cb = e.target.closest(selector);
            if (!cb || !container.contains(cb)) return;
            if (cb.checked) { // it was just ticked by this click
                place(cb);
                render();
                return;
            }
            // it was ticked before this click → keep it ticked until confirmed
            const n = slots.indexOf(cb) + 1;
            if (n <= 0) { render(); return; }
            e.preventDefault();
            if (busy) return;
            busy = true;
            const ask = window.appConfirm
                ? window.appConfirm(fill(T.confirmText, n, labelOf(cb)), { title: fill(T.confirmTitle, n), confirmText: fill(T.confirmOk, n) })
                : Promise.resolve(window.confirm(fill(T.confirmText, n, labelOf(cb))));
            Promise.resolve(ask).then(ok => {
                busy = false;
                if (!ok) return;
                cb.checked = false;
                vacate(cb);
                render();
                fire(cb);
            });
        });

        // Keyboard (space) and programmatic changes.
        container.addEventListener('change', function (e) {
            const cb = e.target.closest(selector);
            if (!cb) return;
            if (cb.checked) place(cb); else if (slots.includes(cb)) vacate(cb);
            render();
        });

        // Untick everything and start numbering from 1 again.
        function clear() {
            boxes().forEach(cb => { if (cb.checked) { cb.checked = false; fire(cb); } });
            slots = [];
            render();
        }
        container.addEventListener('click', function (e) {
            if (e.target.closest('[data-oc-clear]')) { e.preventDefault(); clear(); }
        });

        function values() { return slots.filter(Boolean).filter(cb => cb.checked).map(cb => cb.value); }

        function reset(fromDom) {
            slots = [];
            if (fromDom !== false) boxes().filter(cb => cb.checked).forEach(cb => slots.push(cb));
            render();
        }

        if (opts.form && opts.name) {
            const form = typeof opts.form === 'string' ? document.querySelector(opts.form) : opts.form;
            form && form.addEventListener('submit', function () {
                form.querySelectorAll('input[data-oc-ordered]').forEach(el => el.remove());
                boxes().forEach(cb => { if (cb.name) { cb.dataset.ocName = cb.name; cb.removeAttribute('name'); } });
                values().forEach(v => {
                    const h = document.createElement('input');
                    h.type = 'hidden'; h.name = opts.name; h.value = v; h.setAttribute('data-oc-ordered', '1');
                    form.appendChild(h);
                });
                // put the names back for the next use of the form (modal reopened, back button…)
                setTimeout(() => boxes().forEach(cb => { if (cb.dataset.ocName) cb.name = cb.dataset.ocName; }), 0);
            }, true);
            form && form.addEventListener('reset', () => setTimeout(() => reset(), 0));
        }

        reset();
        const api = { values, reset, render, clear };
        container._oc = api;
        return api;
    }

    // Styles (self-contained; colours follow the theme, so dark mode works).
    if (!document.getElementById('ordered-checks-css')) {
        const st = document.createElement('style');
        st.id = 'ordered-checks-css';
        st.textContent = [
            '.oc-badge{display:none;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;margin-right:.35rem;border-radius:999px;background:var(--bs-primary);color:#fff;font-size:.72rem;font-weight:700;line-height:1;vertical-align:text-bottom}',
            '.oc-badge.is-on{display:inline-flex}',
            '.oc-on > .form-check-label{font-weight:600}',
            '.oc-summary{display:flex;flex-wrap:wrap;align-items:center;gap:.3rem .35rem;font-size:.8rem;min-height:1.6rem}',
            '.oc-chip-title{color:var(--bs-secondary-color);margin-right:.15rem}',
            '.oc-chip{display:inline-flex;align-items:center;gap:.3rem;padding:.1rem .5rem .1rem .15rem;border-radius:999px;border:1px solid var(--bs-border-color);background:var(--bs-body-bg)}',
            '.oc-chip b{display:inline-flex;align-items:center;justify-content:center;min-width:1.2rem;height:1.2rem;border-radius:999px;background:var(--bs-primary);color:#fff;font-size:.7rem}',
            '.oc-chip.is-hole{border-style:dashed;border-color:var(--bs-warning);color:var(--bs-warning-text-emphasis)}',
            '.oc-chip.is-hole b{background:var(--bs-warning);color:#000}',
            '.oc-sep{color:var(--bs-secondary-color);font-size:.65rem}',
        ].join('\n');
        document.head.appendChild(st);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    window.OrderedChecks = { attach };
})();
