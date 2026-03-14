@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.activity-logs.index') }}">Activity Logs</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.activity-logs.year', $year) }}">{{ $year }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Month {{ $month }}</li>
                </ol>
            </nav>
            @php
                $monthName = \Carbon\Carbon::create()->month((int)$month)->locale('th')->translatedFormat('F');
            @endphp
            <h2 class="fw-bold mb-3">เดือน {{ $monthName }} {{ $year }} - เลือกวัน (Day)</h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($days->count() > 0)
                <div class="row g-3">
                    @foreach($days as $day)
                    @php
                        $date = \Carbon\Carbon::createFromDate($year, (int)$month, $day);
                        $dayName = $date->locale('th')->translatedFormat('l');
                        $fullDate = $date->locale('th')->translatedFormat('d F Y');
                    @endphp
                    <div class="col-6 col-md-3 col-lg-2">
                        <a href="{{ route('admin.activity-logs.day', ['year' => $year, 'month' => $month, 'day' => $day]) }}" class="text-decoration-none">
                            <div class="card bg-white border hover-shadow transition h-100 text-center py-3">
                                <div class="card-body">
                                    <div class="display-4 fw-bold text-primary mb-1">{{ $day }}</div>
                                    <div class="text-dark small fw-bold">{{ $dayName }}</div>
                                    <div class="text-muted small">{{ $fullDate }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted">{{ __('ไม่มีข้อมูลสำหรับเดือนนี้') }}</p>
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
