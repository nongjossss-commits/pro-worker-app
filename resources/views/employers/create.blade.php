@extends('layouts.app')

@section('title', 'Add New Employer')

@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">เพิ่มข้อมูลนายจ้างใหม่</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('employers.store') }}" method="POST">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerNameTh" class="form-label">ชื่อนายจ้าง (ไทย) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="employerNameTh" name="employerNameTh" value="{{ old('employerNameTh') }}" required>
            </div>
            <div class="col-md-6">
                <label for="employerId" class="form-label">รหัสนายจ้าง <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="employerId" name="employerId" value="{{ old('employerId') }}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employerTaxId" class="form-label">เลขประจำตัวนายจ้าง</label>
                <input type="text" class="form-control" id="employerTaxId" name="employerTaxId" value="{{ old('employerTaxId') }}">
            </div>
            <div class="col-md-6">
                <label for="businessType" class="form-label">ประเภทกิจการ</label>
                <input type="text" class="form-control" id="businessType" name="businessType" value="{{ old('businessType') }}">
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection
