@extends('layouts.app')

@section('title', __('Edit Agent'))

@section('content')
<div class="content-section">
    <h2 class="mb-4">{{ __('Edit Agent') }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('agents.update', $agent) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="agentNameEn" class="form-label">{{ __('Agent Name') }}</label>
                <input type="text" class="form-control" id="agentNameEn" name="agentNameEn" value="{{ old('agentNameEn', $agent->agentNameEn) }}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="agentLicense" class="form-label">{{ __('License Number') }}</label>
                <input type="text" class="form-control" id="agentLicense" name="agentLicense" value="{{ old('agentLicense', $agent->agentLicense) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="agentPhone" class="form-label">{{ __('Phone Number') }}</label>
                <input type="tel" class="form-control" id="agentPhone" name="agentPhone" value="{{ old('agentPhone', $agent->agentPhone) }}">
            </div>
            <div class="col-md-6">
                <label for="agentEmail" class="form-label">{{ __('Email') }}</label>
                <input type="email" class="form-control" id="agentEmail" name="agentEmail" value="{{ old('agentEmail', $agent->agentEmail) }}">
            </div>
        </div>
        <div class="mb-3">
            <label for="agentAddress" class="form-label">{{ __('Address') }}</label>
            <textarea class="form-control" id="agentAddress" name="agentAddress" rows="3">{{ old('agentAddress', $agent->agentAddress) }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
        <a href="{{ route('agents.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </form>
</div>
@endsection
