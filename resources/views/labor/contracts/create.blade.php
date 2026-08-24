@extends('labor.layout')

@section('title', __('Issue Pro Worker Contract'))

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="fw-bold mb-0">{{ __('Issue a Pro Worker Contract') }} (เบิกสัญญา Pro Worker)</h4>
    </div>

    @if(!auth()->user()->labor_team_id)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ __('You have not been assigned to a Pro Walker Labor team yet. Please contact a Super Admin before issuing a contract.') }}
        </div>
    @elseif(!$template)
        <div class="alert alert-info">{{ __('No contract template has been set up yet. Please contact a Super Admin.') }}</div>
    @else
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">{{ $template->name }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('labor.contracts.store') }}">
                    @csrf
                    <input type="hidden" name="template_id" value="{{ $template->id }}">

                    @include('labor.contracts._fields', ['template' => $template, 'addressGroups' => $addressGroups])

                    <div class="form-text mb-3">{{ __('Please double-check the document before confirming.') }}</div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" formaction="{{ route('labor.contracts.preview') }}" formtarget="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>{{ __('Preview Document') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-file-earmark-check me-1"></i>{{ __('Issue Contract') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/proworker-address-picker.js') }}?v={{ @filemtime(public_path('js/proworker-address-picker.js')) }}"></script>
@endpush
