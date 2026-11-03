/*
 * Date boxes: what you see is what gets saved (2026-11-03).
 *
 * Every date box is a flatpickr with altInput: the real field (the one with
 * the name, sent to the server) is turned into a HIDDEN input and the user
 * sees a separate text box. A hidden input cannot be reset — form.reset()
 * empties the visible box but leaves the hidden value. Popups that reuse one
 * form for the next record (e.g. Workflow → Add Employee → New) therefore
 * saved the PREVIOUS employee's dates into the next one while the boxes
 * looked empty (bug report 2026-11-03).
 *
 *  - reset:    every date box in the form is really cleared (shown + sent).
 *  - formdata: fired by every form submit and by `new FormData(form)` (the
 *              AJAX saves) — the value sent is made to match the box:
 *              box empty → sent empty; box shows a date → that date is sent.
 *
 * Works for any flatpickr in the page, whoever created it (main layout,
 * Labour layout, cards, finance scripts).
 */
(function () {
    'use strict';

    function datePickers(form) {
        if (!form || !form.querySelectorAll) return [];
        return Array.prototype.filter.call(form.querySelectorAll('input'), function (el) {
            return el._flatpickr && el._flatpickr.altInput && el.name;
        });
    }

    // Make every date box under `root` show the value its real field holds —
    // for fields filled / emptied by a script (e.g. Alpine x-model resetting a
    // form), which flatpickr's visible box does not notice by itself.
    window.syncDatePickers = function (root) {
        datePickers(root || document).forEach(function (el) {
            var fp = el._flatpickr;
            if (fp.config.mode && fp.config.mode !== 'single') return;
            var picked = fp.selectedDates && fp.selectedDates.length ? fp.formatDate(fp.selectedDates[0], fp.config.dateFormat) : '';
            if (el.value === picked) return;
            if (el.value) { try { fp.setDate(el.value, false); } catch (err) {} }
            else { try { fp.clear(false); } catch (err) {} }
        });
    };
    // Every popup, when it opens (forms are often filled just before).
    document.addEventListener('shown.bs.modal', function (e) { window.syncDatePickers(e.target); });

    // Capture phase: runs before the browser resets the fields, so the hidden
    // input's own "default" becomes empty too.
    document.addEventListener('reset', function (e) {
        datePickers(e.target).forEach(function (el) {
            try { el._flatpickr.clear(false); } catch (err) { el.value = ''; }
            el.value = '';
        });
    }, true);

    document.addEventListener('formdata', function (e) {
        var form = e.target;
        datePickers(form).forEach(function (el) {
            var fp = el._flatpickr;
            if (el.disabled || !e.formData.has(el.name)) return;
            if (fp.config.mode && fp.config.mode !== 'single') return;
            // only single fields (name used once in this form)
            if (form.querySelectorAll('[name="' + el.name.replace(/"/g, '\\"') + '"]').length !== 1) return;

            var picked = fp.selectedDates && fp.selectedDates.length ? fp.formatDate(fp.selectedDates[0], fp.config.dateFormat) : '';
            var shown = (fp.altInput.value || '').trim();

            // The field was filled by a script (not through the box): keep that
            // value and show it, so the box never hides what is saved.
            if (el.value !== picked) {
                if (el.value) { try { fp.setDate(el.value, false); } catch (err) {} }
                else { try { fp.clear(false); } catch (err) {} }
                return;
            }
            // Box emptied but the old date is still in the real field — send empty.
            if (shown === '') {
                if (el.value !== '') {
                    try { fp.clear(false); } catch (err) {}
                    el.value = '';
                    e.formData.set(el.name, '');
                }
                return;
            }
            // A date typed in the box that never reached the real field
            // (saved without leaving the box) — send what is shown.
            var d = fp.parseDate(shown, fp.config.altFormat);
            if (d && !isNaN(d.getTime())) {
                var value = fp.formatDate(d, fp.config.dateFormat);
                if (value !== el.value) {
                    fp.setDate(d, false);
                    el.value = value;
                    e.formData.set(el.name, value);
                }
            }
        });
    }, true);
})();
