@extends('labor.layout')

@section('title', __('New Contract Template'))

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">{{ __('Upload New Pro Worker Contract Template') }}</h5>
                </div>
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('labor.contract-templates.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Template Name') }} *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="{{ __('e.g. Pro Worker Service Contract 2026') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Blank Contract PDF') }} *</label>
                            <input type="file" name="file" class="form-control" accept="application/pdf" required>
                            <div class="form-text">{{ __('Max 10MB. After uploading you\'ll place fields on it in the builder.') }}</div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('labor.contract-templates.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Upload & Continue to Builder') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
