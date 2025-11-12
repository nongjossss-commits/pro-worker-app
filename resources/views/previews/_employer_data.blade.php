@use \Carbon\Carbon;
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
            <label class="form-label fw-bold">เลขประจำตัวนายจ้าง</label>
            <p class="form-control-plaintext">{{ $employer->employerTaxId ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ผู้มีอำนาจลงนาม (ไทย)</label>
            <p class="form-control-plaintext">{{ $employer->signerNameTh ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">ผู้มีอำนาจลงนาม (อังกฤษ)</label>
            <p class="form-control-plaintext">{{ $employer->signerNameEn ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ประเภทกิจการ</label>
            <p class="form-control-plaintext">{{ $employer->businessType ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Type of Business</label>
            <p class="form-control-plaintext">{{ $employer->businessTypeEn ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ทุนจดทะเบียน</label>
            <p class="form-control-plaintext">{{ $employer->regCapital ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">จดทะเบียนวันที่</label>
            {{-- This will now work correctly because regDate is cast in the Model --}} <p class="form-control-plaintext">{{ $employer->regDate?->format('d/m/Y') ?? 'N/A' }}</p>
        </div>
    </div>

    <hr>
    <h5>เอกสารแนบของนายจ้าง</h5>
    <div class="row">
        <div class="col-md-4">
            <label class="form-label fw-bold">หนังสือรับรองบริษัท</label>
            @if($employer->document_company_registration)
                <p class="form-control-plaintext">
                    <a href="{{ Storage::url($employer->document_company_registration) }}" target="_blank"><i class="bi bi-file-earmark-text"></i> ดูเอกสาร</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">ภ.พ.20</label>
             @if($employer->document_vat_registration)
                <p class="form-control-plaintext">
                    <a href="{{ Storage::url($employer->document_vat_registration) }}" target="_blank"><i class="bi bi-file-earmark-text"></i> ดูเอกสาร</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">แผนที่</label>
            @if($employer->document_map)
                <p class="form-control-plaintext">
                    <a href="{{ Storage::url($employer->document_map) }}" target="_blank"><i class="bi bi-file-earmark-text"></i> ดูเอกสาร</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
    </div>
</div>

{{-- Registered Address Section --}}
<div class="content-section mt-4">
    <h5 class="mb-3">ที่อยู่ตามทะเบียน</h5>
    <div class="vstack gap-3">
        @forelse ($employer->addresses->where('type', 'registered') as $address)
            <div class="address-card">
                <p class="mb-0">
                    เลขที่ {{ $address->addrNo ?? '' }} หมู่ {{ $address->addrMoo ?? '' }} ซอย{{ $address->addrSoi ?? '' }} ถนน{{ $address->addrRoad ?? '' }}
                    แขวง/ตำบล {{ $address->addrSubDistrict ?? '' }} เขต/อำเภอ {{ $address->addrDistrict ?? '' }}
                    {{ $address->addrProvince ?? '' }} {{ $address->addrZipCode ?? '' }}
                </p>
                <p class="mb-0 text-muted small">
                    Addr: {{ $address->addrNoEn ?? '' }}, Moo: {{ $address->addrMooEn ?? '' }}, Soi: {{ $address->addrSoiEn ?? '' }}, Road: {{ $address->addrRoadEn ?? '' }},
                    {{ $address->addrSubDistrictEn ?? '' }}, {{ $address->addrDistrictEn ?? '' }},
                    {{ $address->addrProvinceEn ?? '' }} {{ $address->addrZipCodeEn ?? '' }}
                </p>
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
             <div class="address-card">
                <p class="mb-0">
                    เลขที่ {{ $address->addrNo ?? '' }} หมู่ {{ $address->addrMoo ?? '' }} ซอย{{ $address->addrSoi ?? '' }} ถนน{{ $address->addrRoad ?? '' }}
                    แขวง/ตำบล {{ $address->addrSubDistrict ?? '' }} เขต/อำเภอ {{ $address->addrDistrict ?? '' }}
                    {{ $address->addrProvince ?? '' }} {{ $address->addrZipCode ?? '' }}
                </p>
                <p class="mb-0 text-muted small">
                    Addr: {{ $address->addrNoEn ?? '' }}, Moo: {{ $address->addrMooEn ?? '' }}, Soi: {{ $address->addrSoiEn ?? '' }}, Road: {{ $address->addrRoadEn ?? '' }},
                    {{ $address->addrSubDistrictEn ?? '' }}, {{ $address->addrDistrictEn ?? '' }},
                    {{ $address->addrProvinceEn ?? '' }} {{ $address->addrZipCodeEn ?? '' }}
                </p>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีที่อยู่</p>
        @endforelse
    </div>
</div>

{{-- Blueprint: Add Stats Summary --}}
<hr class="my-4">
<div class="content-section">
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
                <h6 class="text-muted">มีบัตรชมพู</h6>
                <p class="fs-4 fw-bold mb-0">{{ $stats->with_pink_card ?? 0 }}</p>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <h6>สัญชาติ:</h6>
        <ul class="list-unstyled">
            @forelse($stats->by_nationality ?? [] as $nat)
                 <li>{{ $nat->employeeNationality }}: {{ $nat->total }} คน</li>
            @empty
                <li>ไม่พบข้อมูล</li>
            @endforelse
        </ul>
    </div>
</div>
