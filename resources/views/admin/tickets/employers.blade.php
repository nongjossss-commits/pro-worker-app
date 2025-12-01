@extends('layouts.app')
@section('title', 'กล่องตั๋วงานตามนายจ้าง')
@section('content')
<div class="content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">กล่องตั๋วงานทั้งหมด</h2>

        {{-- Control Bar (Search, View Toggle, Per Page) --}}
        <div class="d-flex flex-column flex-md-row gap-2">
            <form method="GET" action="{{ route('admin.tickets.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                {{-- Preserve View State --}}
                <input type="hidden" name="view" value="{{ $view }}">

                {{-- Search Input --}}
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="ค้นหานายจ้าง..." value="{{ $search }}">
                </div>

                {{-- Hidden Toggle Button (New) --}}
                <a href="{{ route('admin.tickets.index', array_merge(request()->query(), ['hidden' => request('hidden') ? null : 1, 'page' => 1])) }}"
                   class="btn btn-sm {{ request('hidden') ? 'btn-secondary' : 'btn-outline-secondary' }}"
                   title="{{ request('hidden') ? 'แสดงกล่องตั๋วงานปกติ' : 'แสดงกล่องตั๋วงานที่ถูกซ่อน' }}">
                   <i class="bi {{ request('hidden') ? 'bi-eye' : 'bi-eye-slash' }}"></i>
                   {{ request('hidden') ? 'กลับไปหน้าหลัก' : 'กล่องที่ถูกซ่อน' }}
                </a>

                {{-- View Toggle Buttons --}}
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.tickets.index', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn {{ $view == 'card' ? 'btn-primary' : 'btn-outline-secondary' }}" title="Card View">
                        <i class="bi bi-grid-fill"></i>
                    </a>
                    <a href="{{ route('admin.tickets.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn {{ $view == 'table' ? 'btn-primary' : 'btn-outline-secondary' }}" title="Table View">
                        <i class="bi bi-list-ul"></i>
                    </a>
                </div>

                {{-- Per Page Dropdown --}}
                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    @foreach([12, 24, 48, 100] as $option)
                        <option value="{{ $option }}" @selected($perPage == $option)>{{ $option }} / หน้า</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($view == 'card')
        {{-- Card View --}}
        <div class="row g-4">
            @forelse ($employersWithTickets as $user)
                <div class="col-12 col-md-6 col-xl-4">
                    <a href="{{ route('admin.tickets.index', ['employer_id' => $user->id]) }}" class="text-decoration-none">
                        <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                            <i class="bi bi-building text-primary fs-4"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title text-dark mb-1 fw-bold">
                                                {{ $user->employer->employerNameTh ?? $user->name }}
                                            </h5>
                                            <p class="card-text text-muted small">
                                                <i class="bi bi-clock me-1"></i> อัปเดตล่าสุด: {{ \Carbon\Carbon::parse($user->last_activity)->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                    @if($user->unread_tickets_count > 0)
                                        <span class="badge bg-danger rounded-pill animate-pulse">
                                            {{ $user->unread_tickets_count }} ข้อความใหม่
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-auto pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">ตั๋วงานทั้งหมด</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-secondary rounded-pill">{{ $user->total_tickets }}</span>
                                            {{-- V2.5.1: Hide/Unhide Button --}}
                                            @if($user->is_ticket_hidden)
                                                <form action="{{ route('admin.tickets.unhideEmployer', $user->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="button" class="btn btn-sm btn-outline-success border-0 p-0 btn-submit-swal" title="ยกเลิกการซ่อน"
                                                            data-swal-title="ยืนยันการยกเลิก"
                                                            data-swal-text="ต้องการนำกล่องตั๋วงานนี้กลับมาแสดงในรายการหลัก?"
                                                            data-swal-icon="question"
                                                            data-swal-confirm-text="ใช่, นำกลับมา">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.tickets.hideEmployer', $user->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-0 btn-submit-swal" title="ซ่อน"
                                                            data-swal-title="ยืนยันการซ่อน"
                                                            data-swal-text="ซ่อนกล่องตั๋วงานนี้? (จะแสดงใหม่เมื่อมีการอัปเดต)"
                                                            data-swal-icon="warning"
                                                            data-swal-confirm-text="ใช่, ซ่อนเลย">
                                                        <i class="bi bi-eye-slash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        ยังไม่มีข้อมูลตั๋วงาน
                    </div>
                </div>
            @endforelse
        </div>
    @else
        {{-- Table View --}}
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>นายจ้าง/บริษัท</th>
                            <th class="text-center">ข้อความใหม่</th>
                            <th class="text-center">ตั๋วงานทั้งหมด</th>
                            <th>อัปเดตล่าสุด</th>
                            <th class="text-end">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employersWithTickets as $user)
                            <tr>
                                <td class="text-center text-muted">{{ $loop->iteration + ($employersWithTickets->currentPage() - 1) * $employersWithTickets->perPage() }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-light text-primary me-3">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $user->employer->employerNameTh ?? $user->name }}</div>
                                            <div class="text-muted small">{{ $user->employer->employerNameEn ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($user->unread_tickets_count > 0)
                                        <span class="badge bg-danger rounded-pill">
                                            {{ $user->unread_tickets_count }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill">{{ $user->total_tickets }}</span>
                                </td>
                                <td class="text-muted small">
                                    {{ \Carbon\Carbon::parse($user->last_activity)->format('d M Y H:i') }}
                                    <br>
                                    ({{ \Carbon\Carbon::parse($user->last_activity)->diffForHumans() }})
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        <a href="{{ route('admin.tickets.index', ['employer_id' => $user->id]) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye me-1"></i> ดูตั๋วงาน
                                        </a>
                                        {{-- V2.5.1: Hide/Unhide Button --}}
                                        @if($user->is_ticket_hidden)
                                            <form action="{{ route('admin.tickets.unhideEmployer', $user->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="button" class="btn btn-sm btn-outline-success btn-submit-swal" title="ยกเลิกการซ่อน"
                                                        data-swal-title="ยืนยันการยกเลิก"
                                                        data-swal-text="ต้องการนำกล่องตั๋วงานนี้กลับมาแสดงในรายการหลัก?"
                                                        data-swal-icon="question"
                                                        data-swal-confirm-text="ใช่, นำกลับมา">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.tickets.hideEmployer', $user->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-submit-swal" title="ซ่อน"
                                                        data-swal-title="ยืนยันการซ่อน"
                                                        data-swal-text="ซ่อนกล่องตั๋วงานนี้? (จะแสดงใหม่เมื่อมีการอัปเดต)"
                                                        data-swal-icon="warning"
                                                        data-swal-confirm-text="ใช่, ซ่อนเลย">
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    ยังไม่มีข้อมูลตั๋วงาน
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-4">
        {{ $employersWithTickets->links() }}
    </div>
</div>

<style>
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .animate-pulse {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-submit-swal').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            if (!form) return;

            const title = this.dataset.swalTitle;
            const text = this.dataset.swalText;
            const icon = this.dataset.swalIcon;
            const confirmText = this.dataset.swalConfirmText;

            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: (icon === 'danger' || icon === 'warning') ? '#d33' : '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText,
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
@endpush
