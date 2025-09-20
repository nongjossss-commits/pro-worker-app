@extends('layouts.app')
@section('title', 'แก้ไขข้อมูลนายจ้าง')
@section('content')
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลนายจ้าง</h2>
    <form id="employerForm" action="{{ route('employers.update', $employer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <h5>ข้อมูลนายจ้าง</h5>
        <hr>
        {{-- Employer Form Fields --}}
        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label">ชื่อนายจ้าง (ไทย)</label><input type="text" class="form-control" name="employerNameTh" value="{{ $employer->employerNameTh }}"></div>
            <div class="col-md-6"><label class="form-label">ชื่อนายจ้าง (อังกฤษ)</label><input type="text" class="form-control" name="employerNameEn" value="{{ $employer->employerNameEn }}"></div>
        </div>
        {{-- ... other employer fields ... --}}
        <div class="mt-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกข้อมูลนายจ้าง</button>
        </div>
    </form>
</div>
<hr class="my-4">
<div class="d-flex ...">
    <h5>ข้อมูลลูกจ้าง</h5>
    <a href="..." class="btn btn-sm btn-outline-success">เพิ่มพนักงาน</a>
</div>
Task 27: ปฏิบัติการ Overwrite สามประสาน (The Final Trilogy Overwrite)
เป้าหมาย: เขียนทับไฟล์หลัก 3 ไฟล์ด้วยเวอร์ชันที่ถูกต้องสมบูรณ์ เพื่อแก้ไข Bug ทั้งหมดและทำให้ UI กลับมาทำงานได้ 100%
<div id="bulk-action-bar-employer" class="alert alert-info ...">
    {{-- Bulk action bar HTML --}}
</div>
<div id="employeeList">
    <div class="list-group">
    @forelse($employees as $employee)
        @include('partials._employee_card', ['employee' => $employee])
    @empty
        <p>ไม่พบข้อมูลลูกจ้าง</p>
    @endforelse
    </div>
</div>
<div class="mt-3">
    {{ $employees->links() }}
</div>
@endsection
@push('scripts')
<script>
    // Bulk Action JS for Employer page
</script>
@endpush
