{{--
    Loaded via AJAX into the universal preview modal.
    No @extends — outputs body content only.
--}}
<div class="content-section">
    <h5>ข้อมูลพนักงาน</h5>
    <hr>

    <div class="row mb-3">
        {{-- Photo --}}
        <div class="col-md-3 text-center">
            <label class="form-label fw-bold d-block">{{ __('Photo') }}</label>
            @if($delegate->delegatePhoto)
                <img src="{{ asset('storage/' . $delegate->delegatePhoto) }}"
                     alt="Photo"
                     class="img-thumbnail rounded-circle"
                     style="max-width: 150px; max-height: 150px;">
            @else
                <p class="form-control-plaintext text-muted">ไม่มีรูป</p>
            @endif
        </div>

        {{-- Signature --}}
        <div class="col-md-9 text-center">
            <label class="form-label fw-bold d-block">ลายเซ็นพนักงานบริษัท (Delegate Signature)</label>
            @if($delegate->signature_path)
                <img src="{{ asset('storage/' . $delegate->signature_path) }}"
                     alt="Signature"
                     class="img-thumbnail"
                     style="max-width: 200px; max-height: 100px;">
            @else
                <p class="form-control-plaintext text-muted">ไม่มีลายเซ็น</p>
            @endif
        </div>
    </div>

    <hr>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Name (TH)</label>
            <p class="form-control-plaintext">{{ $delegate->delegateNameTh ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Name (EN)</label>
            <p class="form-control-plaintext">{{ $delegate->delegateNameEn ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">National ID</label>
            <p class="form-control-plaintext">{{ $delegate->delegateId ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Employee ID</label>
            <p class="form-control-plaintext">{{ $delegate->delegateEmployeeId ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Issue Date</label>
            <p class="form-control-plaintext">{{ $delegate->delegateIssueDate ? \Carbon\Carbon::parse($delegate->delegateIssueDate)->format('d/m/Y') : 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Expiry Date</label>
            <p class="form-control-plaintext">{{ $delegate->delegateExpiryDate ? \Carbon\Carbon::parse($delegate->delegateExpiryDate)->format('d/m/Y') : 'N/A' }}</p>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Phone</label>
            <p class="form-control-plaintext">{{ $delegate->delegatePhone ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Email</label>
            <p class="form-control-plaintext">{{ $delegate->delegateEmail ?? 'N/A' }}</p>
        </div>
    </div>

    <hr class="my-4">

    {{-- Other Documents --}}
    <h5>{{ __('Other Documents') }}</h5>
    <div class="row g-3">
        @for($i = 1; $i <= 3; $i++)
            @php
                $field = "delegate_doc_other_$i";
                $descField = "delegate_doc_other_{$i}_desc";
                $description = $delegate->{$descField} ?? null;
                $label = $description ? "{$i}. " . __('Other Document') . " {$i} - {$description}" : "{$i}. " . __('Other Document') . " {$i}";
                $url = $delegate->{$field} ? Storage::disk('public')->url($delegate->{$field}) : '#';
                $pdfUrl = $delegate->{$field} ? route('delegates.documents.pdf', ['delegate' => $delegate->id, 'field' => $field]) : '#';
            @endphp
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ $label }}</label>
                @if($delegate->{$field})
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
            @forelse($delegate->addresses->where('type', 'registered') as $addr)
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
            @forelse($delegate->addresses->where('type', 'workplace') as $addr)
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
