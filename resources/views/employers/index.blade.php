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
            <form action="{{ route('employers.index') }}" method="GET" class="d-flex gap-2">
                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="25" @selected(request('per_page', 25) == 25)>25</option>
                    <option value="50" @selected(request('per_page') == 50)>50</option>
                    <option value="100" @selected(request('per_page') == 100)>100</option>
                </select>
                <input type="text" name="search" class="form-control form-control-sm w-auto" placeholder="ค้นหา..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary btn-sm">ค้นหา</button>
            </form>
            <a href="{{ route('employers.export') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Export</a>
            @can('create-employers')
            <a href="{{ route('employers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลใหม่</a>
            @endcan
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
                    <th>เจ้าของงาน</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody id="employer-table-body">
                @forelse ($employers as $employer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $employer->employerNameTh }}</td>
                        <td>{{ $employer->employerId }}</td>
                        <td>{{ $employer->businessType }}</td>
                        <td>{{ $employer->jobOwner->name ?? 'N/A' }}</td>
                        <td class="text-center">
                            @can('edit-employers')
                            <a href="{{ route('employers.edit', $employer) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            @endcan
                            @can('delete-employers')
                            <form action="{{ route('employers.destroy', $employer) }}" method="POST" class="d-inline delete-employer-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">ไม่พบข้อมูลนายจ้าง</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $employers->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteForms = document.querySelectorAll('.delete-employer-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'ยืนยันการลบ',
                text: "คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });
});
</script>
@endpush
