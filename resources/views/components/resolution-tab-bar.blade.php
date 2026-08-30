@props([
    'currentTab',
    'allTabs',
    'type' => 'registration', // 'registration' or 'renewal'
    'routePrefix' => 'production.registration', // 'production.registration' or 'production.renewal'
])

@php
    $isSuperAdmin = auth()->user()->hasRole('super-admin');
    $csrfToken = csrf_token();
    $typeLabel = $type === 'registration' ? __('Registration Resolution') : __('Renewal Resolution');
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <div class="d-flex align-items-center flex-wrap">
            {{-- Tab items --}}
            <ul class="nav nav-tabs border-0 flex-nowrap overflow-auto flex-grow-1" id="resolution-tabs-nav" style="white-space: nowrap;">
                @foreach($allTabs as $tab)
                    <li class="nav-item">
                        <a class="nav-link px-4 py-3 fw-semibold d-flex align-items-center gap-2 {{ $tab->id === $currentTab->id ? 'active text-primary' : 'text-muted' }}"
                           href="{{ route($routePrefix . '.index', ['resolutionTab' => $tab->id]) }}"
                           style="{{ $tab->id === $currentTab->id ? 'border-bottom: 3px solid #3b82f6;' : '' }}">
                            @if($tab->is_default)
                                <i class="bi bi-star-fill text-warning" style="font-size: 0.7rem;" title="{{ __('Default Tab') }}"></i>
                            @endif
                            <span>{{ $tab->name }}</span>

                            {{-- Not-live hint (Super Admin only) — explains why this tab's
                                 employees carry no purple/pink card badge --}}
                            @if($isSuperAdmin && !$tab->badge_enabled)
                                <span class="badge bg-secondary-subtle text-secondary fw-normal" style="font-size: 0.65rem;">{{ __('Badges off') }}</span>
                            @endif

                            {{-- Edit/Delete/Badge-toggle buttons for Super Admin --}}
                            @if($isSuperAdmin)
                                <span class="d-inline-flex gap-1 ms-1">
                                    <button type="button" class="btn btn-link p-0 border-0 resolution-tab-badge-toggle-btn {{ $tab->badge_enabled ? 'text-success' : 'text-muted' }}"
                                            data-tab-id="{{ $tab->id }}"
                                            onclick="event.preventDefault(); event.stopPropagation(); toggleResolutionTabBadge({{ $tab->id }}, this)"
                                            title="{{ $tab->badge_enabled ? __('Badges on — click to turn off for this tab') : __('Badges off — click to turn on for this tab') }}"
                                            style="font-size: 0.9rem;">
                                        <i class="bi {{ $tab->badge_enabled ? 'bi-toggle2-on' : 'bi-toggle2-off' }}"></i>
                                    </button>
                                    <button type="button" class="btn btn-link p-0 border-0 text-muted resolution-tab-edit-btn"
                                            data-tab-id="{{ $tab->id }}"
                                            data-tab-name="{{ $tab->name }}"
                                            onclick="event.preventDefault(); event.stopPropagation(); editResolutionTab({{ $tab->id }}, '{{ addslashes($tab->name) }}')"
                                            title="{{ __('Edit Name') }}" style="font-size: 0.75rem;">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    @if(!$tab->is_default)
                                    <button type="button" class="btn btn-link p-0 border-0 text-danger resolution-tab-delete-btn"
                                            data-tab-id="{{ $tab->id }}"
                                            onclick="event.preventDefault(); event.stopPropagation(); deleteResolutionTab({{ $tab->id }}, '{{ addslashes($tab->name) }}')"
                                            title="{{ __('Delete Tab') }}" style="font-size: 0.75rem;">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                    @endif
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Add Tab button (Super Admin only) --}}
            @if($isSuperAdmin)
            <div class="px-3 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold"
                        onclick="createResolutionTab()"
                        title="{{ __('Add New Tab') }}">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Add Tab') }}
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

@if($isSuperAdmin)
@once
<script>
    function createResolutionTab() {
        Swal.fire({
            title: '{{ __("Create New Tab") }}',
            input: 'text',
            inputLabel: '{{ __("Tab Name") }}',
            inputPlaceholder: '{{ __("e.g., มติ ครม 16 กันยายน 2569") }}',
            showCancelButton: true,
            confirmButtonText: '{{ __("Create") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#3b82f6',
            inputValidator: (value) => {
                if (!value || !value.trim()) return '{{ __("Please enter a tab name") }}';
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                fetch('{{ route("admin.resolution-tabs.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ $csrfToken }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: result.value.trim(),
                        type: '{{ $type }}'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
                        // Navigate to the new tab
                        window.location.href = '{{ url("production/" . $type) }}/' + data.tab.id;
                    } else {
                        Swal.fire('Error', data.message || 'Could not create tab.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'An unexpected error occurred.', 'error'));
            }
        });
    }

    function editResolutionTab(tabId, currentName) {
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
                fetch('{{ url("admin/resolution-tabs") }}/' + tabId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ $csrfToken }}',
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

    function toggleResolutionTabBadge(tabId, btnEl) {
        fetch('{{ url("admin/resolution-tabs") }}/' + tabId + '/toggle-badge', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ $csrfToken }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
                window.location.reload();
            } else {
                Swal.fire('Error', data.message || 'Could not toggle this tab.', 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'An unexpected error occurred.', 'error'));
    }

    function deleteResolutionTab(tabId, tabName) {
        Swal.fire({
            title: '{{ __("Delete Tab?") }}',
            html: `{{ __("Are you sure you want to delete") }} <strong>${tabName}</strong>?<br><br>` +
                  `<small class="text-muted">{{ __("The tab will be permanently deleted after 7 days. You can restore it before then.") }}</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("Delete") }}',
            cancelButtonText: '{{ __("Cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ url("admin/resolution-tabs") }}/' + tabId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ $csrfToken }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
                        // Redirect to the default/first tab
                        window.location.href = '{{ url("production/" . $type) }}';
                    } else {
                        Swal.fire('Error', data.message || 'Could not delete tab.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'An unexpected error occurred.', 'error'));
            }
        });
    }
</script>
@endonce
@endif
