@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.activity-logs.index') }}">{{ __('Activity Logs') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $year }}</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-3">ปี {{ $year }} - เลือกเดือน (Month)</h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($months->count() > 0)
                <div class="row g-3">
                    @foreach($months as $month)
                    @php
                        $monthName = \Carbon\Carbon::create()->month((int)$month)->locale('th')->translatedFormat('F');
                    @endphp
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('admin.activity-logs.month', ['year' => $year, 'month' => $month]) }}" class="text-decoration-none">
                            <div class="card bg-light hover-shadow transition h-100 text-center py-4">
                                <div class="card-body">
                                    <i class="bi bi-folder-fill text-info display-4 mb-2"></i>
                                    <h4 class="mb-0 text-dark fw-bold">{{ $monthName }}</h4>
                                    <small class="text-muted">เดือน {{ $month }}</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted">{{ __('ไม่มีข้อมูลสำหรับปีนี้') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
        transition: all 0.2s;
    }
</style>
@endsection
