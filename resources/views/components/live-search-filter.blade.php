@props([
    'endpoint',
    'name' => 'search',
    'value' => '',
    'placeholder' => '',
    'minChars' => 2,
    'idTarget' => null,
    'idValue' => '',
    'disabled' => false,
    // Name of a hidden "exact pick" field (e.g. employee_pick_id) that a picked
    // result's `pick` fills — kept across re-submits, cleared when typing again.
    'pickField' => null,
])

@php
    $inputId = 'lsf-' . uniqid();
@endphp

@if($idTarget)
    <input type="hidden" name="{{ $idTarget }}" id="{{ $inputId }}-id" value="{{ $idValue }}">
@endif
@if($pickField)
    @foreach(array_filter(array_map('trim', explode(',', $pickField))) as $pickName)
        <input type="hidden" name="{{ $pickName }}" value="{{ request($pickName) }}" data-live-search-pick>
    @endforeach
@endif

<input
    type="text"
    name="{{ $name }}"
    id="{{ $inputId }}"
    placeholder="{{ $placeholder }}"
    value="{{ $value }}"
    autocomplete="off"
    data-live-search-endpoint="{{ $endpoint }}"
    data-live-search-min-chars="{{ $minChars }}"
    data-live-search-empty="{{ __('ไม่พบข้อมูลที่ตรงกัน') }}"
    @if($idTarget) data-live-search-id-input="{{ $inputId }}-id" @endif
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'live-search-input']) }}
>
<div id="{{ $inputId }}-dropdown" class="live-search-dropdown bg-white border rounded shadow-lg d-none"></div>

@once
    <style>
        .live-search-dropdown {
            max-height: 360px;
            overflow-y: auto;
            z-index: 2000;
        }
        .live-search-result-item:hover,
        .live-search-result-item.active-result {
            background-color: #f0f4ff;
        }
    </style>
@endonce
