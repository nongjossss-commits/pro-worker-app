{{-- Page Expired — same design as every other error page (errors/minimal),
     keeping the original wording, the "log in again" button and the
     automatic redirect to the login page. --}}
@extends('errors::minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('หน้าเว็บหมดอายุ'))
@section('hint', __('ขออภัย เซสชั่นของคุณหมดอายุเนื่องจากไม่ได้ใช้งานเป็นเวลานาน') . ' ' . __('ระบบจะนำคุณกลับไปหน้าเข้าสู่ระบบในอีกสักครู่...'))

@section('actions')
    <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> {{ __('เข้าสู่ระบบใหม่ทันที') }}</a>
@endsection

@section('scripts')
    <script>
        // Auto-redirect to the login page after 3 seconds
        setTimeout(function () {
            window.location.href = "{{ route('login') }}";
        }, 3000);
    </script>
@endsection
