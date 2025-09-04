@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลนายจ้าง')

@section('content')

{{-- Employer Info Form --}}
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลนายจ้าง</h2>
    <form id="employerForm" action="{{ route('employers.update', $employer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h5>ข้อมูลนายจ้าง</h5>
        <hr>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">ชื่อนายจ้าง (ไทย)</label>
                <input type="text" class="form-control" id="employerNameTh" name="employerNameTh" value="{{ old('employerNameTh', $employer->employerNameTh) }}">
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">ชื่อนายจ้าง (อังกฤษ)</label>
                <input type="text" class="form-control" id="employerNameEn" name="employerNameEn" value="{{ old('employerNameEn', $employer->employerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerId" class="form-label">รหัสนายจ้าง</label>
                <input type="text" class="form-control" id="employerId" name="employerId" value="{{ old('employerId', $employer->employerId) }}" readonly required>
            </div>
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">เลขประจำตัวนายจ้าง</label>
                <input type="text" class="form-control" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId', $employer->employerTaxId) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">ผู้มีอำนาจลงนาม (ไทย)</label>
                <input type="text" class="form-control" id="signerNameTh" name="signerNameTh" value="{{ old('signerNameTh', $employer->signerNameTh) }}">
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">ผู้มีอำนาจลงนาม (อังกฤษ)</label>
                <input type="text" class="form-control" id="signerNameEn" name="signerNameEn" value="{{ old('signerNameEn', $employer->signerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessType" class="form-label">ประเภทกิจการ</label>
                <input type="text" class="form-control" id="businessType" name="businessType" value="{{ old('businessType', $employer->businessType) }}">
            </div>
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">Type of Business</label>
                <input type="text" class="form-control" id="businessTypeEn" name="businessTypeEn" value="{{ old('businessTypeEn', $employer->businessTypeEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regCapital" class="form-label">ทุนจดทะเบียน</label>
                <input type="text" class="form-control" id="regCapital" name="regCapital" value="{{ old('regCapital', $employer->regCapital) }}">
            </div>
            <div class="col-md-6">
                <label for="regDate" class="form-label">จดทะเบียนวันที่</label>
                <input type="date" class="form-control" id="regDate" name="regDate" value="{{ old('regDate', $employer->regDate) }}">
            </div>
        </div>

        <hr>
        <h5>เอกสารแนบของนายจ้าง</h5>
        <div class="row">
            <div class="col-md-4">
                <label for="document_company_registration" class="form-label">หนังสือรับรองบริษัท</label>
                <input type="file" class="form-control form-control-sm" id="document_company_registration" name="document_company_registration">
                @if($employer->document_company_registration)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employer->document_company_registration) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <label for="document_vat_registration" class="form-label">ภ.พ.20</label>
                <input type="file" class="form-control form-control-sm" id="document_vat_registration" name="document_vat_registration">
                @if($employer->document_vat_registration)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employer->document_vat_registration) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <label for="document_map" class="form-label">แผนที่</label>
                <input type="file" class="form-control form-control-sm" id="document_map" name="document_map">
                @if($employer->document_map)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employer->document_map) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกข้อมูลนายจ้าง</button>
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>

{{-- Registered Address Section --}}
<div class="content-section mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">ที่อยู่ตามทะเบียน</h5>
        <button type="button" class="btn btn-sm btn-outline-success add-address-btn" data-bs-toggle="modal" data-bs-target="#addressModal" data-address-type="registered">
            <i class="bi bi-plus-lg"></i> เพิ่มที่อยู่
        </button>
    </div>
    <div id="registeredAddressList" class="vstack gap-3">
        @forelse ($employer->addresses->where('type', 'registered') as $address)
            <div class="address-card d-flex justify-content-between align-items-start" id="address-card-{{$address->id}}">
                <div>
                    <p class="mb-0">
                        เลขที่ {{ $address->addrNo }} หมู่ {{ $address->addrMoo }} ซอย{{ $address->addrSoi }} ถนน{{ $address->addrRoad }}
                        แขวง/ตำบล {{ $address->addrSubDistrict }} เขต/อำเภอ {{ $address->addrDistrict }}
                        {{ $address->addrProvince }} {{ $address->addrZipCode }}
                    </p>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary edit-address-btn" data-id="{{ $address->id }}" data-bs-toggle="modal" data-bs-target="#addressModal"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger delete-address-btn" data-id="{{ $address->id }}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีที่อยู่</p>
        @endforelse
    </div>
</div>

{{-- Workplace Address Section --}}
<div class="content-section mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">ที่อยู่สถานที่ทำงาน</h5>
        <button type="button" class="btn btn-sm btn-outline-success add-address-btn" data-bs-toggle="modal" data-bs-target="#addressModal" data-address-type="workplace">
            <i class="bi bi-plus-lg"></i> เพิ่มที่อยู่
        </button>
    </div>
    <div id="workplaceAddressList" class="vstack gap-3">
        @forelse ($employer->addresses->where('type', 'workplace') as $address)
            <div class="address-card d-flex justify-content-between align-items-start" id="address-card-{{$address->id}}">
                <div>
                    <p class="mb-0">
                        เลขที่ {{ $address->addrNo }} หมู่ {{ $address->addrMoo }} ซอย{{ $address->addrSoi }} ถนน{{ $address->addrRoad }}
                        แขวง/ตำบล {{ $address->addrSubDistrict }} เขต/อำเภอ {{ $address->addrDistrict }}
                        {{ $address->addrProvince }} {{ $address->addrZipCode }}
                    </p>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary edit-address-btn" data-id="{{ $address->id }}" data-bs-toggle="modal" data-bs-target="#addressModal"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-outline-danger delete-address-btn" data-id="{{ $address->id }}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีที่อยู่</p>
        @endforelse
    </div>
</div>


{{-- Employee List Section --}}
<div class="content-section mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h5>ข้อมูลพนักงาน <span id="employeeTotalCount" class="badge bg-secondary fw-normal">{{ count($employees) }}</span></h5>
        {{-- <div class="d-flex gap-2 flex-wrap">
            <input type="text" class="form-control form-control-sm" id="searchEmployeeInput" placeholder="ค้นหาพนักงาน..." style="width: 150px;">
            <select class="form-select form-select-sm" id="searchEmployeeNationality" style="width: 150px;">
                <option value="">-- ทุกสัญชาติ --</option>
                <option>ลาว</option>
                <option>กัมพูชา</option>
                <option>เมียนมา</option>
                <option>เวียดนาม</option>
            </select>
            <select class="form-select form-select-sm" id="searchEmployeeMOUGroup" style="width: 200px;">
                <option value="">-- ทุกประเภท มติ. --</option>
                <option>MOU</option>
                <option>มติต่ออายุในประเทศ</option>
                <option>มติขึ้นทะเบียน</option>
                <option>อื่นๆ</option>
            </select>
            <select class="form-select form-select-sm" id="searchEmployeePinkCard" style="width: 150px;">
                <option value="">-- บัตรชมพู --</option>
                <option value="has_card">มีบัตรชมพู</option>
                <option value="no_card">ไม่มีบัตรชมพู</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-success export-btn" data-export-type="employees"><i class="bi bi-download"></i> ส่งออก</button>
            <a href="{{ route('employers.employees.create', $employer) }}" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> เพิ่มพนักงาน</a>
        </div> --}}
    </div>
    <div id="employeeList" class="vstack gap-3">
        @forelse ($employees as $employee)
        <div class="employee-card d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex align-items-center flex-grow-1">
                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Employee Photo">
                <div class="flex-grow-1">
                    <p class="mb-0"><strong>{{ $employee->employeeNameEn ?? 'No English Name' }}</strong></p>
                    <p class="mb-1 text-muted small">{{ $employee->employeeNameTh ?? 'ไม่มีชื่อภาษาไทย' }} ({{ $employee->employeePosition ?? 'ไม่ระบุตำแหน่ง' }})</p>
                    <p class="mb-1 text-muted small">Passport: {{ $employee->employeePassport ?? '-' }} (หมดอายุ: {{ $employee->passportExpiryDate ? \Carbon\Carbon::parse($employee->passportExpiryDate)->format('d M Y') : '-' }})</p>
                    <p class="mb-1 text-muted small">Work Permit: {{ $employee->employeeWorkPermit ?? '-' }} (หมดอายุ: {{ $employee->workPermitExpiryDate ? \Carbon\Carbon::parse($employee->workPermitExpiryDate)->format('d M Y') : '-' }})</p>
                    <p class="mb-0 text-muted small">Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) หมดอายุ: {{ $employee->visaExpiryDate ? \Carbon\Carbon::parse($employee->visaExpiryDate)->format('d M Y') : '-' }} | 90-Day: {{ $employee->ninetyDayReportDate ? \Carbon\Carbon::parse($employee->ninetyDayReportDate)->format('d M Y') : '-' }}</p>
                </div>
            </div>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-outline-primary" title="แก้ไข"><i class="bi bi-pencil-fill"></i></a>
                <button type="button" class="btn btn-outline-warning terminate-employee-btn" data-id="{{ $employee->id }}" title="แจ้งออก/เลิกจ้าง"><i class="bi bi-person-dash-fill"></i></button>
                <button type="button" class="btn btn-outline-danger delete-employee-btn" data-id="{{ $employee->id }}" title="ลบ"><i class="bi bi-trash-fill"></i></button>
            </div>
        </div>
        @empty
            <p class="text-muted">ไม่พบข้อมูลพนักงาน</p>
        @endforelse
    </div>
</div>

{{-- Employment History Section --}}
<div class="content-section mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
        <h5>ประวัติการจ้างงาน</h5>
        <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" id="searchHistoryInput" placeholder="ค้นหาในประวัติ..." style="width: 200px;">
            <button type="button" class="btn btn-sm btn-outline-success export-btn" data-export-type="employmentHistory"><i class="bi bi-download"></i> ส่งออก</button>
        </div>
    </div>
    <div id="employmentHistoryList" class="vstack gap-3">
        {{-- Terminated employees will be loaded here via JavaScript --}}
         <p class="text-muted">ไม่มีประวัติการจ้างงาน</p>
    </div>
</div>

@endsection

{{-- Add/Edit Address Modal --}}
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">เพิ่ม/แก้ไขที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    @csrf
                    <input type="hidden" id="addressId" name="id">
                    <input type="hidden" id="addressType" name="type">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="addrNo" class="form-label">บ้านเลขที่</label>
                            <input type="text" class="form-control" id="addrNo" name="addrNo">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="addrMoo" class="form-label">หมู่</label>
                            <input type="text" class="form-control" id="addrMoo" name="addrMoo">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="addrSoi" class="form-label">ซอย</label>
                            <input type="text" class="form-control" id="addrSoi" name="addrSoi">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="addrRoad" class="form-label">ถนน</label>
                            <input type="text" class="form-control" id="addrRoad" name="addrRoad">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="addrProvince" class="form-label">จังหวัด</label>
                            <input type="text" class="form-control" id="addrProvince" name="addrProvince">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="addrDistrict" class="form-label">อำเภอ/เขต</label>
                            <input type="text" class="form-control" id="addrDistrict" name="addrDistrict">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="addrSubDistrict" class="form-label">ตำบล/แขวง</label>
                            <input type="text" class="form-control" id="addrSubDistrict" name="addrSubDistrict">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="addrZipCode" class="form-label">รหัสไปรษณีย์</label>
                            <input type="text" class="form-control" id="addrZipCode" name="addrZipCode">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" id="saveAddress">บันทึก</button>
            </div>
        </div>
    </div>
</div>

{{-- Terminate Employee Modal --}}
<div class="modal fade" id="terminateEmployeeModal" tabindex="-1" aria-labelledby="terminateEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminateEmployeeModalLabel">แจ้งออก / เลิกจ้าง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="terminateEmployeeForm">
                    <input type="hidden" id="terminateEmployeeId">
                    <div class="mb-3">
                        <label for="terminateDate" class="form-label">วันที่แจ้งออก / เลิกจ้าง</label>
                        <input type="date" class="form-control" id="terminateDate" required>
                    </div>
                     <div class="mb-3">
                        <label for="terminationReason" class="form-label">เหตุผล</label>
                        <textarea class="form-control" id="terminationReason" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="confirmTerminateEmployeeButton">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const employerId = '{{ $employer->id }}';

    // Address Management
    const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
    const addressForm = document.getElementById('addressForm'); // Assuming you will add this form to the modal
    const addressModalLabel = document.getElementById('addressModalLabel');

    document.querySelectorAll('.add-address-btn, .edit-address-btn').forEach(button => {
        button.addEventListener('click', function () {
            const addressId = this.dataset.id;
            const addressType = this.dataset.addressType || this.closest('.content-section').querySelector('.add-address-btn').dataset.addressType;

            addressForm.reset();
            addressForm.querySelector('#addressId').value = '';
            addressForm.querySelector('#addressType').value = addressType;

            if (addressId) { // Edit
                addressModalLabel.textContent = 'แก้ไขที่อยู่';
                fetch(`/addresses/${addressId}/edit`)
                    .then(response => response.json())
                    .then(data => {
                        addressForm.querySelector('#addressId').value = data.id;
                        Object.keys(data).forEach(key => {
                            const field = addressForm.querySelector(`#${key}`);
                            if(field) field.value = data[key];
                        });
                    });
            } else { // Add
                addressModalLabel.textContent = 'เพิ่มที่อยู่';
            }
            addressModal.show();
        });
    });

    document.getElementById('saveAddress').addEventListener('click', function() {
        const addressId = addressForm.querySelector('#addressId').value;
        const url = addressId ? `/addresses/${addressId}` : '/addresses';
        const method = addressId ? 'PUT' : 'POST';
        const formData = new FormData(addressForm);
        if(method === 'PUT') {
            formData.append('_method', 'PUT');
        }
        formData.append('addressable_id', employerId);
        formData.append('addressable_type', 'App\\Models\\Employer');


        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                location.reload(); // Simple reload to show changes
            }
        });
    });

    document.querySelectorAll('.delete-address-btn').forEach(button => {
        button.addEventListener('click', function() {
            if(confirm('Are you sure you want to delete this address?')) {
                const addressId = this.dataset.id;
                fetch(`/addresses/${addressId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        this.closest('.address-card').remove();
                    }
                });
            }
        });
    });


    // Terminate Employee
    const terminateModal = new bootstrap.Modal(document.getElementById('terminateEmployeeModal'));
    const terminateForm = document.getElementById('terminateEmployeeForm');
    const terminateEmployeeIdInput = document.getElementById('terminateEmployeeId');

    document.getElementById('employeeList').addEventListener('click', function (e) {
        if (e.target.closest('.terminate-employee-btn')) {
            const button = e.target.closest('.terminate-employee-btn');
            const employeeId = button.dataset.id;
            terminateEmployeeIdInput.value = employeeId;
            terminateModal.show();
        }
    });

    document.getElementById('confirmTerminateEmployeeButton').addEventListener('click', function () {
        const employeeId = terminateEmployeeIdInput.value;
        const terminateDate = document.getElementById('terminateDate').value;
        const terminationReason = document.getElementById('terminationReason').value;

        fetch(`/employees/${employeeId}/terminate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                terminateDate: terminateDate,
                terminationReason: terminationReason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Move employee card to history
                const employeeCard = document.querySelector(`.terminate-employee-btn[data-id='${employeeId}']`).closest('.employee-card');
                employeeCard.remove();
                // For simplicity, we just reload the page to see the history updated.
                // A more advanced implementation would dynamically create and append the history card.
                location.reload();
            } else {
                alert('Failed to terminate employee.');
            }
        })
        .catch(error => console.error('Error:', error));
    });

    /*
    // Filter Employees
    const searchInput = document.getElementById('searchEmployeeInput');
    const nationalitySelect = document.getElementById('searchEmployeeNationality');
    const mouGroupSelect = document.getElementById('searchEmployeeMOUGroup');
    const pinkCardSelect = document.getElementById('searchEmployeePinkCard');

    function filterEmployees() {
        const search = searchInput.value;
        const nationality = nationalitySelect.value;
        const mouGroup = mouGroupSelect.value;
        const pinkCard = pinkCardSelect.value;

        const url = new URL(`{{ route('employers.employees.filter', $employer->id) }}`);
        url.searchParams.append('search', search);
        url.searchParams.append('nationality', nationality);
        url.searchParams.append('mouGroup', mouGroup);
        url.searchParams.append('pinkCard', pinkCard);

        fetch(url)
            .then(response => response.json())
            .then(employees => {
                const employeeList = document.getElementById('employeeList');
                employeeList.innerHTML = '';
                if (employees.length > 0) {
                    employees.forEach(employee => {
                        const card = `
                        <div class="employee-card d-flex justify-content-between align-items-start gap-3">
                            <div class="d-flex align-items-center flex-grow-1">
                                <img src="${employee.employeePhoto ? '/storage/' + employee.employeePhoto : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC'}" class="employee-photo-thumb" alt="Employee Photo">
                                <div class="flex-grow-1">
                                    <p class="mb-0"><strong>${employee.employeeNameEn ?? 'No English Name'}</strong></p>
                                    <p class="mb-1 text-muted small">${employee.employeeNameTh ?? 'ไม่มีชื่อภาษาไทย'} (${employee.employeePosition ?? 'ไม่ระบุตำแหน่ง'})</p>
                                    <p class="mb-1 text-muted small">Passport: ${employee.employeePassport ?? '-'} (หมดอายุ: ${employee.passportExpiryDate ? new Date(employee.passportExpiryDate).toLocaleDateString('en-GB') : '-'})</p>
                                    <p class="mb-1 text-muted small">Work Permit: ${employee.employeeWorkPermit ?? '-'} (หมดอายุ: ${employee.workPermitExpiryDate ? new Date(employee.workPermitExpiryDate).toLocaleDateString('en-GB') : '-'})</p>
                                    <p class="mb-0 text-muted small">Visa (${employee.workPermitMOUGroup ?? '-'}) หมดอายุ: ${employee.visaExpiryDate ? new Date(employee.visaExpiryDate).toLocaleDateString('en-GB') : '-'} | 90-Day: ${employee.ninetyDayReportDate ? new Date(employee.ninetyDayReportDate).toLocaleDateString('en-GB') : '-'}</p>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a href="/employees/${employee.id}/edit" class="btn btn-outline-primary" title="แก้ไข"><i class="bi bi-pencil-fill"></i></a>
                                <button type="button" class="btn btn-outline-warning terminate-employee-btn" data-id="${employee.id}" title="แจ้งออก/เลิกจ้าง"><i class="bi bi-person-dash-fill"></i></button>
                                <button type="button" class="btn btn-outline-danger delete-employee-btn" data-id="${employee.id}" title="ลบ"><i class="bi bi-trash-fill"></i></button>
                            </div>
                        </div>`;
                        employeeList.innerHTML += card;
                    });
                } else {
                    employeeList.innerHTML = '<p class="text-muted">ไม่พบข้อมูลพนักงานที่ตรงกับเงื่อนไข</p>';
                }
                 document.getElementById('employeeTotalCount').textContent = employees.length;
            });
    }

    searchInput.addEventListener('input', filterEmployees);
    nationalitySelect.addEventListener('change', filterEmployees);
    mouGroupSelect.addEventListener('change', filterEmployees);
    pinkCardSelect.addEventListener('change', filterEmployees);
    */

    // Filter History
    const searchHistoryInput = document.getElementById('searchHistoryInput');

    function filterHistory() {
        const search = searchHistoryInput.value;
        const url = new URL(`{{ route('employers.history.filter', $employer->id) }}`);
        url.searchParams.append('search', search);

        fetch(url)
            .then(response => response.json())
            .then(employees => {
                const historyList = document.getElementById('employmentHistoryList');
                historyList.innerHTML = '';
                if (employees.length > 0) {
                    employees.forEach(employee => {
                        const card = `
                        <div class="employee-card bg-light d-flex justify-content-between align-items-start gap-3">
                             <div class="d-flex align-items-center flex-grow-1">
                                <img src="${employee.employeePhoto ? '/storage/' + employee.employeePhoto : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC'}" class="employee-photo-thumb" alt="Employee Photo">
                                <div class="flex-grow-1">
                                    <p class="mb-0"><strong>${employee.employeeNameEn ?? 'No English Name'}</strong></p>
                                    <p class="mb-1 text-muted small">${employee.employeeNameTh ?? ''} (${employee.employeePosition ?? 'ไม่ระบุตำแหน่ง'})</p>
                                    <p class="mb-0 text-danger small"><strong>เลิกจ้างวันที่:</strong> ${new Date(employee.terminated_at).toLocaleDateString('en-GB')} - ${employee.termination_reason || 'N/A'}</p>
                                </div>
                            </div>
                        </div>`;
                        historyList.innerHTML += card;
                    });
                } else {
                    historyList.innerHTML = '<p class="text-muted">ไม่มีประวัติการจ้างงาน</p>';
                }
            });
    }

    searchHistoryInput.addEventListener('input', filterHistory);
    // Initial load of history
    filterHistory();
});
</script>
@endpush
