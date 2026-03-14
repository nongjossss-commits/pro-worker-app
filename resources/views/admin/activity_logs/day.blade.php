@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.activity-logs.index') }}">{{ __('Activity Logs') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.activity-logs.year', $year) }}">{{ $year }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.activity-logs.month', ['year' => $year, 'month' => $month]) }}">Month {{ $month }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Day {{ $day }}</li>
                </ol>
            </nav>
            @php
                $fullDate = \Carbon\Carbon::parse($date)->locale('th')->translatedFormat('d F Y (l)');
            @endphp
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold mb-1">บันทึกประจำวัน: {{ $fullDate }}</h2>
                    <p class="text-muted mb-0">{{ __('แสดงรายการแก้ไขอัพเดตและเปลี่ยนแปลงทุกย่างก้าว') }}</p>
                </div>
                <div>
                    <form action="{{ route('admin.activity-logs.day', ['year' => $year, 'month' => $month, 'day' => $day]) }}" method="GET" class="d-flex align-items-center gap-2">
                        <label for="user_id" class="text-nowrap fw-bold">กรองตามผู้ใช้:</label>
                        <select name="user_id" id="user_id" class="form-select" onchange="this.form.submit()">
                            <option value="">{{ __('-- แสดงทุกคน (All Users) --') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 150px;">{{ __('เวลา (Time)') }}</th>
                            <th style="width: 200px;">{{ __('ผู้ดำเนินการ (User)') }}</th>
                            <th style="width: 120px;">{{ __('การกระทำ') }}</th>
                            <th>{{ __('รายละเอียด (Description)') }}</th>
                            <th>{{ __('ข้อมูลการเปลี่ยนแปลง (Changes)') }}</th>
                            <th style="width: 150px;">{{ __('IP / Device') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-muted font-monospace">
                                    {{ $log->created_at->format('H:i:s') }}
                                </td>
                                <td>
                                    @if($log->{{ __('user)') }}<div class="fw-bold">{{ $log->user->name }}</div>
                                        <div class="small text-muted">{{ $log->user->email }}</div>
                                        <span class="badge bg-secondary rounded-pill">{{ $log->user->roles->pluck('name')->first() ?? 'N/A' }}</span>
                                    @else
                                        <span class="text-muted fst-italic">{{ __('System / Guest') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $actionColor = match($log->action) {
                                            'create' => 'success',
                                            'update' => 'primary',
                                            'delete', 'force_delete' => 'danger',
                                            'restore' => 'info',
                                            'login' => 'success',
                                            'logout' => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $actionColor }} text-uppercase">
                                        {{ \App\Helpers\ActivityLogHelper::formatAction($log->action) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $log->description }}</div>
                                    <div class="small text-muted">ID: {{ $log->subject_id }} ({{ \App\Helpers\ActivityLogHelper::formatModel($log->subject_type) }})</div>
                                </td>
                                <td>
                                    @if($log->properties && (isset($log->properties['old']) || isset($log->properties['attributes'])))
                                        <button class="btn btn-sm btn-outline-secondary btn-view-changes"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#changesModal-{{ $log->id }}">
                                            <i class="bi bi-eye"></i>{{ __('ดูการเปลี่ยนแปลง') }}</button>

                                        <!-- Modal for Changes -->
                                        <div class="modal fade" id="changesModal-{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">รายละเอียดการเปลี่ยนแปลง (ID: {{ $log->id }})</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        @php
                                                            $readableChanges = \App\Helpers\ActivityLogHelper::generateReadableChanges($log);
                                                        @endphp

                                                        @if(count($readableChanges) > 0)
                                                            <ul class="list-group">
                                                                @foreach($readableChanges as $change)
                                                                    <li class="list-group-item">{!! $change !!}</li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <p class="text-muted">{{ __('ไม่มีข้อมูลการเปลี่ยนแปลงที่สำคัญ หรือไม่สามารถระบุได้') }}</p>

                                                            <div class="accordion mt-3" id="accordionRaw{{ $log->id }}">
                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header" id="headingRaw{{ $log->id }}">
                                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRaw{{ $log->id }}" aria-expanded="false" aria-controls="collapseRaw{{ $log->id }}">{{ __('ดูข้อมูลดิบ (Raw Data)') }}</button>
                                                                    </h2>
                                                                    <div id="collapseRaw{{ $log->id }}" class="accordion-collapse collapse" aria-labelledby="headingRaw{{ $log->id }}" data-bs-parent="#accordionRaw{{ $log->id }}">
                                                                        <div class="accordion-body">
                                                                            @if(isset($log->properties['old']))
                                                                                <h6 class="fw-bold text-danger">ค่าเดิม (Old):</h6>
                                                                                <pre class="bg-light p-2 rounded">@json($log->properties['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                                                            @endif

                                                                            @if(isset($log->properties['attributes']))
                                                                                <h6 class="fw-bold text-success mt-3">ค่าใหม่ (New):</h6>
                                                                                <pre class="bg-light p-2 rounded">@json($log->properties['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    <div>{{ $log->ip_address }}</div>
                                    <div class="text-truncate" style="max-width: 150px;" title="{{ $log->user_agent }}">
                                        {{ $log->user_agent }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">{{ __('ไม่พบรายการบันทึกสำหรับตัวกรองนี้') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
