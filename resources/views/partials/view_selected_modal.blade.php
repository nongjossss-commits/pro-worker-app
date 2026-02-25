<div class="modal fade" id="viewSelectedModal" tabindex="-1" aria-labelledby="viewSelectedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="viewSelectedModalLabel">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ __('Selected Employees') }}
                    <span class="badge bg-white text-info ms-2" id="modal-selected-count">0</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div id="selected-items-container" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                    <!-- Items will be populated here via JS -->
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-check2-circle display-1 opacity-25"></i>
                        <p class="mt-3">{{ __('No employees selected') }}</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-outline-danger me-auto" onclick="window.clearGlobalSelection(); bootstrap.Modal.getInstance(document.getElementById('viewSelectedModal')).hide();">
                    <i class="bi bi-trash me-1"></i> {{ __('Clear All Selection') }}
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
