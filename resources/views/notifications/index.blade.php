@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')

@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('notifications.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="ค้นหา..." value="{{ request('search') }}" style="width: 200px;">

                <select name="nationality" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">-- ทุกสัญชาติ --</option>
                    <option value="เมียนมา" @selected(request('nationality') == 'เมียนมา')>เมียนมา</option>
                    <option value="ลาว" @selected(request('nationality') == 'ลาว')>ลาว</option>
                    <option value="กัมพูชา" @selected(request('nationality') == 'กัมพูชา')>กัมพูชา</option>
                    <option value="เวียดนาม" @selected(request('nationality') == 'เวียดนาม')>เวียดนาม</option>
                </select>

                <select name="mou_type" class="form-select form-select-sm" style="width: 200px;">
                    <option value="">-- ทุกประเภท มติ. --</option>
                    <option value="MOU" @selected(request('mou_type') == 'MOU')>MOU</option>
                    <option value="มติต่ออายุในประเทศ" @selected(request('mou_type') == 'มติต่ออายุในประเทศ')>มติต่ออายุในประเทศ</option>
                    <option value="มติขึ้นทะเบียน" @selected(request('mou_type') == 'มติขึ้นทะเบียน')>มติขึ้นทะเบียน</option>
                    <option value="อื่นๆ" @selected(request('mou_type') == 'อื่นๆ')>อื่นๆ</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                <a href="{{ route('notifications.index') }}" class="btn btn-secondary btn-sm">ล้าง</a>

                <div class="btn-group btn-group-sm ms-md-auto">
                    <input type="radio" class="btn-check" name="view" id="view-card" value="card" onchange="this.form.submit()" @checked(request('view', 'card') === 'card')>
                    <label class="btn btn-outline-primary" for="view-card"><i class="bi bi-grid-3x3-gap-fill"></i></label>

                    <input type="radio" class="btn-check" name="view" id="view-table" value="table" onchange="this.form.submit()" @checked(request('view') === 'table')>
                    <label class="btn btn-outline-primary" for="view-table"><i class="bi bi-table"></i></label>
                </div>

                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected(request('per_page', $perPageOptions[0]) == $option)>แสดง {{ $option }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>



    <div class="tab-content pt-4" id="notificationTabContent">
        <div class="tab-pane fade show active" id="all-notifications" role="tabpanel">
            @if($currentView === 'card')
                {{-- Card View --}}
                @forelse($paginatedNotifications as $notification)
                    @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                @empty
                    <p class="text-center text-muted">ไม่พบการแจ้งเตือนที่ตรงกับเงื่อนไข</p>
                @endforelse
            @else
                {{-- Table View --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">พนักงาน</th>
                                <th scope="col">นายจ้าง</th>
                                <th scope="col">ประเภทการแจ้งเตือน</th>
                                <th scope="col">วันที่ครบกำหนด</th>
                                <th scope="col">สถานะ</th>
                                <th scope="col" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paginatedNotifications as $notification)
                                @php
                                    $employee = $notification->employee;
                                    $employer = $employee?->employer;
                                    $daysRemaining = $notification->days_remaining;
                                    $dueDate = \Carbon\Carbon::parse($notification->due_date);
                                    \Carbon\Carbon::setLocale('th');

                                    $rowClass = '';
                                    if ($daysRemaining < 0) {
                                        $rowClass = 'table-dark';
                                    } elseif ($daysRemaining <= $notification->danger_threshold) {
                                        $rowClass = 'table-danger';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>{{ $loop->iteration + ($paginatedNotifications->currentPage() - 1) * $paginatedNotifications->perPage() }}</td>
                                    <td>
                                        @if($employee)
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}"
                                                 class="employee-photo-thumb"
                                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 1rem;"
                                                 alt="Photo">
                                            <div>
                                                <div class="fw-bold">{{ $employee->employeeNameEn ?? 'N/A' }}</div>
                                                <div class="text-muted small">{{ $employee->employeeNameTh ?? '' }}</div>
                                            </div>
                                        </div>
                                        @else
                                        <span class="text-danger">Employee not found</span>
                                        @endif
                                    </td>
                                    <td>{{ $employer->employerNameTh ?? 'N/A' }}</td>
                                    <td>
                                        {{ $notificationTypeNames[$notification->type] ?? ucfirst(str_replace('_', ' ', $notification->type)) }}
                                    </td>
                                    <td>{{ $dueDate->translatedFormat('d F Y') }}</td>
                                    <td>
                                        <span class="badge {{ $daysRemaining < 0 ? 'bg-dark' : 'bg-secondary' }}">
                                            {{ $daysRemaining < 0 ? 'เลยกำหนด ' . abs($daysRemaining) . ' วัน' : 'เหลือ ' . $daysRemaining . ' วัน' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('notifications.viewEmployee', ['notificationId' => $notification->id]) }}" class="btn btn-info" title="ดูข้อมูล">
                                                <i class="bi bi-search"></i>
                                            </a>
                                            <button type="button" class="btn btn-success renew-btn" title="ต่ออายุ" data-bs-toggle="modal" data-bs-target="#renewNotificationModal" data-notification-id="{{ $notification->id }}">
                                                <i class="bi bi-calendar-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-warning" title="ยกเลิกการต่ออายุ" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">ไม่พบการแจ้งเตือนที่ตรงกับเงื่อนไข</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-4">
                {{ $paginatedNotifications->links() }}
            </div>
        </div>

        <div class="mt-5">
            <h3 class="mb-4">รายการที่ถูกยกเลิก</h3>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>พนักงาน</th>
                                    <th>นายจ้าง</th>
                                    <th>ประเภทการแจ้งเตือน</th>
                                    <th>วันที่ยกเลิก</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cancelledNotifications as $notification)
                                    <tr>
                                        <td>
                                            @if($notification->employee)
                                                {{ $notification->employee->employeeNameTh ?? 'N/A' }}
                                                <div class="small text-muted">{{ $notification->employee->employeeNameEn ?? '' }}</div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($notification->employee && $notification->employee->employer)
                                                {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $notificationTypeNames[$notification->type] ?? ucfirst(str_replace('_', ' ', $notification->type)) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($notification->cancelled_at ?? $notification->updated_at)->translatedFormat('d F Y H:i') }}</td>
                                        <td class="text-center">
                                            <form action="{{ route('notifications.restore', $notification) }}" method="POST" class="d-inline-block" onsubmit="return confirm('คุณต้องการนำรายการนี้กลับมาใช่หรือไม่?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success" title="นำกลับมา">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('notifications.forceDelete', $notification) }}" method="POST" class="d-inline-block" onsubmit="return confirm('คุณต้องการลบรายการนี้อย่างถาวรใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="ลบถาวร">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">ไม่มีรายการที่ถูกยกเลิก</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($cancelledNotifications->hasPages())
                        <div class="mt-4">
                            {{ $cancelledNotifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Renew Notification Modal --}}
    <div class="modal fade" id="renewNotificationModal" tabindex="-1" aria-labelledby="renewNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renewNotificationModalLabel">ต่ออายุการแจ้งเตือน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="renewNotificationForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_due_date" class="form-label">วันที่ครบกำหนดใหม่</label>
                            <input type="date" class="form-control" id="new_due_date" name="new_due_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel Notification Modal --}}
    <div class="modal fade" id="cancelNotificationModal" tabindex="-1" aria-labelledby="cancelNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelNotificationModalLabel">ยืนยันการยกเลิก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="cancelNotificationForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <p>คุณต้องการยกเลิกการแจ้งเตือนนี้ใช่หรือไม่?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ไม่</button>
                        <button type="submit" class="btn btn-warning">ใช่, ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handle Renew Modal
    const renewModal = document.getElementById('renewNotificationModal');
    if (renewModal) {
        const renewForm = document.getElementById('renewNotificationForm');
        const renewUrlTemplate = '{{ route("notifications.renew", ["notification" => "REPLACE_ID"]) }}';

        renewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const notificationId = button.getAttribute('data-notification-id');
            renewForm.action = renewUrlTemplate.replace('REPLACE_ID', notificationId);
        });
    }

    // Handle Cancel Modal
    const cancelModal = document.getElementById('cancelNotificationModal');
    if (cancelModal) {
        const cancelForm = document.getElementById('cancelNotificationForm');
        const cancelUrlTemplate = '{{ route("notifications.cancel", ["notification" => "REPLACE_ID"]) }}';

        cancelModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const notificationId = button.getAttribute('data-notification-id');
            cancelForm.action = cancelUrlTemplate.replace('REPLACE_ID', notificationId);
        });
    }
});
</script>
@endpush
