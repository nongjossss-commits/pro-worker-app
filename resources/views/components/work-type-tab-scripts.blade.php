{{-- resources/views/components/work-type-tab-scripts.blade.php
     Shared Super-Admin JS for managing Work Type tabs (create/rename/delete).
     Included from both work-type-tab-bar.blade.php (Pre-Production/Workflow
     tab pages) and workflow/dashboard.blade.php (the /workflow landing page,
     which shows its own tile grid instead of the tab bar) so tab management
     is reachable and identical from either place. @once guards against
     double-declaring these functions if a page ever includes both. --}}
@once
@push('scripts')
<script>
    function createWorkTypeTab() {
        Swal.fire({
            title: '{{ __("Create New Tab") }}',
            html:
                '<input id="swal-tab-name" class="swal2-input" placeholder="{{ __("e.g., ต่อวีซ่า") }}">' +
                '<div class="form-check text-start mx-4 mt-2">' +
                    '<input class="form-check-input" type="checkbox" id="swal-allow-multiple">' +
                    '<label class="form-check-label small" for="swal-allow-multiple">' +
                        '{{ __("Allow multiple cards per employer") }} ' +
                        '<span class="text-muted d-block">{{ __("Like MOU Import — a new card each time, instead of one shared card reused over and over") }}</span>' +
                    '</label>' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: '{{ __("Create") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#3b82f6',
            focusConfirm: false,
            preConfirm: () => {
                const name = document.getElementById('swal-tab-name').value.trim();
                if (!name) {
                    Swal.showValidationMessage('{{ __("Please enter a tab name") }}');
                    return false;
                }
                return {
                    name: name,
                    allow_multiple_orders: document.getElementById('swal-allow-multiple').checked,
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                fetch('{{ route("admin.work-types.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
                        // Stay on whichever page (Workflow or Pre-Production) this was
                        // triggered from — both share the same WorkType tabs.
                        const base = window.location.pathname.replace(/\/$/, '') || '{{ route("workflow.index") }}';
                        window.location.href = base + '?tab=' + data.type.slug;
                    } else {
                        Swal.fire('Error', data.message || 'Could not create tab.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'An unexpected error occurred.', 'error'));
            }
        });
    }

    // allowMultipleOrders/isSystem come from the tab's own current values
    // (see work-type-tab-bar.blade.php/workflow/dashboard.blade.php) so the
    // checkbox opens pre-filled — system tabs (MOU Import etc.) never show
    // it at all, since their card mode is fixed and WorkTypeController's
    // own update() guard ignores the field for them anyway.
    function editWorkTypeTab(tabId, currentName, allowMultipleOrders, isSystem) {
        const checkboxHtml = isSystem ? '' :
            '<div class="form-check text-start mx-4 mt-2">' +
                '<input class="form-check-input" type="checkbox" id="swal-allow-multiple-edit"' + (allowMultipleOrders ? ' checked' : '') + '>' +
                '<label class="form-check-label small" for="swal-allow-multiple-edit">' +
                    '{{ __("Allow multiple cards per employer") }} ' +
                    '<span class="text-muted d-block">{{ __("Like MOU Import — a new card each time, instead of one shared card reused over and over") }}</span>' +
                '</label>' +
            '</div>';

        Swal.fire({
            title: '{{ __("Edit Tab Name") }}',
            html:
                '<input id="swal-tab-name-edit" class="swal2-input" value="' + currentName.replace(/"/g, '&quot;') + '">' +
                checkboxHtml,
            showCancelButton: true,
            confirmButtonText: '{{ __("Save") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#3b82f6',
            focusConfirm: false,
            preConfirm: () => {
                const name = document.getElementById('swal-tab-name-edit').value.trim();
                if (!name) {
                    Swal.showValidationMessage('{{ __("Please enter a tab name") }}');
                    return false;
                }
                const payload = { name: name };
                if (!isSystem) {
                    payload.allow_multiple_orders = document.getElementById('swal-allow-multiple-edit').checked;
                }
                return payload;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                fetch('{{ url("admin/work-types") }}/' + tabId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
                        window.location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Could not update tab.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'An unexpected error occurred.', 'error'));
            }
        });
    }

    function deleteWorkTypeTab(tabId, tabName, ordersCount) {
        // Deleting a tab is a SOFT delete — it just disappears from the tab
        // list; every job/card that was ever under it stays fully intact in
        // the database (see WorkTypeController::destroy()'s docblock).
        const warnLine = ordersCount > 0
            ? '{{ __("This tab currently has") }} ' + ordersCount + ' {{ __("job(s). They will NOT be deleted — this only removes the tab from the list; the jobs\' data stays exactly as it is.") }}'
            : '{{ __("This tab has no jobs in it right now.") }}';

        Swal.fire({
            title: '{{ __("Delete this tab?") }} “' + tabName + '”',
            html: '<div class="text-start small">' + warnLine + '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("Delete") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#dc3545',
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ url("admin/work-types") }}/' + tabId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
                        // Drop back to the current page's tab list (no ?tab=, since
                        // that tab's slug no longer exists) — works for both Workflow
                        // and Pre-Production since they share the same tabs.
                        window.location.href = window.location.pathname.replace(/\/$/, '') || '{{ route("workflow.index") }}';
                    } else {
                        Swal.fire('Error', data.message || 'Could not delete tab.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'An unexpected error occurred.', 'error'));
            }
        });
    }
</script>
@endpush
@endonce
