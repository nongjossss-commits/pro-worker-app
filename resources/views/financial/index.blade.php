@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">{{ __('Financial Hub') }}</h1>
        <a href="{{ route('finance.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('Create Manual Bill') }}
        </a>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'overview' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'overview']) }}">
                <i class="bi bi-pie-chart me-1"></i> {{ __('Overview') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'workflow' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'workflow']) }}">
                <i class="bi bi-briefcase me-1"></i> {{ __('Workflow & Pre Production') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'registration' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'registration']) }}">
                <i class="bi bi-person-plus me-1"></i> {{ __('Registration Resolution') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'renewal' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'renewal']) }}">
                <i class="bi bi-arrow-repeat me-1"></i> {{ __('Renewal Resolution') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'manual' ? 'active' : '' }}" href="{{ route('finance.index', ['tab' => 'manual']) }}">
                <i class="bi bi-journal-text me-1"></i> {{ __('Manual Bills') }}
            </a>
        </li>
    </ul>

    @if($tab === 'overview')
    @include('financial.tabs.overview')
    @elseif($tab === 'workflow')
    @include('financial.tabs.workflow')
    @elseif($tab === 'registration')
    @include('financial.tabs.registration')
    @elseif($tab === 'renewal')
    @include('financial.tabs.renewal')
    @elseif($tab === 'manual')
    @include('financial.tabs.manual')
    @endif
</div>
@endsection
