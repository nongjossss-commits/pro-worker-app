@extends('layouts.app')

@section('title', 'Delegates List')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Delegates</h1>
                <div class="d-flex gap-2">
                    <input type="text" id="delegate-search-input" class="form-control form-control-sm" placeholder="Search...">
                    <a href="{{ route('delegates.create') }}" class="btn btn-primary">Add Delegate</a>
                </div>
            </div>
            <hr>
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name (TH)</th>
                        <th>National ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="delegate-table-body">
                    @foreach ($delegates as $delegate)
                    <tr>
                        <td>
                            @if ($delegate->delegatePhoto)
                                <img src="{{ asset('storage/' . $delegate->delegatePhoto) }}" alt="{{ $delegate->delegateNameEn }}" width="50">
                            @endif
                        </td>
                        <td>{{ $delegate->delegateNameTh }}</td>
                        <td>{{ $delegate->delegateId }}</td>
                        <td>
                            @can('edit-delegates')
                            <a href="{{ route('delegates.edit', $delegate->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            @endcan
                            @can('delete-delegates')
                            <button type="button" class="btn btn-sm btn-danger btn-trigger-delete-modal"
                                    data-bs-toggle="modal"
                                    data-bs-target="#centralDeleteConfirmationModal"
                                    data-action="{{ route('delegates.destroy', $delegate->id) }}"
                                    data-message="Are you sure you want to delete delegate '{{ $delegate->delegateNameTh }}'?">
                                Delete
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search for Delegates Table
    const searchInput_delegate = document.getElementById('delegate-search-input');
    const tableBody_delegate = document.getElementById('delegate-table-body');
    const tableRows_delegate = tableBody_delegate.getElementsByTagName('tr');

    searchInput_delegate.addEventListener('keyup', function() {
        const searchTerm = searchInput_delegate.value.toLowerCase();
        for (let row of tableRows_delegate) {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? "" : "none";
        }
    });
</script>
@endpush
