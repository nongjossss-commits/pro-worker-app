{{-- resources/views/sales/partials/_create_modal.blade.php --}}
<div class="modal fade" id="createSalesLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('sales.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>สร้างรายการเสนอราคาใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="createTypeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="existing-tab" data-bs-toggle="tab" data-bs-target="#existing-tab-pane" type="button" role="tab" aria-controls="existing-tab-pane" aria-selected="true">
                                <i class="bi bi-search me-1"></i> เลือกลูกค้าเดิม
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="new-tab" data-bs-toggle="tab" data-bs-target="#new-tab-pane" type="button" role="tab" aria-controls="new-tab-pane" aria-selected="false">
                                <i class="bi bi-person-plus me-1"></i> เพิ่มลูกค้าใหม่ (ชั่วคราว)
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="createTypeTabsContent">
                        {{-- Existing Employer Tab --}}
                        <div class="tab-pane fade show active" id="existing-tab-pane" role="tabpanel" aria-labelledby="existing-tab" tabindex="0">
                            <div class="mb-3">
                                <label for="employer_id" class="form-label">ค้นหาและเลือกลูกค้า <span class="text-danger">*</span></label>
                                <select class="form-select select2-employer" name="employer_id" id="employer_id" style="width: 100%;">
                                    <option value="">-- เลือกลูกค้าที่มีในระบบ --</option>
                                    @foreach($employers as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->employerNameTh }} ({{ $emp->employerTaxId ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- New Employer Tab --}}
                        <div class="tab-pane fade" id="new-tab-pane" role="tabpanel" aria-labelledby="new-tab" tabindex="0">
                            <div class="alert alert-info py-2">
                                <i class="bi bi-info-circle me-2"></i> ข้อมูลนี้จะยังไม่ถูกบันทึกลงฐานข้อมูลจริง จนกว่าจะมีการคอนเฟิร์มส่งต่องาน
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อลูกค้า (ไทย) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="employerNameTh" id="employerNameTh_new">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อลูกค้า (อังกฤษ)</label>
                                    <input type="text" class="form-control" name="employerNameEn">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">เบอร์โทรศัพท์</label>
                                    <input type="text" class="form-control" name="employerPhone">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">เลขเสียภาษี/บัตรประชาชน</label>
                                    <input type="text" class="form-control" name="employerTaxId">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">ชื่อผู้ติดต่อ / เจ้าของงาน</label>
                                    <input type="text" class="form-control" name="jobOwner">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">โรงพยาบาลประกันสังคม</label>
                                    <input type="text" class="form-control" name="socialSecurityHospital">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">อีเมล</label>
                                    <input type="text" class="form-control" name="employerEmail">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">รหัสผ่านอีเมล</label>
                                    <input type="text" class="form-control" name="employerPassword">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">รหัส RE Outsource</label>
                                    <input type="text" class="form-control" name="outsource_re_code">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">รหัสผ่าน Outsource</label>
                                    <input type="text" class="form-control" name="outsource_password">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">ประเภทธุรกิจ (ไทย)</label>
                                    <input type="text" class="form-control" name="businessType">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ประเภทธุรกิจ (อังกฤษ)</label>
                                    <input type="text" class="form-control" name="businessTypeEn">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">ชื่อผู้มีอำนาจลงนาม 1 (ไทย)</label>
                                    <input type="text" class="form-control" name="signerNameTh">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อผู้มีอำนาจลงนาม 1 (อังกฤษ)</label>
                                    <input type="text" class="form-control" name="signerNameEn">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อผู้มีอำนาจลงนาม 2 (ไทย)</label>
                                    <input type="text" class="form-control" name="signer_2_name_th">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อผู้มีอำนาจลงนาม 2 (อังกฤษ)</label>
                                    <input type="text" class="form-control" name="signer_2_name_en">
                                </div>
                                <div class="col-12">
                                    <hr class="my-4">
                                    <h6 class="fw-bold"><i class="bi bi-folder-fill me-2"></i>เอกสารแนบ (ถ้ามี)</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">1. หนังสือรับรองบริษัท / บัตรประชาชน</label>
                                    <input type="file" class="form-control" name="employer_doc_company" accept=".pdf,.doc,.docx,.jpg,.png">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">1.1 วันหมดอายุ</label>
                                    <input type="date" class="form-control" name="employer_doc_company_expiry">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">2. สัญญาเช่าบ้าน / ทะเบียนบ้าน</label>
                                    <input type="file" class="form-control" name="employer_doc_lease" accept=".pdf,.doc,.docx,.jpg,.png">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">3. สัญญาก่อสร้าง / แผนที่</label>
                                    <input type="file" class="form-control" name="employer_doc_construction" accept=".pdf,.doc,.docx,.jpg,.png">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label class="form-label">4. เอกสารอื่นๆ 1</label>
                                    <input type="file" class="form-control" name="employer_doc_other_1" accept=".pdf,.doc,.docx,.jpg,.png">
                                    <input type="text" class="form-control form-control-sm mt-1" name="employer_doc_other_1_desc" placeholder="ระบุรายละเอียดเอกสาร">
                                </div>
                                <div class="col-md-6 mt-3">
                                    <label class="form-label">5. เอกสารอื่นๆ 2</label>
                                    <input type="file" class="form-control" name="employer_doc_other_2" accept=".pdf,.doc,.docx,.jpg,.png">
                                    <input type="text" class="form-control form-control-sm mt-1" name="employer_doc_other_2_desc" placeholder="ระบุรายละเอียดเอกสาร">
                                </div>
                                <div class="col-md-6 mt-3 mb-4">
                                    <label class="form-label">6. เอกสารอื่นๆ 3</label>
                                    <input type="file" class="form-control" name="employer_doc_other_3" accept=".pdf,.doc,.docx,.jpg,.png">
                                    <input type="text" class="form-control form-control-sm mt-1" name="employer_doc_other_3_desc" placeholder="ระบุรายละเอียดเอกสาร">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="submitCreateLead"><i class="bi bi-save me-1"></i> บันทึกและสร้างใบเสนอราคา</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
{{-- Select2 for employer search --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Select2 if jQuery is loaded
        if (typeof jQuery !== 'undefined') {
            $('.select2-employer').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#createSalesLeadModal')
            });
        }

        // Logic to clear inputs based on active tab to pass validation
        const form = document.querySelector('#createSalesLeadModal form');
        const employerSelect = document.getElementById('employer_id');
        const employerNameInput = document.getElementById('employerNameTh_new');

        form.addEventListener('submit', function(e) {
            const isNewTabActive = document.getElementById('new-tab').classList.contains('active');

            if (isNewTabActive) {
                if(!employerNameInput.value) {
                    e.preventDefault();
                    alert('กรุณากรอกชื่อลูกค้า (ไทย)');
                    return;
                }
                if(employerSelect) employerSelect.value = ''; // Clear select
            } else {
                if(!employerSelect || !employerSelect.value) {
                    e.preventDefault();
                    alert('กรุณาเลือกลูกค้าเดิม');
                    return;
                }
                if(employerNameInput) employerNameInput.value = ''; // Clear new input
            }
        });

        // Tab change listener to clear fields
        document.getElementById('new-tab').addEventListener('shown.bs.tab', function () {
            if(typeof jQuery !== 'undefined') {
                $('#employer_id').val(null).trigger('change');
            }
        });
        document.getElementById('existing-tab').addEventListener('shown.bs.tab', function () {
            document.getElementById('employerNameTh_new').value = '';
        });
    });
</script>
@endpush