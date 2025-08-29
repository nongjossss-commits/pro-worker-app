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
