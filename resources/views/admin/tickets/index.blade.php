@extends('layouts.admin')
@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">กล่องข้อความตั๋วงาน (Ticket Inbox)</h1>
        <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-plus"></i>
            </span>
            <span class="text">สร้างตั๋วใหม่</span>
        </a>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">รายการตั๋วทั้งหมด</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>หัวเรื่อง</th>
                            <th>นายจ้าง</th>
                            <th>ผู้รับผิดชอบ (Staff)</th>
                            <th>สถานะ</th>
                            <th>อัปเดตล่าสุด</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td>
                                @if ($ticket->employer)
                                    {{ $ticket->employer->company_name }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if ($ticket->assignedStaff)
                                    {{ $ticket->assignedStaff->name }}
                                @else
                                    <span class="text-warning">ยังไม่ได้รับมอบหมาย</span>
                                @endif
                            </td>
                            <td>
                                @if ($ticket->status == 'open')
                                    <span class="badge badge-success">เปิด (Open)</span>
                                @elseif ($ticket->status == 'in_progress')
                                    <span class="badge badge-info">กำลังดำเนินการ</span>
                                @elseif ($ticket->status == 'closed')
                                    <span class="badge badge-secondary">ปิด (Closed)</span>
                                @else
                                    <span class="badge badge-light">{{ ucfirst($ticket->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $ticket->updated_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> ดู/ตอบกลับ
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">ยังไม่มีตั๋วในระบบ</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
// Call the dataTables jQuery plugin
$(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[ 5, "desc" ]] // Sort by "Last Update" (column index 5) descending
    });
});
</script>
@endpush
@push('styles')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush
