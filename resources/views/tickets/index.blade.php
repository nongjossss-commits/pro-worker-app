@extends('layouts.app')
@section('title', 'ส่งคำขอ/ติดตามงาน')
@section('content')
<div class="content-section">
    @if ($message = Session::get('success'))
    <div class="alert alert-success mb-4" role="alert">
        {{ $message }}
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">รายการคำขอของฉัน</h2>
        <div class="d-flex flex-column flex-md-row gap-2">
            {{-- Per Page Selection (Must match employers.index) --}}
            <form action="{{ route('tickets.index') }}" method="GET" class="d-flex gap-2">
                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="25" @selected(request('per_page', 25) == 25)>25</option>
                    <option value="50" @selected(request('per_page') == 50)>50</option>
                    <option value="100" @selected(request('per_page') == 100)>100</option>
                </select>
            </form>
            {{-- Create New Ticket Button --}}
            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> สร้างคำขอใหม่
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>หัวเรื่อง</th>
                    <th>สถานะ</th>
                    <th>วันที่สร้าง</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->id }}</td>
                    <td>{{ Str::limit($ticket->subject, 70) }}</td>
                    <td>
                        {{-- Use Accessors for Status Badge --}}
                        <span class="badge bg-{{ $ticket->status_color }}">
                            {{ $ticket->status_name }}
                        </span>
                    </td>
                    {{-- Format date consistently --}}
                    <td>{{ $ticket->created_at->format('d M Y H:i') }}</td>
                    <td class="text-center">
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> ดูรายละเอียด
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">คุณยังไม่ได้ส่งคำขอใดๆ</td>
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
