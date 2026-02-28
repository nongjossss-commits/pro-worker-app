{{--
    This view is intentionally simplified to display data.
    It does not include @extends, @section, or any form elements.
    It is loaded via AJAX into a modal.
--}}

<div class="content-section">
    <h5>ข้อมูลนายจ้าง</h5>
    <hr>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ชื่อนายจ้าง (ไทย)</label>
            <p class="form-control-plaintext">{{ $employer->employerNameTh ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">ชื่อนายจ้าง (อังกฤษ)</label>
            <p class="form-control-plaintext">{{ $employer->employerNameEn ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">รหัสนายจ้าง</label>
            <p class="form-control-plaintext">{{ $employer->employerId ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">เจ้าของงาน</label>
            {{-- Handle Relationship --}}
            <p class="form-control-plaintext">{{ $employer->jobOwner?->name ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ผู้ดูแล (Admin)</label>
            <p class="form-control-plaintext">{{ $employer->assignedStaff?->name ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">เลขประจำตัวนายจ้าง</label>
            <p class="form-control-plaintext">{{ $employer->employerTaxId ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ประเภทกิจการ (ไทย)</label>
            <p class="form-control-plaintext">{{ $employer->businessType ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">ประเภทกิจการ (อังกฤษ)</label>
            <p class="form-control-plaintext">{{ $employer->businessTypeEn ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- START: เพิ่มฟิลด์ที่ขาดไป --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">อีเมลนายจ้าง</label>
            <p class="form-control-plaintext">{{ $employer->employerEmail ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">รหัสสำหรับอีเมล</label>
            <p class="form-control-plaintext text-break">{{ $employer->employerPassword ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">RE Code</label>
            <p class="form-control-plaintext">{{ $employer->outsource_re_code ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">รหัสสำหรับเข้าระบบ Outsource</label>
            <p class="form-control-plaintext text-break">{{ $employer->outsource_password ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
            <p class="form-control-plaintext">{{ $employer->employerPhone ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">โรงพยาบาลประกันสังคม</label>
            <p class="form-control-plaintext">{{ $employer->socialSecurityHospital ?? 'N/A' }}</p>
        </div>
    </div>
    {{-- END: เพิ่มฟิลด์ที่ขาดไป --}}

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ผู้มีอำนาจลงนาม 1 (ไทย)</label>
            <p class="form-control-plaintext">{{ $employer->signerNameTh ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">ผู้มีอำนาจลงนาม 1 (อังกฤษ)</label>
            <p class="form-control-plaintext">{{ $employer->signerNameEn ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ผู้มีอำนาจลงนาม 2 (ไทย)</label>
            <p class="form-control-plaintext">{{ $employer->signerNameTh2 ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">ผู้มีอำนาจลงนาม 2 (อังกฤษ)</label>
            <p class="form-control-plaintext">{{ $employer->signerNameEn2 ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ทุนจดทะเบียน</label>
            <p class="form-control-plaintext">{{ $employer->regCapital ? number_format($employer->regCapital, 2) : 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">จดทะเบียนวันที่</label>
            <p class="form-control-plaintext">{{ $employer->regDate ? $employer->regDate->format('d/m/Y') : 'N/A' }}</p>
        </div>
         <div class="col-md-6">
            <label class="form-label fw-bold">ค่าแรงขั้นต่ำ</label>
            <p class="form-control-plaintext">{{ $employer->minimum_wage ? number_format($employer->minimum_wage, 2) : 'N/A' }}</p>
        </div>
    </div>

    <hr>
    <h5>เอกสารแนบของนายจ้าง (อัปเดต)</h5>
    {{-- START: แสดงเอกสารชุดใหม่ 6 ช่อง --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">1. หนังสือรับรองบริษัท / บัตรประชาชน</label>
            @if($employer->employer_doc_company)
                <p class="form-control-plaintext">
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_company) }}', 'ดูหนังสือรับรองบริษัท')" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_company']) }}" download class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">วันหมดอายุ (หนังสือรับรอง/บัตร)</label>
            <p class="form-control-plaintext">{{ $employer->employer_doc_company_expiry ? \Carbon\Carbon::parse($employer->employer_doc_company_expiry)->format('d/m/Y') : 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">2. สัญญาเช่าบ้าน / ทะเบียนบ้าน</label>
             @if($employer->employer_doc_lease)
                <p class="form-control-plaintext">
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_lease) }}', 'ดูสัญญาเช่า')" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_lease']) }}" download class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">3. สัญญาก่อสร้าง / แผนที่</label>
            @if($employer->employer_doc_construction)
                <p class="form-control-plaintext">
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_construction) }}', 'ดูใบอนุญาตก่อสร้าง')" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_construction']) }}" download class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label fw-bold">4. เอกสารอื่นๆ 1{{ $employer->employer_doc_other_1_desc ? ' - ' . $employer->employer_doc_other_1_desc : '' }}</label>
            @if($employer->employer_doc_other_1)
                <p class="form-control-plaintext">
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_other_1) }}', '{{ $employer->employer_doc_other_1_desc ?? 'ดูเอกสารอื่นๆ 1' }}')" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_other_1']) }}" download class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">5. เอกสารอื่นๆ 2{{ $employer->employer_doc_other_2_desc ? ' - ' . $employer->employer_doc_other_2_desc : '' }}</label>
            @if($employer->employer_doc_other_2)
                <p class="form-control-plaintext">
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_other_2) }}', '{{ $employer->employer_doc_other_2_desc ?? 'ดูเอกสารอื่นๆ 2' }}')" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_other_2']) }}" download class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">6. เอกสารอื่นๆ 3{{ $employer->employer_doc_other_3_desc ? ' - ' . $employer->employer_doc_other_3_desc : '' }}</label>
            @if($employer->employer_doc_other_3)
                <p class="form-control-plaintext">
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_other_3) }}', '{{ $employer->employer_doc_other_3_desc ?? 'ดูเอกสารอื่นๆ 3' }}')" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_other_3']) }}" download class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
    </div>
    {{-- END: แสดงเอกสารชุดใหม่ 6 ช่อง --}}
</div>

{{-- Registered Address Section --}}
<div class="content-section mt-4">
    <h5 class="mb-3">ที่อยู่ตามทะเบียน</h5>
    <div class="vstack gap-3">
        @forelse ($employer->addresses->where('type', 'registered') as $address)
            <div class="address-card p-3 border rounded">
                <p class="mb-1"><strong>TH:</strong> {{ $address->full_address_th }}</p>
                <p class="mb-0 text-muted"><strong>EN:</strong> {{ $address->full_address_en }}</p>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีที่อยู่</p>
        @endforelse
    </div>
</div>

{{-- Workplace Address Section --}}
<div class="content-section mt-4">
    <h5 class="mb-3">ที่อยู่สถานที่ทำงาน</h5>
    <div class="vstack gap-3">
        @forelse ($employer->addresses->where('type', 'workplace') as $address)
             <div class="address-card p-3 border rounded">
                <p class="mb-1"><strong>TH:</strong> {{ $address->full_address_th }}</p>
                <p class="mb-0 text-muted"><strong>EN:</strong> {{ $address->full_address_en }}</p>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีที่อยู่</p>
        @endforelse
    </div>
</div>

{{-- Stats Summary (from $stats) --}}
<div class="content-section mt-4">
    <h5 class="mb-3">สรุปข้อมูลลูกจ้าง</h5>
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card p-3 border rounded bg-light">
                <h6 class="text-muted">ลูกจ้างทั้งหมด</h6>
                <p class="fs-4 fw-bold mb-0">{{ $stats->total ?? 0 }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3 border rounded bg-light">
                <h6 class="text-muted">เพศชาย</h6>
                <p class="fs-4 fw-bold mb-0">{{ $stats->male ?? 0 }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3 border rounded bg-light">
                <h6 class="text-muted">เพศหญิง</h6>
                <p class="fs-4 fw-bold mb-0">{{ $stats->female ?? 0 }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card p-3 border rounded bg-light">
                <h6 class="text-muted">ลูกจ้างที่ถูกแจ้งออก</h6>
                <p class="fs-4 fw-bold mb-0">{{ $stats->terminated_total ?? 0 }}</p>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <h6>สรุปตามสัญชาติ (เฉพาะลูกจ้างที่ยังไม่แจ้งออก):</h6>
        <ul class="list-unstyled">
        @forelse($stats->breakdown as $nationality => $data)
        <li>
            <strong>{{ $nationality ?: 'ไม่ระบุสัญชาติ' }}:</strong> {{ $data->total_count }} คน (ชาย: {{ $data->male_count }} / หญิง: {{ $data->female_count }})
        </li>
        @empty
        <li>ไม่พบข้อมูลการแบ่งสัญชาติ</li>
        @endforelse
        </ul>
    </div>
</div>

