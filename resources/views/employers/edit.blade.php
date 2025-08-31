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
