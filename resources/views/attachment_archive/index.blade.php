@extends('layouts.app')

@section('title', __('Replaced files'))

@section('content')
{{-- Old attachments of one employee / employer kept for 30 days (AttachmentArchiveController). --}}
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1"><i class="bi bi-archive me-2"></i>{{ __('Replaced files') }} — {{ $title }}</h4>
            <div class="text-muted small">
                {{ __('Old attachments that were replaced or removed are kept for :days days, then deleted for good.', ['days' => \App\Support\AttachmentArchive::KEEP_DAYS]) }}
            </div>
        </div>
        @can('manage-tickets')
        <a href="{{ route('admin.activity-logs.subject-history', ['subject_type' => \App\Support\AttachmentArchive::OWNERS[$owner], 'subject_id' => $model->id]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i>{{ __('Change history') }}
        </a>
        @endcan
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Slot') }}</th>
                        <th>{{ __('Old file') }}</th>
                        <th>{{ __('Replaced / removed on') }}</th>
                        <th>{{ __('Deleted for good on') }}</th>
                        <th class="text-end">{{ __('Size') }}</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $f)
                        @php $daysLeft = max(0, (int) now()->startOfDay()->diffInDays($f['deleted_on']->copy()->startOfDay(), false)); @endphp
                        <tr>
                            <td class="fw-semibold">{{ $f['label'] }}</td>
                            <td class="text-break small">{{ $f['name'] }}</td>
                            <td class="text-nowrap">{{ $f['archived_at']->format('d/m/Y H:i') }}</td>
                            <td class="text-nowrap">
                                {{ $f['deleted_on']->format('d/m/Y') }}
                                <span class="badge {{ $daysLeft <= 3 ? 'bg-danger' : 'bg-secondary' }} ms-1">{{ __(':n days left', ['n' => $daysLeft]) }}</span>
                            </td>
                            <td class="text-end text-nowrap small">{{ number_format($f['size'] / 1024, 0) }} KB</td>
                            <td class="text-end">
                                <a href="{{ route('attachment-archive.show', [$owner, $model->id, basename($f['path'])]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>{{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>{{ __('No replaced files in the last :days days.', ['days' => \App\Support\AttachmentArchive::KEEP_DAYS]) }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
