@extends('labor.layout')

@section('title', 'Audit Log - Pro Walker Labour')

@section('content')
<h4 class="fw-bold mb-3">{{ __('Audit Log') }}</h4>
<p class="text-muted small mb-3">{{ __('Every change made to any team\'s ledger — visible to Super Admin only.') }}</p>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('When') }}</th>
                    <th>{{ __('Who') }}</th>
                    <th>{{ __('Team') }}</th>
                    <th>{{ __('Action') }}</th>
                    <th>{{ __('Details') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user->name ?? __('Unknown') }}</td>
                    <td>{{ $log->team->name ?? '-' }}</td>
                    <td>
                        <span class="badge
                            @if($log->action === 'created') bg-success
                            @elseif($log->action === 'deleted') bg-danger
                            @elseif($log->action === 'restored') bg-info
                            @else bg-warning text-dark
                            @endif">
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td>
                        <details>
                            <summary class="small text-muted" style="cursor:pointer;">{{ __('View diff') }}</summary>
                            <pre class="small bg-light p-2 rounded mt-1 mb-0" style="max-width: 400px; white-space: pre-wrap;">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">{{ __('No activity recorded yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-white">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
