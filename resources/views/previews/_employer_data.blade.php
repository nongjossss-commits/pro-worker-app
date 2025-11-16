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
            <p class="form-control-plaintext">{{ $employer->regDate ? $employer->regDate->format('d/m/Y') : 'N/A' }}</p>
        </div>
    </div>

{{-- NEW: Address Information --}}
<div class="row mb-3">
    <h5>ข้อมูลที่อยู่ (Addresses)</h5>
    <hr>
    <div class="col-md-6">
        <label class="form-label fw-bold">ที่อยู่ตามทะเบียน</label>
        @php $registeredAddresses = $employer->addresses->where('type', 'registered'); @endphp
        @forelse($registeredAddresses as $address)
            {{-- Assuming 'full_address_string' accessor exists as implied by edit forms --}}
            <p class="form-control-plaintext py-0">{{ $address->full_address_string ?? 'N/A' }}</p>
        @empty
            <p class="form-control-plaintext py-0">N/A</p>
        @endforelse
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold">ที่อยู่สถานที่ทำงาน</label>
        @php $workplaceAddresses = $employer->addresses->where('type', 'workplace'); @endphp
        @forelse($workplaceAddresses as $address)
            <p class="form-control-plaintext py-0">{{ $address->full_address_string ?? 'N/A' }}</p>
        @empty
            <p class="form-control-plaintext py-0">N/A</p>
        @endforelse
    </div>
</div>

{{-- NEW: File Attachments --}}
<div class="row mb-3">
    <h5>ไฟล์แนบ (File Attachments)</h5>
    <hr>
    <div class="col-12">
        @if($employer->media->isEmpty())
            <p class="text-muted">N/A</p>
        @else
            <ul class="list-unstyled">
                @foreach($employer->media as $media)
                    <li>
                        <a href="{{ $media->getUrl() }}" target="_blank">{{ $media->file_name }}</a> ({{ $media->human_readable_size }})
                    </li>
                @endforeach
            </ul>
        @endif
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
