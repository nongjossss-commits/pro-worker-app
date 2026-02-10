@extends('layouts.app')

@section('title', 'เพิ่มพนักงานใหม่')

@section('content')
<div class="content-section">
    @if(isset($employer) && $employer)
        <h2 class="mb-4">เพิ่มพนักงานสำหรับ {{ $employer->employerNameTh }}</h2>
    @else
        <h2 class="mb-4">เพิ่มพนักงานใหม่</h2>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @if(isset($employer) && $employer)
            <input type="hidden" name="source_employer_id" value="{{ $employer->id }}">
        @endif

        @include('employees.partials._form_fields', [
            'prefix' => '',
            'employee' => null,
            'employers' => $employers ?? null
        ])

        <div class="mt-4 d-flex justify-content-end">
            <a href="{{ isset($employer) ? route('employers.edit', $employer->id) : route('employees.index') }}" class="btn btn-secondary me-2">ยกเลิก</a>
            <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน</button>
        </div>
    </form>
</div>

<!-- Cropper Modal -->
<x-cropper-modal />
@endsection

@push('scripts')
    @include('employees.partials._shared_scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.initEmployeeForm('');
        });
    </script>
@endpush
