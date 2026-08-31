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
            input: 'text',
            inputLabel: '{{ __("Tab Name") }}',
            inputPlaceholder: '{{ __("e.g., ต่อวีซ่า") }}',
            showCancelButton: true,
            confirmButtonText: '{{ __("Create") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#3b82f6',
            inputValidator: (value) => {
                if (!value || !value.trim()) return '{{ __("Please enter a tab name") }}';
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
                    body: JSON.stringify({ name: result.value.trim() })
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

    function editWorkTypeTab(tabId, currentName) {
        Swal.fire({
            title: '{{ __("Edit Tab Name") }}',
            input: 'text',
            inputValue: currentName,
            showCancelButton: true,
            confirmButtonText: '{{ __("Save") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#3b82f6',
            inputValidator: (value) => {
                if (!value || !value.trim()) return '{{ __("Please enter a tab name") }}';
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
                    body: JSON.stringify({ name: result.value.trim() })
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
        const warnLine = ordersCount > 0
            ? '{{ __("This tab currently has") }} ' + ordersCount + ' {{ __("job(s). They will be permanently deleted along with it.") }}'
            : '{{ __("This tab has no jobs in it right now.") }}';

        Swal.fire({
            title: '{{ __("Delete this tab?") }} “' + tabName + '”',
            html: '<div class="text-start small">' + warnLine + ' {{ __("This cannot be undone.") }}</div>',
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
