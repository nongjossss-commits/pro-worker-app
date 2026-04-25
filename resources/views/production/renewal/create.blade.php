@extends('layouts.app')

@section('title', 'เพิ่มพนักงาน (Renewal Resolution)')

@section('content')
<div class="content-section">
    @if(isset($employer) && $employer)
        <h2 class="mb-4">เพิ่มพนักงาน (Renewal) สำหรับ {{ $employer->employerNameTh }}</h2>
    @else
        <h2 class="mb-4">เพิ่มพนักงาน (Renewal)</h2>
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

    {{-- Pass the specific action route for RenewalController --}}
    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
        @csrf

        @include('employees.partials.create_form_partial_content')

        <div class="mt-4 d-flex justify-content-end">
            <a href="{{ route('production.renewal.operations', ['resolutionTab' => $currentTab->id]) }}" class="btn btn-secondary me-2">ยกเลิก</a>
            <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน (Renewal)</button>
        </div>
    </form>
</div>

<!-- Cropper Modal -->
<x-cropper-modal />
@endsection

@push('scripts')
@include('employees.partials._edit_scripts')
@endpush
