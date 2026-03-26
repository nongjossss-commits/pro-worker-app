<div class="modal fade" id="editEmployeeModal-{{ $emp->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('sales.employees.update', [$lead->id, $emp->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขข้อมูลลูกจ้างใหม่ (ชั่วคราว)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ-นามสกุล (ภาษาอังกฤษ) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="employeeNameEn" value="{{ $emp->employeeNameEn }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ-นามสกุล (ภาษาไทย)</label>
                            <input type="text" class="form-control" name="employeeNameTh" value="{{ $emp->employeeNameTh }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">เพศ</label>
                            <select class="form-select" name="employeeGender">
                                <option value="">ไม่ได้ระบุ</option>
                                <option value="Male" {{ $emp->employeeGender == 'Male' ? 'selected' : '' }}>ชาย (Male)</option>
                                <option value="Female" {{ $emp->employeeGender == 'Female' ? 'selected' : '' }}>หญิง (Female)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">เลขพาสปอร์ต</label>
                            <input type="text" class="form-control" name="employeePassport" value="{{ $emp->employeePassport }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">เลขใบอนุญาตทำงาน</label>
                            <input type="text" class="form-control" name="employeeWorkPermit" value="{{ $emp->employeeWorkPermit }}">
                        </div>
                        <div class="col-md-12 mt-4">
                            <hr>
                            <h6 class="fw-bold"><i class="bi bi-folder-fill me-2"></i>อัปเดตเอกสารแนบลูกจ้าง</h6>
                            <small class="text-muted d-block mb-3">หากไม่ต้องการเปลี่ยนเอกสาร ไม่ต้องเลือกไฟล์ใหม่</small>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">รูปถ่ายหน้าตรง</label>
                            @if($emp->photo_path)
                                <div class="mb-1"><a href="{{ asset('storage/'.$emp->photo_path) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">1. พาสปอร์ต (Passport)</label>
                            @if($emp->employee_doc_1)
                                <div class="mb-1"><a href="{{ asset('storage/'.$emp->employee_doc_1) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employee_doc_1" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">2. ใบอนุญาตทำงาน (Work Permit)</label>
                            @if($emp->employee_doc_2)
                                <div class="mb-1"><a href="{{ asset('storage/'.$emp->employee_doc_2) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employee_doc_2" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">3. วีซ่า (Visa)</label>
                            @if($emp->employee_doc_visa)
                                <div class="mb-1"><a href="{{ asset('storage/'.$emp->employee_doc_visa) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employee_doc_visa" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">4. บัตรชมพู (Pink Card)</label>
                            @if($emp->employee_doc_3)
                                <div class="mb-1"><a href="{{ asset('storage/'.$emp->employee_doc_3) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employee_doc_3" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">5. ทะเบียนบ้านลูกจ้าง (TR 38/1)</label>
                            @if($emp->employee_doc_4)
                                <div class="mb-1"><a href="{{ asset('storage/'.$emp->employee_doc_4) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                            @endif
                            <input type="file" class="form-control" name="employee_doc_4" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>

                        @for($i = 1; $i <= 3; $i++)
                            @php
                                $fieldName = "employee_doc_other_" . $i;
                                $descName = "other_doc_" . $i . "_desc";
                            @endphp
                            <div class="col-md-6 mt-3">
                                <label class="form-label">เอกสารอื่นๆ {{ $i }}</label>
                                @if($emp->$fieldName)
                                    <div class="mb-1"><a href="{{ asset('storage/'.$emp->$fieldName) }}" target="_blank"><i class="bi bi-file-earmark-check text-success"></i> ดูไฟล์เดิม</a></div>
                                @endif
                                <input type="file" class="form-control" name="{{ $fieldName }}" accept=".pdf,.doc,.docx,.jpg,.png">
                                <input type="text" class="form-control form-control-sm mt-1" name="{{ $descName }}" value="{{ $emp->$descName }}" placeholder="ระบุรายละเอียดเอกสาร">
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>
