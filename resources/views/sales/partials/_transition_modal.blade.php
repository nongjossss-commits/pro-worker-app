{{-- resources/views/sales/partials/_transition_modal.blade.php --}}
<div class="modal fade" id="transitionModal-{{ $lead->id }}" tabindex="-1" aria-labelledby="transitionModalLabel-{{ $lead->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('sales.transition', $lead->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="transitionModalLabel-{{ $lead->id }}"><i class="bi bi-send-check me-2"></i>ส่งต่องาน (Transition) - {{ $lead->employerNameTh }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> เมื่อกดยืนยัน ข้อมูลลูกค้าและลูกจ้างชั่วคราวทั้งหมด <strong>จะถูกบันทึกลงในฐานข้อมูลหลัก (Employers/Employees) โดยอัตโนมัติ</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">เลือกปลายทางของงาน <span class="text-danger">*</span></label>
                        <select name="destination" class="form-select select2-destination" style="width: 100%;" required>
                            <option value="">-- เลือกเมนูปลายทาง --</option>
                            <optgroup label="เมนู Production (มติ)">
                                <option value="production.registration">มติลงทะเบียน (Registration)</option>
                                <option value="production.renewal">มติต่ออายุ (Renewal)</option>
                            </optgroup>
                            <optgroup label="เมนู Workflow (แจ้งเข้า/ออก)">
                                <option value="workflow.notify_out">แจ้งออก</option>
                                <option value="workflow.mou_import">MOU นำเข้า</option>
                                <option value="workflow.mou_renewal">ต่ออายุ MOU</option>
                                <option value="workflow.change_employer">เปลี่ยนนายจ้าง</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="card bg-light border-0 shadow-sm mt-3">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-dark mb-2">สรุปข้อมูลที่จะถูกสร้างจริง:</h6>
                            <ul class="list-unstyled mb-0">
                                <li>
                                    <i class="bi bi-building me-2 text-primary"></i> ลูกค้า: <strong>{{ $lead->employerNameTh }}</strong>
                                    @if($lead->employer_id)
                                        <span class="badge bg-secondary ms-1">ลูกค้าเดิม</span>
                                    @else
                                        <span class="badge bg-danger ms-1">สร้างลูกค้าใหม่</span>
                                    @endif
                                </li>
                                <li class="mt-2">
                                    <i class="bi bi-people-fill me-2 text-success"></i> ลูกจ้างทั้งหมด <strong>{{ $lead->employees->count() }}</strong> คน
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="bi bi-check-circle me-1"></i> ยืนยันการส่งต่อข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery !== 'undefined') {
            // Need to apply to specific modal to avoid bugs when there are multiple modals in DOM
            $('.modal').on('shown.bs.modal', function () {
                $(this).find('.select2-destination').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $(this)
                });
            });
        }
    });
</script>
@endonce
@endpush