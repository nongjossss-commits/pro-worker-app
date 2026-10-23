{{--
    Employee details on the work cards (Workflow, Pre-Production,
    Registration / Renewal resolution) — the same up-to-6 fields Super Admin
    picks for the Employees menu cards (Super Admin → Settings → Employee
    Card Details, EmployeeCardFieldsService). Every filled value has a copy
    button: one click copies it, no text selecting (.pv-copy, handler in
    layouts/_app_scripts).

    @include('partials._work_card_fields', ['employee' => $employee])
--}}
@php $workCardFields = $employee ? \App\Services\EmployeeCardFieldsService::selected() : []; @endphp
@if(count($workCardFields))
    <div class="work-card-fields small mt-1">
        @foreach($workCardFields as $field)
            @php
                $wcfLabel = \App\Services\EmployeeCardFieldsService::label($field);
                $wcfValue = \App\Services\EmployeeCardFieldsService::value($employee, $field);
            @endphp
            <span class="wcf-item" title="{{ $wcfLabel }}: {{ $wcfValue }}">
                <span class="wcf-label">{{ $wcfLabel }}:</span>
                <span class="wcf-value">{{ $wcfValue }}</span>
                @if($wcfValue !== '-')
                    <button type="button" class="pv-copy wcf-copy" data-copy="{{ $wcfValue }}" title="{{ __('Copy') }} {{ $wcfLabel }}" aria-label="{{ __('Copy') }} {{ $wcfLabel }}"><i class="bi bi-copy"></i></button>
                @endif
            </span>
        @endforeach
    </div>
    @once
        <style>
            .work-card-fields { display: flex; flex-wrap: wrap; gap: .2rem .9rem; color: var(--bs-secondary-color); }
            .work-card-fields .wcf-item { display: inline-flex; align-items: center; gap: .25rem; min-width: 0; max-width: 100%; }
            .work-card-fields .wcf-label { white-space: nowrap; }
            .work-card-fields .wcf-value { color: var(--bs-body-color); font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        </style>
    @endonce
    @include('partials._card_copy_btn_style')
@endif
