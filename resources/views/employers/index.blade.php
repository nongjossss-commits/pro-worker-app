@extends('layouts.app')

@section('title', __('Employer Data'))

@section('content')
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
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
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
                    <tr draggable="true"
                        ondragstart="window.startDragGlobal(event, 'employer', {
                            id: {{ $employer->id }},
                            name: '{{ addslashes($employer->employerNameTh) }}',
                            code: '{{ $employer->employerId }}',
                            url: '{{ route('employers.edit', $employer->id) }}'
                        })"
                        style="cursor: grab;">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $employer->employerNameTh }}</td>
                        <td>{{ $employer->employerId }}</td>
                        <td>{{ $employer->businessType }}</td>
                        <td>{{ $employer->jobOwner->name ?? 'N/A' }}</td>
                        <td>{{ $employer->assignedStaff->name ?? '-' }}</td>
                        <td class="text-center">
                            <div class="d-flex flex-column flex-md-row gap-1 justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $employer->id }}" title="{{ __('Preview Data') }}"> <i class="bi bi-search"></i> </button>
                                @can('edit-employers')
                                <a href="{{ route('employers.edit', $employer) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
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
                        <td colspan="7" class="text-center text-muted">{{ __('No employers found') }}</td>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
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
});
</script>
@endpush
