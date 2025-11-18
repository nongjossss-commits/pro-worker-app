<div class="container-fluid">
 {{-- Header: Photo & Name --}}
 <div class="row mb-4 align-items-center">
 <div class="col-md-3 text-center">
 <div class="mb-2">
 @if($employee->employeePhoto && Storage::disk('public')->exists($employee->employeePhoto))
 <img src="{{ Storage::url($employee->employeePhoto) }}" alt="Profile" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
 @else
 <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; font-size: 3rem;">
 {{ mb_substr($employee->employeeNameTh ?? 'U', 0, 1) }}
 </div>
 @endif
 </div>
 <h5 class="fw-bold text-primary mb-0">{{ $employee->english_prefix ?? '' }} {{ $employee->employeeNameTh ?? '-' }}</h5>
 <small class="text-muted">{{ $employee->employeeNameEn ?? '-' }}</small>
 <div class="mt-2">
 <span class="badge bg-{{ $employee->status == 1 ? 'success' : 'secondary' }}">
 {{ $employee->status == 1 ? 'Active' : 'Inactive' }}
 </span>
 </div>
 </div>
 <div class="col-md-9">
 <div class="row g-3">
 <div class="col-md-4">
 <label class="fw-bold text-muted small">ชื่อเล่น</label>
 <div class="border-bottom pb-1">{{ $employee->employeeNickname ?? '-' }}</div>
 </div>
 <div class="col-md-4">
 <label class="fw-bold text-muted small">เพศ</label>
 <div class="border-bottom pb-1">{{ $employee->employeeGender ?? '-' }}</div>
 </div>
 <div class="col-md-4">
 <label class="fw-bold text-muted small">สัญชาติ</label>
 <div class="border-bottom pb-1">{{ $employee->employeeNationality ?? '-' }}</div>
 </div>
 <div class="col-md-4">
 <label class="fw-bold text-muted small">วันเกิด (อายุ)</label>
 <div class="border-bottom pb-1">
 {{ $employee->employeeDob ? \Carbon\Carbon::parse($employee->employeeDob)->format('d/m/Y') : '-' }} ({{ $employee->employeeAge ?? 0 }} ปี)
 </div>
 </div>
 <div class="col-md-4">
 <label class="fw-bold text-muted small">เบอร์โทรศัพท์</label>
 <div class="border-bottom pb-1">{{ $employee->employeePhone ?? '-' }}</div>
 </div>
 <div class="col-md-12">
 <div class="alert alert-light border mt-2 mb-0 p-2">
 <div class="row">
 <div class="col-md-6">
 <strong><i class="bi bi-envelope"></i> Email:</strong> {{ $employee->email ?? '-' }}
 </div>
 <div class="col-md-6">
 <strong><i class="bi bi-key"></i> Password:</strong> <span class="text-danger">{{ $employee->password ?? '-' }}</span>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>
 </div>

 <hr>

 {{-- Section 2: Work & Documents --}}
 <h6 class="text-secondary fw-bold"><i class="bi bi-briefcase"></i> ข้อมูลงานและเอกสารประจำตัว</h6>
 <div class="row g-3 mb-4">
 <div class="col-md-6">
 <div class="card h-100 border-light bg-light">
 <div class="card-body p-3">
 <strong>ตำแหน่งงาน:</strong> {{ $employee->job_position ?? '-' }}<br>
 <strong>ลักษณะงาน:</strong> {{ $employee->job_description ?? '-' }}
 </div>
 </div>
 </div>

 {{-- Passport --}}
 <div class="col-md-6">
 <div class="card h-100">
 <div class="card-header py-1 small fw-bold bg-white">1. พาสปอร์ต (Passport)</div>
 <div class="card-body p-2 small">
 <div class="d-flex justify-content-between"><span>เลขที่:</span> <strong>{{ $employee->employeePassport ?? '-' }}</strong></div>
 <div class="d-flex justify-content-between"><span>วันออก:</span> <span>{{ $employee->passport_issue_date ? \Carbon\Carbon::parse($employee->passport_issue_date)->format('d/m/Y') : '-' }}</span></div>
 <div class="d-flex justify-content-between"><span>หมดอายุ:</span> <span class="text-danger">{{ $employee->passportExpiryDate ? \Carbon\Carbon::parse($employee->passportExpiryDate)->format('d/m/Y') : '-' }}</span></div>
 @if($employee->passport_file)
 <a href="{{ Storage::url($employee->passport_file) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100 mt-2"><i class="bi bi-eye"></i> ดูไฟล์แนบ</a>
 @endif
 </div>
 </div>
 </div>

 {{-- Visa --}}
 <div class="col-md-4">
 <div class="card h-100">
 <div class="card-header py-1 small fw-bold bg-white">2. วีซ่า (Visa)</div>
 <div class="card-body p-2 small">
 <div>ประเภท: <strong>{{ $employee->visa_type ?? '-' }}</strong></div>
 <div>หมดอายุ: {{ $employee->visaExpiryDate ? \Carbon\Carbon::parse($employee->visaExpiryDate)->format('d/m/Y') : '-' }}</div>
 @if($employee->visa_file)
 <a href="{{ Storage::url($employee->visa_file) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100 mt-2"><i class="bi bi-eye"></i> ดูไฟล์</a>
 @endif
 </div>
 </div>
 </div>

 {{-- Work Permit --}}
 <div class="col-md-4">
 <div class="card h-100">
 <div class="card-header py-1 small fw-bold bg-white">3. ใบอนุญาตทำงาน (Work Permit)</div>
 <div class="card-body p-2 small">
 <div>เลขที่: <strong>{{ $employee->employeeWorkPermit ?? '-' }}</strong></div>
 <div>หมดอายุ: {{ $employee->workPermitExpiryDate ? \Carbon\Carbon::parse($employee->workPermitExpiryDate)->format('d/m/Y') : '-' }}</div>
 @if($employee->work_permit_file)
 <a href="{{ Storage::url($employee->work_permit_file) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100 mt-2"><i class="bi bi-eye"></i> ดูไฟล์</a>
 @endif
 </div>
 </div>
 </div>

 {{-- Pink Card --}}
 <div class="col-md-4">
 <div class="card h-100">
 <div class="card-header py-1 small fw-bold bg-white">4. บัตรชมพู & 90 วัน</div>
 <div class="card-body p-2 small">
 <div>เลขที่: <strong>{{ $employee->pinkCardNo ?? '-' }}</strong></div>
 <div>90วัน ครบ: {{ $employee->ninetyDayReportDate ? \Carbon\Carbon::parse($employee->ninetyDayReportDate)->format('d/m/Y') : '-' }}</div>
 @if($employee->pink_card_file)
 <a href="{{ Storage::url($employee->pink_card_file) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100 mt-2"><i class="bi bi-eye"></i> ดูไฟล์</a>
 @endif
 </div>
 </div>
 </div>
 </div>

 <hr>

 {{-- Section 3: Insurance --}}
 <h6 class="text-info fw-bold"><i class="bi bi-shield-check"></i> ข้อมูลประกัน ({{ $employee->insurance_type ?? 'ไม่มีข้อมูล' }})</h6>
 <div class="card mb-4 border-info">
 <div class="card-body">
 <div class="row">
 @if($employee->insurance_type == 'ประกันสังคม')
 <div class="col-md-6"><strong>โรงพยาบาล:</strong> {{ $employee->hospital_name ?? '-' }}</div>
 <div class="col-md-6 text-end">
 @if($employee->social_security_file)
 <a href="{{ Storage::url($employee->social_security_file) }}" target="_blank" class="btn btn-sm btn-info text-white"><i class="bi bi-file-earmark-medical"></i> ไฟล์ประกันสังคม</a>
 @endif
 </div>
 @elseif(in_array($employee->insurance_type, ['ประกันโรงพยาบาล', 'ประกันเอกชน']))
 <div class="col-md-4"><strong>บริษัท/รพ.:</strong> {{ $employee->insurance_company ?? $employee->hospital_name ?? '-' }}</div>
 <div class="col-md-4"><strong>วันหมดอายุ:</strong> {{ $employee->insurance_expiry_date ? \Carbon\Carbon::parse($employee->insurance_expiry_date)->format('d/m/Y') : '-' }}</div>
 <div class="col-md-4 text-end">
 @if($employee->insurance_file)
 <a href="{{ Storage::url($employee->insurance_file) }}" target="_blank" class="btn btn-sm btn-info text-white"><i class="bi bi-file-earmark-medical"></i> ไฟล์กรมธรรม์</a>
 @endif
 </div>
 @else
 <div class="col-12 text-muted">- ไม่ระบุรายละเอียด -</div>
 @endif
 </div>
 </div>
 </div>

 {{-- Section 4: Other Attachments Loop --}}
 <h6 class="text-secondary fw-bold"><i class="bi bi-paperclip"></i> เอกสารอื่นๆ (ไฟล์ที่ 5-12)</h6>
 <ul class="list-group mb-3">
 @php
 $hasOtherFiles = false;
 @endphp
 @for ($i = 5; $i <= 12; $i++)
 @php
 $fileField = 'file_' . $i;
 @endphp
 @if($employee->$fileField)
 @php
 $hasOtherFiles = true;
 @endphp
 <li class="list-group-item d-flex justify-content-between align-items-center p-2">
 <span><i class="bi bi-file-earmark"></i> เอกสารแนบตำแหน่งที่ {{ $i }}</span>
 <a href="{{ Storage::url($employee->$fileField) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Download</a>
 </li>
 @endif
 @endfor

 @if(!$hasOtherFiles)
 <li class="list-group-item text-center text-muted p-2"><small>ไม่มีเอกสารเพิ่มเติม</small></li>
 @endif
 </ul>
</div>