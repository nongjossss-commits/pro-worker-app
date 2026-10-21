/*
 * List tables → cards on phones (styles: public/css/mobile-table-cards.css).
 *
 * Every list table in the program is picked up automatically — including
 * rows loaded later by AJAX — and each cell gets data-label from its column
 * header. Computers see the table unchanged (the CSS only applies < 768px).
 *
 * Left as a normal (side-scrolling) table:
 *  - data-mobile-table="scroll" on the table or any wrapper (accounting
 *    reports, ledgers, statements, document line items);
 *  - editable grids (text / number / date inputs, selects or textareas in
 *    the rows), tables without a header row, tables using rowspan, and
 *    tables nested in another table.
 */
(function () {
    'use strict';

    const SELECT_ALL = (window.MobileTableCardsText && window.MobileTableCardsText.selectAll) || 'Select all';

    function headerCells(table) {
        const thead = table.tHead;
        if (!thead || !thead.rows.length) return null;
        const row = thead.rows[thead.rows.length - 1];
        const labels = [];
        Array.from(row.cells).forEach(th => {
            const copy = th.cloneNode(true);
            copy.querySelectorAll('input, button, .dropdown-menu, .visually-hidden, .sr-only').forEach(n => n.remove());
            const text = copy.textContent.replace(/\s+/g, ' ').trim();
            const span = Math.max(1, th.colSpan || 1);
            for (let i = 0; i < span; i++) labels.push({ text, th, hasCheckbox: !!th.querySelector('input[type="checkbox"]') });
        });
        return labels;
    }

    function qualifies(table) {
        if (table.closest('[data-mobile-table="scroll"]')) return false;
        if (table.parentElement && table.parentElement.closest('table')) return false;
        if (!table.tBodies.length) return false;
        const labels = headerCells(table);
        if (!labels || labels.length < 3) return false;
        const body = table.tBodies[0];
        if (body.querySelector('td[rowspan]:not([rowspan="1"]), th[rowspan]:not([rowspan="1"])')) return false;
        if (body.querySelector('input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]), select, textarea')) return false;
        return true;
    }

    const isActionCell = td => {
        const btns = td.querySelectorAll('a.btn, button, .btn, form');
        if (!btns.length) return false;
        const clone = td.cloneNode(true);
        clone.querySelectorAll('a.btn, button, .btn, form, .dropdown-menu').forEach(n => n.remove());
        return clone.textContent.trim() === '';
    };
    const isLeadCell = (td, label) => {
        const text = td.textContent.trim();
        if (label.hasCheckbox || (td.querySelector('input[type="checkbox"]') && text === '')) return true;
        if (label.text === '' && text.length <= 4) return true;
        return /^(#|no\.?|ลำดับ|ที่)$/i.test(label.text) && /^\d{0,5}\.?$/.test(text);
    };

    function labelRow(tr, labels) {
        if (tr.dataset.mtc === '1') return;
        const cells = Array.from(tr.cells);
        if (cells.length === 1 && (cells[0].colSpan || 1) > 1) {
            cells[0].classList.add('mtc-full');
            tr.dataset.mtc = '1';
            return;
        }
        let col = 0;
        let titled = false;
        cells.forEach(td => {
            const label = labels[col] || { text: '' };
            col += Math.max(1, td.colSpan || 1);
            if ((td.colSpan || 1) > 1) { td.classList.add('mtc-full'); return; }
            if (isLeadCell(td, label)) { td.classList.add('mtc-lead'); return; }
            if (isActionCell(td)) { td.classList.add('mtc-actions'); return; }
            if (!titled && label.text !== '' && td.textContent.trim() !== '') {
                td.classList.add('mtc-title');
                titled = true;
                return;
            }
            if (label.text) td.setAttribute('data-label', label.text);
            if (td.innerHTML.trim() === '') td.classList.add('mtc-empty');
        });
        tr.dataset.mtc = '1';
    }

    function selectAllBar(table, labels) {
        const lead = labels.find(l => l.hasCheckbox);
        if (!lead || table.dataset.mtcSelectAll === '1') return;
        const master = lead.th.querySelector('input[type="checkbox"]');
        if (!master) return;
        table.dataset.mtcSelectAll = '1';
        const bar = document.createElement('label');
        bar.className = 'mtc-selectall';
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'form-check-input m-0';
        cb.checked = master.checked;
        cb.addEventListener('change', () => { if (master.checked !== cb.checked) master.click(); });
        master.addEventListener('change', () => { cb.checked = master.checked; cb.indeterminate = master.indeterminate; });
        bar.appendChild(cb);
        bar.appendChild(document.createTextNode(SELECT_ALL));
        const anchor = table.closest('.table-responsive') || table;
        anchor.parentNode.insertBefore(bar, anchor);
    }

    function apply(table) {
        if (!qualifies(table)) {
            table.classList.remove('mtc');
            return;
        }
        const labels = headerCells(table);
        table.classList.add('mtc');
        Array.from(table.tBodies).forEach(tb => Array.from(tb.rows).forEach(tr => labelRow(tr, labels)));
        if (table.tFoot) Array.from(table.tFoot.rows).forEach(tr => labelRow(tr, labels));
        selectAllBar(table, labels);
    }

    function scan(root) {
        (root || document).querySelectorAll('table').forEach(apply);
    }

    let pending = false;
    function schedule() {
        if (pending) return;
        pending = true;
        requestAnimationFrame(() => { pending = false; scan(document); });
    }

    function start() {
        scan(document);
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(muts => {
                for (const m of muts) {
                    for (const n of m.addedNodes) {
                        if (n.nodeType === 1 && (n.tagName === 'TR' || n.tagName === 'TABLE' || n.tagName === 'TBODY' || (n.querySelector && n.querySelector('table, tr')))) { schedule(); return; }
                    }
                }
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    window.MobileTableCards = { scan, apply };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
