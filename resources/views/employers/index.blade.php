@extends('layouts.app')

@section('title', __('Employer Data'))

@section('content')
<x-help-button manual="employers" title="{{ __('Employers') }}" />
<div class="content-section">
    @if ($message = Session::get('success'))
        <div class="alert alert-success mb-4" role="alert">
            {{ $message }}
        </div>
    @endif
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">{{ __('Employer List') }}</h2>
        <div class="d-flex flex-column flex-md-row gap-2">
            <form action="{{ route('employers.index') }}" method="GET" class="d-flex flex-column flex-md-row gap-2">
                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="10" @selected(request('per_page', 10) == 10)>10</option>
                    <option value="25" @selected(request('per_page') == 25)>25</option>
                    <option value="50" @selected(request('per_page') == 50)>50</option>
                    <option value="100" @selected(request('per_page') == 100)>100</option>
                </select>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search') }}..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Search') }}</button>
                @if(request('search'))
                    <a href="{{ route('employers.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Clear') }}</a>
                @endif
            </form>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                 <a href="{{ route('employers.export') }}" class="btn btn-sm btn-outline-secondary flex-grow-1 flex-md-grow-0 text-nowrap"><i class="bi bi-download"></i> {{ __('Export') }}</a>
                @can('create-employers')
                <a href="{{ route('employers.create') }}" class="btn btn-primary btn-sm flex-grow-1 flex-md-grow-0 text-nowrap"><i class="bi bi-plus-circle me-1"></i> {{ __('Add New') }}</a>
                @endcan
            </div>
        </div>
    </div>

    <x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

    <div class="bulk-action-bar align-items-center gap-2 p-2 bg-light border rounded shadow-lg"
         style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: auto; min-width: 400px;"
         id="bulkActionBar"
         draggable="true"
         ondragstart="window.startDragBulk && window.startDragBulk(event)">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="select-all-checkbox">
            <label class="form-check-label" for="select-all-checkbox">
                {{ __('Select All') }} (<span id="selected-count">0</span>)
            </label>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-danger" onclick="window.clearEmployerSelection()">{{ __('Clear Selection') }}</button>
            <button class="btn btn-sm btn-info text-white" onclick="window.openViewSelectedEmployers()">
                <i class="bi bi-eye me-1"></i> {{ __('View Selected') }}
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="table-select-all-checkbox" disabled></th>
                    <th>#</th>
                    <th style="width: 1%;"></th> {{-- Drag Handle Column --}}
                    <th>{{ __('Employer Name (Thai)') }}</th>
                    <th>{{ __('Employer ID') }}</th>
                    <th>{{ __('Business Type') }}</th>
                    <th>{{ __('Job Owner') }}</th>
                    <th>{{ __('Responsible Person') }}</th>
                    <th class="text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody id="employer-table-body">
                @forelse ($employers as $employer)
                    <tr id="employer-row-{{ $employer->id }}">
                        <td>
                            <input class="form-check-input employer-checkbox" type="checkbox" value="{{ $employer->id }}"
                                   data-name-th="{{ $employer->employerNameTh }}"
                                   data-employer-id="{{ $employer->employerId }}"
                                   data-business-type="{{ $employer->businessType }}">
                        </td>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab"
                               draggable="true"
                               ondragstart="window.startDragGlobal(event, 'employer', {
                                   id: {{ $employer->id }},
                                   name: '{{ addslashes($employer->employerNameTh) }}',
                                   code: '{{ $employer->employerId }}',
                                   url: '{{ route('employers.edit', $employer->id) }}'
                               })"
                               title="{{ __('Drag') }}">
                            </i>
                        </td>
                        <td>
                            {{ $employer->employerNameTh }}
                            @if(request('addrProvince'))
                                @foreach($employer->getMatchedAddresses(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $address)
                                    <div class="text-primary small fw-bold mt-1">
                                        {{ $address->full_address }}
                                        <span class="text-muted fw-normal">({{ $address->type === 'registered' ? __('Registered Address') : __('Workplace Address') }})</span>
                                    </div>
                                @endforeach
                            @endif
                        </td>
                        <td>{{ $employer->employerId }}</td>
                        <td>{{ $employer->businessType }}</td>
                        <td>{{ $employer->jobOwner->name ?? 'N/A' }}</td>
                        <td>
                            @if($employer->caretakers->isNotEmpty())
                                {{ $employer->caretakers->pluck('name')->join(', ') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex flex-column flex-md-row gap-1 justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $employer->id }}" title="{{ __('Preview Data') }}"> <i class="bi bi-search"></i> </button>
                                @can('edit-employers')
                                <a href="{{ route('employers.edit', ['employer' => $employer->id, 'return_url' => request()->fullUrl()]) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                @endcan
                                @can('delete-employers')
                                <form action="{{ route('employers.destroy', $employer) }}" method="POST" class="d-grid d-md-inline delete-employer-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">{{ __('No employers found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $employers->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .highlight {
        animation: highlight-fade 3s ease-out forwards;
        border: 2px solid #f97316 !important; /* An orange border */
        background-color: #fff7ed !important; /* Light orange background */
    }
    @keyframes highlight-fade {
        from { background-color: #ffedd5; border-color: #f97316; }
        to { background-color: transparent; border-color: transparent; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if (session('highlight_employer'))
        const employerId = '{{ session('highlight_employer') }}';
        const row = document.getElementById('employer-row-' + employerId);
        if (row) {
            row.classList.add('highlight');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => {
                row.classList.remove('highlight');
            }, 5000);
        }
    @endif

    const deleteForms = document.querySelectorAll('.delete-employer-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: '{{ __('Confirm Deletion') }}',
                text: "{{ __('Are you sure you want to delete this item?') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '{{ __('Yes, delete it!') }}',
                cancelButtonText: '{{ __('Cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });

    // --- Employer Selection Logic ---
    const STORAGE_KEY = 'selectedEmployerData';
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const tableSelectAllCheckbox = document.getElementById('table-select-all-checkbox');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCountSpan = document.getElementById('selected-count');

    // Helper functions
    function getSelected() {
        try { return JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]'); }
        catch { return []; }
    }
    function setSelected(data) {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        updateUI();
    }
    function getRowData(checkbox) {
        return {
            id: checkbox.value,
            name_th: checkbox.dataset.nameTh || '',
            name_en: checkbox.dataset.employerId || '', // Use Employer ID as EN name/code
            employer_name: checkbox.dataset.businessType || '', // Use Business Type as Subtitle
            photo: 'https://placehold.co/50x50/e2e8f0/6c757d?text=EMP' // Placeholder
        };
    }

    function updateUI() {
        const selected = getSelected();
        const selectedIds = selected.map(i => String(i.id));

        // Update checkboxes
        document.querySelectorAll('.employer-checkbox').forEach(cb => {
            cb.checked = selectedIds.includes(String(cb.value));
        });

        // Update Bulk Action Bar
        if (selected.length > 0) {
            bulkActionBar.style.display = 'flex';
            if(selectedCountSpan) selectedCountSpan.textContent = selected.length;
        } else {
            bulkActionBar.style.display = 'none';
        }

        // Update Select All Checkboxes
        const visibleCheckboxes = document.querySelectorAll('.employer-checkbox');
        const allVisibleSelected = visibleCheckboxes.length > 0 && Array.from(visibleCheckboxes).every(cb => cb.checked);

        if (selectAllCheckbox) selectAllCheckbox.checked = allVisibleSelected;
        if (tableSelectAllCheckbox) {
            tableSelectAllCheckbox.checked = allVisibleSelected;
            tableSelectAllCheckbox.disabled = visibleCheckboxes.length === 0;
        }
    }

    // Event Listeners
    document.body.addEventListener('change', function(e) {
        if (e.target.matches('.employer-checkbox')) {
            const current = getSelected();
            const data = getRowData(e.target);
            let next = [];
            if (e.target.checked) {
                // Add if not exists
                if (!current.find(i => String(i.id) === String(data.id))) {
                    next = [...current, data];
                } else {
                    next = current;
                }
            } else {
                next = current.filter(i => String(i.id) !== String(data.id));
            }
            setSelected(next);
        }
    });

    function handleSelectAll(isChecked) {
        const current = getSelected();
        const visible = document.querySelectorAll('.employer-checkbox');
        let next = [...current];

        if (isChecked) {
            visible.forEach(cb => {
                const data = getRowData(cb);
                if (!next.find(i => String(i.id) === String(data.id))) {
                    next.push(data);
                }
            });
        } else {
            const visibleIds = Array.from(visible).map(cb => String(cb.value));
            next = next.filter(i => !visibleIds.includes(String(i.id)));
        }
        setSelected(next);
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            handleSelectAll(this.checked);
        });
    }

    if (tableSelectAllCheckbox) {
        tableSelectAllCheckbox.addEventListener('change', function() {
            handleSelectAll(this.checked);
        });
    }

    // Expose global functions for buttons
    window.clearEmployerSelection = function() {
        setSelected([]);
    };

    window.openViewSelectedEmployers = function() {
        const data = getSelected();
        // Use global modal with custom data, custom title, and custom storage key
        if (window.openViewSelectedModal) {
            window.openViewSelectedModal(data, '{{ __('Selected Employers') }}', STORAGE_KEY);
        } else {
            console.error('window.openViewSelectedModal is not defined');
        }
    };

    // Listen for custom event from global modal removal
    window.addEventListener('custom-selection-changed', function(e) {
        if (e.detail && e.detail.key === STORAGE_KEY) {
            updateUI();
        }
    });

    // Initial Load
    updateUI();
});
</script>
@endpush
