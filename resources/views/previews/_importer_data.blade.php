{{--
    Loaded via AJAX into the universal preview modal.
    No @extends — outputs body content only.
--}}
<div class="content-section">
    {{-- Basic Info --}}
    <h5>ข้อมูลบริษัทนำเข้า</h5>
    <hr>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">ชื่อ บนจ. (ไทย)</label>
            <p class="form-control-plaintext">{{ $importer->importerNameTh ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">ชื่อ บนจ. (อังกฤษ)</label>
            <p class="form-control-plaintext">{{ $importer->importerNameEn ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label fw-bold">เลขประจำตัว</label>
            <p class="form-control-plaintext">{{ $importer->importerId ?? 'N/A' }}</p>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">เลขที่ใบอนุญาต</label>
            <p class="form-control-plaintext">{{ $importer->importerLicenseNo ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label fw-bold">วันที่ออกใบอนุญาต</label>
            <p class="form-control-plaintext">{{ $importer->importerLicenseIssueDate ? \Carbon\Carbon::parse($importer->importerLicenseIssueDate)->format('d/m/Y') : 'N/A' }}</p>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">วันสิ้นสุดใบอนุญาต</label>
            <p class="form-control-plaintext">{{ $importer->importerLicenseExpiryDate ? \Carbon\Carbon::parse($importer->importerLicenseExpiryDate)->format('d/m/Y') : 'N/A' }}</p>
        </div>
    </div>

    <hr class="my-4">

    {{-- Signers --}}
    <h5>{{ __('Authorized Signatories & Stamp') }}</h5>
    <div class="row g-3">
        {{-- Signer 1 --}}
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted fw-bold">คนเซ็น 1</h6>
                    <div class="mb-2">
                        <label class="form-label fw-bold small mb-0">ชื่อ (ไทย)</label>
                        <p class="form-control-plaintext mb-0">{{ $importer->importerSignerTh ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small mb-0">ชื่อ (อังกฤษ)</label>
                        <p class="form-control-plaintext mb-0">{{ $importer->importerSignerEn ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="form-label fw-bold small mb-1">{{ __('Signature') }}</label>
                        @if($importer->signature_1_path)
                            <div class="border rounded bg-white d-flex justify-content-center align-items-center overflow-hidden" style="width: 180px; height: 100px;">
                                <img src="{{ Storage::url($importer->signature_1_path) }}" class="img-fluid" style="max-height: 100%;">
                            </div>
                        @else
                            <p class="form-control-plaintext text-muted">ไม่มีลายเซ็น</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Signer 2 --}}
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted fw-bold">คนเซ็น 2</h6>
                    <div class="mb-2">
                        <label class="form-label fw-bold small mb-0">ชื่อ (ไทย)</label>
                        <p class="form-control-plaintext mb-0">{{ $importer->signer_2_name_th ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small mb-0">ชื่อ (อังกฤษ)</label>
                        <p class="form-control-plaintext mb-0">{{ $importer->signer_2_name_en ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="form-label fw-bold small mb-1">{{ __('Signature') }}</label>
                        @if($importer->signature_2_path)
                            <div class="border rounded bg-white d-flex justify-content-center align-items-center overflow-hidden" style="width: 180px; height: 100px;">
                                <img src="{{ Storage::url($importer->signature_2_path) }}" class="img-fluid" style="max-height: 100%;">
                            </div>
                        @else
                            <p class="form-control-plaintext text-muted">ไม่มีลายเซ็น</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Stamp --}}
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted fw-bold">{{ __('Importer Stamp') }}</h6>
                    @if($importer->importer_stamp_path)
                        <div class="border rounded bg-white d-flex justify-content-center align-items-center overflow-hidden" style="width: 100px; height: 100px;">
                            <img src="{{ Storage::url($importer->importer_stamp_path) }}" class="img-fluid" style="max-height: 100%;">
                        </div>
                    @else
                        <p class="form-control-plaintext text-muted">ไม่มีตราประทับ</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Other Documents --}}
    <h5>{{ __('Other Documents') }}</h5>
    <div class="row g-3">
        @for($i = 1; $i <= 3; $i++)
            @php
                $field = "importer_doc_other_$i";
                $descField = "importer_doc_other_{$i}_desc";
                $description = $importer->{$descField} ?? null;
                $label = $description ? "{$i}. " . __('Other Document') . " {$i} - {$description}" : "{$i}. " . __('Other Document') . " {$i}";
                $url = $importer->{$field} ? Storage::disk('public')->url($importer->{$field}) : '#';
                $pdfUrl = $importer->{$field} ? route('importers.documents.pdf', ['importer' => $importer->id, 'field' => $field]) : '#';
            @endphp
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ $label }}</label>
                @if($importer->{$field})
                    <p class="form-control-plaintext">
                        <a href="#" onclick="event.preventDefault(); viewPDF('{{ $url }}', 'ดูเอกสาร')" class="btn btn-success btn-sm text-white">
                            <i class="bi bi-eye-fill"></i> ดูเอกสาร
                        </a>
                        <a href="{{ $pdfUrl }}" download class="btn btn-danger btn-sm text-white ms-1">
                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                        </a>
                    </p>
                @else
                    <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
                @endif
            </div>
        @endfor
    </div>

    <hr class="my-4">

    {{-- Addresses --}}
    <h5>{{ __('Addresses') }}</h5>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">{{ __('Registered Address') }}</label>
            @forelse($importer->addresses->where('type', 'registered') as $addr)
                <div class="border p-2 mb-2 rounded bg-light">
                    <p class="mb-0">{{ $addr->full_address_th }}</p>
                    <p class="mb-0 text-muted small">{{ $addr->full_address_en }}</p>
                </div>
            @empty
                <p class="form-control-plaintext text-muted">{{ __('No address yet') }}</p>
            @endforelse
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">{{ __('Workplace Address') }}</label>
            @forelse($importer->addresses->where('type', 'workplace') as $addr)
                <div class="border p-2 mb-2 rounded bg-light">
                    <p class="mb-0">{{ $addr->full_address_th }}</p>
                    <p class="mb-0 text-muted small">{{ $addr->full_address_en }}</p>
                </div>
            @empty
                <p class="form-control-plaintext text-muted">{{ __('No address yet') }}</p>
            @endforelse
        </div>
    </div>
</div>
