{{--
  Share employee / employer cards outside the program (LINE, e-mail, Teams …).

  Any element with data-share-type="employee|employer" + data-share-id="…"
  (optional data-share-notification="…") gets:
    • hover  → prefetch the readable data (GET /share-card/{type}/{id})
    • drag   → other apps receive readable text (text/plain + text/html);
               the in-app chat still gets its JSON (window.startDragGlobal)
    • click  → (only with data-share-menu) a small menu: copy text,
               copy / save / share a card image
  Every share that leaves the page is logged (POST /share-card/log).
  Which fields go out is set by an admin: Admin → Share settings
  (ShareCardService). Handles: components/share-handle.blade.php.
--}}
@php
    $shareI18n = [
        'loading' => __('Loading...'),
        'copyText' => __('Copy as text'),
        'copyImage' => __('Copy as card image'),
        'saveImage' => __('Save card image'),
        'share' => __('Share...'),
        'dragHint' => __('Tip: drag this button into a LINE chat'),
        'settings' => __('Share settings'),
        'copiedText' => __('Copied — paste it in the chat (Ctrl+V)'),
        'copiedImage' => __('Card image copied — paste it in the chat (Ctrl+V)'),
        'saved' => __('Card image saved'),
        'failed' => __('Could not load the data. Please try again.'),
        'imageFallback' => __('This browser cannot copy images — the card was saved as a file instead.'),
        'shareRetry' => __('Please press Share again.'),
        'employeeCard' => __('Employee card'),
        'employerCard' => __('Employer card'),
        'sentFrom' => __('Sent from'),
    ];
    $shareIsAdmin = auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin']);
@endphp
<style>
    .share-card-menu { position: fixed; z-index: 2100; min-width: 250px; max-width: 320px; }
    .share-card-menu .dropdown-header { white-space: normal; font-weight: 600; color: var(--bs-body-color); }
    .share-card-menu .share-hint { font-size: .8rem; white-space: normal; }
    [data-share-handle] { cursor: grab; }
    [data-share-handle]:active { cursor: grabbing; }
</style>
<script>
(function () {
    const T = @json($shareI18n);
    const IS_ADMIN = @json($shareIsAdmin);
    const SETTINGS_URL = @json($shareIsAdmin ? route('admin.settings.share.index') : null);
    const cache = new Map();   // key -> Promise<data>
    const ready = new Map();   // key -> data
    let currentDrag = null;
    let menuEl = null;

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function infoFromEl(el) {
        if (!el || !el.dataset.shareType || !el.dataset.shareId) return null;
        const n = el.dataset.shareNotification || '';
        return { type: el.dataset.shareType, id: el.dataset.shareId, notification: n,
                 key: el.dataset.shareType + ':' + el.dataset.shareId + ':' + n, name: el.dataset.shareName || '' };
    }

    function infoFromPayload(type, p) {
        p = p || {};
        let t = null, id = null, n = '';
        if (type === 'employee' || type === 'employer') { t = type; id = p.id; }
        else if (type === 'notification') {
            n = p.id || '';
            if (p.employee_id) { t = 'employee'; id = p.employee_id; }
            else if (p.employer_id) { t = 'employer'; id = p.employer_id; }
        }
        if (!t || !id) return null;
        return { type: t, id: String(id), notification: String(n), key: t + ':' + id + ':' + n, name: '' };
    }

    function fetchShare(info, withPhoto) {
        const k = info.key + (withPhoto ? ':p' : '');
        if (!cache.has(k)) {
            const qs = new URLSearchParams({ notification: info.notification || '', photo: withPhoto ? 1 : 0 });
            const p = fetch('/share-card/' + info.type + '/' + info.id + '?' + qs, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
                .then(d => { ready.set(k, d); if (withPhoto) ready.set(info.key, d); return d; })
                .catch(err => { cache.delete(k); throw err; });
            cache.set(k, p);
        }
        return cache.get(k);
    }

    function readyData(info) {
        return ready.get(info.key) || ready.get(info.key + ':p') || null;
    }

    function logShare(info, method) {
        try {
            fetch('/share-card/log', {
                method: 'POST', keepalive: true, credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ type: info.type, id: info.id, method: method })
            }).catch(() => {});
        } catch (_) {}
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function htmlFor(d) {
        let h = '<div><b>' + esc(d.title) + '</b>';
        (d.lines || []).forEach(l => { h += '<br>' + esc(l.label) + ': ' + esc(l.value); });
        if (d.alert) h += '<br><b>⚠️ ' + esc(d.alert.label) + ': ' + esc(d.alert.value) + '</b>';
        return h + '</div>';
    }

    function fallbackText(p, type) {
        p = p || {};
        const name = p.employee_name_en || p.name || p.title_en || p.title || '';
        const lines = [name];
        if (p.employee_name_th && p.employee_name_th !== name) lines.push(p.employee_name_th);
        if (p.subtitle && p.subtitle !== name) lines.push(p.subtitle);
        const employer = p.employer_name_th || p.employer_name;
        if (employer) lines.push('🏢 ' + employer);
        // Links / tickets / groups point inside the program — the link is the useful part.
        if ((type === 'link' || type === 'ticket') && p.url && p.url !== '#') lines.push(p.url);
        return lines.filter(Boolean).join('\n');
    }

    // ---- Drag -----------------------------------------------------------

    // Called by window.startDragGlobal (layouts/app) after it has set the
    // in-app chat JSON: replace text/plain with readable text for other apps.
    function decorateDrag(e, type, payload) {
        const el = e.target && e.target.closest ? e.target.closest('[data-share-type][data-share-id]') : null;
        const info = infoFromEl(el) || infoFromPayload(type, payload);
        if (!info) {
            const t = fallbackText(payload, type);
            if (t) e.dataTransfer.setData('text/plain', t);
            return;
        }
        const d = readyData(info);
        e.dataTransfer.setData('text/plain', d ? d.text : fallbackText(payload, type));
        if (d) e.dataTransfer.setData('text/html', htmlFor(d));
        else fetchShare(info, false).catch(() => {});
        currentDrag = { info: info, internal: false };
    }

    // New-style handles (components/share-handle) have no inline ondragstart.
    document.addEventListener('dragstart', function (e) {
        const el = e.target && e.target.closest ? e.target.closest('[data-share-handle]') : null;
        if (!el || typeof window.startDragGlobal !== 'function') return;
        const info = infoFromEl(el);
        if (!info) return;
        const d = readyData(info);
        const chat = d ? d.chat : { id: Number(info.id), title: info.name || '', subtitle: '' };
        window.startDragGlobal(e, info.type, chat);
    });

    document.addEventListener('drop', function () { if (currentDrag) currentDrag.internal = true; }, true);
    document.addEventListener('dragend', function (e) {
        if (!currentDrag) return;
        const drag = currentDrag;
        currentDrag = null;
        if (!drag.internal && e.dataTransfer && e.dataTransfer.dropEffect !== 'none') logShare(drag.info, 'drag');
    });

    // Prefetch on hover so a drag that starts right after has real data.
    document.addEventListener('pointerover', function (e) {
        const el = e.target && e.target.closest ? e.target.closest('[data-share-type][data-share-id]') : null;
        const info = infoFromEl(el);
        if (info && !cache.has(info.key)) fetchShare(info, false).catch(() => {});
    }, { passive: true });

    // ---- Card image --------------------------------------------------------

    function loadImage(src) {
        return new Promise(resolve => {
            if (!src) return resolve(null);
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = src;
        });
    }

    function wrap(ctx, text, maxWidth) {
        // Thai has no spaces between words: break on spaces first, then by character.
        const out = [];
        const fits = s => ctx.measureText(s).width <= maxWidth;
        const seg = (typeof Intl !== 'undefined' && Intl.Segmenter);
        const words = s => seg ? Array.from(new Intl.Segmenter('th', { granularity: 'word' }).segment(s), x => x.segment) : s.split(/(\s+)/);
        // Grapheme clusters keep Thai vowels / tone marks with their consonant.
        const chars = s => seg ? Array.from(new Intl.Segmenter('th', { granularity: 'grapheme' }).segment(s), x => x.segment) : Array.from(s);
        String(text).split('\n').forEach(para => {
            let line = '';
            words(para).forEach(tok => {
                if (fits(line + tok)) { line += tok; return; }
                if (line.trim()) out.push(line.trimEnd());
                line = tok.trimStart();
                // A single word wider than the box: break it between characters.
                while (line && !fits(line)) {
                    let cut = '';
                    for (const ch of chars(line)) { if (!fits(cut + ch) && cut) break; cut += ch; }
                    out.push(cut);
                    line = line.slice(cut.length);
                }
            });
            out.push(line.trimEnd());
        });
        return out;
    }

    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    async function renderCard(d) {
        try { await document.fonts.ready; } catch (_) {}
        const font = getComputedStyle(document.body).fontFamily || 'sans-serif';
        const photo = await loadImage(d.photo);
        const S = 2, W = 720, P = 36;
        const brand = (d.brand && d.brand.color) || '#F97316';
        const accent = (d.brand && d.brand.accent) || brand;

        function layout(ctx, draw) {
            let y = 0;
            // Header band
            if (draw) {
                const g = ctx.createLinearGradient(0, 0, W, 0);
                g.addColorStop(0, brand); g.addColorStop(1, accent);
                ctx.fillStyle = g; ctx.fillRect(0, 0, W, 84);
                ctx.fillStyle = '#fff';
                ctx.font = '700 26px ' + font; ctx.textBaseline = 'middle';
                ctx.fillText((d.brand && d.brand.name) || '', P, 42);
                ctx.font = '500 18px ' + font; ctx.textAlign = 'right';
                ctx.fillText(d.type === 'employer' ? T.employerCard : T.employeeCard, W - P, 42);
                ctx.textAlign = 'left';
            }
            y = 84 + 28;

            // Photo / initial + title
            const avatar = 132;
            const textX = P + avatar + 24;
            if (draw) {
                ctx.save();
                ctx.beginPath(); ctx.arc(P + avatar / 2, y + avatar / 2, avatar / 2, 0, Math.PI * 2); ctx.closePath();
                if (photo) { ctx.clip(); ctx.drawImage(photo, P, y, avatar, avatar); }
                else {
                    ctx.fillStyle = brand; ctx.fill();
                    ctx.fillStyle = '#fff'; ctx.font = '700 56px ' + font; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                    ctx.fillText(Array.from((d.title || '?').replace(/^(Mr|Mrs|Miss|Ms)\.?\s+/i, ''))[0] || '?', P + avatar / 2, y + avatar / 2 + 2);
                    ctx.textAlign = 'left';
                }
                ctx.restore();
                ctx.strokeStyle = 'rgba(0,0,0,.08)'; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.arc(P + avatar / 2, y + avatar / 2, avatar / 2, 0, Math.PI * 2); ctx.stroke();
            }
            ctx.textBaseline = 'top';
            ctx.font = '700 30px ' + font;
            let ty = y + 8;
            wrap(ctx, d.title, W - textX - P).forEach(line => { if (draw) { ctx.fillStyle = '#111827'; ctx.fillText(line, textX, ty); } ty += 40; });
            const first = (d.lines || [])[0];
            if (first && (first.key === 'name_th' || first.key === 'name_en')) {
                ctx.font = '500 22px ' + font;
                wrap(ctx, first.value, W - textX - P).forEach(line => { if (draw) { ctx.fillStyle = '#4b5563'; ctx.fillText(line, textX, ty); } ty += 32; });
            }
            y = Math.max(y + avatar, ty) + 24;

            // Rows
            const rows = (d.lines || []).filter((l, i) => !(i === 0 && (l.key === 'name_th' || l.key === 'name_en')));
            const labelW = 230;
            rows.forEach((l, i) => {
                ctx.font = '500 20px ' + font;
                const lLines = wrap(ctx, l.label, labelW - 16);
                ctx.font = '600 22px ' + font;
                const vLines = wrap(ctx, l.value, W - P * 2 - labelW);
                const h = Math.max(lLines.length * 28, vLines.length * 32, 32) + 20;
                if (draw) {
                    if (i % 2 === 0) { ctx.fillStyle = '#f8fafc'; ctx.fillRect(P - 12, y, W - P * 2 + 24, h); }
                    ctx.fillStyle = '#6b7280'; ctx.font = '500 20px ' + font;
                    lLines.forEach((t, j) => ctx.fillText(t, P, y + 12 + j * 28));
                    ctx.fillStyle = '#111827'; ctx.font = '600 22px ' + font;
                    vLines.forEach((v, j) => ctx.fillText(v, P + labelW, y + 10 + j * 32));
                }
                y += h;
            });

            // Alert (notification reason)
            if (d.alert) {
                y += 16;
                ctx.font = '700 22px ' + font;
                const aLines = wrap(ctx, '⚠ ' + d.alert.label + ': ' + d.alert.value, W - P * 2 - 32);
                const h = aLines.length * 32 + 28;
                if (draw) {
                    ctx.fillStyle = '#fef2f2'; roundRect(ctx, P, y, W - P * 2, h, 14); ctx.fill();
                    ctx.strokeStyle = '#fca5a5'; ctx.lineWidth = 2; ctx.stroke();
                    ctx.fillStyle = '#b91c1c';
                    aLines.forEach((a, j) => ctx.fillText(a, P + 16, y + 14 + j * 32));
                }
                y += h;
            }

            // Footer
            y += 24;
            if (draw) {
                ctx.strokeStyle = '#e5e7eb'; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(P, y); ctx.lineTo(W - P, y); ctx.stroke();
                ctx.fillStyle = '#9ca3af'; ctx.font = '400 16px ' + font;
                const now = new Date();
                const stamp = now.toLocaleDateString('en-GB') + ' ' + now.toTimeString().slice(0, 5);
                ctx.fillText(T.sentFrom + ' ' + ((d.brand && d.brand.name) || '') + ' · ' + stamp, P, y + 14);
            }
            return y + 14 + 22 + P / 2;
        }

        const probe = document.createElement('canvas').getContext('2d');
        const H = Math.ceil(layout(probe, false));
        const canvas = document.createElement('canvas');
        canvas.width = W * S; canvas.height = H * S;
        const ctx = canvas.getContext('2d');
        ctx.scale(S, S);
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, W, H);
        layout(ctx, true);
        return new Promise(res => canvas.toBlob(b => res(b), 'image/png'));
    }

    function fileName(d) {
        return 'card-' + String(d.title || d.type).replace(/[^\w฀-๿]+/g, '_').replace(/^_|_$/g, '') + '.png';
    }

    function downloadBlob(blob, name) {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = name;
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(a.href), 4000);
    }

    // ---- Actions -----------------------------------------------------------

    function toast(msg, icon) { if (typeof window.appToast === 'function') window.appToast(msg, icon); }

    function copyText(info) {
        const dataP = fetchShare(info, false);
        let p;
        try {
            // Promise-valued ClipboardItem keeps the click's permission while the data loads.
            const item = new ClipboardItem({
                'text/plain': dataP.then(d => new Blob([d.text], { type: 'text/plain' })),
                'text/html': dataP.then(d => new Blob([htmlFor(d)], { type: 'text/html' })),
            });
            p = navigator.clipboard.write([item]);
        } catch (_) {
            p = dataP.then(d => navigator.clipboard.writeText(d.text));
        }
        p.then(() => { toast(T.copiedText); logShare(info, 'copy_text'); })
         .catch(() => toast(T.failed, 'error'));
    }

    function copyImage(info) {
        const blobP = fetchShare(info, true).then(renderCard);
        let p;
        try {
            p = navigator.clipboard.write([new ClipboardItem({ 'image/png': blobP })]);
        } catch (err) {
            p = Promise.reject(err);
        }
        p.then(() => { toast(T.copiedImage); logShare(info, 'copy_image'); })
         .catch(() => blobP.then(blob => fetchShare(info, true).then(d => {
                downloadBlob(blob, fileName(d));
                toast(T.imageFallback, 'info');
                logShare(info, 'download_image');
            })).catch(() => toast(T.failed, 'error')));
    }

    function saveImage(info) {
        fetchShare(info, true).then(d => renderCard(d).then(blob => {
            downloadBlob(blob, fileName(d));
            toast(T.saved);
            logShare(info, 'download_image');
        })).catch(() => toast(T.failed, 'error'));
    }

    const prepared = new Map(); // key -> {file, text} ready for navigator.share
    function prepareShare(info) {
        return fetchShare(info, true).then(d => renderCard(d).then(blob => {
            const v = { file: new File([blob], fileName(d), { type: 'image/png' }), text: d.text, title: d.title };
            prepared.set(info.key, v);
            return v;
        }));
    }

    function shareNative(info) {
        const v = prepared.get(info.key);
        if (!v) { prepareShare(info).then(() => toast(T.shareRetry, 'info')).catch(() => toast(T.failed, 'error')); return; }
        const data = { title: v.title, text: v.text };
        if (navigator.canShare && navigator.canShare({ files: [v.file] })) data.files = [v.file];
        navigator.share(data).then(() => logShare(info, 'share')).catch(() => {});
    }

    // ---- Menu --------------------------------------------------------------

    function closeMenu() { if (menuEl) { menuEl.remove(); menuEl = null; } }

    function openMenu(handle, info) {
        closeMenu();
        menuEl = document.createElement('div');
        menuEl.className = 'dropdown-menu show shadow share-card-menu';
        menuEl.dataset.openedAt = String(Date.now());
        const canShare = typeof navigator.share === 'function';
        menuEl.innerHTML =
            '<h6 class="dropdown-header" data-share-title>' + esc(info.name || T.loading) + '</h6>' +
            '<button type="button" class="dropdown-item" data-act="copy_text"><i class="bi bi-clipboard me-2"></i>' + esc(T.copyText) + '</button>' +
            '<button type="button" class="dropdown-item" data-act="copy_image"><i class="bi bi-image me-2"></i>' + esc(T.copyImage) + '</button>' +
            '<button type="button" class="dropdown-item" data-act="save_image"><i class="bi bi-download me-2"></i>' + esc(T.saveImage) + '</button>' +
            (canShare ? '<button type="button" class="dropdown-item" data-act="share"><i class="bi bi-share me-2"></i>' + esc(T.share) + '</button>' : '') +
            '<div class="dropdown-divider"></div>' +
            '<div class="px-3 pb-1 text-muted share-hint"><i class="bi bi-lightbulb me-1"></i>' + esc(T.dragHint) + '</div>' +
            (IS_ADMIN && SETTINGS_URL ? '<div class="dropdown-divider"></div><a class="dropdown-item small" href="' + esc(SETTINGS_URL) + '"><i class="bi bi-gear me-2"></i>' + esc(T.settings) + '</a>' : '');
        document.body.appendChild(menuEl);

        const r = handle.getBoundingClientRect();
        const mw = menuEl.offsetWidth, mh = menuEl.offsetHeight;
        let left = Math.min(r.right - mw, window.innerWidth - mw - 8);
        left = Math.max(8, left);
        let top = r.bottom + 6;
        if (top + mh > window.innerHeight - 8) top = Math.max(8, r.top - mh - 6);
        menuEl.style.left = left + 'px';
        menuEl.style.top = top + 'px';

        fetchShare(info, true).then(d => {
            const h = menuEl && menuEl.querySelector('[data-share-title]');
            if (h) h.textContent = d.title;
            if (canShare) prepareShare(info).catch(() => {});
        }).catch(() => {
            const h = menuEl && menuEl.querySelector('[data-share-title]');
            if (h) h.textContent = T.failed;
        });

        menuEl.addEventListener('click', function (e) {
            const b = e.target.closest('[data-act]');
            if (!b) return;
            const act = b.dataset.act;
            closeMenu();
            if (act === 'copy_text') copyText(info);
            else if (act === 'copy_image') copyImage(info);
            else if (act === 'save_image') saveImage(info);
            else if (act === 'share') shareNative(info);
        });
    }

    document.addEventListener('click', function (e) {
        const handle = e.target.closest ? e.target.closest('[data-share-menu][data-share-type][data-share-id]') : null;
        if (handle) {
            e.preventDefault();
            e.stopPropagation();
            if (menuEl && menuEl.dataset.for === handle.dataset.shareType + handle.dataset.shareId) { closeMenu(); return; }
            openMenu(handle, infoFromEl(handle));
            if (menuEl) menuEl.dataset.for = handle.dataset.shareType + handle.dataset.shareId;
            return;
        }
        if (menuEl && !menuEl.contains(e.target)) closeMenu();
    }, true);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
    // Close on page scroll — but not the scroll the browser does to bring the
    // clicked handle into view right as the menu opens.
    window.addEventListener('scroll', function () {
        if (menuEl && Date.now() - Number(menuEl.dataset.openedAt || 0) > 400) closeMenu();
    }, { passive: true, capture: true });
    window.addEventListener('resize', closeMenu);

    window.ShareCard = { decorateDrag: decorateDrag, fetch: fetchShare, render: renderCard };
})();
</script>
