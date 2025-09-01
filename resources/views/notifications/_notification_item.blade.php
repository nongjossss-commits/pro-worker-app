@props(['notification', 'label'])

<div class="card notification-item shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <div class="flex-shrink-0">
                {{-- Placeholder for employee image --}}
                <div class="d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #e9ecef; border-radius: 8px;">
                    <i class="bi bi-person fs-4 text-muted"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <h5 class="card-title mb-1">{{ $notification->employee->name_th ?? 'N/A' }} ({{ $notification->employee->name_en ?? 'N/A' }})</h5>
                <p class="card-text mb-1">
                    <span class="fw-bold">นายจ้าง:</span> {{ $notification->employee->employer->name ?? 'N/A' }}
                </p>
                <p class="card-text small text-muted">
                    <span class="fw-bold">{{ $label }} หมดอายุ:</span> {{ \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') }}
                </p>
            </div>
            <div class="text-end flex-shrink-0 ms-2">
                @php
                    $dueDate = \Carbon\Carbon::parse($notification->due_date)->startOfDay();
                    $daysRemaining = \Carbon\Carbon::now()->startOfDay()->diffInDays($dueDate, false);
                    $badgeClass = 'bg-secondary';
                    if ($daysRemaining < 0) {
                        $badgeClass = 'bg-black';
                    } elseif ($daysRemaining <= 30) {
                        $badgeClass = 'bg-danger';
                    } elseif ($daysRemaining <= 60) {
                        $badgeClass = 'bg-warning text-dark';
                    } elseif ($daysRemaining <= 90) {
                        $badgeClass = 'bg-info text-dark';
                    }
                @endphp
                <span class="badge {{ $badgeClass }} mb-2 d-block text-nowrap fs-6">
                    @if($daysRemaining >= 0)
                        เหลือ {{ $daysRemaining }} วัน
                    @else
                        หมดอายุแล้ว {{ abs($daysRemaining) }} วัน
                    @endif
                </span>
            </div>
        </div>
        @if($notification->status !== 'cancelled')
        <hr>
        <div class="d-flex justify-content-end gap-2">
            <a href="{{-- route('employees.show', $notification->employee->id) --}}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person-lines-fill me-1"></i> ดูข้อมูลพนักงาน
            </a>
            <button class="btn btn-sm btn-primary">
                <i class="bi bi-check2-circle me-1"></i> ต่ออายุ / ดำเนินการ
            </button>
            @if($notification->status === 'unread')
            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelRenewalModal-{{ $notification->id }}">
                <i class="bi bi-x-circle me-1"></i> ยกเลิกการต่ออายุ
            </button>
            @endif
        </div>
        @else
        <div class="alert alert-warning mt-3 mb-0">
            <p class="mb-1 fw-bold">ยกเลิกเมื่อ: {{ \Carbon\Carbon::parse($notification->cancelled_at)->format('d/m/Y H:i') }}</p>
            <p class="mb-0"><strong>เหตุผล:</strong> {{ $notification->cancellation_reason }}</p>
        </div>
        @endif
    </div>
</div>

<!-- Cancel Renewal Modal -->
@if($notification->status !== 'cancelled')
<div class="modal fade" id="cancelRenewalModal-{{ $notification->id }}" tabindex="-1" aria-labelledby="cancelRenewalModalLabel-{{ $notification->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelRenewalModalLabel-{{ $notification->id }}">ยืนยันการยกเลิก</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('notifications.cancel', $notification->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>โปรดระบุเหตุผลสำหรับการยกเลิกการแจ้งเตือนของ <strong>{{ $notification->employee->name_th }}</strong></p>
                    <div class="mb-3">
                        <label for="cancellation_reason-{{ $notification->id }}" class="form-label">เหตุผลการยกเลิก</label>
                        <textarea class="form-control" id="cancellation_reason-{{ $notification->id }}" name="cancellation_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" class="btn btn-danger">ยืนยันการยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
