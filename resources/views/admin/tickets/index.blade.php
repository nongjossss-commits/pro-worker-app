@extends('layouts.app')
@section('title', 'กล่องตั๋วงาน (Admin/Staff)')
@section('content')
<div class="content-section">
    @if ($message = Session::get('success'))
    <div class="alert alert-success mb-4" role="alert">
        {{ $message }}
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">กล่องตั๋วงานทั้งหมด</h2>
        <div class="d-flex gap-2">
            {{-- Per Page Selection (Must match employers.index) --}}
            <form action="{{ route('admin.tickets.index') }}" method="GET" class="d-flex gap-2">
                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="25" @selected(request('per_page', 25) == 25)>25</option>
                    <option value="50" @selected(request('per_page') == 50)>50</option>
                    <option value="100" @selected(request('per_page') == 100)>100</option>
                </select>
                {{-- Future Search/Filter inputs can be added here --}}
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>หัวเรื่อง</th>
                    <th>บริษัท/นายจ้าง</th>
                    <th>สถานะ</th>
                    <th>ผู้รับผิดชอบ</th>
                    <th>วันที่สร้าง</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->id }}</td>
                    <td>{{ Str::limit($ticket->subject, 50) }}</td>
                    <td>
                        {{-- Display Company Name if available, otherwise User Name --}}
                        {{-- Uses optimized relationship loading --}}
                        @if($ticket->employerUser)
                            {{-- Access employerNameTh via the nested relationship --}}
                            {{ $ticket->employerUser->employer->employerNameTh ?? $ticket->employerUser->name }}
                        @else
                            N/A (User Deleted)
                        @endif
                    </td>
                    <td>
                        {{-- Use Accessors for Status Badge --}}
                        <span class="badge bg-{{ $ticket->status_color }}">
                            {{ $ticket->status_name }}
                        </span>
                    </td>
                    <td>
                        {{ $ticket->assignedStaff->name ?? 'ยังไม่ได้มอบหมาย' }}
                    </td>
                    {{-- Format date consistently --}}
                    <td>{{ $ticket->created_at->format('d M Y H:i') }}</td>
                    <td class="text-center">
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> ดูรายละเอียด
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">ไม่พบตั๋วงาน</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{-- Pagination: Crucial to append existing query params (like per_page) --}}
            {{ $tickets->appends(request()->except('page'))->links() }}
        </div>
    </div>
</div>
@endsection
