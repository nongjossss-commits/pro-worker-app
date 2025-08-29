@extends('layouts.app')

@section('title', 'ข้อมูลนายจ้าง')

@section('content')
<div class="content-section">
    @if ($message = Session::get('success'))
        <div class="alert alert-success mb-4" role="alert">
            {{ $message }}
        </div>
    @endif
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
         <h2 class="mb-3 mb-md-0">รายการข้อมูลนายจ้าง</h2>
         <div class="d-flex gap-2">
            <a href="{{ route('employers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลใหม่</a>
         </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>ชื่อนายจ้าง (ไทย)</th>
                    <th>รหัสนายจ้าง</th>
                    <th>ประเภทกิจการ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employers as $employer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $employer->employerNameTh }}</td>
                        <td>{{ $employer->employerId }}</td>
                        <td>{{ $employer->businessType }}</td>
                        <td class="text-center">
                            <a href="{{ route('employers.edit', $employer) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            <form action="{{ route('employers.destroy', $employer) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?')">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">ไม่พบข้อมูลนายจ้าง</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
