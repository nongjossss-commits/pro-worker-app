@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-3">{{ __('Activity Logs (บันทึกการปฏิบัติงาน)') }}</h2>
            <p class="text-muted">{{ __('เลือกปีเพื่อดูบันทึกข้อมูลการทำงาน หรือค้นหาวันที่โดยตรง') }}</p>
        </div>
    </div>

    <div class="row">
        <!-- Date Search Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-search me-2 text-primary"></i>{{ __('ค้นหาตามวันที่') }}</h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <form action="{{ route('admin.activity-logs.search') }}" method="GET">
                        <div class="mb-3">
                            <label for="date" class="form-label">{{ __('เลือกวันที่ต้องการดู') }}</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('ไปที่วันที่เลือก') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Year Selection -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-folder me-2 text-warning"></i>{{ __('เลือกปี (Year)') }}</h5>
                </div>
                <div class="card-body">
                    @if($years->count() > 0)
                        <div class="row g-3">
                            @foreach($years as $year)
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ route('admin.activity-logs.year', $year) }}" class="text-decoration-none">
                                    <div class="card bg-light hover-shadow transition h-100 text-center py-4">
                                        <div class="card-body">
                                            <i class="bi bi-folder-fill text-warning display-4 mb-2"></i>
                                            <h4 class="mb-0 text-dark fw-bold">{{ $year }}</h4>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted display-1"></i>
                            <p class="mt-3 text-muted">{{ __('ยังไม่มีบันทึกกิจกรรม') }}</p>
                        </div>
                    @endif
                </div>
            </div>
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
