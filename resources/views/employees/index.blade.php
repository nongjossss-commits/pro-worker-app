@extends('layouts.app')
@section('title', 'ข้อมูลลูกจ้าง')
@section('content')
<div class="p-4 p-md-5 content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">รายการข้อมูลลูกจ้างทั้งหมด</h2>
        <a href="{{ route('employers.index') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลใหม่</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            {{-- Filter Form --}}
        </div>
    </div>
    <div id="bulk-action-bar" class="alert alert-info d-flex justify-content-between align-items-center mb-4" style="display: none !important;">
        <div>
            <input class="form-check-input" type="checkbox" id="select-all-checkbox">
            <label class="form-check-label ms-2" for="select-all-checkbox">เลือกทั้งหมด (<span id="selected-count">0</span>)</label>
        </div>
        <button class="btn btn-primary btn-sm" disabled>ดำเนินการกับรายการที่เลือก</button>
    </div>
    <div id="employeeListContainer">
        <div class="list-group">
            @forelse($employees as $employee)
                @include('partials._employee_card', ['employee' => $employee])
            @empty
                <p class="text-center text-muted">ไม่พบข้อมูลลูกจ้าง</p>
            @endforelse
        </div>
    </div>
    <div class="mt-4">
        {{ $employees->links() }}
    </div>
</div>
@push('scripts')
<script>
    // Bulk Action JS
</script>
@endpush
@endsection
