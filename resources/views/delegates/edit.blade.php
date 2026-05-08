@extends('layouts.app')

@section('title', 'Edit Delegate')

@section('content')
<div class="content-section container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">Edit Delegate</h2>
            <hr>
            <form id="saveDelegateForm" action="{{ route('delegates.update', $delegate->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row mb-4">
                    <div class="col-md-6 text-center">
                        <label class="form-label d-block text-muted mb-2">Photo</label>
                        <div class="mb-3">
                            <input type="file" name="delegatePhoto" id="delegatePhoto" class="form-control" accept="image/*" onchange="previewImage(event, 'photoPreview')">
                        </div>
                        <img id="photoPreview"
                             src="{{ $delegate->delegatePhoto ? asset('storage/' . $delegate->delegatePhoto) : '#' }}"
                             alt="Photo Preview"
                             style="{{ $delegate->delegatePhoto ? '' : 'display: none;' }} max-width: 150px; max-height: 150px; margin-top: 10px;"
                             class="img-thumbnail rounded-circle">
                    </div>

                    @if(auth()->user()->hasAnyRole(['super-admin', 'admin', 'staff']))
                    <div class="col-md-6 text-center border-start">
                        <label class="form-label d-block text-muted mb-2">ลายเซ็นพนักงานบริษัท (Delegate Signature)</label>
                        <div class="mb-3 d-flex gap-2">
                            <input type="file" name="delegate_signature" id="delegate_signature" class="form-control" accept="image/*" onchange="previewImage(event, 'signaturePreview')">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'delegate_signature' } }))" title="สแกนเอกสาร">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <img id="signaturePreview"
                             src="{{ $delegate->signature_path ? asset('storage/' . $delegate->signature_path) : '#' }}"
                             alt="Signature Preview"
                             style="{{ $delegate->signature_path ? '' : 'display: none;' }} max-width: 200px; max-height: 100px; margin-top: 10px;"
                             class="img-thumbnail">
                    </div>
                    @endif
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateNameTh">Name (TH)</label>
                            <input type="text" name="delegateNameTh" id="delegateNameTh" class="form-control" value="{{ $delegate->delegateNameTh }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateNameEn">Name (EN)</label>
                            <input type="text" name="delegateNameEn" id="delegateNameEn" class="form-control" value="{{ $delegate->delegateNameEn }}">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateId">National ID</label>
                            <input type="text" name="delegateId" id="delegateId" class="form-control" value="{{ $delegate->delegateId }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateEmployeeId">Employee ID</label>
                            <input type="text" name="delegateEmployeeId" id="delegateEmployeeId" class="form-control" value="{{ $delegate->delegateEmployeeId }}">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateIssueDate">Issue Date</label>
                            <input type="date" name="delegateIssueDate" id="delegateIssueDate" class="form-control" value="{{ $delegate->delegateIssueDate }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateExpiryDate">Expiry Date</label>
                            <input type="date" name="delegateExpiryDate" id="delegateExpiryDate" class="form-control" value="{{ $delegate->delegateExpiryDate }}">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegatePhone">Phone</label>
                            <input type="text" name="delegatePhone" id="delegatePhone" class="form-control" value="{{ $delegate->delegatePhone }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateEmail">Email</label>
                            <input type="email" name="delegateEmail" id="delegateEmail" class="form-control" value="{{ $delegate->delegateEmail }}">
                        </div>
                    </div>
                </div>

                <hr>
                <h5>{{ __('Other Documents') }}</h5>
                <div class="row mb-3">
                    @for($i=1; $i<=3; $i++)
                    @php $field = "delegate_doc_other_$i"; $descField = "delegate_doc_other_{$i}_desc"; @endphp
                    <div class="col-md-4">
                        <label for="{{ $field }}" class="form-label">{{ $i }}. {{ __('Other Document') }} {{ $i }} <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                        <div class="input-group input-group-sm mb-2">
                            <input type="file" class="form-control form-control-sm" id="{{ $field }}" name="{{ $field }}" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: '{{ $field }}' } }))">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        @if($delegate->$field)
                        <div class="mb-2">
                            <a href="{{ route('delegates.documents.pdf', ['delegate' => $delegate->id, 'field' => $field]) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Preview PDF">
                                <i class="bi bi-file-earmark-pdf"></i> Preview
                            </a>
                            <a href="{{ route('delegates.documents.pdf', ['delegate' => $delegate->id, 'field' => $field]) }}?disposition=attachment" class="btn btn-sm btn-outline-secondary" title="Download PDF">
                                <i class="bi bi-download"></i> Download
                            </a>
                        </div>
                        @endif
                        <input type="text" class="form-control form-control-sm" id="{{ $descField }}" name="{{ $descField }}" value="{{ old($descField, $delegate->$descField) }}" placeholder="{{ __('Specify description...') }}">
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
                                    data-addressable-id="{{ $delegate->id }}"
                                    data-addressable-type="App\Models\Delegate"
                                    data-type="registered">
                                {{ __('Add Address') }}
                            </button>
                        </div>
                        <div id="registeredAddressList" class="vstack gap-3">
                            @forelse($delegate->addresses->where('type', 'registered') as $addr)
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
                                    data-addressable-id="{{ $delegate->id }}"
                                    data-addressable-type="App\Models\Delegate"
                                    data-type="workplace">
                                {{ __('Add Address') }}
                            </button>
                        </div>
                        <div id="workplaceAddressList" class="vstack gap-3">
                             @forelse($delegate->addresses->where('type', 'workplace') as $addr)
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
                    <button type="submit" class="btn btn-primary">Update Delegate</button>
                    <a href="{{ route('delegates.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

@include('partials._address_management')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const delegateId = {{ $delegate->id }};

    document.getElementById('addressable_id').value = delegateId;
    document.getElementById('addressable_type').value = 'App\\Models\\Delegate';

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
        document.getElementById('addressable_id').value = delegateId;
        document.getElementById('addressable_type').value = 'App\\Models\\Delegate';
        document.getElementById('addressFormMethod').value = '';
        document.getElementById('addrDistrict').disabled = true;
        document.getElementById('addrSubDistrict').disabled = true;
    });
});

function previewImage(event, previewId) {
    const input = event.target;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            preview.src = e.target.result;
            preview.style.display = 'inline-block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
