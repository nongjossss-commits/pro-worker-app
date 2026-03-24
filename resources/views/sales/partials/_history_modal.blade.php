{{-- resources/views/sales/partials/_history_modal.blade.php --}}
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>ประวัติรายการเสนอราคา</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>ลูกค้า</th>
                                <th>จำนวนลูกจ้าง</th>
                                <th>สถานะ</th>
                                <th>วันที่จัดการล่าสุด</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $item->employerNameTh }}</span>
                                        <div class="text-muted small">
                                            @if($item->employer_id)
                                                <i class="bi bi-person-check-fill text-info" title="ลูกค้าเดิม"></i>
                                            @else
                                                <i class="bi bi-person-plus-fill text-warning" title="ลูกค้าชั่วคราว"></i>
                                            @endif
                                            {{ $item->employerTaxId ?? '-' }}
                                        </div>
                                    </td>
                                    <td>{{ $item->employees->count() }} คน</td>
                                    <td>
                                        @if($item->status == 'transitioned')
                                            <span class="badge bg-success">ส่งต่องานแล้ว</span>
                                            <div class="small text-muted mt-1"><i class="bi bi-arrow-right"></i> {{ $item->workflow_destination }}</div>
                                        @elseif($item->status == 'cancelled' || $item->trashed())
                                            <span class="badge bg-danger">ยกเลิก/ลบ</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->updated_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($item->trashed() || $item->status == 'cancelled')
                                            <form action="{{ route('sales.restore', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการกู้คืนรายการนี้ เพื่อกลับไปหน้า เสนอราคา หรือไม่?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> กู้คืน</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">ดำเนินการแล้ว</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">ไม่มีประวัติการจัดการ</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>
