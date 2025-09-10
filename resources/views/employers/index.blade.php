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
            <input type="text" name="search" id="employer-search-input" class="form-control form-control-sm" placeholder="ค้นหา..." value="{{ request('search') }}">
            <a href="{{ route('employers.export') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Export</a>
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
                        <td colspan="6" class="text-center text-muted">ไม่พบข้อมูลนายจ้าง</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('employer-search-input');
        const tableBody = document.getElementById('employer-table-body');
        if (searchInput && tableBody) {
            const tableRows = tableBody.getElementsByTagName('tr');

            searchInput.addEventListener('keyup', function() {
                const searchTerm = searchInput.value.toLowerCase();
                for (let row of tableRows) {
                    // Check all cells in the row for the search term
                    const rowText = row.textContent || row.innerText;
                    if (rowText.toLowerCase().includes(searchTerm)) {
                        row.style.display = ""; // Show row if it matches
                    } else {
                        row.style.display = "none"; // Hide row if it doesn't match
                    }
                }
            });
        }
    });
</script>
@endpush
