<div class="modal fade" id="bulkDownloadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="bulkDownloadForm" method="POST" action="{{ route('labor.contracts.bulk-download') }}">
            @csrf
            <div id="bulkDownloadIdsContainer"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Download Selected Contracts') }} (<span id="bulkDownloadModalCount">0</span>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="variant" id="bulkVariantOriginal" value="original" checked>
                        <label class="form-check-label" for="bulkVariantOriginal">
                            {{ __('Original contracts') }}
                            <div class="small text-muted">{{ __('The system-generated PDF for each selected contract.') }}</div>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="variant" id="bulkVariantSigned" value="signed">
                        <label class="form-check-label" for="bulkVariantSigned">
                            {{ __('Signed copies only') }}
                            <div class="small text-muted">{{ __('Only the employer-signed scan attached back to each contract.') }}</div>
                        </label>
                    </div>
                    {{-- Computed client-side from each ticked row's data-has-signed
                         attribute — warns BEFORE downloading, since a response
                         that streams a file back never becomes a page load the
                         server could otherwise flash a "X were skipped" message
                         onto (see LaborContractController::bulkDownload()'s
                         docblock for why this is done here instead). --}}
                    <div class="alert alert-warning small mb-0" id="bulkDownloadSignedWarning" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i>{{ __('Download') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('bulkDownloadModal');
            if (!modalEl) return;

            const idsContainer = document.getElementById('bulkDownloadIdsContainer');
            const countSpan = document.getElementById('bulkDownloadModalCount');
            const warning = document.getElementById('bulkDownloadSignedWarning');
            const radios = modalEl.querySelectorAll('input[name="variant"]');

            function selectedCheckboxes() {
                return Array.from(document.querySelectorAll('.contract-checkbox:checked'));
            }

            function refreshWarning() {
                const variant = modalEl.querySelector('input[name="variant"]:checked').value;
                const boxes = selectedCheckboxes();
                const missing = boxes.filter(cb => cb.dataset.hasSigned !== '1').length;

                if (variant === 'signed' && missing > 0) {
                    warning.textContent = missing === boxes.length
                        ? '{{ __('None of the selected contracts have a signed copy attached yet — nothing would be downloaded.') }}'
                        : missing + ' {{ __('of the selected contracts have no signed copy yet and will be skipped.') }}';
                    warning.style.display = 'block';
                } else {
                    warning.style.display = 'none';
                }
            }

            radios.forEach(r => r.addEventListener('change', refreshWarning));

            modalEl.addEventListener('show.bs.modal', function () {
                // Rebuild the hidden ids[] inputs from whatever is currently
                // ticked in the table every time the modal opens.
                idsContainer.innerHTML = '';
                const boxes = selectedCheckboxes();
                boxes.forEach(cb => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'ids[]';
                    hidden.value = cb.value;
                    idsContainer.appendChild(hidden);
                });
                countSpan.textContent = boxes.length;
                refreshWarning();
            });
        });
    </script>
    @endpush
@endonce
