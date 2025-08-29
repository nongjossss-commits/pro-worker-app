@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลบริษัทนำเข้า')

@section('content')
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลบริษัทนำเข้า</h2>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('importers.update', $importer->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerNameTh" class="form-label">ชื่อ บนจ. (ไทย)</label>
                <input type="text" class="form-control" id="importerNameTh" name="importerNameTh" value="{{ old('importerNameTh', $importer->importerNameTh) }}">
            </div>
            <div class="col-md-6">
                <label for="importerNameEn" class="form-label">ชื่อ บนจ. (อังกฤษ)</label>
                <input type="text" class="form-control" id="importerNameEn" name="importerNameEn" value="{{ old('importerNameEn', $importer->importerNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerId" class="form-label">เลขประจำตัว</label>
                <input type="text" class="form-control" id="importerId" name="importerId" value="{{ old('importerId', $importer->importerId) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="importerLicenseNo" class="form-label">เลขที่ใบอนุญาต</label>
                <input type="text" class="form-control" id="importerLicenseNo" name="importerLicenseNo" value="{{ old('importerLicenseNo', $importer->importerLicenseNo) }}">
            </div>
            <div class="col-md-4">
                <label for="importerLicenseIssueDate" class="form-label">วันที่ออกใบอนุญาต</label>
                <input type="date" class="form-control" id="importerLicenseIssueDate" name="importerLicenseIssueDate" value="{{ old('importerLicenseIssueDate', $importer->importerLicenseIssueDate) }}">
            </div>
            <div class="col-md-4">
                <label for="importerLicenseExpiryDate" class="form-label">วันสิ้นสุดใบอนุญาต</label>
                <input type="date" class="form-control" id="importerLicenseExpiryDate" name="importerLicenseExpiryDate" value="{{ old('importerLicenseExpiryDate', $importer->importerLicenseExpiryDate) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="importerSignerTh" class="form-label">คนเซ็น (ไทย)</label>
                <input type="text" class="form-control" id="importerSignerTh" name="importerSignerTh" value="{{ old('importerSignerTh', $importer->importerSignerTh) }}">
            </div>
            <div class="col-md-6">
                <label for="importerSignerEn" class="form-label">คนเซ็น (อังกฤษ)</label>
                <input type="text" class="form-control" id="importerSignerEn" name="importerSignerEn" value="{{ old('importerSignerEn', $importer->importerSignerEn) }}">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">อัปเดต</button>
        <a href="{{ route('importers.index') }}" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
@endsection
