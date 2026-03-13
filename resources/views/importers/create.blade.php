@extends('layouts.app')

@section('title', 'เพิ่มข้อมูลบริษัทนำเข้า')

@section('content')
<div class="content-section">
    <h2 class="mb-4">เพิ่มข้อมูลบริษัทนำเข้า</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form id="saveImporterForm" action="{{ route('importers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerNameTh" class="form-label">ชื่อ บนจ. (ไทย)</label>
                <input type="text" class="form-control" id="importerNameTh" name="importerNameTh" required>
            </div>
            <div class="col-md-6">
                <label for="importerNameEn" class="form-label">ชื่อ บนจ. (อังกฤษ)</label>
                <input type="text" class="form-control" id="importerNameEn" name="importerNameEn">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerId" class="form-label">เลขประจำตัว</label>
                <input type="text" class="form-control" id="importerId" name="importerId">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="importerLicenseNo" class="form-label">เลขที่ใบอนุญาต</label>
                <input type="text" class="form-control" id="importerLicenseNo" name="importerLicenseNo">
            </div>
            <div class="col-md-4">
                <label for="importerLicenseIssueDate" class="form-label">วันที่ออกใบอนุญาต</label>
                <input type="date" class="form-control" id="importerLicenseIssueDate" name="importerLicenseIssueDate">
            </div>
            <div class="col-md-4">
                <label for="importerLicenseExpiryDate" class="form-label">วันสิ้นสุดใบอนุญาต</label>
                <input type="date" class="form-control" id="importerLicenseExpiryDate" name="importerLicenseExpiryDate">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerSignerTh" class="form-label">คนเซ็น (ไทย)</label>
                <input type="text" class="form-control" id="importerSignerTh" name="importerSignerTh">
            </div>
            <div class="col-md-6">
                <label for="importerSignerEn" class="form-label">คนเซ็น (อังกฤษ)</label>
                <input type="text" class="form-control" id="importerSignerEn" name="importerSignerEn">
            </div>
        </div>

        <hr>
        <h5>{{ __('Other Documents') }}</h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="importer_doc_other_1" class="form-label">1. {{ __('Other Document') }} 1 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('importer_doc_other_1') is-invalid @enderror" id="importer_doc_other_1" name="importer_doc_other_1" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'importer_doc_other_1' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <input type="text" class="form-control form-control-sm mt-2 @error('importer_doc_other_1_desc') is-invalid @enderror" id="importer_doc_other_1_desc" name="importer_doc_other_1_desc" value="{{ old('importer_doc_other_1_desc') }}" placeholder="{{ __('Specify description...') }}">
                @error('importer_doc_other_1')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="importer_doc_other_2" class="form-label">2. {{ __('Other Document') }} 2 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('importer_doc_other_2') is-invalid @enderror" id="importer_doc_other_2" name="importer_doc_other_2" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'importer_doc_other_2' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <input type="text" class="form-control form-control-sm mt-2 @error('importer_doc_other_2_desc') is-invalid @enderror" id="importer_doc_other_2_desc" name="importer_doc_other_2_desc" value="{{ old('importer_doc_other_2_desc') }}" placeholder="{{ __('Specify description...') }}">
                @error('importer_doc_other_2')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="importer_doc_other_3" class="form-label">3. {{ __('Other Document') }} 3 <span class="text-muted small">(รองรับไฟล์สูงสุด 5 MB)</span></label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm @error('importer_doc_other_3') is-invalid @enderror" id="importer_doc_other_3" name="importer_doc_other_3" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'importer_doc_other_3' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <input type="text" class="form-control form-control-sm mt-2 @error('importer_doc_other_3_desc') is-invalid @enderror" id="importer_doc_other_3_desc" name="importer_doc_other_3_desc" value="{{ old('importer_doc_other_3_desc') }}" placeholder="{{ __('Specify description...') }}">
                @error('importer_doc_other_3')
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
            <button type="submit" class="btn btn-primary">บันทึก</button>
            <a href="{{ route('importers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
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
    const mainForm = document.getElementById('saveImporterForm');
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
        document.getElementById('addressable_type').value = 'App\\Models\\Importer';
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
</script>
@endpush
