{{-- resources/views/sales/partials/_quotation_modal.blade.php --}}
<div class="modal fade" id="quotationModal-{{ $lead->id }}" tabindex="-1" aria-labelledby="quotationModalLabel-{{ $lead->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="quotationModalLabel-{{ $lead->id }}">
                    <i class="bi bi-cash-stack me-2"></i> การเงิน: {{ $lead->employerNameTh }}
                    <span class="badge bg-info ms-2">{{ $lead->employees->count() }} ลูกจ้าง</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="financeTabBody-{{ $lead->id }}" style="min-height: 400px;">
                <div class="d-flex flex-column align-items-center justify-content-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted">กำลังโหลดข้อมูลการเงิน...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var modalEl = document.getElementById('quotationModal-{{ $lead->id }}');
    if (!modalEl) return;

    var loaded = false;

    // Ensure financial-manager.js is loaded globally (only once)
    function ensureFinancialManagerScript() {
        return new Promise(function(resolve) {
            if (typeof window.financialManager === 'function') {
                resolve();
                return;
            }
            // Check if script tag already exists
            if (document.querySelector('script[src*="financial-manager.js"]')) {
                // Wait for it to load
                var check = setInterval(function() {
                    if (typeof window.financialManager === 'function') {
                        clearInterval(check);
                        resolve();
                    }
                }, 100);
                return;
            }
            var s = document.createElement('script');
            s.src = '{{ asset("js/financial-manager.js") }}';
            s.onload = function() {
                // Small delay to ensure function is registered
                setTimeout(resolve, 50);
            };
            document.head.appendChild(s);
        });
    }

    modalEl.addEventListener('show.bs.modal', function() {
        if (loaded) return;

        var body = document.getElementById('financeTabBody-{{ $lead->id }}');

        // Step 1: Load financial-manager.js first
        ensureFinancialManagerScript().then(function() {
            // Step 2: Fetch the financial tab HTML
            return fetch('{{ route("sales.finance.tab", $lead->id) }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            });
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.text();
        })
        .then(function(html) {
            // Step 3: Strip <script> tags from HTML (we already loaded the JS)
            var cleanHtml = html.replace(/<script[\s\S]*?<\/script>/gi, '');
            body.innerHTML = '<div class="p-3">' + cleanHtml + '</div>';

            // Step 4: Execute inline scripts that are NOT external src
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            tempDiv.querySelectorAll('script').forEach(function(script) {
                if (!script.src && script.textContent.trim()) {
                    try { eval(script.textContent); } catch(e) { console.warn('Script eval error:', e); }
                }
            });

            // Step 5: Initialize Alpine.js on the new content
            if (typeof Alpine !== 'undefined') {
                Alpine.initTree(body);
            }

            loaded = true;
        })
        .catch(function(err) {
            body.innerHTML = '<div class="alert alert-danger m-3"><i class="bi bi-exclamation-triangle me-2"></i>ไม่สามารถโหลดข้อมูลการเงินได้: ' + err.message + '</div>';
        });
    });
})();
</script>
@endpush
