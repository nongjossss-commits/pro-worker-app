@extends('layouts.app')

@section('title', 'เพิ่มข้อมูลนายจ้าง')

@section('content')
<div class="content-section">
    <h2 class="mb-4">เพิ่มข้อมูลนายจ้าง</h2>
    <form action="{{ route('employers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

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
                <input type="text" class="form-control @error('employerNameTh') is-invalid @enderror" id="employerNameTh" name="employerNameTh" value="{{ old('employerNameTh') }}" required>
                @error('employerNameTh')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="employerNameEn" class="form-label">ชื่อนายจ้าง (อังกฤษ)</label>
                <input type="text" class="form-control @error('employerNameEn') is-invalid @enderror" id="employerNameEn" name="employerNameEn" value="{{ old('employerNameEn') }}">
                @error('employerNameEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerId" class="form-label">รหัสนายจ้าง</label>
                <input type="text" class="form-control @error('employerId') is-invalid @enderror" id="employerId" name="employerId" value="{{ old('employerId') }}" required>
                @error('employerId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">เลขประจำตัวนายจ้าง</label>
                <input type="text" class="form-control @error('employerTaxId') is-invalid @enderror" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId') }}">
                @error('employerTaxId')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="businessType" class="form-label">ประเภทกิจการ</label>
                <input type="text" class="form-control @error('businessType') is-invalid @enderror" id="businessType" name="businessType" value="{{ old('businessType') }}">
                @error('businessType')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="signerNameTh" class="form-label">ผู้มีอำนาจลงนาม (ไทย)</label>
                <input type="text" class="form-control @error('signerNameTh') is-invalid @enderror" id="signerNameTh" name="signerNameTh" value="{{ old('signerNameTh') }}">
                @error('signerNameTh')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="signerNameEn" class="form-label">ผู้มีอำนาจลงนาม (อังกฤษ)</label>
                <input type="text" class="form-control @error('signerNameEn') is-invalid @enderror" id="signerNameEn" name="signerNameEn" value="{{ old('signerNameEn') }}">
                @error('signerNameEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="businessTypeEn" class="form-label">Type of Business</label>
                <input type="text" class="form-control @error('businessTypeEn') is-invalid @enderror" id="businessTypeEn" name="businessTypeEn" value="{{ old('businessTypeEn') }}">
                @error('businessTypeEn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="regCapital" class="form-label">ทุนจดทะเบียน</label>
                <input type="text" class="form-control @error('regCapital') is-invalid @enderror" id="regCapital" name="regCapital" value="{{ old('regCapital') }}">
                @error('regCapital')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="regDate" class="form-label">จดทะเบียนวันที่</label>
                <input type="date" class="form-control @error('regDate') is-invalid @enderror" id="regDate" name="regDate" value="{{ old('regDate') }}">
                @error('regDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="minimum_wage" class="form-label">ค่าแรงขั้นต่ำ</label>
                <input type="text" class="form-control @error('minimum_wage') is-invalid @enderror" id="minimum_wage" name="minimum_wage" value="{{ old('minimum_wage') }}">
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
            </div>
            <div class="col-md-4">
                <label for="document_vat_registration" class="form-label">ภ.พ.20</label>
                <input type="file" class="form-control @error('document_vat_registration') is-invalid @enderror" id="document_vat_registration" name="document_vat_registration">
                @error('document_vat_registration')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="document_map" class="form-label">แผนที่</label>
                <input type="file" class="form-control @error('document_map') is-invalid @enderror" id="document_map" name="document_map">
                @error('document_map')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-primary">บันทึก</button>
        <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
@endsection
