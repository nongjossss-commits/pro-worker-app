@extends('layouts.app')

@section('title', __('Importers'))

@section('content')
<x-help-button manual="importers" title="{{ __('Importers') }}" />
<div class="content-section">
    @if ($message = Session::get('success'))
        <div class="alert alert-success mb-4" role="alert">
            {{ $message }}
        </div>
    @endif
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
         <h2 class="mb-3 mb-md-0">{{ __('Importer List') }}</h2>
         <div class="d-flex flex-column flex-md-row gap-2">
            <input type="text" id="importer-search-input" class="form-control form-control-sm" placeholder="{{ __('Search...') }}">
            <a href="{{ route('importers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> {{ __('Add New') }}</a>
         </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Importer Name (Thai)') }}</th>
                    <th>{{ __('Importer ID') }}</th>
                    <th>{{ __('License Number') }}</th>
                    <th class="text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody id="importer-table-body">
                @forelse ($importers as $importer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $importer->importerNameTh }}</td>
                        <td>{{ $importer->importerId }}</td>
                        <td>{{ $importer->importerLicenseNo }}</td>
                        <td class="text-center">
                            <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="importer" data-model-id="{{ $importer->id }}" title="{{ __('Preview Data') }}">
                                <i class="bi bi-search"></i>
                            </button>
                            @can('edit-importers')
                            <a href="{{ route('importers.edit', $importer->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                            @endcan
                            @can('delete-importers')
                            <form action="{{ route('importers.destroy', $importer->id) }}" method="POST" class="d-grid d-md-inline delete-form">
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
                        <td colspan="5" class="text-center text-muted">{{ __('No importers found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search for Importers Table
    const searchInput_importer = document.getElementById('importer-search-input');
    const tableBody_importer = document.getElementById('importer-table-body');
    const tableRows_importer = tableBody_importer.getElementsByTagName('tr');

    searchInput_importer.addEventListener('keyup', function() {
        const searchTerm = searchInput_importer.value.toLowerCase();
        for (let row of tableRows_importer) {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? "" : "none";
        }
    });

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: @json(__('Are you sure?')),
                text: @json(__('You will not be able to revert this!')),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: @json(__('Yes, delete it!')),
                cancelButtonText: @json(__('Cancel'))
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                @json(__('Deleted!')),
                                @json(__('Your data has been deleted')),
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                @json(__('Error!')),
                                data.error || @json(__('Could not delete data')),
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        Swal.fire(
                            @json(__('Error!')),
                            @json(__('Error submitting data')),
                            'error'
                        );
                    });
                }
            });
        });
    });
</script>
@endpush
