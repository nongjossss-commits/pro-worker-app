{{-- Pro Worker contracts pages: shared visual layer (public/css/proworker-contracts.css). --}}
@once
@push('styles')
<link rel="stylesheet" href="{{ asset('css/proworker-contracts.css') }}?v={{ @filemtime(public_path('css/proworker-contracts.css')) }}">
@endpush
@endonce
