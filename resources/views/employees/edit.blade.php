@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลพนักงาน')

@section('content')
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลพนักงานสำหรับ {{ $employer->employerNameTh }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('employers.employees.update', [$employer, $employee]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employeeNameTh" class="form-label">ชื่อพนักงาน (ไทย)</label>
                <input type="text" class="form-control" id="employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh', $employee->employeeNameTh) }}" required>
            </div>
            <div class="col-md-6">
                <label for="employeeNameEn" class="form-label">ชื่อพนักงาน (อังกฤษ)</label>
                <input type="text" class="form-control" id="employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn', $employee->employeeNameEn) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employeeNationality" class="form-label">สัญชาติ</label>
                <input type="text" class="form-control" id="employeeNationality" name="employeeNationality" value="{{ old('employeeNationality', $employee->employeeNationality) }}">
            </div>
            <div class="col-md-6">
                <label for="employeePassport" class="form-label">เลขพาสปอร์ต</label>
                <input type="text" class="form-control" id="employeePassport" name="employeePassport" value="{{ old('employeePassport', $employee->employeePassport) }}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="passportExpiryDate" class="form-label">วันหมดอายุพาสปอร์ต</label>
                <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate', $employee->passportExpiryDate) }}">
            </div>
            <div class="col-md-6">
                <label for="employeeWorkPermit" class="form-label">ใบอนุญาตทำงาน</label>
                <input type="text" class="form-control" id="employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit', $employee->employeeWorkPermit) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="workPermitExpiryDate" class="form-label">วันหมดอายุใบอนุญาตทำงาน</label>
                <input type="date" class="form-control" id="workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate', $employee->workPermitExpiryDate) }}">
            </div>
            <div class="col-md-6">
                <label for="visaExpiryDate" class="form-label">วันหมดอายุวีซ่า</label>
                <input type="date" class="form-control" id="visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate', $employee->visaExpiryDate) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="ninetyDayReportDate" class="form-label">วันที่ต้องรายงาน 90 วัน</label>
                <input type="date" class="form-control" id="ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate', $employee->ninetyDayReportDate) }}">
            </div>
            <div class="col-md-6">
                <label for="employeePhoto" class="form-label">รูปภาพพนักงาน</label>
                <input type="file" class="form-control" id="employeePhoto" name="employeePhoto">
                @if ($employee->employeePhoto)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $employee->employeePhoto) }}" alt="Employee Photo" width="100">
                        <p class="form-text">รูปภาพปัจจุบัน</p>
                    </div>
                @endif
            </div>
        </div>

        <button type="submit" class="btn btn-primary">อัปเดต</button>
        <a href="{{ route('employers.edit', $employer) }}" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
@endsection
