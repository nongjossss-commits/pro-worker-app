@extends('layouts.app')

@section('title', __('Agents'))

@section('content')
<div class="content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
         <h2 class="mb-3 mb-md-0">{{ __('Agent List') }}</h2>
         <div class="d-flex gap-2">
            <input type="text" id="agent-search-input" class="form-control form-control-sm" placeholder="{{ __('Search...') }}">
            <a href="{{ route('agents.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> {{ __('Add New') }}</a>
         </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Agent Name') }}</th>
                    <th>{{ __('License Number') }}</th>
                    <th>{{ __('Phone Number') }}</th>
                    <th class="text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody id="agent-table-body">
                @forelse ($agents as $agent)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $agent->agentNameEn }}</td>
                        <td>{{ $agent->agentLicense }}</td>
                        <td>{{ $agent->agentPhone }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="agent" data-model-id="{{ $agent->id }}" title="{{ __('Preview Data') }}">
                                <i class="bi bi-search"></i>
                            </button>
                            @can('edit-agents')
                            <a href="{{ route('agents.edit', $agent) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                            @endcan
                            @can('delete-agents')
                            <form action="{{ route('agents.destroy', $agent) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ __('No agents found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search for Agents Table
    const searchInput_agent = document.getElementById('agent-search-input');
    const tableBody_agent = document.getElementById('agent-table-body');
    const tableRows_agent = tableBody_agent.getElementsByTagName('tr');

    searchInput_agent.addEventListener('keyup', function() {
        const searchTerm = searchInput_agent.value.toLowerCase();
        for (let row of tableRows_agent) {
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
