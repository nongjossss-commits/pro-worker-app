@extends('layouts.app')

@section('title', 'Add Delegate')

@section('content')
<div class="content-section container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">Add Delegate</h2>
            <hr>
            <form id="saveDelegateForm" action="{{ route('delegates.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row mb-4">
                    <div class="col-md-12 text-center">
                        <label class="form-label d-block text-muted mb-2">Photo</label>
                        <div class="mb-3">
                            <input type="file" name="delegatePhoto" id="delegatePhoto" class="form-control" accept="image/*" onchange="previewImage(event, 'photoPreview')">
                        </div>
                        <img id="photoPreview" src="#" alt="Photo Preview" style="display: none; max-width: 150px; max-height: 150px; margin-top: 10px;" class="img-thumbnail rounded-circle">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateNameTh">Name (TH)</label>
                            <input type="text" name="delegateNameTh" id="delegateNameTh" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateNameEn">Name (EN)</label>
                            <input type="text" name="delegateNameEn" id="delegateNameEn" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateId">National ID</label>
                            <input type="text" name="delegateId" id="delegateId" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateEmployeeId">Employee ID</label>
                            <input type="text" name="delegateEmployeeId" id="delegateEmployeeId" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateIssueDate">Issue Date</label>
                            <input type="date" name="delegateIssueDate" id="delegateIssueDate" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateExpiryDate">Expiry Date</label>
                            <input type="date" name="delegateExpiryDate" id="delegateExpiryDate" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegatePhone">Phone</label>
                            <input type="text" name="delegatePhone" id="delegatePhone" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateEmail">Email</label>
                            <input type="email" name="delegateEmail" id="delegateEmail" class="form-control">
                        </div>
                    </div>
                </div>

                <hr>
                <h5>{{ __('Other Documents') }}</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="delegate_doc_other_1" class="form-label">1. {{ __('Other Document') }} 1 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                        <div class="input-group input-group-sm">
                            <input type="file" class="form-control form-control-sm @error('delegate_doc_other_1') is-invalid @enderror" id="delegate_doc_other_1" name="delegate_doc_other_1" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'delegate_doc_other_1' } }))">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <input type="text" class="form-control form-control-sm mt-2 @error('delegate_doc_other_1_desc') is-invalid @enderror" id="delegate_doc_other_1_desc" name="delegate_doc_other_1_desc" value="{{ old('delegate_doc_other_1_desc') }}" placeholder="{{ __('Specify description...') }}">
                        @error('delegate_doc_other_1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="delegate_doc_other_2" class="form-label">2. {{ __('Other Document') }} 2 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                        <div class="input-group input-group-sm">
                            <input type="file" class="form-control form-control-sm @error('delegate_doc_other_2') is-invalid @enderror" id="delegate_doc_other_2" name="delegate_doc_other_2" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'delegate_doc_other_2' } }))">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <input type="text" class="form-control form-control-sm mt-2 @error('delegate_doc_other_2_desc') is-invalid @enderror" id="delegate_doc_other_2_desc" name="delegate_doc_other_2_desc" value="{{ old('delegate_doc_other_2_desc') }}" placeholder="{{ __('Specify description...') }}">
                        @error('delegate_doc_other_2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="delegate_doc_other_3" class="form-label">3. {{ __('Other Document') }} 3 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                        <div class="input-group input-group-sm">
                            <input type="file" class="form-control form-control-sm @error('delegate_doc_other_3') is-invalid @enderror" id="delegate_doc_other_3" name="delegate_doc_other_3" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'delegate_doc_other_3' } }))">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <input type="text" class="form-control form-control-sm mt-2 @error('delegate_doc_other_3_desc') is-invalid @enderror" id="delegate_doc_other_3_desc" name="delegate_doc_other_3_desc" value="{{ old('delegate_doc_other_3_desc') }}" placeholder="{{ __('Specify description...') }}">
                        @error('delegate_doc_other_3')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
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
                                    data-type="registered">
                                {{ __('Add Address') }}
                            </button>
                        </div>
                        <div id="registeredAddressList" class="vstack gap-3">
                            <p class="text-muted">{{ __('No address yet') }}</p>
                        </div>
                    </div>

                    {{-- Workplace Address Section --}}
                    <div class="content-section mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">{{ __('Workplace Address') }}</h5>
                            <button type="button" class="btn btn-sm btn-primary add-address-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addressModal"
                                    data-type="workplace">
                                {{ __('Add Address') }}
                            </button>
                        </div>
                        <div id="workplaceAddressList" class="vstack gap-3">
                            <p class="text-muted">{{ __('No address yet') }}</p>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="registered_addresses" id="registered_addresses_json">
                <input type="hidden" name="workplace_addresses" id="workplace_addresses_json">

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Add Delegate</button>
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
    // --- Temporary Address Storage ---
    let tempRegisteredAddresses = [];
    let tempWorkplaceAddresses = [];

    const registeredAddressList = document.getElementById('registeredAddressList');
    const workplaceAddressList = document.getElementById('workplaceAddressList');
    const mainForm = document.getElementById('saveDelegateForm');
    const addressModalEl = document.getElementById('addressModal');
    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveAddressButton = document.getElementById('saveAddressBtn');

    let currentlyEditing = null; // { type: 'registered', index: 0 }

    function getFullAddressStringFromObject(addr) {
        const th_parts = [
            addr.addrNo,
            addr.addrMoo ? `หมู่ ${addr.addrMoo}` : '',
            addr.addrSoi ? `ซอย${addr.addrSoi}` : '',
            addr.addrRoad ? `ถนน${addr.addrRoad}` : '',
            addr.addrSubDistrict,
            addr.addrDistrict,
            addr.addrProvince,
            addr.addrZipCode
        ].filter(Boolean).join(' ');

        const en_parts = [
            addr.addrNoEn,
            addr.addrMooEn ? `Moo ${addr.addrMooEn}` : '',
            addr.addrSoiEn ? `Soi ${addr.addrSoiEn}` : '',
            addr.addrRoadEn ? `Road ${addr.addrRoadEn}` : '',
            addr.addrSubDistrictEn,
            addr.addrDistrictEn,
            addr.addrProvinceEn,
            addr.addrZipCodeEn
        ].filter(Boolean).join(', ');

        return { th: th_parts || 'N/A', en: en_parts || 'N/A' };
    }

    function renderTempAddressList(type) {
        const listContainer = document.getElementById(type === 'registered' ? 'registeredAddressList' : 'workplaceAddressList');
        const addresses = type === 'registered' ? tempRegisteredAddresses : tempWorkplaceAddresses;

        listContainer.innerHTML = '';
        if (addresses.length === 0) {
            listContainer.innerHTML = '<p class="text-muted fst-italic">{{ __('No address yet') }}</p>';
            return;
        }

        addresses.forEach((addr, index) => {
            const fullAddress = getFullAddressStringFromObject(addr);
            const card = document.createElement('div');
            card.className = 'address-card d-flex justify-content-between align-items-start border p-2 mb-2 rounded';
            card.innerHTML = `
                <div>
                    <p class="mb-0">${fullAddress.th}</p>
                    <p class="mb-0 text-muted small">${fullAddress.en}</p>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary temp-edit-address-btn" data-type="${type}" data-index="${index}" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger temp-delete-address-btn" data-type="${type}" data-index="${index}" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                </div>
            `;
            listContainer.appendChild(card);
        });
    }

    function openAddressModalForTemp(type, index, addressToEdit) {
        addressForm.reset();
        document.getElementById('addressType').value = type;

        for (const key in addressToEdit) {
            if (addressForm.elements[key]) {
                addressForm.elements[key].value = addressToEdit[key];
            }
        }
        document.getElementById('addrProvince').value = addressToEdit.addrProvince;
        document.getElementById('addrDistrict').disabled = false;
        document.getElementById('addrDistrict').value = addressToEdit.addrDistrict;
        document.getElementById('addrSubDistrict').disabled = false;
        document.getElementById('addrSubDistrict').value = addressToEdit.addrSubDistrict;

        currentlyEditing = { type, index };
        addressModal.show();
    }

    function handleSaveAddressTemporarily(e) {
        e.preventDefault();
        const formData = new FormData(addressForm);
        const addressData = Object.fromEntries(formData.entries());
        const addressType = document.getElementById('addressType').value || document.getElementById('address_type').value;

        if (!addressData.addrNo || !addressData.addrProvince || !addressData.addrDistrict || !addressData.addrSubDistrict) {
             alert('{{ __('Please fill in complete address information') }}');
             return;
        }

        if (currentlyEditing) {
            if (currentlyEditing.type === 'registered') {
                tempRegisteredAddresses[currentlyEditing.index] = addressData;
            } else {
                tempWorkplaceAddresses[currentlyEditing.index] = addressData;
            }
        } else {
            if (addressType === 'registered') {
                tempRegisteredAddresses.push(addressData);
            } else if (addressType === 'workplace') {
                tempWorkplaceAddresses.push(addressData);
            }
        }

        renderTempAddressList(currentlyEditing ? currentlyEditing.type : addressType);
        currentlyEditing = null;
        addressModal.hide();
    }

    const newSaveAddressButton = saveAddressButton.cloneNode(true);
    saveAddressButton.parentNode.replaceChild(newSaveAddressButton, saveAddressButton);
    newSaveAddressButton.addEventListener('click', handleSaveAddressTemporarily);

    document.getElementById('addressListsContainer').addEventListener('click', function(e) {
        const editBtn = e.target.closest('.temp-edit-address-btn');
        const deleteBtn = e.target.closest('.temp-delete-address-btn');

        if (editBtn) {
            const type = editBtn.dataset.type;
            const index = parseInt(editBtn.dataset.index, 10);
            const addressToEdit = (type === 'registered' ? tempRegisteredAddresses : tempWorkplaceAddresses)[index];
            openAddressModalForTemp(type, index, addressToEdit);
        }

        if (deleteBtn) {
            const type = deleteBtn.dataset.type;
            const index = parseInt(deleteBtn.dataset.index, 10);

            if (confirm('{{ __('Are you sure you want to delete this address?') }}')) {
                if (type === 'registered') {
                    tempRegisteredAddresses.splice(index, 1);
                } else {
                    tempWorkplaceAddresses.splice(index, 1);
                }
                renderTempAddressList(type);
            }
        }
    });

    mainForm.addEventListener('submit', function (e) {
        document.getElementById('registered_addresses_json').value = JSON.stringify(tempRegisteredAddresses);
        document.getElementById('workplace_addresses_json').value = JSON.stringify(tempWorkplaceAddresses);
    });

    addressModalEl.addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        document.getElementById('addressable_type').value = 'App\\Models\\Delegate';
        document.getElementById('addrDistrict').disabled = true;
        document.getElementById('addrSubDistrict').disabled = true;
        currentlyEditing = null;
    });

    document.querySelectorAll('.add-address-btn').forEach(button => {
        button.addEventListener('click', function() {
            const addressType = this.getAttribute('data-type');
            document.getElementById('address_type').value = addressType;
        });
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
