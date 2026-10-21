/*
 * Upload size guard — stops a form from being sent when its files are bigger
 * than the server accepts (PHP upload_max_filesize / post_max_size, passed in
 * window.UploadLimits by the layout). Without it the server drops a too-big
 * file silently or the whole request fails (error page), and the user thinks
 * it was saved. Shows which file is too big instead.
 */
(function () {
    'use strict';
    const L = window.UploadLimits || {};
    if (!L.file && !L.post) return;

    const mb = b => (b / 1048576).toFixed(b >= 10485760 ? 0 : 1) + ' MB';
    const fill = (s, map) => Object.keys(map).reduce((t, k) => t.split(':' + k).join(map[k]), s);

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        const inputs = form.querySelectorAll('input[type="file"]');
        if (!inputs.length) return;

        let total = 0;
        const tooBig = [];
        inputs.forEach(inp => Array.from(inp.files || []).forEach(f => {
            total += f.size;
            if (L.file && f.size > L.file) tooBig.push(f.name + ' (' + mb(f.size) + ')');
        }));

        let msg = null;
        if (tooBig.length) {
            msg = fill(L.textFile || 'These files are larger than :max each and cannot be uploaded: :files', { max: mb(L.file), files: tooBig.join(', ') });
        } else if (L.post && total > L.post * 0.98) {
            msg = fill(L.textTotal || 'The files together are :total — the limit for one save is :max. Save fewer files at a time.', { total: mb(total), max: mb(L.post) });
        }
        if (!msg) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        if (window.appAlert) appAlert(msg, { icon: 'warning', title: L.textTitle || 'File too large' });
        else window.alert(msg);
    }, true);
})();
