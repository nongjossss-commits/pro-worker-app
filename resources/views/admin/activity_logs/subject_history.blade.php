@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.activity-logs.index') }}">Activity Logs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">ประวัติ: {{ $subjectLabel }}</li>
                </ol>
            </nav>

            {{-- ข้อมูลสรุป Subject --}}
            <div class="card shadow-sm border-primary border-opacity-25 mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        @if($subjectType === 'App\\Models\\Employee')
                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center" style="width:56px; height:56px; font-size:1.5rem;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div>
                                <span class="badge bg-info mb-1">ลูกจ้าง (Employee)</span>
                                <h4 class="fw-bold mb-0">{{ $subject->employeeNameEn ?: '-' }}</h4>
                                <div class="text-muted">{{ $subject->employeeNameTh ?: '-' }}</div>
                                <div class="d-flex flex-wrap gap-2 mt-1 small text-muted">
                                    <span><strong>ID:</strong> {{ $subject->id }}</span>
                                    @if($subject->employeePassport)
                                        <span>| <strong>Passport:</strong> {{ $subject->employeePassport }}</span>
                                    @endif
                                    @if($subject->employee_reference_id)
                                        <span>| <strong>Ref:</strong> {{ $subject->employee_reference_id }}</span>
                                    @endif
                                    @if($subject->request_number)
                                        <span>| <strong>RA:</strong> {{ $subject->request_number }}</span>
                                    @endif
                                    @if($subject->employer)
                                        <span>| <i class="bi bi-building"></i> {{ $subject->employer->employerNameTh ?? $subject->employer->employerNameEn ?? '-' }}</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center" style="width:56px; height:56px; font-size:1.5rem;">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <span class="badge bg-warning text-dark mb-1">นายจ้าง (Employer)</span>
                                <h4 class="fw-bold mb-0">{{ $subject->employerNameTh ?: '-' }}</h4>
                                <div class="text-muted">{{ $subject->employerNameEn ?: '-' }}</div>
                                <div class="small text-muted mt-1"><strong>ID:</strong> {{ $subject->id }}</div>
                            </div>
                        @endif
                        <div class="ms-auto text-end">
                            <div class="display-6 fw-bold text-primary">{{ $logs->total() }}</div>
                            <div class="small text-muted">รายการทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ตัวกรองหมวดหมู่ --}}
            @php $currentCategory = $categoryFilter ?? ''; @endphp
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="{{ route('admin.activity-logs.subject-history', ['subject_type' => $subjectType, 'subject_id' => $subjectId, 'user_id' => $userId ?? '', 'action' => $actionFilter ?? '']) }}"
                   class="btn btn-sm {{ $currentCategory === '' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-list-ul me-1"></i> ประวัติทั้งหมด
                </a>
                <a href="{{ route('admin.activity-logs.subject-history', ['subject_type' => $subjectType, 'subject_id' => $subjectId, 'category' => 'changes', 'user_id' => $userId ?? '', 'action' => $actionFilter ?? '']) }}"
                   class="btn btn-sm {{ $currentCategory === 'changes' ? 'btn-info' : 'btn-outline-info' }}">
                    <i class="bi bi-pencil-square me-1"></i> แก้ไข/เปลี่ยนแปลง/ดาวน์โหลด
                </a>
                <a href="{{ route('admin.activity-logs.subject-history', ['subject_type' => $subjectType, 'subject_id' => $subjectId, 'category' => 'movement', 'user_id' => $userId ?? '', 'action' => $actionFilter ?? '']) }}"
                   class="btn btn-sm {{ $currentCategory === 'movement' ? 'btn-warning' : 'btn-outline-warning' }}">
                    <i class="bi bi-arrow-left-right me-1"></i> แจ้งออก/ย้ายนายจ้าง/ดำเนินการ
                </a>
            </div>

            {{-- ตัวกรองละเอียด --}}
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>ประวัติการดำเนินการ</h5>
                <form action="{{ route('admin.activity-logs.subject-history') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="subject_type" value="{{ $subjectType }}">
                    <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                    @if($currentCategory)
                        <input type="hidden" name="category" value="{{ $currentCategory }}">
                    @endif
                    <select name="user_id" class="form-select form-select-sm" style="width: auto; min-width: 180px;" onchange="this.form.submit()">
                        <option value="">-- ทุกผู้ใช้ --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ ($userId ?? '') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                    <select name="action" class="form-select form-select-sm" style="width: auto; min-width: 160px;" onchange="this.form.submit()">
                        <option value="">-- ทุกประเภท --</option>
                        @foreach($actions as $act)
                            <option value="{{ $act }}" {{ ($actionFilter ?? '') == $act ? 'selected' : '' }}>
                                {{ \App\Helpers\ActivityLogHelper::formatAction($act) }} ({{ $act }})
                            </option>
                        @endforeach
                    </select>
                    @if(($userId ?? '') || ($actionFilter ?? '') || $currentCategory)
                        <a href="{{ route('admin.activity-logs.subject-history', ['subject_type' => $subjectType, 'subject_id' => $subjectId]) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> ล้างทั้งหมด
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 180px;">วันที่ / เวลา</th>
                            <th style="width: 200px;">ผู้ดำเนินการ (User)</th>
                            <th style="width: 120px;">การกระทำ</th>
                            <th>รายละเอียด (Description)</th>
                            <th>ข้อมูลการเปลี่ยนแปลง (Changes)</th>
                            <th style="width: 130px;">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-muted font-monospace small">{{ $log->created_at->format('H:i:s') }}</div>
                                    <div class="text-muted small">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if($log->user)
                                        <div class="fw-bold">{{ $log->user->name }}</div>
                                        <div class="small text-muted">{{ $log->user->email }}</div>
                                        <span class="badge bg-secondary rounded-pill">{{ $log->user->roles->pluck('name')->first() ?? 'N/A' }}</span>
                                    @else
                                        <span class="text-muted fst-italic">System / Guest</span>
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
                                            'download' => 'warning',
                                            'export' => 'warning',
                                            'upload' => 'info',
                                            'print', 'generate_document' => 'dark',
                                            'bulk_action' => 'primary',
                                            'import' => 'info',
                                            'status_change' => 'primary',
                                            'terminate' => 'danger',
                                            'reinstate' => 'success',
                                            'transfer' => 'warning',
                                            'workflow_process' => 'info',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $actionColor }} text-uppercase">
                                        {{ \App\Helpers\ActivityLogHelper::formatAction($log->action) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $log->description }}</div>
                                    @if($log->subject_type)
                                        <div class="small text-muted">
                                            <span class="badge bg-light text-dark border me-1">ID: {{ $log->subject_id }}</span>
                                            {{ \App\Helpers\ActivityLogHelper::formatModel($log->subject_type) }}
                                        </div>
                                    @endif

                                    {{-- Special detail for transfer --}}
                                    @if($log->action === 'transfer' && $log->properties)
                                        <div class="mt-1 small">
                                            <span class="badge bg-secondary">จาก:</span> {{ $log->properties['old_employer_name'] ?? '-' }}
                                            <i class="bi bi-arrow-right mx-1"></i>
                                            <span class="badge bg-warning text-dark">ไป:</span> {{ $log->properties['new_employer_name'] ?? '-' }}
                                        </div>
                                    @endif

                                    {{-- Special detail for terminate --}}
                                    @if($log->action === 'terminate' && $log->properties)
                                        <div class="mt-1 small">
                                            <span class="badge bg-danger">นายจ้าง:</span> {{ $log->properties['employer_name'] ?? '-' }}
                                            @if($log->properties['termination_reason'] ?? null)
                                                | <strong>เหตุผล:</strong> {{ $log->properties['termination_reason'] }}
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Special detail for reinstate --}}
                                    @if($log->action === 'reinstate' && $log->properties)
                                        <div class="mt-1 small">
                                            <span class="badge bg-success">กลับเข้า:</span> {{ $log->properties['employer_name'] ?? '-' }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($log->properties && (isset($log->properties['old']) || isset($log->properties['attributes'])))
                                        <button class="btn btn-sm btn-outline-secondary btn-view-changes"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#changesModal-{{ $log->id }}">
                                            <i class="bi bi-eye"></i> ดูการเปลี่ยนแปลง
                                        </button>

                                        <div class="modal fade" id="changesModal-{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">รายละเอียดการเปลี่ยนแปลง — {{ $log->created_at->format('d/m/Y H:i:s') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3 small text-muted">
                                                            <strong>ผู้ดำเนินการ:</strong> {{ $log->user->name ?? 'System' }}
                                                            | <strong>IP:</strong> {{ $log->ip_address }}
                                                        </div>

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
                                                            <p class="text-muted">ไม่มีข้อมูลการเปลี่ยนแปลงที่สำคัญ หรือไม่สามารถระบุได้</p>

                                                            <div class="accordion mt-3" id="accordionRaw{{ $log->id }}">
                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header" id="headingRaw{{ $log->id }}">
                                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRaw{{ $log->id }}">
                                                                            ดูข้อมูลดิบ (Raw Data)
                                                                        </button>
                                                                    </h2>
                                                                    <div id="collapseRaw{{ $log->id }}" class="accordion-collapse collapse" data-bs-parent="#accordionRaw{{ $log->id }}">
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
                                    {{ $log->ip_address }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 opacity-25"></i>
                                    <p class="mt-2">ไม่พบรายการบันทึกสำหรับตัวกรองนี้</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
