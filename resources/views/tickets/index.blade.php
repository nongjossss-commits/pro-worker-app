@extends('layouts.app')
@section('title', __('Submit Request/Track Work'))
@section('content')
<div class="content-section">
    @if ($message = Session::get('success'))
    <div class="alert alert-success mb-4" role="alert">
        {{ $message }}
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">{{ __('My Request List') }}</h2>
        <div class="d-flex flex-column flex-md-row gap-2">
            {{-- Per Page Selection (Must match employers.index) --}}
            <form action="{{ route('tickets.index') }}" method="GET" class="d-flex gap-2">
                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="25" @selected(request('per_page', 25) == 25)>{{ __('Show') }} 25</option>
                    <option value="50" @selected(request('per_page') == 50)>{{ __('Show') }} 50</option>
                    <option value="100" @selected(request('per_page') == 100)>{{ __('Show') }} 100</option>
                </select>
            </form>
            {{-- Create New Ticket Button --}}
            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> {{ __('Create New Ticket') }}
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>{{ __('Subject') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created Date') }}</th>
                    <th class="text-center">{{ __('Manage') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                <tr class="{{ $ticket->employer_unread_count > 0 ? 'table-warning' : '' }}"
                    draggable="true"
                    ondragstart="window.startDragGlobal(event, 'ticket', {
                        id: {{ $ticket->id }},
                        title: @json($ticket->subject),
                        subtitle: 'Ticket #{{ $ticket->id }}',
                        url: '{{ route('tickets.show', $ticket) }}'
                    })"
                    style="cursor: grab;">
                    <td>{{ $ticket->id }}</td>
                    <td>
                        {{ Str::limit($ticket->subject, 70) }}
                        @if($ticket->employer_unread_count > 0)
                            <span class="badge bg-danger ms-2">{{ __('New Message') }}</span>
                        @endif
                    </td>
                    <td>
                        {{-- Use Accessors for Status Badge --}}
                        <span class="badge bg-{{ $ticket->status_color }}">
                            {{ $ticket->status_name }}
                        </span>
                    </td>
                    {{-- Format date consistently --}}
                    <td>{{ $ticket->created_at->format('d M Y H:i') }}</td>
                    <td class="text-center">
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary position-relative">
                            <i class="bi bi-eye"></i> {{ __('View Details') }}
                            @if($ticket->employer_unread_count > 0)
                                <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
                                    <span class="visually-hidden">Unread</span>
                                </span>
                            @endif
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">{{ __("You haven't submitted any requests yet") }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{-- Pagination: Crucial to append existing query params (like per_page) --}}
            {{ $tickets->appends(request()->except('page'))->links() }}
        </div>
    </div>
</div>
@endsection
