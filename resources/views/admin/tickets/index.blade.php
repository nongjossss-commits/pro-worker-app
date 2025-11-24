@extends('layouts.app')
@section('title', __('Ticket Inbox') . ' (Admin/Staff)')
@section('content')
<div class="content-section">
    @if ($message = Session::get('success'))
    <div class="alert alert-success mb-4" role="alert">
        {{ $message }}
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="d-flex align-items-center gap-3">
            @if(request('employer_id'))
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                </a>
                <h2 class="mb-0">{{ __('Ticket') }}: {{ $employerName ?? __('Unspecified') }}</h2>
            @else
                <h2 class="mb-0">{{ __('Ticket Inbox') }}</h2>
            @endif
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            {{-- Per Page Selection (Must match employers.index) --}}
            <form action="{{ route('admin.tickets.index') }}" method="GET" class="d-flex gap-2 align-items-center">
                @if(request('employer_id'))
                    <input type="hidden" name="employer_id" value="{{ request('employer_id') }}">
                @endif
                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="25" @selected(request('per_page', 25) == 25)>{{ __('Show') }} 25</option>
                    <option value="50" @selected(request('per_page') == 50)>{{ __('Show') }} 50</option>
                    <option value="100" @selected(request('per_page') == 100)>{{ __('Show') }} 100</option>
                </select>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>{{ __('Subject') }}</th>
                    <th>{{ __('Company/Employer') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Assignee') }}</th>
                    <th>{{ __('Created Date') }}</th>
                    <th class="text-center">{{ __('Manage') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                <tr class="{{ $ticket->admin_unread_count > 0 ? 'table-info' : '' }}"
                    draggable="true"
                    data-drag-payload="{{ json_encode([
                        'id' => $ticket->id,
                        'title' => $ticket->subject,
                        'subtitle' => 'Ticket #' . $ticket->id,
                        'url' => route('admin.tickets.show', $ticket)
                    ]) }}"
                    ondragstart="window.startDragGlobal(event, 'ticket', JSON.parse(this.dataset.dragPayload))"
                    style="cursor: grab;">
                    <td>{{ $ticket->id }}</td>
                    <td>
                        {{ Str::limit($ticket->subject, 50) }}
                        @if($ticket->admin_unread_count > 0)
                            <span class="badge bg-danger ms-2">{{ __('New') }}</span>
                        @endif
                    </td>
                    <td>
                        {{-- Display Company Name if available, otherwise User Name --}}
                        {{-- Uses optimized relationship loading --}}
                        @if($ticket->employerUser && $ticket->employerUser->employer)
                            {{ $ticket->employerUser->employer->employerNameTh ?? $ticket->employerUser->name }}
                            <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $ticket->employerUser->employer->id }}" title="{{ __('Preview Data') }}"> <i class="bi bi-search"></i> </button>
                        @elseif($ticket->employerUser)
                             {{ $ticket->employerUser->name }}
                        @else
                            N/A (User Deleted)
                        @endif
                    </td>
                    <td>
                        {{-- Use Accessors for Status Badge --}}
                        <span class="badge bg-{{ $ticket->status_color }}">
                            {{ $ticket->status_name }}
                        </span>
                    </td>
                    <td>
                        {{ $ticket->assignedStaff->name ?? __('Unassigned') }}
                    </td>
                    {{-- Format date consistently --}}
                    <td>{{ $ticket->created_at->format('d M Y H:i') }}</td>
                    <td class="text-center">
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary position-relative">
                            <i class="bi bi-eye"></i> {{ __('View Details') }}
                            @if($ticket->admin_unread_count > 0)
                                <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
                                    <span class="visually-hidden">Unread</span>
                                </span>
                            @endif
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">{{ __('No tickets found') }}</td>
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
