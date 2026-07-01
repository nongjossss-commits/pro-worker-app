{{--
    Employer preview — loaded via AJAX into the universal preview modal.

    Layout parallels the Employee redesign:
      * Portrait phone: 2-column grid of compact fields
      * Landscape phone / tablet: 3-4 columns
      * PC / desktop: darker + larger labels since horizontal space is abundant

    All CSS is scoped under .empr-preview so it doesn't clash with the
    employee preview styles (.emp-preview).
--}}
<style>
    .empr-preview .preview-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #f97316;
        border-left: 4px solid #f97316;
        padding-left: 8px;
        margin: 0 0 10px;
    }
    .empr-preview .preview-hr {
        border-top: 1px dashed #e5e7eb;
        margin: 12px 0 14px;
    }
    .empr-preview .preview-field {
        margin-bottom: 8px;
        min-width: 0;
    }
    .empr-preview .preview-label {
        display: block;
        font-size: 0.7rem;
        color: #6b7280;
        line-height: 1.15;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .empr-preview .preview-value {
        font-size: 0.88rem;
        font-weight: 500;
        line-height: 1.25;
        color: #111827;
        word-break: break-word;
        min-height: 1.25rem;
    }
    .empr-preview .preview-value.text-muted-empty {
        color: #9ca3af;
        font-weight: 400;
    }
    .empr-preview .preview-attach-btn {
        padding: 2px 8px;
        font-size: 0.75rem;
    }
    .empr-preview .preview-section-header {
        position: sticky;
        top: 0;
        background: #ffffff;
        padding: 6px 0 4px;
        z-index: 2;
    }
    .empr-preview .empr-address-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
        background: #f9fafb;
        font-size: 0.85rem;
    }
    .empr-preview .empr-stat-card {
        border-radius: 10px;
        padding: 10px 12px;
        background: #f3f4f6;
    }
    .empr-preview .empr-stat-card h6 {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .empr-preview .empr-stat-card p {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: #111827;
    }

    /* Tablet+ */
    @media (min-width: 768px) {
        .empr-preview .preview-label { font-size: 0.8rem; }
        .empr-preview .preview-value { font-size: 1rem; }
        .empr-preview .preview-section-title { font-size: 1.05rem; }
        .empr-preview .preview-field { margin-bottom: 10px; }
        .empr-preview .preview-attach-btn { padding: 4px 10px; font-size: 0.85rem; }
        .empr-preview .empr-address-card { font-size: 0.95rem; }
        .empr-preview .empr-stat-card h6 { font-size: 0.85rem; }
        .empr-preview .empr-stat-card p { font-size: 1.7rem; }
    }

    /* PC / desktop: darker + bigger labels since space isn't a constraint. */
    @media (min-width: 992px) {
        .empr-preview .preview-label {
            font-size: 0.95rem;
            color: #374151;
            font-weight: 500;
            line-height: 1.3;
            margin-bottom: 4px;
        }
        .empr-preview .preview-value {
            font-size: 1.15rem;
            font-weight: 600;
            line-height: 1.4;
        }
        .empr-preview .preview-section-title { font-size: 1.2rem; }
        .empr-preview .preview-field { margin-bottom: 16px; }
        .empr-preview .preview-attach-btn { padding: 5px 12px; font-size: 0.95rem; }
        .empr-preview .empr-stat-card { padding: 14px 16px; }
        .empr-preview .empr-stat-card p { font-size: 1.9rem; }
    }
    @media (min-width: 1200px) {
        .empr-preview .preview-label { font-size: 1rem; }
        .empr-preview .preview-value { font-size: 1.2rem; line-height: 1.45; }
        .empr-preview .preview-section-title { font-size: 1.25rem; }
        .empr-preview .preview-field { margin-bottom: 18px; }
    }
</style>

@php
    // Shared "N/A → dash" renderer — same idea as the employee preview so
    // truly-missing fields look softer than filled ones.
    $val = function ($v) {
        if ($v === null || $v === '' || $v === 'N/A') {
            return '<span class="preview-value text-muted-empty">-</span>';
        }
        return '<span class="preview-value">' . e($v) . '</span>';
    };
@endphp

<div class="empr-preview">

    {{-- ================== SECTION 1: Identity ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">ข้อมูลนายจ้าง</h6>
    </div>
    <div class="row g-2 mb-1">
        <div class="col-12 col-md-6 preview-field">
            <span class="preview-label">ชื่อนายจ้าง (ไทย)</span>
            {!! $val($employer->employerNameTh) !!}
        </div>
        <div class="col-12 col-md-6 preview-field">
            <span class="preview-label">ชื่อนายจ้าง (อังกฤษ)</span>
            {!! $val($employer->employerNameEn) !!}
        </div>
        @if($employer->name_suffix)
        <div class="col-12 col-md-6 preview-field">
            <span class="preview-label">ต่อท้ายชื่อ</span>
            {!! $val($employer->name_suffix) !!}
        </div>
        @endif
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">รหัสนายจ้าง</span>
            {!! $val($employer->employerId) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เจ้าของงาน</span>
            {!! $val($employer->jobOwner?->name) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ผู้ดูแล (Admin)</span>
            {!! $val($employer->assignedStaff?->name) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขประจำตัวนายจ้าง</span>
            {!! $val($employer->employerTaxId) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ประเภทกิจการ (ไทย)</span>
            {!! $val($employer->businessType) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ประเภทกิจการ (อังกฤษ)</span>
            {!! $val($employer->businessTypeEn) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ทุนจดทะเบียน</span>
            {!! $val($employer->regCapital ? number_format($employer->regCapital, 2) : null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">จดทะเบียนวันที่</span>
            {!! $val($employer->regDate ? $employer->regDate->format('d/m/Y') : null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ค่าแรงขั้นต่ำ</span>
            {!! $val($employer->minimum_wage ? number_format($employer->minimum_wage, 2) : null) !!}
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 2: Contact + Credentials ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">ติดต่อและระบบภายนอก</h6>
    </div>
    <div class="row g-2 mb-1">
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เบอร์โทร</span>
            {!! $val($employer->employerPhone) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">อีเมล</span>
            {!! $val($employer->employerEmail) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">รหัสอีเมล</span>
            {!! $val($employer->employerPassword) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">รพ. ประกันสังคม</span>
            {!! $val($employer->socialSecurityHospital) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">RE Code</span>
            {!! $val($employer->outsource_re_code) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">รหัส Outsource</span>
            {!! $val($employer->outsource_password) !!}
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 3: Signers ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">ผู้มีอำนาจลงนาม</h6>
    </div>
    <div class="row g-2 mb-1">
        <div class="col-6 col-md-3 preview-field">
            <span class="preview-label">ผู้ลงนาม 1 (ไทย)</span>
            {!! $val($employer->signerNameTh) !!}
        </div>
        <div class="col-6 col-md-3 preview-field">
            <span class="preview-label">ผู้ลงนาม 1 (อังกฤษ)</span>
            {!! $val($employer->signerNameEn) !!}
        </div>
        <div class="col-6 col-md-3 preview-field">
            <span class="preview-label">ผู้ลงนาม 2 (ไทย)</span>
            {!! $val($employer->signerNameTh2) !!}
        </div>
        <div class="col-6 col-md-3 preview-field">
            <span class="preview-label">ผู้ลงนาม 2 (อังกฤษ)</span>
            {!! $val($employer->signerNameEn2) !!}
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 4: Documents ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">เอกสารแนบของนายจ้าง</h6>
    </div>
    <div class="row g-2 mb-1">
        {{-- 1. Company doc + expiry --}}
        <div class="col-12 col-sm-6 col-md-4 preview-field">
            <span class="preview-label">1. หนังสือรับรอง / บัตรประชาชน</span>
            @if($employer->employer_doc_company)
                <div>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_company) }}', 'ดูหนังสือรับรองบริษัท')" class="btn btn-success preview-attach-btn text-white"><i class="bi bi-eye-fill"></i> ดู</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_company']) }}" download class="btn btn-danger preview-attach-btn text-white ms-1"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </div>
            @else
                <span class="preview-value text-muted-empty">ไม่มีเอกสาร</span>
            @endif
        </div>
        <div class="col-6 col-sm-6 col-md-4 preview-field">
            <span class="preview-label">วันหมดอายุ (หนังสือรับรอง)</span>
            {!! $val($employer->employer_doc_company_expiry ? \Carbon\Carbon::parse($employer->employer_doc_company_expiry)->format('d/m/Y') : null) !!}
        </div>
        {{-- 2. Lease --}}
        <div class="col-12 col-sm-6 col-md-4 preview-field">
            <span class="preview-label">2. สัญญาเช่า / ทะเบียนบ้าน</span>
            @if($employer->employer_doc_lease)
                <div>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_lease) }}', 'ดูสัญญาเช่า')" class="btn btn-success preview-attach-btn text-white"><i class="bi bi-eye-fill"></i> ดู</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_lease']) }}" download class="btn btn-danger preview-attach-btn text-white ms-1"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </div>
            @else
                <span class="preview-value text-muted-empty">ไม่มีเอกสาร</span>
            @endif
        </div>
        {{-- 3. Construction --}}
        <div class="col-12 col-sm-6 col-md-4 preview-field">
            <span class="preview-label">3. สัญญาก่อสร้าง / แผนที่</span>
            @if($employer->employer_doc_construction)
                <div>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->employer_doc_construction) }}', 'ดูใบอนุญาตก่อสร้าง')" class="btn btn-success preview-attach-btn text-white"><i class="bi bi-eye-fill"></i> ดู</a>
                    <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => 'employer_doc_construction']) }}" download class="btn btn-danger preview-attach-btn text-white ms-1"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </div>
            @else
                <span class="preview-value text-muted-empty">ไม่มีเอกสาร</span>
            @endif
        </div>
        {{-- 4-6. Other docs --}}
        @foreach ([1, 2, 3] as $i)
            @php
                $docField = "employer_doc_other_{$i}";
                $descField = "employer_doc_other_{$i}_desc";
                $labelSuffix = $employer->{$descField} ? ' - ' . $employer->{$descField} : '';
            @endphp
            <div class="col-12 col-sm-6 col-md-4 preview-field">
                <span class="preview-label" title="{{ (3 + $i) }}. เอกสารอื่นๆ {{ $i }}{{ $labelSuffix }}">
                    {{ (3 + $i) }}. เอกสารอื่นๆ {{ $i }}{{ $labelSuffix }}
                </span>
                @if($employer->{$docField})
                    <div>
                        <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->{$docField}) }}', '{{ $employer->{$descField} ?? 'ดูเอกสารอื่นๆ '.$i }}')" class="btn btn-success preview-attach-btn text-white"><i class="bi bi-eye-fill"></i> ดู</a>
                        <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => $docField]) }}" download class="btn btn-danger preview-attach-btn text-white ms-1"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                    </div>
                @else
                    <span class="preview-value text-muted-empty">ไม่มีเอกสาร</span>
                @endif
            </div>
        @endforeach
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 5: Addresses ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">ที่อยู่</h6>
    </div>
    <div class="row g-2 mb-1">
        <div class="col-12 col-md-6">
            <div class="preview-field">
                <span class="preview-label">ตามทะเบียน</span>
                <div class="vstack gap-2 mt-1">
                    @forelse($employer->addresses->where('type', 'registered') as $address)
                        <div class="empr-address-card">
                            <div><strong>TH:</strong> {{ $address->full_address_th }}</div>
                            <div class="text-muted"><strong>EN:</strong> {{ $address->full_address_en }}</div>
                        </div>
                    @empty
                        <span class="preview-value text-muted-empty">ยังไม่มีที่อยู่</span>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="preview-field">
                <span class="preview-label">สถานที่ทำงาน</span>
                <div class="vstack gap-2 mt-1">
                    @forelse($employer->addresses->where('type', 'workplace') as $address)
                        <div class="empr-address-card">
                            <div><strong>TH:</strong> {{ $address->full_address_th }}</div>
                            <div class="text-muted"><strong>EN:</strong> {{ $address->full_address_en }}</div>
                        </div>
                    @empty
                        <span class="preview-value text-muted-empty">ยังไม่มีที่อยู่</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 6: Employee Stats ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">สรุปข้อมูลลูกจ้าง</h6>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-6 col-md-3">
            <div class="empr-stat-card">
                <h6>ลูกจ้างทั้งหมด</h6>
                <p>{{ $stats->total ?? 0 }}</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="empr-stat-card">
                <h6>เพศชาย</h6>
                <p>{{ $stats->male ?? 0 }}</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="empr-stat-card">
                <h6>เพศหญิง</h6>
                <p>{{ $stats->female ?? 0 }}</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="empr-stat-card">
                <h6>แจ้งออกแล้ว</h6>
                <p>{{ $stats->terminated_total ?? 0 }}</p>
            </div>
        </div>
    </div>
    <div class="row g-2">
        <div class="col-12 preview-field">
            <span class="preview-label">สรุปตามสัญชาติ (เฉพาะลูกจ้างที่ยังไม่แจ้งออก)</span>
            @if(isset($stats->breakdown) && count($stats->breakdown))
                <ul class="list-unstyled mb-0 mt-1" style="font-size: 0.9rem;">
                    @foreach($stats->breakdown as $nationality => $data)
                        <li>
                            <strong>{{ $nationality ?: 'ไม่ระบุสัญชาติ' }}:</strong>
                            {{ $data->total_count }} คน
                            <span class="text-muted">(ชาย: {{ $data->male_count }} / หญิง: {{ $data->female_count }})</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <span class="preview-value text-muted-empty">ไม่พบข้อมูลการแบ่งสัญชาติ</span>
            @endif
        </div>
    </div>
</div>
