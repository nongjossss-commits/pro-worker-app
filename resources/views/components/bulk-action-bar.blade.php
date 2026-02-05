<div class="bulk-action-bar mb-3 align-items-center gap-2"
     style="display: none;"
     id="bulkActionBar">
    <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="select-all-checkbox">
        <label class="form-check-label" for="select-all-checkbox">
            {{ __('Select All') }} (<span id="selected-count">0</span>)
        </label>
    </div>

    <div class="dropdown">
        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false" disabled>
            {{ __('Actions') }}
        </button>
        <ul class="dropdown-menu" aria-labelledby="bulkActionDropdown">
            <li><a class="dropdown-item" href="#" id="bulk-advanced-edit-btn"><i class="bi bi-pencil-square me-2"></i>{{ __('Advanced Edit') }}</a></li>
            <li><a class="dropdown-item" href="#" id="bulk-advanced-export-btn"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Advanced Export') }}</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" id="bulk-download-btn"><i class="bi bi-download me-2"></i>{{ __('Download Files') }}</a></li>
            <li><a class="dropdown-item" href="#" id="bulk-transfer-btn"><i class="bi bi-arrow-left-right me-2"></i>{{ __('Transfer') }}</a></li>
            <li><a class="dropdown-item" href="#" id="bulk-send-data-btn"><i class="bi bi-send me-2"></i>{{ __('Send Data') }}</a></li>

            @if(!isset($hideSendToProduction) || !$hideSendToProduction)
            <li><a class="dropdown-item" href="#" id="bulk-send-production-btn"><i class="bi bi-clipboard-data me-2"></i>{{ __('Send to P Production') }}</a></li>
            @endif

            @can('manage-tickets')
            <li><a class="dropdown-item" href="#" id="bulk-generate-pdf-btn"><i class="bi bi-file-earmark-pdf me-2"></i>{{ __('Automated PDF') }}</a></li>
            @endcan
        </ul>
    </div>
    <button class="btn btn-sm btn-outline-danger" onclick="window.clearGlobalSelection();">{{ __('Clear Selection') }}</button>
    <button class="btn btn-sm btn-info text-white" id="btn-view-selected">
        <i class="bi bi-eye me-1"></i> {{ __('View Selected') }}
    </button>
    <div class="ms-auto text-muted small d-none d-md-block">
        <i class="bi bi-arrows-move me-1"></i> {{ __('Drag to Chat') }}
    </div>
</div>

<!-- View Selected Items Modal -->
<div class="modal fade" id="viewSelectedModal" tabindex="-1" aria-labelledby="viewSelectedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSelectedModalLabel">{{ __('Selected Employees') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="selected-list-container" class="list-group list-group-flush">
                    <!-- Items will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
