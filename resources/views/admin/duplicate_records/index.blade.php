@extends('layouts.app')

@section('title', __('Duplicate Records'))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('Duplicate Records') }}</h1>
            <p class="text-muted mb-0">{{ __('Employees or employers that share the same passport, work permit, pink card, ID number, or tax ID — review and fix each group below.') }}</p>
        </div>
        <span class="badge bg-danger fs-6">{{ count($groups) }}</span>
    </div>

    @if(empty($groups))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
                <p class="mt-3 mb-0 text-muted">{{ __('No duplicate records found right now.') }}</p>
            </div>
        </div>
    @else
        @foreach($groups as $group)
            <div class="card mb-3">
                <div class="card-header bg-white d-flex flex-wrap align-items-center gap-2">
                    <span class="badge {{ $group['model'] === 'employee' ? 'bg-primary' : 'bg-info' }}">
                        {{ $group['model'] === 'employee' ? __('Employee') : __('Employer') }}
                    </span>
                    <span class="fw-bold">{{ $group['label'] }}:</span>
                    <span class="text-danger fw-bold">{{ $group['value'] }}</span>
                    <span class="text-muted small ms-auto">{{ count($group['records']) }} {{ __('records') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($group['records'] as $record)
                            <div class="col-md-6 col-lg-4">
                                <div class="d-flex align-items-center gap-2 border rounded p-2 h-100">
                                    @if($record['photo_url'])
                                        <img src="{{ $record['photo_url'] }}" style="width:48px;height:48px;object-fit:cover;border-radius:50%;flex-shrink:0;">
                                    @else
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                                            <i class="bi bi-building text-muted"></i>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold text-truncate">
                                            {{ $record['name'] }}
                                            @if($record['terminated'])
                                                <span class="badge bg-secondary">{{ __('Terminated') }}</span>
                                            @endif
                                        </div>
                                        <div class="small text-muted text-truncate">{{ $record['sub_label'] }}</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-info flex-shrink-0 btn-preview" data-model-type="{{ $group['model'] }}" data-model-id="{{ $record['id'] }}" title="{{ __('Preview Data') }}">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <a href="{{ $record['edit_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary flex-shrink-0" title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
