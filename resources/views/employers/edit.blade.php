@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลนายจ้าง')

@section('content')
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลนายจ้าง</h2>
    <form action="{{ route('employers.update', $employer->id) }}" method="POST" enctype="multipart/form-data">
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

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">ชื่อนายจ้าง (ไทย)</label>
                <input type="text" class="form-control @error('employerNameTh') is-invalid @enderror" id="employerNameTh" name="employerNameTh" value="{{ old('employerNameTh', $employer->employerNameTh) }}" required>
                @error('employerNameTh')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">ชื่อนายจ้าง (อังกฤษ)</label>
                <input type="text" class="form-control @error('employerNameEn') is-invalid @enderror" id="employerNameEn" name="employerNameEn" value="{{ old('employerNameEn', $employer->employerNameEn) }}">
                @error('employerNameEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerId" class="form-label">รหัสนายจ้าง</label>
                <input type="text" class="form-control @error('employerId') is-invalid @enderror" id="employerId" name="employerId" value="{{ old('employerId', $employer->employerId) }}" required>
                @error('employerId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">เลขประจำตัวนายจ้าง</label>
                <input type="text" class="form-control @error('employerTaxId') is-invalid @enderror" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId', $employer->employerTaxId) }}">
                @error('employerTaxId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="businessType" class="form-label">ประเภทกิจการ</label>
                <input type="text" class="form-control @error('businessType') is-invalid @enderror" id="businessType" name="businessType" value="{{ old('businessType', $employer->businessType) }}">
                @error('businessType')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">ผู้มีอำนาจลงนาม (ไทย)</label>
                <input type="text" class="form-control @error('signerNameTh') is-invalid @enderror" id="signerNameTh" name="signerNameTh" value="{{ old('signerNameTh', $employer->signerNameTh) }}">
                @error('signerNameTh')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">ผู้มีอำนาจลงนาม (อังกฤษ)</label>
                <input type="text" class="form-control @error('signerNameEn') is-invalid @enderror" id="signerNameEn" name="signerNameEn" value="{{ old('signerNameEn', $employer->signerNameEn) }}">
                @error('signerNameEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">Type of Business</label>
                <input type="text" class="form-control @error('businessTypeEn') is-invalid @enderror" id="businessTypeEn" name="businessTypeEn" value="{{ old('businessTypeEn', $employer->businessTypeEn) }}">
                @error('businessTypeEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="regCapital" class="form-label">ทุนจดทะเบียน</label>
                <input type="text" class="form-control @error('regCapital') is-invalid @enderror" id="regCapital" name="regCapital" value="{{ old('regCapital', $employer->regCapital) }}">
                @error('regCapital')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regDate" class="form-label">จดทะเบียนวันที่</label>
                <input type="date" class="form-control @error('regDate') is-invalid @enderror" id="regDate" name="regDate" value="{{ old('regDate', $employer->regDate) }}">
                @error('regDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="minimum_wage" class="form-label">ค่าแรงขั้นต่ำ</label>
                <input type="text" class="form-control @error('minimum_wage') is-invalid @enderror" id="minimum_wage" name="minimum_wage" value="{{ old('minimum_wage', $employer->minimum_wage) }}">
                @error('minimum_wage')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <hr>
        <h5>เอกสารแนบ</h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="document_company_registration" class="form-label">หนังสือรับรองบริษัท</label>
                <input type="file" class="form-control @error('document_company_registration') is-invalid @enderror" id="document_company_registration" name="document_company_registration">
                @error('document_company_registration')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if ($employer->document_company_registration)
                    <a href="{{ asset('storage/' . $employer->document_company_registration) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                @endif
            </div>
            <div class="col-md-4">
                <label for="document_vat_registration" class="form-label">ภ.พ.20</label>
                <input type="file" class="form-control @error('document_vat_registration') is-invalid @enderror" id="document_vat_registration" name="document_vat_registration">
                @error('document_vat_registration')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if ($employer->document_vat_registration)
                    <a href="{{ asset('storage/' . $employer->document_vat_registration) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                @endif
            </div>
            <div class="col-md-4">
                <label for="document_map" class="form-label">แผนที่</label>
                <input type="file" class="form-control @error('document_map') is-invalid @enderror" id="document_map" name="document_map">
                @error('document_map')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if ($employer->document_map)
                    <a href="{{ asset('storage/' . $employer->document_map) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                @endif
            </div>
        </div>
        <button type="submit" class="btn btn-primary">อัปเดต</button>
        <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>

<div class="content-section mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>ที่อยู่ตามทะเบียน</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal" data-address-type="registered">
            เพิ่มที่อยู่
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>บ้านเลขที่</th>
                    <th>หมู่</th>
                    <th>ซอย</th>
                    <th>ถนน</th>
                    <th>จังหวัด</th>
                    <th>อำเภอ/เขต</th>
                    <th>ตำบล/แขวง</th>
                    <th>รหัสไปรษณีย์</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody id="registered-addresses-list">
                @foreach ($employer->addresses->where('type', 'registered') as $address)
                <tr id="address-{{ $address->id }}">
                    <td>{{ $address->addrNo }}</td>
                    <td>{{ $address->addrMoo }}</td>
                    <td>{{ $address->addrSoi }}</td>
                    <td>{{ $address->addrRoad }}</td>
                    <td>{{ $address->addrProvince }}</td>
                    <td>{{ $address->addrDistrict }}</td>
                    <td>{{ $address->addrSubDistrict }}</td>
                    <td>{{ $address->addrZipCode }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-warning btn-sm edit-address" data-id="{{ $address->id }}" data-bs-toggle="modal" data-bs-target="#addressModal">แก้ไข</button>
                        <button type="button" class="btn btn-danger btn-sm delete-address" data-id="{{ $address->id }}">ลบ</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="content-section mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>ที่อยู่สถานที่ทำงาน</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal" data-address-type="workplace">
            เพิ่มที่อยู่
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>บ้านเลขที่</th>
                    <th>หมู่</th>
                    <th>ซอย</th>
                    <th>ถนน</th>
                    <th>จังหวัด</th>
                    <th>อำเภอ/เขต</th>
                    <th>ตำบล/แขวง</th>
                    <th>รหัสไปรษณีย์</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody id="workplace-addresses-list">
                @foreach ($employer->addresses->where('type', 'workplace') as $address)
                <tr id="address-{{ $address->id }}">
                    <td>{{ $address->addrNo }}</td>
                    <td>{{ $address->addrMoo }}</td>
                    <td>{{ $address->addrSoi }}</td>
                    <td>{{ $address->addrRoad }}</td>
                    <td>{{ $address->addrProvince }}</td>
                    <td>{{ $address->addrDistrict }}</td>
                    <td>{{ $address->addrSubDistrict }}</td>
                    <td>{{ $address->addrZipCode }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-warning btn-sm edit-address" data-id="{{ $address->id }}" data-bs-toggle="modal" data-bs-target="#addressModal">แก้ไข</button>
                        <button type="button" class="btn btn-danger btn-sm delete-address" data-id="{{ $address->id }}">ลบ</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="content-section mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>ข้อมูลพนักงาน</h2>
        <a href="{{ route('employers.employees.create', ['employer' => $employer->id]) }}" class="btn btn-primary">เพิ่มพนักงาน</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>รูปภาพ</th>
                    <th>ชื่อ (ไทย)</th>
                    <th>เลขพาสปอร์ต</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                <tr>
                    <td>
                        @if ($employee->employeePhoto)
                            <img src="{{ asset('storage/' . $employee->employeePhoto) }}" alt="Employee Photo" width="50">
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $employee->employeeNameTh }}</td>
                    <td>{{ $employee->employeePassport }}</td>
                    <td class="text-center">
                        <a href="{{ route('employers.employees.edit', [$employer, $employee]) }}" class="btn btn-warning btn-sm">แก้ไข</a>
                        <form action="{{ route('employers.employees.destroy', [$employer, $employee]) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลพนักงานคนนี้?')">ลบ</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">ไม่พบข้อมูลพนักงาน</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<!-- Address Modal -->
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
                    <input type="hidden" name="addressable_id" value="{{ $employer->id }}">
                    <input type="hidden" name="addressable_type" value="employer">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addressModal = document.getElementById('addressModal');
    const addressForm = document.getElementById('addressForm');
    const saveAddressBtn = document.getElementById('saveAddress');
    const addressModalLabel = document.getElementById('addressModalLabel');

    // Handle modal opening for both add and edit
    addressModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const addressId = button.getAttribute('data-id'); // null for new
        const addressType = button.getAttribute('data-address-type');

        addressForm.reset();
        document.getElementById('addressId').value = '';
        if (addressType) {
            document.getElementById('addressType').value = addressType;
        }

        if (addressId) { // Editing
            addressModalLabel.textContent = 'แก้ไขที่อยู่';
            fetch(`/addresses/${addressId}/edit`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('addressId').value = data.id;
                    document.getElementById('addressType').value = data.type;
                    document.getElementById('addrNo').value = data.addrNo || '';
                    document.getElementById('addrMoo').value = data.addrMoo || '';
                    document.getElementById('addrSoi').value = data.addrSoi || '';
                    document.getElementById('addrRoad').value = data.addrRoad || '';
                    document.getElementById('addrProvince').value = data.addrProvince || '';
                    document.getElementById('addrDistrict').value = data.addrDistrict || '';
                    document.getElementById('addrSubDistrict').value = data.addrSubDistrict || '';
                    document.getElementById('addrZipCode').value = data.addrZipCode || '';
                });
        } else { // Adding
            addressModalLabel.textContent = 'เพิ่มที่อยู่';
        }
    });

    // Handle form submission (save)
    saveAddressBtn.addEventListener('click', function () {
        const addressId = document.getElementById('addressId').value;
        const url = addressId ? `/addresses/${addressId}` : '/addresses';
        const method = addressId ? 'PUT' : 'POST';

        const formData = new FormData(addressForm);
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        fetch(url, {
            method: 'POST', // HTML forms only support GET/POST. Laravel uses a hidden _method field for PUT/PATCH/DELETE.
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            const address = data;
            const addressListId = address.type + '-addresses-list';
            const addressList = document.getElementById(addressListId);
            let row = document.getElementById('address-' + address.id);

            const rowContent = `
                <td>${address.addrNo || ''}</td>
                <td>${address.addrMoo || ''}</td>
                <td>${address.addrSoi || ''}</td>
                <td>${address.addrRoad || ''}</td>
                <td>${address.addrProvince || ''}</td>
                <td>${address.addrDistrict || ''}</td>
                <td>${address.addrSubDistrict || ''}</td>
                <td>${address.addrZipCode || ''}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-warning btn-sm edit-address" data-id="${address.id}" data-bs-toggle="modal" data-bs-target="#addressModal">แก้ไข</button>
                    <button type="button" class="btn btn-danger btn-sm delete-address" data-id="${address.id}">ลบ</button>
                </td>
            `;

            if (row) { // Update existing row
                row.innerHTML = rowContent;
            } else { // Create new row
                row = document.createElement('tr');
                row.id = 'address-' + address.id;
                row.innerHTML = rowContent;
                addressList.appendChild(row);
            }

            var modal = bootstrap.Modal.getInstance(addressModal);
            modal.hide();
        })
        .catch(error => {
            console.error('Error:', error);
            // You could display errors to the user here
            let errorMsg = 'An error occurred.';
            if (error.errors) {
                errorMsg = Object.values(error.errors).map(e => e.join(' ')).join('\n');
            }
            alert(errorMsg);
        });
    });

    // Handle delete
    document.body.addEventListener('click', function(event) {
        if (event.target.classList.contains('delete-address')) {
            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบที่อยู่นี้?')) {
                const addressId = event.target.getAttribute('data-id');
                fetch(`/addresses/${addressId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('address-' + addressId).remove();
                    } else {
                        alert('Failed to delete address.');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    });
});
</script>
@endsection
