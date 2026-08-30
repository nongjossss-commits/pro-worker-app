{{--
    Super Admin only — "Move Attachment Files" bulk tool. Included once,
    globally, in layouts/app.blade.php (mirrors <x-job-check-widget>'s
    pattern) so every page with an employee-checkbox bulk-action bar can
    call window.openBulkMoveAttachmentsModal(employeeIds) without each
    page needing its own copy of this modal.

    Replaces the old Super Admin Settings > Attachment Files "Swap"
    tool, which acted on ALL employees in the system at once — too blunt,
    since different resolution groups/tabs can have their attachments in
    different positions. This only ever touches the IDs the operator
    explicitly checkbox-selected on whichever page they're on.
--}}
@php
    $bulkMoveDocFields = collect(range(1, 18))->map(function ($i) {
        $labels = [
            9 => 'Document 9 (Other 1)', 10 => 'Document 10 (Other 2)', 11 => 'Document 11 (Other 3)',
            12 => 'Document 12 (Other 4)', 13 => 'Document 13 (Other 5)', 14 => 'Document 14 (Other 6)',
            15 => 'Document 15 (Other 7)', 16 => 'Document 16 (Other 8)', 17 => 'Document 17 (Other 9)',
            18 => 'Document 18 (Other 10)',
        ];
        return ['value' => "employee_doc_{$i}", 'label' => $labels[$i] ?? "Document {$i}"];
    });
@endphp

<div class="modal fade" id="bulkMoveAttachmentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title"><i class="bi bi-folder-symlink-fill me-1"></i> {{ __('Move Attachment Files') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    {{ __('Applies only to the employees you selected') }}
                    (<strong id="bulkMoveAttachmentsCount">0</strong> {{ __('selected') }}).
                </p>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">{{ __('From') }}</label>
                        <select class="form-select form-select-sm" id="bulkMoveFromField">
                            @foreach($bulkMoveDocFields as $f)
                                <option value="{{ $f['value'] }}">{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">{{ __('To') }}</label>
                        <select class="form-select form-select-sm" id="bulkMoveToField">
                            @foreach($bulkMoveDocFields as $f)
                                <option value="{{ $f['value'] }}" {{ $f['value'] === 'employee_doc_2' ? 'selected' : '' }}>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label class="form-label small fw-bold d-block mb-2">{{ __('Mode') }}</label>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="bulkMoveMode" id="bulkMoveModeSwap" value="swap" checked>
                    <label class="form-check-label" for="bulkMoveModeSwap">
                        <strong>{{ __('Swap Both') }}:</strong> {{ __('File A goes to B, File B goes to A. Both files are kept.') }}
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="bulkMoveMode" id="bulkMoveModeDelete" value="move_delete">
                    <label class="form-check-label text-danger" for="bulkMoveModeDelete">
                        <strong>{{ __('Move & Delete') }}:</strong> {{ __('File A moves to B. Whatever was in B is permanently deleted. A becomes empty.') }}
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="bulkMoveMode" id="bulkMoveModeMerge" value="merge_append">
                    <label class="form-check-label text-primary" for="bulkMoveModeMerge">
                        <strong>{{ __('Merge into B') }}:</strong> {{ __("File A's pages are appended after File B's pages into one combined PDF, kept in B. A becomes empty. (If B is empty, this is just a plain move.)") }}
                    </label>
                </div>

                <div class="alert alert-warning small mb-0 mt-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    {{ __('This physically moves/merges/deletes uploaded files and cannot be easily undone. Double-check the From/To positions before running.') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-warning fw-bold" id="bulkMoveAttachmentsSubmitBtn" onclick="window.executeBulkMoveAttachments()">
                    {{ __('Execute') }}
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    let _bulkMoveAttachmentsEmployeeIds = [];

    window.openBulkMoveAttachmentsModal = function (employeeIds) {
        employeeIds = (employeeIds || []).map(id => parseInt(id, 10)).filter(id => !isNaN(id));

        if (employeeIds.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('{{ __('No employees selected') }}', '{{ __('Please select at least one employee first.') }}', 'warning');
            }
            return;
        }

        _bulkMoveAttachmentsEmployeeIds = employeeIds;
        document.getElementById('bulkMoveAttachmentsCount').textContent = employeeIds.length;

        const modalEl = document.getElementById('bulkMoveAttachmentsModal');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    window.executeBulkMoveAttachments = function () {
        const fromField = document.getElementById('bulkMoveFromField').value;
        const toField = document.getElementById('bulkMoveToField').value;
        const mode = document.querySelector('input[name="bulkMoveMode"]:checked').value;

        if (fromField === toField) {
            Swal.fire('{{ __('Error') }}', '{{ __('From and To must be different positions.') }}', 'error');
            return;
        }

        const modeLabels = {
            swap: '{{ __('Swap Both') }}',
            move_delete: '{{ __('Move & Delete') }}',
            merge_append: '{{ __('Merge into B') }}',
        };

        Swal.fire({
            title: '{{ __('Are you sure?') }}',
            html: '{{ __('This will run') }} <strong>' + modeLabels[mode] + '</strong> {{ __('for') }} <strong>' + _bulkMoveAttachmentsEmployeeIds.length + '</strong> {{ __('employees') }}.<br><br>' +
                  '<small class="text-danger">{{ __('This cannot be easily undone.') }}</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __('Yes, execute') }}',
            cancelButtonText: '{{ __('Cancel') }}',
        }).then((result) => {
            if (!result.isConfirmed) return;

            const btn = document.getElementById('bulkMoveAttachmentsSubmitBtn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __('Processing...') }}';

            fetch('{{ route('employees.bulkMoveAttachments') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    employee_ids: _bulkMoveAttachmentsEmployeeIds,
                    from_field: fromField,
                    to_field: toField,
                    mode: mode,
                }),
            })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || ('HTTP ' + res.status));
                }
                return data;
            })
            .then((data) => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkMoveAttachmentsModal')).hide();
                Swal.fire({
                    icon: data.failed > 0 ? 'warning' : 'success',
                    title: '{{ __('Done') }}',
                    text: data.message,
                });
                if (window.clearGlobalSelection) window.clearGlobalSelection();
                setTimeout(() => window.location.reload(), 1200);
            })
            .catch((err) => {
                Swal.fire('{{ __('Error') }}', err.message, 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    };
</script>
@endpush
@endonce
