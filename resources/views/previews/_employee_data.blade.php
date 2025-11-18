<div class="content-section">
 {{-- 1. Personal Information --}}
 <h5 class="text-primary"><i class="bi bi-person-badge"></i> ข้อมูลส่วนตัว</h5>
 <hr>
 <div class="row g-3 mb-4">
 <div class="col-md-3">
 <label class="fw-bold text-muted">ชื่อ-นามสกุล (ไทย)</label>
 <p>{{ $employee->english_prefix ?? '' }} {{ $employee->employeeNameTh ?? '-' }}</p>
 </div>
 <div class="col-md-3">
 <label class="fw-bold text-muted">Name (English)</label>
 <p>{{ $employee->employeeNameEn ?? '-' }}</p>
 </div>
 <div class="col-md-3">
 <label class="fw-bold text-muted">ชื่อเล่น</label>
 <p>{{ $employee->employeeNickname ?? '-' }}</p>
 </div>
 <div class="col-md-3">
 <label class="fw-bold text-muted">สัญชาติ</label>
 <p>{{ $employee->employeeNationality ?? '-' }}</p>
 </div>
 <div class="col-md-3">
 <label class="fw-bold text-muted">เบอร์โทรศัพท์</label>
 <p>{{ $employee->employeePhone ?? '-' }}</p>
 </div>
 <div class="col-md-3">
 <label class="fw-bold text-muted">Email (Login)</label>
 <p>{{ $employee->email ?? '-' }}</p>
 </div>
 <div class="col-md-3">
 <label class="fw-bold text-muted">Password</label>
 <p class="text-danger">{{ $employee->password ?? '-' }}</p>
 </div>
 <div class="col-md-3">
 <label class="fw-bold text-muted">สถานะ</label>
 <span class="badge bg-{{ $employee->status == 1 ? 'success' : 'secondary' }}">
 {{ $employee->status == 1 ? 'Active' : 'Inactive' }}
 </span>
 </div>
 </div>
 {{-- 2. Documents & Files --}}
 <h5 class="text-success"><i class="bi bi-folder2-open"></i> เอกสารและไฟล์แนบ</h5>
 <hr>
 {{-- Passport --}}
 <div class="card mb-3 border-light bg-light">
 <div class="card-body">
 <h6 class="card-title fw-bold text-dark">1. ข้อมูลพาสปอร์ต (Passport)</h6>
 <div class="row">
 <div class="col-md-3"><small class="text-muted">เลขที่:</small> {{ $employee->employeePassport ?? '-' }}</div>
 <div class="col-md-3"><small class="text-muted">วันออก:</small> {{ $employee->passport_issue_date ? \Carbon\Carbon::parse($employee->passport_issue_date)->format('d/m/Y') : '-' }}</div>
 <div class="col-md-3"><small class="text-muted">วันหมดอายุ:</small> {{ $employee->passportExpiryDate ? \Carbon\Carbon::parse($employee->passportExpiryDate)->format('d/m/Y') : '-' }}</div>
 <div class="col-md-3">
 @if($employee->passport_file)
 <a href="{{ Storage::url($employee->passport_file) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> ดูไฟล์</a>
 @else
 <span class="text-muted">-</span>
 @endif
 </div>
 </div>
 </div>
 </div>
 {{-- Visa --}}
 <div class="card mb-3 border-light bg-light">
 <div class="card-body">
 <h6 class="card-title fw-bold text-dark">2. ข้อมูลวีซ่า (Visa)</h6>
 <div class="row">
 <div class="col-md-3"><small class="text-muted">ประเภท:</small> {{ $employee->visa_type ?? '-' }}</div>
 <div class="col-md-3"><small class="text-muted">วันหมดอายุ:</small> {{ $employee->visaExpiryDate ? \Carbon\Carbon::parse($employee->visaExpiryDate)->format('d/m/Y') : '-' }}</div>
 <div class="col-md-6 text-end">
 @if($employee->visa_file)
 <a href="{{ Storage::url($employee->visa_file) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> ดูไฟล์</a>
 @else
 <span class="text-muted">-</span>
 @endif
 </div>
 </div>
 </div>
 </div>
 {{-- Work Permit --}}
 <div class="card mb-3 border-light bg-light">
 <div class="card-body">
 <h6 class="card-title fw-bold text-dark">3. ใบอนุญาตทำงาน (Work Permit)</h6>
 <div class="row">
 <div class="col-md-3"><small class="text-muted">เลขที่:</small> {{ $employee->employeeWorkPermit ?? '-' }}</div>
 <div class="col-md-3"><small class="text-muted">วันหมดอายุ:</small> {{ $employee->workPermitExpiryDate ? \Carbon\Carbon::parse($employee->workPermitExpiryDate)->format('d/m/Y') : '-' }}</div>
 <div class="col-md-6 text-end">
 @if($employee->work_permit_file)
 <a href="{{ Storage::url($employee->work_permit_file) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> ดูไฟล์</a>
 @else
 <span class="text-muted">-</span>
 @endif
 </div>
 </div>
 </div>
 </div>
 {{-- Pink Card & 90 Day --}}
 <div class="row mb-3">
 <div class="col-md-6">
 <div class="card border-light bg-light h-100">
 <div class="card-body">
 <h6 class="card-title fw-bold">4. บัตรชมพู</h6>
 <p class="mb-1">เลขที่: {{ $employee->pinkCardNo ?? '-' }}</p>
 @if($employee->pink_card_file)
 <a href="{{ Storage::url($employee->pink_card_file) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> ดูไฟล์</a>
 @endif
 </div>
 </div>
 </div>
 <div class="col-md-6">
 <div class="card border-light bg-light h-100">
 <div class="card-body">
 <h6 class="card-title fw-bold">รายงานตัว 90 วัน</h6>
 <p class="mb-0">วันครบกำหนด: {{ $employee->ninetyDayReportDate ? \Carbon\Carbon::parse($employee->ninetyDayReportDate)->format('d/m/Y') : '-' }}</p>
 </div>
 </div>
 </div>
 </div>
 {{-- Insurance --}}
 <h5 class="text-info mt-4"><i class="bi bi-shield-check"></i> ข้อมูลประกัน</h5>
 <hr>
 <div class="row g-3">
 <div class="col-md-4">
 <label class="fw-bold text-muted">ประเภทประกัน</label>
 <p>{{ $employee->insurance_type ?? '-' }}</p>
 </div>
 @if($employee->insurance_type == 'ประกันสังคม')
 <div class="col-md-4">
 <label class="fw-bold text-muted">โรงพยาบาล</label>
 <p>{{ $employee->hospital_name ?? '-' }}</p>
 </div>
 <div class="col-md-4">
 <label class="fw-bold text-muted">ไฟล์ประกันสังคม</label>
 @if($employee->social_security_file)
 <br><a href="{{ Storage::url($employee->social_security_file) }}" target="_blank" class="btn btn-sm btn-outline-info">ดูไฟล์</a>
 @else
 <p>-</p>
 @endif
 </div>
 @elseif($employee->insurance_type == 'ประกันโรงพยาบาล' || $employee->insurance_type == 'ประกันเอกชน')
 <div class="col-md-4">
 <label class="fw-bold text-muted">บริษัท/รพ.</label>
 <p>{{ $employee->insurance_company ?? $employee->hospital_name ?? '-' }}</p>
 </div>
 <div class="col-md-4">
 <label class="fw-bold text-muted">วันหมดอายุ</label>
 <p>{{ $employee->insurance_expiry_date ? \Carbon\Carbon::parse($employee->insurance_expiry_date)->format('d/m/Y') : '-' }}</p>
 </div>
 <div class="col-md-12">
 <label class="fw-bold text-muted">ไฟล์กรมธรรม์</label>
 @if($employee->insurance_file)
 <br><a href="{{ Storage::url($employee->insurance_file) }}" target="_blank" class="btn btn-sm btn-outline-info">ดูไฟล์</a>
 @else
 <p>-</p>
 @endif
 </div>
 @endif
 </div>
 {{-- Other Files Loop --}}
 @php
 $hasOtherFiles = false;
 for($i=5; $i<=12; $i++) {
 if($employee->{'file_'.$i}) {
 $hasOtherFiles = true;
 break;
 }
 }
 @endphp
 @if($hasOtherFiles)
 <h5 class="text-warning mt-4"><i class="bi bi-paperclip"></i> เอกสารอื่นๆ</h5>
 <hr>
 <ul class="list-group">
 @for ($i = 5; $i <= 12; $i++)
 @if($employee->{'file_'.$i})
 <li class="list-group-item d-flex justify-content-between align-items-center">
 <span>เอกสารแนบที่ {{ $i }}</span>
 <a href="{{ Storage::url($employee->{'file_'.$i}) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
 <i class="bi bi-download"></i> ดาวน์โหลด
 </a>
 </li>
 @endif
 @endfor
 </ul>
 @endif
</div>