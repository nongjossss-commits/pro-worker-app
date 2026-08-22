@extends('layouts.app')

@section('title', __('Daily Report'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">{{ __('Daily Report') }} (รายงานประจำวัน)</h4>
    </div>

    @php $reportFormAction = route('finance.books-reports.index'); @endphp
    @include('financial.books-reports._content')
</div>
@endsection
