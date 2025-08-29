@extends('layouts.app')

@section('title', 'เพิ่มพนักงาน')

@section('content')
<div class="content-section">
    <h2 class="mb-4">เพิ่มพนักงานสำหรับ {{ $employer->employerNameTh }}</h2>
    <form action="{{ route('employers.employees.store', $employer) }}" method="POST">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employeeNameTh" class="form-label">ชื่อพนักงาน (ไทย)</label>
                <input type="text" class="form-control" id="employeeNameTh" name="employeeNameTh" required>
            </div>
            <div class="col-md-6">
                <label for="employeeNameEn" class="form-label">ชื่อพนักงาน (อังกฤษ)</label>
                <input type="text" class="form-control" id="employeeNameEn" name="employeeNameEn">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employeeNationality" class="form-label">สัญชาติ</label>
                <input type="text" class="form-control" id="employeeNationality" name="employeeNationality">
            </div>
            <div class="col-md-6">
                <label for="employeePassport" class="form-label">เลขพาสปอร์ต</label>
                <input type="text" class="form-control" id="employeePassport" name="employeePassport" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="passportExpiryDate" class="form-label">วันหมดอายุพาสปอร์ต</label>
                <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate">
            </div>
            <div class="col-md-6">
                <label for="employeeWorkPermit" class="form-label">ใบอนุญาตทำงาน</label>
                <input type="text" class="form-control" id="employeeWorkPermit" name="employeeWorkPermit">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="workPermitExpiryDate" class="form-label">วันหมดอายุใบอนุญาตทำงาน</label>
                <input type="date" class="form-control" id="workPermitExpiryDate" name="workPermitExpiryDate">
            </div>
            <div class="col-md-6">
                <label for="visaExpiryDate" class="form-label">วันหมดอายุวีซ่า</label>
                <input type="date" class="form-control" id="visaExpiryDate" name="visaExpiryDate">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="ninetyDayReportDate" class="form-label">วันที่ต้องรายงาน 90 วัน</label>
                <input type="date" class="form-control" id="ninetyDayReportDate" name="ninetyDayReportDate">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">บันทึก</button>
        <a href="{{ route('employers.edit', $employer) }}" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
@endsection
