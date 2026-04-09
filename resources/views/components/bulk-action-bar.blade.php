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
    document.addEventListener('DOMContentLoaded', function() {
        (function() {
            const barId = '{{ $id }}';
            const selector = '{{ $checkboxSelector }}';

            const bar = document.getElementById(barId);
            if (!bar) return;

            const selectAll = document.getElementById(barId + '-select-all');
            const countSpan = document.getElementById(barId + '-count');
            const downloadBtn = bar.querySelector('.btn-bulk-download');

            function update() {
                const checkboxes = document.querySelectorAll(selector);
                const checked = document.querySelectorAll(selector + ':checked');
                const count = checked.length;

                if (countSpan) countSpan.textContent = count;

                if (count > 0) {
                    bar.style.display = 'flex';
                } else {
                    bar.style.display = 'none';
                }

                if (selectAll) {
                    if (checkboxes.length > 0 && checked.length === checkboxes.length) {
                        selectAll.checked = true;
                        selectAll.indeterminate = false;
                    } else if (count > 0) {
                        selectAll.checked = false;
                        selectAll.indeterminate = true;
                    } else {
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                    }
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll(selector);
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    update();
                });
            }

            document.body.addEventListener('change', function(e) {
                if (e.target.matches(selector)) {
                    update();
                }
            });

            // Listen for a custom event to force update (useful for AJAX reloads)
            document.addEventListener('bulk-action-bar:update', function() {
                update();
            });

            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const checked = Array.from(document.querySelectorAll(selector + ':checked')).map(cb => cb.value);

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
            update();
        })();
    });
</script>
