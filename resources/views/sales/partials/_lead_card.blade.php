{{-- resources/views/sales/partials/_lead_card.blade.php --}}
<div class="card lead-card mb-3 border border-1 border-secondary shadow-sm" data-id="{{ $lead->id }}" data-status="{{ $lead->status }}">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="card-title fw-bold text-dark mb-0">
                {{ $lead->employerNameTh }}
                @if($lead->employer_id)
                    <span class="badge bg-info text-dark ms-1" title="ลูกค้าเดิมจากระบบ">
                        <i class="bi bi-person-check-fill"></i>
                    </span>
                @else
                    <span class="badge bg-warning text-dark ms-1" title="ลูกค้าใหม่ชั่วคราว">
                        <i class="bi bi-person-plus-fill"></i>
                    </span>
                @endif
            </h6>

            <div class="dropdown">
                <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    @if(!$lead->employer_id)
                        <li>
                            <a class="dropdown-item text-primary" href="#" data-bs-toggle="modal" data-bs-target="#editEmployerModal-{{ $lead->id }}">
                                <i class="bi bi-pencil me-2"></i>แก้ไขข้อมูลลูกค้า
                            </a>
                        </li>
                    @endif
                    <li><a class="dropdown-item text-danger" href="#" onclick="if(confirm('ย้ายรายการนี้ลงประวัติ/ถังขยะ ใช่หรือไม่?')) { document.getElementById('delete-lead-{{ $lead->id }}').submit(); }"><i class="bi bi-trash me-2"></i>ทิ้ง/ยกเลิก</a></li>
                </ul>
                <form id="delete-lead-{{ $lead->id }}" action="{{ route('sales.destroy', $lead->id) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>

        <p class="card-text text-muted small mb-2"><i class="bi bi-telephone me-1"></i> {{ $lead->employerPhone ?? 'ไม่ได้ระบุ' }}</p>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="badge bg-light text-dark border"><i class="bi bi-people me-1"></i>ลูกจ้าง {{ $lead->employees->count() }} คน</span>

            {{-- Action buttons based on status --}}
            @if($lead->status === 'confirmed')
                <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#transitionModal-{{ $lead->id }}">
                    <i class="bi bi-send-check me-1"></i> ส่งต่องาน
                </button>
            @else
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="changeStatusExplicit({{ $lead->id }}, '{{ $nextStatus }}')">
                    @if($nextStatus == 'deciding')
                        <i class="bi bi-arrow-right-circle me-1"></i> รอตัดสินใจ
                    @else
                        <i class="bi bi-check-circle me-1"></i> คอนเฟิร์ม
                    @endif
                </button>
            @endif
        </div>

        <div class="d-grid gap-2 mt-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#manageEmployeesModal-{{ $lead->id }}">
                <i class="bi bi-person-lines-fill me-1"></i> จัดการลูกจ้าง
            </button>
            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#quotationModal-{{ $lead->id }}">
                <i class="bi bi-file-earmark-text me-1"></i> ใบเสนอราคา / การเงิน
            </button>
        </div>
    </div>
</div>