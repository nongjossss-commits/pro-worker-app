@extends('layouts.app')

@section('title', 'สร้างงานใหม่')

@section('content')
<div class="content-section">
    <h2 class="mb-4">{{ __('สร้างงานใหม่จากพนักงาน') }}</h2>

    @if ($errors->{{ __('any())') }}<div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="#" method="POST">
        @csrf
        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
        <h5>{{ __('ข้อมูลพนักงาน') }}</h5>
        <hr>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employeeName" class="form-label">{{ __('ชื่อพนักงาน') }}</label>
                <input type="text" class="form-control" id="employeeName" name="employeeName" value="{{ $employee->employeeNameTh }}" readonly>
            </div>
            <div class="col-md-6">
                <label for="employerName" class="form-label">{{ __('ชื่อนายจ้าง') }}</label>
                <input type="text" class="form-control" id="employerName" name="employerName" value="{{ $employee->employer->employerNameTh }}" readonly>
            </div>
        </div>

        <h5 class="mt-4">{{ __('รายละเอียดงาน') }}</h5>
        <hr>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="jobTitle" class="form-label">{{ __('ชื่องาน') }}</label>
                <input type="text" class="form-control" id="jobTitle" name="jobTitle" value="{{ old('jobTitle') }}" required>
            </div>
            <div class="col-md-6">
                <label for="salary" class="form-label">{{ __('เงินเดือน') }}</label>
                <input type="number" class="form-control" id="salary" name="salary" value="{{ old('salary') }}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="jobDescription" class="form-label">{{ __('รายละเอียดงาน') }}</label>
                <textarea class="form-control" id="jobDescription" name="jobDescription" rows="3">{{ old('jobDescription') }}</textarea>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="startDate" class="form-label">{{ __('วันที่เริ่มงาน') }}</label>
                <input type="date" class="form-control" id="startDate" name="startDate" value="{{ old('startDate') }}" required>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">{{ __('สร้างงาน') }}</button>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">{{ __('ยกเลิก') }}</a>
        </div>
    </form>
</div>
@endsection
