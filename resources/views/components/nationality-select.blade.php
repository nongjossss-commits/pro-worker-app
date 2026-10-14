{{--
    Nationality picker — a normal <select> (same name/id/value/change events as
    before, so existing scripts and x-model keep working) listing every
    nationality (App\Helpers\NationalityList), turned into a type-to-search box
    with a full list behind the ▾ button and the user's recent picks on focus
    by public/js/nationality-select.js.
    Values stay the Thai names already stored (เมียนมา, ลาว, กัมพูชา, เวียดนาม…).

    Usage: <x-nationality-select name="employeeNationality" id="…" :selected="$value"
                                  :empty="__('-- Select Nationality --')" class="form-select" />
    `empty` = text of the "nothing chosen" option (filters: "-- All Nationalities --"); null = no such option.
--}}
@props(['selected' => '', 'empty' => null])
@php
    $selected = (string) ($selected ?? '');
    $natOptions = \App\Helpers\NationalityList::options();
    $known = array_column($natOptions, 'value');
@endphp
<select {{ $attributes->merge(['class' => 'js-nationality-select']) }}
        data-nat-recent="{{ __('Recently selected') }}"
        data-nat-main="{{ __('Main nationalities') }}"
        data-nat-all="{{ __('All nationalities') }}"
        data-nat-none="{{ __('No matching nationality') }}"
        data-nat-placeholder="{{ __('Type to search nationality...') }}">
    @if($empty !== null)
        <option value="" data-search="">{{ $empty }}</option>
    @endif
    @if($selected !== '' && ! in_array($selected, $known, true))
        {{-- A value saved before the full list existed — keep it selectable as-is. --}}
        <option value="{{ $selected }}" data-search="{{ mb_strtolower($selected) }}" selected>{{ $selected }}</option>
    @endif
    @foreach($natOptions as $opt)
        <option value="{{ $opt['value'] }}" data-search="{{ $opt['search'] }}" @if($opt['main']) data-main="1" @endif @selected($selected === $opt['value'])>{{ $opt['label'] }}</option>
    @endforeach
</select>
@once
    <style>
        .nat-combo { display: inline-block; vertical-align: middle; }
        .nat-combo:not([style*="width"]) { display: block; width: 100%; }
        .nat-combo-input { padding-right: 2rem; text-overflow: ellipsis; }
        .nat-combo-toggle {
            position: absolute; top: 0; right: 0; bottom: 0; width: 2rem; border: 0; background: transparent;
            color: var(--bs-secondary-color); display: flex; align-items: center; justify-content: center;
        }
        .nat-combo-toggle:hover { color: var(--bs-body-color); }
        .nat-combo-list {
            position: fixed; z-index: 2100; overflow-y: auto; padding: .25rem 0;
            background: var(--bs-body-bg); color: var(--bs-body-color);
            border: 1px solid var(--bs-border-color); border-radius: .5rem; box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .18);
        }
        .nat-combo-head {
            padding: .35rem .75rem .15rem; font-size: .7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .03em; color: var(--bs-secondary-color);
        }
        .nat-combo-item {
            display: flex; align-items: center; gap: .5rem; padding: .3rem .75rem; cursor: pointer;
            font-size: .875rem; line-height: 1.6; white-space: nowrap;
        }
        .nat-combo-item.is-active { background: var(--bs-tertiary-bg); }
        .nat-combo-item.is-selected { font-weight: 600; color: var(--bs-primary); }
        .nat-combo-none { padding: .6rem .75rem; font-size: .85rem; color: var(--bs-secondary-color); }
    </style>
@endonce
