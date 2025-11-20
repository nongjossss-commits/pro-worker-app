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
        <div class="d-flex align-items-center gap-3">
            @if(request('employer_id'))
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> กลับ
                </a>
                <h2 class="mb-0">ตั๋วงาน: {{ $employerName ?? 'ไม่ระบุ' }}</h2>
            @else
                <h2 class="mb-0">กล่องตั๋วงานทั้งหมด</h2>
            @endif
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            {{-- Per Page Selection (Must match employers.index) --}}
            <form action="{{ route('admin.tickets.index') }}" method="GET" class="d-flex gap-2 align-items-center">
                @if(request('employer_id'))
                    <input type="hidden" name="employer_id" value="{{ request('employer_id') }}">
                @endif
                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="25" @selected(request('per_page', 25) == 25)>25</option>
                    <option value="50" @selected(request('per_page') == 50)>50</option>
                    <option value="100" @selected(request('per_page') == 100)>100</option>
                </select>
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
                <tr class="{{ $ticket->admin_unread_count > 0 ? 'table-info' : '' }}">
                    <td>{{ $ticket->id }}</td>
                    <td>
                        {{ Str::limit($ticket->subject, 50) }}
                        @if($ticket->admin_unread_count > 0)
                            <span class="badge bg-danger ms-2">ใหม่</span>
                        @endif
                    </td>
                    <td>
                        {{-- Display Company Name if available, otherwise User Name --}}
                        {{-- Uses optimized relationship loading --}}
                        @if($ticket->employerUser && $ticket->employerUser->employer)
                            {{ $ticket->employerUser->employer->employerNameTh ?? $ticket->employerUser->name }}
                            <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $ticket->employerUser->employer->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
                        @elseif($ticket->employerUser)
                             {{ $ticket->employerUser->name }}
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
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary position-relative">
                            <i class="bi bi-eye"></i> ดูรายละเอียด
                            @if($ticket->admin_unread_count > 0)
                                <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
                                    <span class="visually-hidden">Unread</span>
                                </span>
                            @endif
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
