@extends('layouts.app')

@section('title', 'เพิ่มข้อมูลเอเจนซี่')

@section('content')
<div class="content-section">
    <h2 class="mb-4">{{ __('เพิ่มข้อมูลเอเจนซี่') }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('agents.store') }}" method="POST">
        @csrf
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="agentNameEn" class="form-label">{{ __('ชื่อเอเจนซี่ (Agent Name)') }}</label>
                <input type="text" class="form-control" id="agentNameEn" name="agentNameEn" value="{{ old('agentNameEn') }}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="agentLicense" class="form-label">License</label>
                <input type="text" class="form-control" id="agentLicense" name="agentLicense" value="{{ old('agentLicense') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="agentPhone" class="form-label">{{ __('เบอร์โทรศัพท์') }}</label>
                <input type="tel" class="form-control" id="agentPhone" name="agentPhone" value="{{ old('agentPhone') }}">
            </div>
            <div class="col-md-6">
                <label for="agentEmail" class="form-label">{{ __('อีเมล') }}</label>
                <input type="email" class="form-control" id="agentEmail" name="agentEmail" value="{{ old('agentEmail') }}">
            </div>
        </div>
        <div class="mb-3">
            <label for="agentAddress" class="form-label">{{ __('ที่อยู่') }}</label>
            <textarea class="form-control" id="agentAddress" name="agentAddress" rows="3">{{ old('agentAddress') }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('บันทึก') }}</button>
        <a href="{{ route('agents.index') }}" class="btn btn-secondary">{{ __('ยกเลิก') }}</a>
    </form>
</div>
@endsection
