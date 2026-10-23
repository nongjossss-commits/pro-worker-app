{{-- Shared JS (no <script> tag): lazy-loads /production/{id}/finance-tab into #finance-modal-body-{id} on first open. Used by Pre-Production and Workflow. --}}
    // ─────────────────────────────────────────────────────────────
    // Finance Modal — Lazy Loading
    // โหลดข้อมูล Finance ของแต่ละ order เฉพาะตอนที่คลิกเปิด
    // ไม่โหลดพร้อมกันทุก order ตอนเปิดหน้า ช่วยลดภาระหน้าเว็บมาก
    // ─────────────────────────────────────────────────────────────
    let _fmScriptPromise = null;

    function _ensureFinancialManagerScript() {
        if (!_fmScriptPromise) {
            _fmScriptPromise = new Promise((resolve, reject) => {
                // ถ้า financialManager ถูกโหลดไปแล้ว (เช่น เปิด modal ที่สองขึ้นไป) ก็ไม่ต้องโหลดซ้ำ
                if (typeof financialManager === 'function') { resolve(); return; }
                const s = document.createElement('script');
                s.src = '{{ asset("js/financial-manager.js") }}?v={{ @filemtime(public_path("js/financial-manager.js")) }}';
                s.onload  = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }
        return _fmScriptPromise;
    }

    const _financeTabCache = {};

    window.openFinanceModal = async function(orderId) {
        const modal = document.getElementById('financeModal-' + orderId);
        if (!modal) return;

        // เปิด modal ทันที (แสดง spinner ก่อน)
        bootstrap.Modal.getOrCreateInstance(modal).show();

        // ถ้าโหลดไปแล้ว ไม่ต้องโหลดซ้ำ
        if (_financeTabCache[orderId]) return;

        const body = document.getElementById('finance-modal-body-' + orderId);

        try {
            // โหลด HTML ของ finance tab และ financial-manager.js พร้อมกัน
            const [html] = await Promise.all([
                fetch('/production/' + orderId + '/finance-tab', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                }).then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                }),
                _ensureFinancialManagerScript()
            ]);

            // ดึง HTML แล้วลบ <script src> ออกก่อน inject
            // เพราะ financial-manager.js โหลดจาก _ensureFinancialManagerScript แล้ว
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            doc.querySelectorAll('script').forEach(s => s.remove());
            body.innerHTML = doc.body.innerHTML;

            _financeTabCache[orderId] = true;

            // ให้ Alpine.js รับรู้ component ที่ inject เข้ามาใหม่
            if (window.Alpine) Alpine.initTree(body);

        } catch (err) {
            body.innerHTML = [
                '<div class="text-center py-5 text-danger">',
                '<i class="bi bi-exclamation-triangle-fill fs-2 mb-3 d-block"></i>',
                '<p class="fw-bold">โหลดข้อมูลไม่สำเร็จ</p>',
                '<button class="btn btn-outline-danger btn-sm" onclick="_financeTabCache[' + orderId + ']=false; window.openFinanceModal(' + orderId + ')">',
                '<i class="bi bi-arrow-clockwise me-1"></i>ลองใหม่</button>',
                '</div>'
            ].join('');
        }
    };
