@props([
    'tabs',
    'activeTab' => null,
    'routeName', // 'workflow.index' or 'production.index'
])

@php
    $isSuperAdmin = auth()->check() && auth()->user()->hasRole('super-admin');
@endphp

@foreach($tabs as $tab)
    <li class="nav-item">
        <a class="nav-link {{ isset($activeTab) && $activeTab->id === $tab->id ? 'active fw-bold shadow-sm' : 'bg-white border text-secondary' }} d-flex align-items-center gap-1"
           href="{{ route($routeName, ['tab' => $tab->slug]) }}"
           style="white-space: nowrap;">
            <span>{{ $tab->name }}</span>
            @if($isSuperAdmin)
                <span class="d-inline-flex align-items-center ms-1"
                      onclick="event.preventDefault(); event.stopPropagation(); editWorkTypeTab({{ $tab->id }}, '{{ addslashes($tab->name) }}', {{ $tab->allow_multiple_orders ? 'true' : 'false' }}, {{ $tab->is_system ? 'true' : 'false' }})"
                      title="{{ __('Edit Name') }}" style="font-size: 0.7rem; opacity: 0.6;">
                    <i class="bi bi-pencil-fill"></i>
                </span>
                @if(!$tab->is_system)
                    <span class="d-inline-flex align-items-center ms-1"
                          onclick="event.preventDefault(); event.stopPropagation(); deleteWorkTypeTab({{ $tab->id }}, '{{ addslashes($tab->name) }}', {{ (int) $tab->orders()->count() }})"
                          title="{{ __('Delete Tab') }}" style="font-size: 0.7rem; opacity: 0.6;">
                        <i class="bi bi-trash-fill"></i>
                    </span>
                @endif
            @endif
        </a>
    </li>
@endforeach

@if($isSuperAdmin)
    <li class="nav-item">
        <button type="button" class="btn btn-sm btn-outline-primary fw-bold"
                onclick="createWorkTypeTab()"
                title="{{ __('Add New Tab') }}"
                style="white-space: nowrap;">
            <i class="bi bi-plus-lg"></i>
        </button>
    </li>
@endif

@if($isSuperAdmin)
    @include('components.work-type-tab-scripts')
@endif
