@extends('layouts.app')

@section('title', 'เพิ่มข้อมูลนายจ้าง')

@section('content')
<div class="content-section">
    <h2 class="mb-4">เพิ่มข้อมูลนายจ้าง</h2>
    <form action="{{ route('employers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">ชื่อนายจ้าง (ไทย)</label>
                <input type="text" class="form-control" id="employerNameTh" name="employerNameTh" required>
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">ชื่อนายจ้าง (อังกฤษ)</label>
                <input type="text" class="form-control" id="employerNameEn" name="employerNameEn">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerId" class="form-label">รหัสนายจ้าง</label>
                <input type="text" class="form-control" id="employerId" name="employerId" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">เลขประจำตัวนายจ้าง</label>
                <input type="text" class="form-control" id="employerTaxId" name="employerTaxId">
            </div>
            <div class="col-md-6">
                <label for="businessType" class="form-label">ประเภทกิจการ</label>
                <input type="text" class="form-control" id="businessType" name="businessType">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">ผู้มีอำนาจลงนาม (ไทย)</label>
                <input type="text" class="form-control" id="signerNameTh" name="signerNameTh">
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">ผู้มีอำนาจลงนาม (อังกฤษ)</label>
                <input type="text" class="form-control" id="signerNameEn" name="signerNameEn">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">Type of Business</label>
                <input type="text" class="form-control" id="businessTypeEn" name="businessTypeEn">
            </div>
            <div class="col-md-6">
                <label for="regCapital" class="form-label">ทุนจดทะเบียน</label>
                <input type="text" class="form-control" id="regCapital" name="regCapital">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regDate" class="form-label">จดทะเบียนวันที่</label>
                <input type="date" class="form-control" id="regDate" name="regDate">
            </div>
            <div class="col-md-6">
                <label for="minimum_wage" class="form-label">ค่าแรงขั้นต่ำ</label>
                <input type="text" class="form-control" id="minimum_wage" name="minimum_wage">
            </div>
        </div>
        <hr>
        <h5>เอกสารแนบ</h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="document_company_registration" class="form-label">หนังสือรับรองบริษัท</label>
                <input type="file" class="form-control" id="document_company_registration" name="document_company_registration">
            </div>
            <div class="col-md-4">
                <label for="document_vat_registration" class="form-label">ภ.พ.20</label>
                <input type="file" class="form-control" id="document_vat_registration" name="document_vat_registration">
            </div>
            <div class="col-md-4">
                <label for="document_map" class="form-label">แผนที่</label>
                <input type="file" class="form-control" id="document_map" name="document_map">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">บันทึก</button>
        <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
@endsection
