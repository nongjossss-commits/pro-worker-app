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
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all position-relative">
                        {{-- Action Dropdown --}}
                        <div class="position-absolute top-0 end-0 p-2">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" id="dropdownMenuButton-{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton-{{ $user->id }}">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.tickets.index', ['employer_id' => $user->id]) }}">
                                            <i class="bi bi-folder2-open me-2"></i> ดูตั๋วงานทั้งหมด
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('admin.users.hide_tickets', $user) }}" method="POST" class="hide-ticket-box-form d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-warning">
                                                <i class="bi bi-eye-slash me-2"></i> ซ่อนกล่องตั๋วงาน
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {{-- Card Body (wrapped in a link) --}}
                        <a href="{{ route('admin.tickets.index', ['employer_id' => $user->id]) }}" class="text-decoration-none text-dark d-flex flex-column flex-grow-1">
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
                                        <span class="badge bg-secondary rounded-pill">{{ $user->total_tickets }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
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
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.tickets.index', ['employer_id' => $user->id]) }}">
                                                    <i class="bi bi-folder2-open me-2"></i> ดูตั๋วงานทั้งหมด
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.users.hide_tickets', $user) }}" method="POST" class="hide-ticket-box-form d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-warning">
                                                        <i class="bi bi-eye-slash me-2"></i> ซ่อนกล่องตั๋วงาน
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
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
    document.querySelectorAll('.hide-ticket-box-form').forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "คุณต้องการซ่อนกล่องตั๋วงานนี้ใช่ไหม? การดำเนินการนี้สามารถยกเลิกได้เมื่อมีการอัปเดตใหม่",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, ซ่อนเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            })
        });
    });
});
</script>
@endpush
