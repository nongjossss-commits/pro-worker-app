@extends('layouts.app')
@section('title', 'กล่องตั๋วงานตามนายจ้าง')
@section('content')
<div class="content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">กล่องตั๋วงานทั้งหมด</h2>
    </div>

    <div class="row g-4">
        @forelse ($employersWithTickets as $stat)
            <div class="col-12 col-md-6 col-xl-4">
                <a href="{{ route('admin.tickets.index', ['employer_id' => $stat->employer_user_id]) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                        <i class="bi bi-building text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title text-dark mb-1 fw-bold">
                                            {{ $stat->employerUser->employer->employerNameTh ?? $stat->employerUser->name }}
                                        </h5>
                                        <p class="card-text text-muted small">
                                            <i class="bi bi-clock me-1"></i> อัปเดตล่าสุด: {{ \Carbon\Carbon::parse($stat->last_activity)->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                @if($stat->unread_tickets_count > 0)
                                    <span class="badge bg-danger rounded-pill animate-pulse">
                                        {{ $stat->unread_tickets_count }} ข้อความใหม่
                                    </span>
                                @endif
                            </div>

                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">ตั๋วงานทั้งหมด</span>
                                    <span class="badge bg-secondary rounded-pill">{{ $stat->total_tickets }}</span>
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
</style>
@endsection
