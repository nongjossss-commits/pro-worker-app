/*
 * Date fields: type the digits only — the slashes go in by themselves.
 *   09062026      → 09/06/2026
 *   090620261430  → 09/06/2026 14:30   (date + time pickers)
 * Works on every flatpickr text box that shows d/m/Y or d/m/Y H:i (the global
 * input[type=date] pickers in layouts/app and every page-level picker), found
 * through event delegation — nothing to wire up per page.
 *  - typing your own "/" still works ("9/6/2026" → 09/06/2026)
 *  - a first digit that can't start a 2-digit day / month / hour is padded
 *    (day 4 → 04/, month 2 → 02/, hour 3 → 03:)
 *  - once complete, the date is applied to the picker at once (calendar jumps,
 *    hidden Y-m-d value + change events fire); an impossible date such as
 *    31/02/2026 is marked red instead of silently rolling over
 *  - phones get the number keypad
 */
(function () {
    'use strict';

    var LAYOUTS = {
        'd/m/Y': { len: [2, 2, 4], sep: ['/', '/'], pad: [3, 1] },
        'd/m/Y H:i': { len: [2, 2, 4, 2, 2], sep: ['/', '/', ' ', ':'], pad: [3, 1, null, 2] },
    };

    function pickerFor(el) {
        if (!el || el.tagName !== 'INPUT') return null;
        if (el._flatpickr) return el._flatpickr.config.allowInput ? el._flatpickr : null;
        var prev = el.previousElementSibling;
        if (prev && prev._flatpickr && prev._flatpickr.altInput === el && prev._flatpickr.config.allowInput) {
            return prev._flatpickr;
        }
        return null;
    }

    function layoutFor(fp) {
        var fmt = fp.config.altInput ? fp.config.altFormat : fp.config.dateFormat;
        return LAYOUTS[fmt] ? { fmt: fmt, spec: LAYOUTS[fmt] } : null;
    }

    function format(raw, spec, deleting) {
        var fields = [''], i = 0;
        for (var c = 0; c < raw.length; c++) {
            var ch = raw.charAt(c);
            if (ch >= '0' && ch <= '9') {
                if (fields[i].length === spec.len[i]) {
                    if (i + 1 >= spec.len.length) break;
                    fields[++i] = '';
                }
                fields[i] += ch;
                // "4" can't begin a day (max 31) → 04, "2" a month → 02, "3" an hour → 03
                var max = spec.pad[i];
                if (!deleting && max != null && fields[i].length === 1 && +ch > max && i + 1 < spec.len.length) {
                    fields[i] = '0' + ch;
                }
            } else if (fields[i].length > 0 && i + 1 < spec.len.length) {
                // the user typed a separator: close the current part (9/ → 09/)
                if (spec.len[i] === 2 && fields[i].length === 1) fields[i] = '0' + fields[i];
                fields[++i] = '';
            }
        }
        var out = fields[0];
        for (var k = 1; k < fields.length; k++) out += spec.sep[k - 1] + fields[k];
        if (!deleting && fields[i].length === spec.len[i] && i + 1 < spec.len.length) {
            out += spec.sep[i];
        }
        var complete = i === spec.len.length - 1 && fields[i].length === spec.len[i];
        return { text: out, complete: complete };
    }

    var toastTimer = null;
    function warnInvalid() {
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            var msg = (window.DateAutoSlashText && window.DateAutoSlashText.invalid) || 'Invalid date';
            if (window.appToast) window.appToast(msg, 'warning');
        }, 50);
    }

    // flatpickr turns an impossible typed date (31/02/2026) into a real one
    // (03/03/2026) when the box loses focus. Parse the typed format strictly
    // instead: an impossible date is rejected (the box empties) with a warning.
    // Other strings (Y-m-d values set by code) keep flatpickr's own parser.
    function makeStrict(fp, layout) {
        if (fp._autoSlashStrict) return;
        fp._autoSlashStrict = true;
        var own = fp.config.parseDate;
        fp.config.parseDate = function (str, fmt) {
            if (fmt !== layout.fmt || own) {
                return own ? own(str, fmt) : flatpickr.parseDate(str, fmt);
            }
            var text = format(str, layout.spec, true).text;
            var date = flatpickr.parseDate(text, fmt);
            if (date && flatpickr.formatDate(date, fmt) === text) return date;
            warnInvalid();
            return undefined;
        };
    }

    document.addEventListener('input', function (e) {
        var el = e.target;
        var fp = pickerFor(el);
        if (!fp) return;
        var layout = layoutFor(fp);
        if (!layout) return;
        makeStrict(fp, layout);
        el.classList.remove('is-invalid');
        // only while typing at the end — editing in the middle is left alone
        if (el.selectionStart !== el.value.length) return;

        var deleting = !!(e.inputType && e.inputType.indexOf('delete') === 0);
        var res = format(el.value, layout.spec, deleting);
        if (res.text !== el.value) el.value = res.text;
        if (!res.complete || deleting) return;

        var date = fp.parseDate(res.text, layout.fmt);
        if (date && fp.formatDate(date, layout.fmt) === res.text) {
            fp.setDate(date, true);
        } else {
            el.classList.add('is-invalid');
        }
    }, true);

    // Number keypad on phones (the slashes are typed for the user).
    document.addEventListener('focusin', function (e) {
        var el = e.target;
        if (el.hasAttribute && !el.hasAttribute('inputmode')) {
            var fp = pickerFor(el);
            if (fp && layoutFor(fp)) el.setAttribute('inputmode', 'numeric');
        }
    }, true);

    window.DateAutoSlash = { format: function (raw, fmt, deleting) { return format(raw, LAYOUTS[fmt], !!deleting); } };
})();
