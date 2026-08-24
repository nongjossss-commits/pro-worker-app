<div class="modal fade" id="downloadChoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Include Contractor Signature & Stamp?') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-muted">{{ __("Choose whether the downloaded PDF should include the Contractor's signature and stamp, or leave that area blank.") }}</p>
            </div>
            <div class="modal-footer">
                <a href="#" id="downloadChoiceWithSignature" class="btn btn-primary">
                    <i class="bi bi-file-earmark-check"></i> {{ __('Download with Signature & Stamp') }}
                </a>
                <a href="#" id="downloadChoiceWithoutSignature" class="btn btn-outline-secondary">
                    {{ __('Download without Signature & Stamp') }}
                </a>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var downloadChoiceModal = document.getElementById('downloadChoiceModal');
            if (!downloadChoiceModal) return;

            downloadChoiceModal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var baseUrl = trigger.getAttribute('data-download-url');
                if (!baseUrl) return;

                var withSignature = baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + 'include_signature=1';
                var withoutSignature = baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + 'include_signature=0';

                document.getElementById('downloadChoiceWithSignature').setAttribute('href', withSignature);
                document.getElementById('downloadChoiceWithoutSignature').setAttribute('href', withoutSignature);
            });
        });
    </script>
    @endpush
@endonce
