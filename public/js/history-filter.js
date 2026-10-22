/*
 * "Job History" popup filters (partials/_history_panel.blade.php):
 *   HistoryPanel.open(bodyElement, url)
 * loads the panel into the popup body, then month / date / search changes
 * reload only the list part ([data-history-list]) so typing isn't lost.
 * Typing a search while the month is still the default switches to
 * "All months", so a search finds people finished in any month.
 */
(function () {
    'use strict';

    function spinner() {
        return '<div class="d-flex justify-content-center py-5"><div class="spinner-border text-secondary" role="status"></div></div>';
    }

    function bind(body, url) {
        const form = body.querySelector('[data-history-filter]');
        const list = body.querySelector('[data-history-list]');
        if (!form || !list) return;
        const month = form.querySelector('[name="month"]');
        let monthTouched = false;
        let timer = null;
        let seq = 0;

        function load() {
            const params = new URLSearchParams();
            form.querySelectorAll('[data-hf]').forEach(el => { if (el.value) params.set(el.name, el.value); });
            const mySeq = ++seq;
            list.style.opacity = '.5';
            fetch(url + (url.includes('?') ? '&' : '?') + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    if (mySeq !== seq) return; // a newer request is on its way
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.querySelector('[data-history-list]');
                    list.innerHTML = fresh ? fresh.innerHTML : html;
                    list.style.opacity = ''; // (Alpine picks up the new cards by itself)
                })
                .catch(() => { list.style.opacity = ''; });
        }

        form.addEventListener('change', e => {
            if (e.target === month) monthTouched = true;
            if (e.target.matches('[data-hf]')) load();
        });
        form.addEventListener('input', e => {
            if (!e.target.matches('input[type="search"][data-hf]')) return;
            if (!monthTouched && month && month.value !== 'all' && e.target.value.trim() !== '') month.value = 'all';
            clearTimeout(timer);
            timer = setTimeout(load, 350);
        });
        const clear = form.querySelector('[data-hf-clear]');
        if (clear) clear.addEventListener('click', () => {
            form.querySelectorAll('[data-hf]').forEach(el => { if (el !== month) el.value = ''; });
            monthTouched = false;
            if (month && month.options.length > 1) month.selectedIndex = 1; // latest month
            load();
        });
    }

    window.HistoryPanel = {
        open(body, url) {
            body.innerHTML = spinner();
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    body.innerHTML = html;
                    bind(body, url);
                })
                .catch(() => { body.innerHTML = '<div class="text-danger text-center p-4">Failed to load history.</div>'; });
        },
    };
})();
