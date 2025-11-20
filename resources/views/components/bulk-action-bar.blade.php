@props(['id' => 'bulk-action-bar', 'checkboxSelector' => '.employee-checkbox'])

<div id="{{ $id }}" class="bulk-action-bar mb-3 align-items-center gap-2" style="display: none;">
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
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function initBulkBar(barId, selector) {
        const bar = document.getElementById(barId);
        if (!bar) return;

        const selectAll = document.getElementById(barId + '-select-all');
        const countSpan = document.getElementById(barId + '-count');
        const downloadBtn = bar.querySelector('.btn-bulk-download');

        function update() {
            const checkboxes = document.querySelectorAll(selector);
            const checked = document.querySelectorAll(selector + ':checked');
            const count = checked.length;

            countSpan.textContent = count;

            if (count > 0) {
                bar.style.display = 'flex';
            } else {
                bar.style.display = 'none';
            }

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

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll(selector);
                checkboxes.forEach(cb => cb.checked = this.checked);
                update();
            });
        }

        // Use event delegation for dynamically added checkboxes
        document.body.addEventListener('change', function(e) {
            if (e.target.matches(selector)) {
                update();
            }
        });

        if (downloadBtn) {
            downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const checked = Array.from(document.querySelectorAll(selector + ':checked')).map(cb => cb.value);
                if (window.openBulkDownloadModal) {
                    window.openBulkDownloadModal(checked);
                } else {
                    console.error('Download modal not found');
                    // Fallback if showToast is available
                    if (typeof showToast === 'function') {
                        showToast('Download function not ready', 'danger');
                    }
                }
            });
        }

        // Initial update
        update();
    }

    // Initialize specific instances based on data attributes or props passed from Blade
    // Since we can't easily pass data from Blade to this once-only script block for every instance,
    // we will auto-initialize based on a convention or data attribute on the bar itself.

    // Better approach: Loop through all .bulk-action-bar and init them.
    // But we need to know the selector for each.
    // Let's add a data-selector attribute to the main div in the component HTML.
});
</script>
@endpush
@endonce

{{-- We add a specific script for this instance to initialize it --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // We define the init function globally (or accessible) or just run logic here.
        // To reuse the logic defined in @once, let's expose it or just structure it differently.
        // Actually, simply duplicating the init logic per instance in a closure is fine and robust.

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

                countSpan.textContent = count;

                if (count > 0) {
                    bar.style.display = 'flex';
                } else {
                    bar.style.display = 'none';
                }

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

            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const checked = Array.from(document.querySelectorAll(selector + ':checked')).map(cb => cb.value);
                    if (window.openBulkDownloadModal) {
                        window.openBulkDownloadModal(checked);
                    } else {
                        alert('Download modal function missing.');
                    }
                });
            }

            update();
        })();
    });
</script>
