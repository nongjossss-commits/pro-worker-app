@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลนายจ้าง')

@section('content')
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลนายจ้าง</h2>
    <form action="{{ route('employers.update', $employer->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">ชื่อนายจ้าง (ไทย)</label>
                <input type="text" class="form-control" id="employerNameTh" name="employerNameTh" value="{{ $employer->employerNameTh }}" required>
            </div>
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
        <button type="submit" class="btn btn-primary">อัปเดต</button>
        <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
@endsection
