@extends('layouts.app')

@section('title', __('Edit Importer'))

@section('content')
<div class="content-section">
    <h2 class="mb-4">{{ __('Edit Importer') }}</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form id="saveImporterForm" action="{{ route('importers.update', $importer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerNameTh" class="form-label">{{ __('Importer Name (Thai)') }}</label>
                <input type="text" class="form-control" id="importerNameTh" name="importerNameTh" value="{{ $importer->importerNameTh }}" required>
            </div>
            <div class="col-md-6">
                <label for="importerNameEn" class="form-label">{{ __('Importer Name (English)') }}</label>
                <input type="text" class="form-control" id="importerNameEn" name="importerNameEn" value="{{ old('importerNameEn', $importer->importerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerId" class="form-label">{{ __('ID Number') }}</label>
                <input type="text" class="form-control" id="importerId" name="importerId" value="{{ old('importerId', $importer->importerId) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="importerLicenseNo" class="form-label">{{ __('License Number') }}</label>
                <input type="text" class="form-control" id="importerLicenseNo" name="importerLicenseNo" value="{{ old('importerLicenseNo', $importer->importerLicenseNo) }}">
            </div>
            <div class="col-md-4">
                <label for="importerLicenseIssueDate" class="form-label">{{ __('License Issue Date') }}</label>
                <input type="date" class="form-control" id="importerLicenseIssueDate" name="importerLicenseIssueDate" value="{{ old('importerLicenseIssueDate', $importer->importerLicenseIssueDate) }}">
            </div>
            <div class="col-md-4">
                <label for="importerLicenseExpiryDate" class="form-label">{{ __('License Expiry Date') }}</label>
                <input type="date" class="form-control" id="importerLicenseExpiryDate" name="importerLicenseExpiryDate" value="{{ old('importerLicenseExpiryDate', $importer->importerLicenseExpiryDate) }}">
            </div>
        </div>
        <h5 class="mb-3 text-primary mt-4">{{ __('Authorized Signatories & Stamp') }}</h5>
        <!-- Signer 1 -->
        <div class="card bg-light mb-3">
            <div class="card-body">
                <h6 class="card-title text-muted fw-bold">{{ __('Signer 1') }}</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="importerSignerTh" class="form-label">{{ __('Signer Name (Thai)') }}</label>
                        <input type="text" class="form-control" id="importerSignerTh" name="importerSignerTh" value="{{ old('importerSignerTh', $importer->importerSignerTh) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="importerSignerEn" class="form-label">{{ __('Signer Name (English)') }}</label>
                        <input type="text" class="form-control" id="importerSignerEn" name="importerSignerEn" value="{{ old('importerSignerEn', $importer->importerSignerEn) }}">
                    </div>
                </div>

                @unless(auth()->user()->hasRole('employer'))
                <div class="mb-3">
                    <label for="signature_1_path" class="form-label">{{ __('Upload Signature Image') }}</label>
                    <input type="file" class="form-control" id="signature_1_path" name="signature_1_path" accept="image/png, image/jpeg, image/jpg">
                    <div class="form-text">{{ __('Max size: 2MB. Allowed formats: PNG, JPG.') }}</div>
                    @if($importer->signature_1_path)
                        <div class="mt-2 border rounded bg-white d-flex justify-content-center align-items-center overflow-hidden" style="width: 180px; height: 100px;">
                            <img src="{{ Storage::url($importer->signature_1_path) }}" class="img-fluid" style="max-height: 100%;">
                        </div>
                    @endif
                </div>
                @endunless
            </div>
        </div>

        <!-- Signer 2 -->
        <div class="card bg-light mb-3">
            <div class="card-body">
                <h6 class="card-title text-muted fw-bold">{{ __('Signer 2 (Optional)') }}</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="signer_2_name_th" class="form-label">{{ __('Signer Name (Thai)') }}</label>
                        <input type="text" class="form-control" id="signer_2_name_th" name="signer_2_name_th" value="{{ old('signer_2_name_th', $importer->signer_2_name_th) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="signer_2_name_en" class="form-label">{{ __('Signer Name (English)') }}</label>
                        <input type="text" class="form-control" id="signer_2_name_en" name="signer_2_name_en" value="{{ old('signer_2_name_en', $importer->signer_2_name_en) }}">
                    </div>
                </div>

                @unless(auth()->user()->hasRole('employer'))
                <div class="mb-3">
                    <label for="signature_2_path" class="form-label">{{ __('Upload Signature Image') }}</label>
                    <input type="file" class="form-control" id="signature_2_path" name="signature_2_path" accept="image/png, image/jpeg, image/jpg">
                    <div class="form-text">{{ __('Max size: 2MB. Allowed formats: PNG, JPG.') }}</div>
                    @if($importer->signature_2_path)
                        <div class="mt-2 border rounded bg-white d-flex justify-content-center align-items-center overflow-hidden" style="width: 180px; height: 100px;">
                            <img src="{{ Storage::url($importer->signature_2_path) }}" class="img-fluid" style="max-height: 100%;">
                        </div>
                    @endif
                </div>
                @endunless
            </div>
        </div>

        <!-- Stamp -->
        @unless(auth()->user()->hasRole('employer'))
        <div class="card bg-light mb-4">
            <div class="card-body">
                <h6 class="card-title text-muted fw-bold">{{ __('Importer Stamp') }}</h6>
                <div class="mb-3">
                    <label for="importer_stamp_path" class="form-label">{{ __('Upload Stamp Image') }}</label>
                    <input type="file" class="form-control" id="importer_stamp_path" name="importer_stamp_path" accept="image/png, image/jpeg, image/jpg">
                    <div class="form-text">{{ __('Max size: 2MB. Allowed formats: PNG, JPG.') }}</div>
                    @if($importer->importer_stamp_path)
                        <div class="mt-2 border rounded bg-white d-flex justify-content-center align-items-center overflow-hidden" style="width: 100px; height: 100px;">
                            <img src="{{ Storage::url($importer->importer_stamp_path) }}" class="img-fluid" style="max-height: 100%;">
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endunless

        <hr>
        <h5>{{ __('Other Documents') }}</h5>
        <div class="row mb-3">
            @for($i=1; $i<=3; $i++)
            @php $field = "importer_doc_other_$i"; $descField = "importer_doc_other_{$i}_desc"; @endphp
            <div class="col-md-4">
                <label for="{{ $field }}" class="form-label">{{ $i }}. {{ __('Other Document') }} {{ $i }} <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm mb-2">
                    <input type="file" class="form-control form-control-sm" id="{{ $field }}" name="{{ $field }}" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: '{{ $field }}' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                @if($importer->$field)
                <div class="mb-2">
                    <a href="{{ route('importers.documents.pdf', ['importer' => $importer->id, 'field' => $field]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Preview PDF">
                        <i class="bi bi-file-earmark-pdf"></i> Preview
                    </a>
                    <a href="{{ route('importers.documents.pdf', ['importer' => $importer->id, 'field' => $field]) }}?disposition=attachment" class="btn btn-sm btn-outline-secondary" title="Download PDF">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
                @endif
                <input type="text" class="form-control form-control-sm" id="{{ $descField }}" name="{{ $descField }}" value="{{ old($descField, $importer->$descField) }}" placeholder="{{ __('Specify description...') }}">
            </div>
            @endfor
        </div>

        <hr>
        <div id="addressListsContainer">
            {{-- Registered Address Section --}}
            <div class="content-section mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ __('Registered Address') }}</h5>
                    <button type="button" class="btn btn-sm btn-primary add-address-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#addressModal"
                            data-addressable-id="{{ $importer->id }}"
                            data-addressable-type="App\Models\Importer"
                            data-type="registered">
                        {{ __('Add Address') }}
                    </button>
                </div>
                <div id="registeredAddressList" class="vstack gap-3">
                    @forelse($importer->addresses->where('type', 'registered') as $addr)
                        <div class="address-card d-flex justify-content-between align-items-start border p-2 mb-2 rounded">
                            <div>
                                <p class="mb-0">{{ $addr->full_address_th }}</p>
                                <p class="mb-0 text-muted small">{{ $addr->full_address_en }}</p>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary edit-existing-address-btn" data-id="{{ $addr->id }}" data-type="registered" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-outline-danger delete-existing-address-btn" data-id="{{ $addr->id }}" data-type="registered" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">{{ __('No address yet') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Workplace Address Section --}}
            <div class="content-section mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ __('Workplace Address') }}</h5>
                    <button type="button" class="btn btn-sm btn-primary add-address-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#addressModal"
                            data-addressable-id="{{ $importer->id }}"
                            data-addressable-type="App\Models\Importer"
                            data-type="workplace">
                        {{ __('Add Address') }}
                    </button>
                </div>
                <div id="workplaceAddressList" class="vstack gap-3">
                     @forelse($importer->addresses->where('type', 'workplace') as $addr)
                        <div class="address-card d-flex justify-content-between align-items-start border p-2 mb-2 rounded">
                            <div>
                                <p class="mb-0">{{ $addr->full_address_th }}</p>
                                <p class="mb-0 text-muted small">{{ $addr->full_address_en }}</p>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary edit-existing-address-btn" data-id="{{ $addr->id }}" data-type="workplace" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-outline-danger delete-existing-address-btn" data-id="{{ $addr->id }}" data-type="workplace" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">{{ __('No address yet') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
            <a href="{{ route('importers.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>

@include('partials._address_management')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const importerId = {{ $importer->id }};

    document.getElementById('addressable_id').value = importerId;
    document.getElementById('addressable_type').value = 'App\\Models\\Importer';

    // Set address type when "Add Address" is clicked
    document.querySelectorAll('.add-address-btn').forEach(button => {
        button.addEventListener('click', function() {
            const addressType = this.getAttribute('data-type');
            document.getElementById('address_type').value = addressType;
            document.getElementById('addressFormMethod').value = 'POST';
            addressForm.action = '{{ route('addresses.store') }}';
        });
    });

    document.getElementById('addressListsContainer').addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-existing-address-btn');
        const deleteBtn = e.target.closest('.delete-existing-address-btn');

        if (editBtn) {
            const id = editBtn.dataset.id;
            const type = editBtn.dataset.type;
            fetch(`/addresses/${id}/edit`)
                .then(response => response.json())
                .then(data => {
                    addressForm.reset();
                    for (const key in data) {
                        if (addressForm.elements[key]) {
                            addressForm.elements[key].value = data[key];
                            if(key === 'addrProvince' || key === 'addrDistrict' || key === 'addrSubDistrict') {
                                addressForm.elements[key].disabled = false;
                                addressForm.elements[key].dispatchEvent(new Event('options-updated'));
                            }
                        }
                    }
                    document.getElementById('address_id').value = id;
                    document.getElementById('address_type').value = type;
                    document.getElementById('addressFormMethod').value = 'PUT';
                    addressForm.action = `/addresses/${id}`;
                    addressModal.show();
                });
        }

        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            if (confirm('{{ __('Are you sure you want to delete this address?') }}')) {
                fetch(`/addresses/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    }
                });
            }
        }
    });

    addressForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const action = this.action;
        const method = document.getElementById('addressFormMethod').value || 'POST';

        fetch(action, {
            method: 'POST', // Always POST for FormData, override with _method
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Error saving address');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });

    addressModalEl.addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        document.getElementById('address_id').value = '';
        document.getElementById('addressable_id').value = importerId;
        document.getElementById('addressable_type').value = 'App\\Models\\Importer';
        document.getElementById('addressFormMethod').value = '';
        document.getElementById('addrDistrict').disabled = true;
        document.getElementById('addrSubDistrict').disabled = true;
    });
});
</script>
@endpush
