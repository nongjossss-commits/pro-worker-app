@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลนายจ้าง')

@section('content')
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลนายจ้าง</h2>
    <form action="{{ route('employers.update', $employer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">ชื่อนายจ้าง (ไทย)</label>
                <input type="text" class="form-control" id="employerNameTh" name="employerNameTh" value="{{ $employer->employerNameTh }}" required>
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">ชื่อนายจ้าง (อังกฤษ)</label>
                <input type="text" class="form-control" id="employerNameEn" name="employerNameEn" value="{{ $employer->employerNameEn }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerId" class="form-label">รหัสนายจ้าง</label>
                <input type="text" class="form-control" id="employerId" name="employerId" value="{{ $employer->employerId }}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">เลขประจำตัวนายจ้าง</label>
                <input type="text" class="form-control" id="employerTaxId" name="employerTaxId" value="{{ $employer->employerTaxId }}">
            </div>
            <div class="col-md-6">
                <label for="businessType" class="form-label">ประเภทกิจการ</label>
                <input type="text" class="form-control" id="businessType" name="businessType" value="{{ $employer->businessType }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">ผู้มีอำนาจลงนาม (ไทย)</label>
                <input type="text" class="form-control" id="signerNameTh" name="signerNameTh" value="{{ $employer->signerNameTh }}">
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">ผู้มีอำนาจลงนาม (อังกฤษ)</label>
                <input type="text" class="form-control" id="signerNameEn" name="signerNameEn" value="{{ $employer->signerNameEn }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">Type of Business</label>
                <input type="text" class="form-control" id="businessTypeEn" name="businessTypeEn" value="{{ $employer->businessTypeEn }}">
            </div>
            <div class="col-md-6">
                <label for="regCapital" class="form-label">ทุนจดทะเบียน</label>
                <input type="text" class="form-control" id="regCapital" name="regCapital" value="{{ $employer->regCapital }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regDate" class="form-label">จดทะเบียนวันที่</label>
                <input type="date" class="form-control" id="regDate" name="regDate" value="{{ $employer->regDate }}">
            </div>
            <div class="col-md-6">
                <label for="minimum_wage" class="form-label">ค่าแรงขั้นต่ำ</label>
                <input type="text" class="form-control" id="minimum_wage" name="minimum_wage" value="{{ $employer->minimum_wage }}">
            </div>
        </div>
        <hr>
        <h5>เอกสารแนบ</h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="document_company_registration" class="form-label">หนังสือรับรองบริษัท</label>
                <input type="file" class="form-control" id="document_company_registration" name="document_company_registration">
                @if ($employer->document_company_registration)
                    <a href="{{ asset('storage/' . $employer->document_company_registration) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                @endif
            </div>
            <div class="col-md-4">
                <label for="document_vat_registration" class="form-label">ภ.พ.20</label>
                <input type="file" class="form-control" id="document_vat_registration" name="document_vat_registration">
                @if ($employer->document_vat_registration)
                    <a href="{{ asset('storage/' . $employer->document_vat_registration) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                @endif
            </div>
            <div class="col-md-4">
                <label for="document_map" class="form-label">แผนที่</label>
                <input type="file" class="form-control" id="document_map" name="document_map">
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
