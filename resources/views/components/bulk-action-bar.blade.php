@props(['id' => 'bulk-action-bar', 'checkboxSelector' => '.employee-checkbox', 'floating' => true])

<div id="{{ $id }}" class="bulk-action-bar align-items-center gap-2 p-2 bg-light border rounded shadow-lg"
     style="display: none;{{ $floating ? ' position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: auto; min-width: 400px;' : '' }}">
    <button class="btn btn-sm btn-info text-white me-2" onclick="window.openViewSelectedModal()">
        <i class="bi bi-eye me-1"></i> {{ __('View Selected') }}
        <span class="badge bg-white text-info ms-1" id="view-selected-count">0</span>
    </button>
    <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="{{ $id }}-select-all">
        <label class="form-check-label" for="{{ $id }}-select-all">
            {{ __('Select All') }} (<span id="{{ $id }}-count">0</span>)
        </label>
    </div>

    <div class="dropdown">
        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{ __('Actions') }}
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item btn-bulk-download" href="#"><i class="bi bi-download me-2"></i>{{ __('Download Files') }}</a></li>
            {{ $slot ?? '' }}
        </ul>
    </div>
    <button class="btn btn-sm btn-outline-danger" onclick="window.clearGlobalSelection();">{{ __('Clear Selection') }}</button>
</div>

<script>
    // The selection is remembered across pages, refreshes and menus until the
    // user presses "Clear Selection" (2026-10-30 — it used to reset when going
    // to page 2):
    //  - ".employee-checkbox" bars use the program-wide employee selection
    //    (window.getGlobalSelectedData — layouts/_app_scripts, sessionStorage)
    //  - any other selector (e.g. Employment History) keeps its own list in
    //    sessionStorage under "bulkSel:{bar id}"
    // window.bulkBarSelectedIds(barId) gives the selected ids for the bar's actions.
    document.addEventListener('DOMContentLoaded', function() {
        (function() {
            const barId = '{{ $id }}';
            const selector = '{{ $checkboxSelector }}';

            const bar = document.getElementById(barId);
            if (!bar) return;

            const usesGlobal = selector === '.employee-checkbox' && typeof window.getGlobalSelectedData === 'function';
            const storeKey = 'bulkSel:' + barId;
            const ownIds = function () {
                try { return JSON.parse(sessionStorage.getItem(storeKey) || '[]').map(String); } catch (e) { return []; }
            };
            const saveOwn = function (ids) {
                try { sessionStorage.setItem(storeKey, JSON.stringify(Array.from(new Set(ids)))); } catch (e) {}
            };
            const selectedIds = function () {
                return usesGlobal ? window.getGlobalSelectedIds() : ownIds();
            };
            window.bulkBarSelectedIds = window.bulkBarSelectedIds || {};
            window.bulkBarSelectedIds[barId] = selectedIds;

            const selectAll = document.getElementById(barId + '-select-all');
            const countSpan = document.getElementById(barId + '-count');
            const downloadBtn = bar.querySelector('.btn-bulk-download');

            // Tick the boxes on this page that are in the remembered selection.
            function syncBoxes() {
                if (usesGlobal) return; // the program-wide selection ticks its own boxes
                const ids = ownIds();
                document.querySelectorAll(selector).forEach(cb => { cb.checked = ids.includes(String(cb.value)); });
            }

            function update() {
                const checkboxes = document.querySelectorAll(selector);
                const visibleChecked = document.querySelectorAll(selector + ':checked').length;
                const count = selectedIds().length;

                if (countSpan) countSpan.textContent = count;
                bar.style.display = count > 0 ? 'flex' : 'none';

                if (selectAll) {
                    if (checkboxes.length > 0 && visibleChecked === checkboxes.length) {
                        selectAll.checked = true;
                        selectAll.indeterminate = false;
                    } else if (visibleChecked > 0 || count > 0) {
                        selectAll.checked = false;
                        selectAll.indeterminate = visibleChecked > 0;
                    } else {
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                    }
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const checked = this.checked;
                    document.querySelectorAll(selector).forEach(cb => {
                        if (cb.disabled || cb.checked === checked) return;
                        cb.checked = checked;
                        // the change event updates the remembered selection (below / _app_scripts)
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    update();
                });
            }

            document.body.addEventListener('change', function(e) {
                if (!e.target.matches(selector)) return;
                if (!usesGlobal) {
                    const ids = ownIds();
                    const id = String(e.target.value);
                    saveOwn(e.target.checked ? ids.concat(id) : ids.filter(x => x !== id));
                }
                setTimeout(update, 0); // after the program-wide selection has been updated
            });

            // "Clear Selection" (window.clearGlobalSelection) also clears this bar's own list.
            bar.querySelectorAll('[onclick*="clearGlobalSelection"]').forEach(btn => btn.addEventListener('click', function () {
                if (!usesGlobal) {
                    saveOwn([]);
                    document.querySelectorAll(selector).forEach(cb => { cb.checked = false; });
                }
                setTimeout(update, 0);
            }));

            // Listen for a custom event to force update (useful for AJAX reloads)
            document.addEventListener('bulk-action-bar:update', function() {
                syncBoxes();
                update();
            });
            document.addEventListener('global-selection-updated', update);

            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const checked = selectedIds();

                    if (window.openBulkDownloadModal) {
                        window.openBulkDownloadModal(checked);
                    } else {
                        console.error('Download modal function missing.');
                         // Fallback if showToast is available
                        if (typeof showToast === 'function') {
                            showToast('Download function not ready', 'danger');
                        }
                    }
                });
            }

            // Initial update
            syncBoxes();
            update();
        })();
    });
</script>
