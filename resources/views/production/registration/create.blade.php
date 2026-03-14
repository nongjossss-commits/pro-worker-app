@extends('layouts.app')

@section('title', 'เพิ่มพนักงาน (Registration Resolution)')

@section('content')
<div class="content-section">
    @if(isset($employer) && $employer)
        <h2 class="mb-4">เพิ่มพนักงาน (Registration) สำหรับ {{ $employer->employerNameTh }}</h2>
    @else
        <h2 class="mb-4">{{ __('เพิ่มพนักงาน (Registration)') }}</h2>
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

    {{-- Pass the specific action route for RegistrationController --}}
    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
        @csrf

        @include('employees.partials.create_form_partial_content')

        <div class="mt-4 d-flex justify-content-end">
            <a href="{{ route('production.registration.index') }}" class="btn btn-secondary me-2">{{ __('ยกเลิก') }}</a>
            <button type="submit" class="btn btn-primary">{{ __('บันทึกข้อมูลพนักงาน (Registration)') }}</button>
        </div>
    </form>
</div>

<!-- Cropper Modal -->
<x-cropper-modal />
@endsection

@push('scripts')
@include('employees.partials._edit_scripts')
@endpush
