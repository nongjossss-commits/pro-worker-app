<div class="modal fade" id="editEmployerModal-{{ $lead->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('sales.update', $lead->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขข้อมูลลูกค้าใหม่ (ชั่วคราว)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อลูกค้า (ไทย) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="employerNameTh" value="{{ $lead->employerNameTh }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ชื่อลูกค้า (อังกฤษ)</label>
                            <input type="text" class="form-control" name="employerNameEn" value="{{ $lead->employerNameEn }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" class="form-control" name="employerPhone" value="{{ $lead->employerPhone }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เลขเสียภาษี/บัตรประชาชน</label>
                            <input type="text" class="form-control" name="employerTaxId" value="{{ $lead->employerTaxId }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">ชื่อผู้ติดต่อ / เจ้าของงาน</label>
                            <input type="text" class="form-control" name="jobOwner" value="{{ $lead->jobOwner }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">โรงพยาบาลประกันสังคม</label>
                            <input type="text" class="form-control" name="socialSecurityHospital" value="{{ $lead->socialSecurityHospital }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">อีเมล</label>
                            <input type="text" class="form-control" name="employerEmail" value="{{ $lead->employerEmail }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รหัสผ่านอีเมล</label>
                            <input type="text" class="form-control" name="employerPassword" value="{{ $lead->employerPassword }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รหัส RE Outsource</label>
                            <input type="text" class="form-control" name="outsource_re_code" value="{{ $lead->outsource_re_code }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รหัสผ่าน Outsource</label>
                            <input type="text" class="form-control" name="outsource_password" value="{{ $lead->outsource_password }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">ประเภทธุรกิจ (ไทย)</label>
                            <input type="text" class="form-control" name="businessType" value="{{ $lead->businessType }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ประเภทธุรกิจ (อังกฤษ)</label>
                            <input type="text" class="form-control" name="businessTypeEn" value="{{ $lead->businessTypeEn }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">ชื่อผู้มีอำนาจลงนาม 1 (ไทย)</label>
                            <input type="text" class="form-control" name="signerNameTh" value="{{ $lead->signerNameTh }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ชื่อผู้มีอำนาจลงนาม 1 (อังกฤษ)</label>
                            <input type="text" class="form-control" name="signerNameEn" value="{{ $lead->signerNameEn }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ชื่อผู้มีอำนาจลงนาม 2 (ไทย)</label>
                            <input type="text" class="form-control" name="signer_2_name_th" value="{{ $lead->signer_2_name_th }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ชื่อผู้มีอำนาจลงนาม 2 (อังกฤษ)</label>
                            <input type="text" class="form-control" name="signer_2_name_en" value="{{ $lead->signer_2_name_en }}">
                        </div>
                        <div class="col-12">
                            <hr class="my-4">
                            <h6 class="fw-bold"><i class="bi bi-folder-fill me-2"></i>อัปเดตเอกสารแนบ</h6>
                            <small class="text-muted d-block mb-3">หากไม่ต้องการเปลี่ยนเอกสาร ไม่ต้องเลือกไฟล์ใหม่</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">1. หนังสือรับรองบริษัท / บัตรประชาชน</label>
                            @if($lead->employer_doc_company)
                                <div class="mb-1"><a href="{{ asset('storage/'.$lead->employer_doc_company) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employer_doc_company" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">1.1 วันหมดอายุ</label>
                            <input type="date" class="form-control" name="employer_doc_company_expiry" value="{{ $lead->employer_doc_company_expiry }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">2. สัญญาเช่าบ้าน / ทะเบียนบ้าน</label>
                            @if($lead->employer_doc_lease)
                                <div class="mb-1"><a href="{{ asset('storage/'.$lead->employer_doc_lease) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employer_doc_lease" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">3. สัญญาก่อสร้าง / แผนที่</label>
                            @if($lead->employer_doc_construction)
                                <div class="mb-1"><a href="{{ asset('storage/'.$lead->employer_doc_construction) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employer_doc_construction" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>

                        <div class="col-md-6 mt-3">
                            <label class="form-label">4. เอกสารอื่นๆ 1</label>
                            @if($lead->employer_doc_other_1)
                                <div class="mb-1"><a href="{{ asset('storage/'.$lead->employer_doc_other_1) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employer_doc_other_1" accept=".pdf,.doc,.docx,.jpg,.png">
                            <input type="text" class="form-control form-control-sm mt-1" name="employer_doc_other_1_desc" value="{{ $lead->employer_doc_other_1_desc }}" placeholder="ระบุรายละเอียดเอกสาร">
                        </div>
                        <div class="col-md-6 mt-3">
                            <label class="form-label">5. เอกสารอื่นๆ 2</label>
                            @if($lead->employer_doc_other_2)
                                <div class="mb-1"><a href="{{ asset('storage/'.$lead->employer_doc_other_2) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employer_doc_other_2" accept=".pdf,.doc,.docx,.jpg,.png">
                            <input type="text" class="form-control form-control-sm mt-1" name="employer_doc_other_2_desc" value="{{ $lead->employer_doc_other_2_desc }}" placeholder="ระบุรายละเอียดเอกสาร">
                        </div>
                        <div class="col-md-6 mt-3 mb-4">
                            <label class="form-label">6. เอกสารอื่นๆ 3</label>
                            @if($lead->employer_doc_other_3)
                                <div class="mb-1"><a href="{{ asset('storage/'.$lead->employer_doc_other_3) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employer_doc_other_3" accept=".pdf,.doc,.docx,.jpg,.png">
                            <input type="text" class="form-control form-control-sm mt-1" name="employer_doc_other_3_desc" value="{{ $lead->employer_doc_other_3_desc }}" placeholder="ระบุรายละเอียดเอกสาร">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>
