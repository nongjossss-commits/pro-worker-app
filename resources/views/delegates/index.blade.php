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
                            <a href="{{ route('delegates.edit', $delegate->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('delegates.destroy', $delegate->id) }}" method="POST" style="display:inline-block;" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
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

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
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
                                'Deleted!',
                                'The record has been deleted.',
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                data.error || 'Could not delete the record.',
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        Swal.fire(
                            'Error!',
                            'An error occurred while submitting the data.',
                            'error'
                        );
                    });
                }
            });
        });
    });
</script>
@endpush
