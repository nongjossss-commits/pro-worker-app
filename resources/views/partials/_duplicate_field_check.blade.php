{{-- Duplicate warning for single fields saved straight from a job card (2026-11-11)
     — the RA number and the Outsource login e-mail are usually typed on the
     Registration / Renewal / Workflow cards AFTER the employee was created, and
     those inline saves skipped the duplicate check: the warning only appeared the
     next time someone saved the full Edit form. This runs the SAME check and the
     SAME popup as resources/js/duplicate-check.js (via its
     window.runManualDuplicateCheck) on a throw-away form holding just those values.

     window.checkEmployeeFieldDuplicates({ name_list_number: '...' }, employeeId)
         → Promise<boolean>: true = no duplicate, or the user chose "save anyway";
           false = cancel (or a blocking duplicate such as the e-mail). --}}
<script>
window.checkEmployeeFieldDuplicates = function (values, excludeId) {
    const filled = Object.entries(values || {}).filter(([, v]) => String(v ?? '').trim() !== '');
    if (!filled.length || typeof window.runManualDuplicateCheck !== 'function') {
        return Promise.resolve(true);
    }
    const form = document.createElement('form');
    form.dataset.duplicateCheckUrl = @json(route('employees.check_duplicate'));
    form.dataset.duplicateModelType = 'employee';
    form.dataset.duplicateFields = JSON.stringify(filled.map(([k]) => k));
    if (excludeId) form.dataset.duplicateExcludeId = String(excludeId);
    form.dataset.duplicateLabelTitle = @json(__('Duplicate data found'));
    form.dataset.duplicateLabelProceed = @json(__('Save anyway'));
    form.dataset.duplicateLabelFix = @json(__('Cancel, fix data first'));
    form.dataset.duplicateLabelOk = @json(__('OK'));
    form.dataset.duplicateLabelTerminated = @json(__('Terminated'));
    filled.forEach(([name, value]) => {
        const input = document.createElement('input');
        input.name = name;
        input.value = String(value).trim();
        form.appendChild(input);
    });
    return window.runManualDuplicateCheck(form);
};
</script>
