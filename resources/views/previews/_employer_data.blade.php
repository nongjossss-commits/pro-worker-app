{{-- resources/views/previews/_employer_data.blade.php --}}

<div class="container-fluid">
    {{-- Employer Information --}}
    <h5>ข้อมูลนายจ้าง</h5>
    <hr>
    <div class="row mb-2">
        <div class="col-md-6">
            <strong>ชื่อนายจ้าง (ไทย):</strong>
            <p class="form-control-plaintext">{{ displayValue($employer->employerNameTh) }}</p>
        </div>
        <div class="col-md-6">
            <strong>ชื่อนายจ้าง (อังกฤษ):</strong>
            <p class="form-control-plaintext">{{ displayValue($employer->employerNameEn) }}</p>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-6">
            <strong>รหัสนายจ้าง:</strong>
            <p class="form-control-plaintext">{{ displayValue($employer->employerId) }}</p>
        </div>
        <div class="col-md-6">
            <strong>เลขประจำตัวนายจ้าง:</strong>
            <p class="form-control-plaintext">{{ displayValue($employer->employerTaxId) }}</p>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-6">
            <strong>ผู้มีอำนาจลงนาม (ไทย):</strong>
            <p class="form-control-plaintext">{{ displayValue($employer->signerNameTh) }}</p>
        </div>
        <div class="col-md-6">
            <strong>ผู้มีอำนาจลงนาม (อังกฤษ):</strong>
            <p class="form-control-plaintext">{{ displayValue($employer->signerNameEn) }}</p>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-6">
            <strong>ประเภทกิจการ:</strong>
            <p class="form-control-plaintext">{{ displayValue($employer->businessType) }} / {{ displayValue($employer->businessTypeEn) }}</p>
        </div>
        <div class="col-md-3">
            <strong>ทุนจดทะเบียน:</strong>
            <p class="form-control-plaintext">{{ displayValue($employer->regCapital) }}</p>
        </div>
        <div class="col-md-3">
            <strong>จดทะเบียนวันที่:</strong>
            <p class="form-control-plaintext">{{ formatDate($employer->regDate) }}</p>
        </div>
    </div>

    <hr>
    <h5>เอกสารแนบของนายจ้าง</h5>
    <div class="row mb-3">
        @php
            $documents = [
                'document_company_registration' => 'หนังสือรับรองบริษัท',
                'document_vat_registration' => 'ภ.พ.20',
                'document_map' => 'แผนที่',
            ];
        @endphp
        @foreach($documents as $field => $label)
            <div class="col-md-4">
                <strong>{{ $label }}:</strong>
                @if($employer->{$field})
                    <p class="form-control-plaintext">
                        <a href="{{ asset('storage/' . $employer->{$field}) }}" target="_blank">
                            <i class="bi bi-file-earmark-text"></i> ดูเอกสาร
                        </a>
                    </p>
                @else
                    <p class="form-control-plaintext text-muted">N/A</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- New Employee Stats Summary Section --}}
    <hr class="my-4">
    <h5>สรุปข้อมูลลูกจ้าง (ที่ยังไม่สิ้นสุดสัญญา)</h5>
    <div class="card">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-4">
                    <h5 class="mb-0">{{ $stats->total }}</h5>
                    <small class="text-muted">ทั้งหมด</small>
                </div>
                <div class="col-4">
                    <h5 class="mb-0">{{ $stats->male }}</h5>
                    <small class="text-muted">ชาย</small>
                </div>
                <div class="col-4">
                    <h5 class="mb-0">{{ $stats->female }}</h5>
                    <small class="text-muted">หญิง</small>
                </div>
            </div>

            @if($stats->breakdown->isNotEmpty())
                <hr>
                <h6>แยกตามสัญชาติ:</h6>
                <ul class="list-group list-group-flush">
                    @foreach($stats->breakdown as $nationality => $data)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $nationality }}
                            <span class="badge bg-primary rounded-pill">
                                รวม: {{ $data->total_count }} (ช: {{ $data->male_count }}, ญ: {{ $data->female_count }})
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
