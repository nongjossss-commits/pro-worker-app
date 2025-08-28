@extends('layouts.app')

@section('title', 'Employer List')

@section('content')
<div class="p-4 p-md-5 content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
         <h2 class="mb-3 mb-md-0">รายการข้อมูลนายจ้าง</h2>
         <div class="d-flex gap-2">
            <a href="{{ route('employers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลใหม่</a>
         </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>ชื่อนายจ้าง (ไทย)</th>
                    <th>รหัสนายจ้าง</th>
                    <th>ประเภทกิจการ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employers as $employer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $employer->employerNameTh }}</td>
                        <td>{{ $employer->employerId }}</td>
                        <td>{{ $employer->businessType }}</td>
                        <td class="text-center">
                            {{-- Action buttons will go here in a later task --}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">ยังไม่มีข้อมูลนายจ้าง</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
