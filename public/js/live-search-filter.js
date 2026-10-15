// Live-search dropdown for program-wide search/filter boxes — modeled after
// the Activity Log subject search (fetch-on-keystroke + dropdown preview).
// Auto-initializes any <input class="live-search-input" data-live-search-endpoint="...">
// Selecting a suggestion fills the input and resubmits its form (GET reload),
// reusing whatever filter mechanism the page already has — it never navigates away.
(function () {
    function debounce(fn, wait) {
        let timer = null;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function initLiveSearchInput(input) {
        if (input.dataset.liveSearchInit) return;
        input.dataset.liveSearchInit = '1';

        const endpoint = input.dataset.liveSearchEndpoint;
        if (!endpoint) return;

        const minChars = parseInt(input.dataset.liveSearchMinChars || '2', 10);
        const dropdown = document.getElementById(input.id + '-dropdown');
        if (!dropdown) return;

        let items = [];
        let activeIndex = -1;
        let currentController = null;

        function positionDropdown() {
            const rect = input.getBoundingClientRect();
            dropdown.style.position = 'fixed';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.top = rect.bottom + 'px';
            dropdown.style.width = rect.width + 'px';
        }

        function closeDropdown() {
            dropdown.classList.add('d-none');
            dropdown.innerHTML = '';
            items = [];
            activeIndex = -1;
        }

        function highlight(idx) {
            const nodes = dropdown.querySelectorAll('.live-search-result-item');
            nodes.forEach((n) => n.classList.remove('active-result'));
            if (idx >= 0 && nodes[idx]) {
                nodes[idx].classList.add('active-result');
                nodes[idx].scrollIntoView({ block: 'nearest' });
            }
            activeIndex = idx;
        }

        function selectItem(idx) {
            const item = items[idx];
            if (!item) return;
            // 'value' (when present) is the raw, literally-searchable text
            // (e.g. the name field without a display-only title prefix) —
            // distinct from 'label', which may be dressed up for the
            // dropdown (title prefix, photo caption, etc.) and would not
            // itself LIKE-match the underlying column.
            input.value = (item.value !== undefined && item.value !== null && item.value !== '') ? item.value : item.label;
            // Linked-filter pattern (e.g. "pick an employer" box feeding a
            // second, scoped search box) — the hidden companion field carries
            // the exact id so the scoped box's query is precise, not a second
            // name-text match that could hit the wrong row on a duplicate name.
            if (input.dataset.liveSearchIdInput) {
                const idField = document.getElementById(input.dataset.liveSearchIdInput);
                if (idField) idField.value = (item.id !== undefined && item.id !== null) ? item.id : '';
            }
            // Exact pick (e.g. one employee among several with the same name):
            // the result may carry { field: value } — put it in hidden fields of
            // the form so the list shows that one record, not every name match.
            setPick(item.pick || null);
            closeDropdown();
            if (input.form) {
                if (typeof input.form.requestSubmit === 'function') {
                    input.form.requestSubmit();
                } else {
                    input.form.submit();
                }
            }
        }

        function renderItems(data) {
            items = Array.isArray(data) ? data : [];
            activeIndex = -1;

            if (!items.length) {
                dropdown.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1"></i>' + escapeHtml(input.dataset.liveSearchEmpty || 'ไม่พบข้อมูลที่ตรงกัน') + '</div>';
                positionDropdown();
                dropdown.classList.remove('d-none');
                return;
            }

            let html = '';
            items.forEach((item, idx) => {
                const groupBadge = item.group
                    ? '<span class="badge bg-info-subtle text-info-emphasis me-2">' + escapeHtml(item.group) + '</span>'
                    : '';
                const avatar = item.photo
                    ? '<img src="' + escapeHtml(item.photo) + '" alt="" class="rounded-circle me-2 flex-shrink-0" style="width:32px;height:32px;object-fit:cover;">'
                    : '';
                html += '<div class="live-search-result-item px-3 py-2 border-bottom" data-index="' + idx + '" style="cursor:pointer;">'
                    + '<div class="d-flex align-items-center">'
                    + avatar
                    + '<div class="flex-grow-1" style="min-width:0;">'
                    + '<div class="d-flex align-items-center">'
                    + groupBadge
                    + '<span class="fw-bold text-truncate">' + escapeHtml(item.label) + '</span>'
                    + '</div>'
                    + (item.sub_label ? '<div class="small text-muted text-truncate">' + escapeHtml(item.sub_label) + '</div>' : '')
                    + '</div></div></div>';
            });
            dropdown.innerHTML = html;
            positionDropdown();
            dropdown.classList.remove('d-none');
        }

        // The user is editing the text again — the previously selected id (if
        // any) no longer necessarily matches what's typed, so drop it rather
        // than silently submit a stale id alongside new, unrelated text.
        if (input.dataset.liveSearchIdInput) {
            input.addEventListener('input', function () {
                const idField = document.getElementById(input.dataset.liveSearchIdInput);
                if (idField) idField.value = '';
            });
        }

        // Exact-pick hidden fields (data-live-search-pick) — set from a picked
        // result's `pick`, cleared as soon as the user types again in any box.
        function setPick(pick) {
            if (!input.form) return;
            input.form.querySelectorAll('input[data-live-search-pick]').forEach(function (f) { f.value = ''; });
            if (!pick) return;
            Object.keys(pick).forEach(function (name) {
                let field = input.form.querySelector('input[type="hidden"][name="' + CSS.escape(name) + '"]');
                if (!field) {
                    field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = name;
                    field.setAttribute('data-live-search-pick', '');
                    input.form.appendChild(field);
                }
                field.value = pick[name];
            });
        }
        input.addEventListener('input', function () { setPick(null); });

        const doSearch = debounce(function () {
            const q = input.value.trim();
            if (q.length < minChars) {
                closeDropdown();
                return;
            }
            if (currentController) currentController.abort();
            currentController = new AbortController();

            // Build the request from the endpoint's own query string plus the
            // enclosing form's CURRENT field values (read live, not baked in
            // at page-render time) — so a hidden field a page updates via JS
            // without reloading (e.g. a client-side tab switcher) is always
            // fresh by the time a keystroke fires this fetch.
            const splitAt = endpoint.indexOf('?');
            const base = splitAt === -1 ? endpoint : endpoint.slice(0, splitAt);
            const params = new URLSearchParams(splitAt === -1 ? '' : endpoint.slice(splitAt + 1));
            if (input.form) {
                new FormData(input.form).forEach(function (value, key) {
                    if (key === input.name || value === '') return;
                    params.set(key, value);
                });
            }
            params.set('q', q);
            // Which box is asking — box 2's suggestions stay inside box 1's result,
            // box 1's are never narrowed by box 2 (App\Services\TwoStageSearch).
            params.set('_box', input.name);

            fetch(base + '?' + params.toString(), { signal: currentController.signal })
                .then((r) => r.json())
                .then((data) => renderItems(data))
                .catch((err) => {
                    if (err.name !== 'AbortError') closeDropdown();
                });
        }, 300);

        input.addEventListener('input', doSearch);

        input.addEventListener('keydown', function (e) {
            if (dropdown.classList.contains('d-none') || !items.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlight(Math.min(activeIndex + 1, items.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlight(Math.max(activeIndex - 1, 0));
            } else if (e.key === 'Enter') {
                if (activeIndex >= 0) {
                    e.preventDefault();
                    selectItem(activeIndex);
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        dropdown.addEventListener('mousedown', function (e) {
            const node = e.target.closest('.live-search-result-item');
            if (!node) return;
            e.preventDefault();
            selectItem(parseInt(node.dataset.index, 10));
        });

        input.addEventListener('focus', function () {
            if (items.length && input.value.trim().length >= minChars) {
                positionDropdown();
                dropdown.classList.remove('d-none');
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target === input || dropdown.contains(e.target)) return;
            closeDropdown();
        });

        window.addEventListener('resize', function () {
            if (!dropdown.classList.contains('d-none')) positionDropdown();
        });
        window.addEventListener('scroll', function () {
            if (!dropdown.classList.contains('d-none')) positionDropdown();
        }, true);
    }

    function initAll() {
        document.querySelectorAll('.live-search-input[data-live-search-endpoint]').forEach(initLiveSearchInput);
    }

    document.addEventListener('DOMContentLoaded', initAll);
    // Re-scan after any AJAX partial re-render that might add new instances.
    window.liveSearchFilterInit = initAll;
})();
