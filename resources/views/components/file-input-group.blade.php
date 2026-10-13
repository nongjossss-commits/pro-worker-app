@props([
    'id',
    'name',
    'label' => null,
    'value' => null, // The file path
    'pdfRoute' => null,
    'description' => null,
    'descriptionValue' => null,
    'required' => false,
    // 'card' = compact attachment card for the attachment grids (see
    // partials/_record_tabs: .att-grid / .att-card). Default = original layout.
    'variant' => 'default',
    'number' => null, // card: number badge shown before the label
    'accept' => 'image/*,application/pdf',
])

@php
    // Clean label for javascript string to avoid syntax errors if it contains HTML or quotes
    $cleanLabel = htmlspecialchars(strip_tags($label ?? ''), ENT_QUOTES);
@endphp

@if($variant === 'card')
<div class="att-card {{ $value ? 'has-file' : '' }}" data-att-card>
    <div class="att-card-head">
        @if($number !== null)<span class="att-num">{{ $number }}</span>@endif
        <label for="{{ $id }}" class="att-title" title="{{ strip_tags($label ?? '') }}">
            {!! $label !!} @if($required)<span class="text-danger">*</span>@endif
        </label>
        @if($value)
            <span class="att-status ok"><i class="bi bi-check-circle-fill"></i> {{ __('Attached') }}</span>
        @else
            <span class="att-status none">{{ __('No file') }}</span>
        @endif
    </div>

    <div class="att-card-actions">
        @if($value)
            <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $value) }}', '{!! $cleanLabel !!}')"
               class="btn att-btn att-btn-view" title="{{ __('View') }}"><i class="bi bi-eye-fill"></i><span>{{ __('View') }}</span></a>
            @if($pdfRoute)
                <a href="{{ $pdfRoute }}" download class="btn att-btn att-btn-pdf" title="PDF"><i class="bi bi-file-earmark-pdf-fill"></i></a>
            @endif
            <button type="button" class="btn att-btn att-btn-edit" title="{{ __('Edit') }}"
                    onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: '{{ $id }}', initialUrl: '{{ asset('storage/' . $value) }}' } }))">
                <i class="bi bi-pencil-square"></i>
            </button>
        @endif
        <span class="att-spacer"></span>
        <label for="{{ $id }}" class="btn att-btn att-btn-pick" title="{{ $value ? __('Replace file') : __('Choose file') }}">
            <i class="bi bi-paperclip"></i><span>{{ $value ? __('Replace file') : __('Choose file') }}</span>
        </label>
        <button type="button" class="btn att-btn" title="{{ __('Scan / take photo') }}"
                onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: '{{ $id }}' } }))">
            <i class="bi bi-camera"></i>
        </button>
    </div>

    {{-- The real input (same id/name as before) — hidden visually, opened by the label above. --}}
    <input type="file"
           class="visually-hidden att-input @error($name) is-invalid @enderror"
           id="{{ $id }}"
           name="{{ $name }}"
           @if($accept) accept="{{ $accept }}" @endif
           multiple
           onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)"
           {{ $attributes }}>

    <div class="att-pending" hidden>
        <i class="bi bi-cloud-arrow-up-fill"></i>
        <span><strong>{{ __('New file') }}:</strong> <span class="att-pending-names"></span>
            <small>({{ __('uploaded when you press Save') }})</small></span>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    @if($description)
        <input type="text"
               class="form-control form-control-sm att-desc @error($description) is-invalid @enderror"
               id="{{ $description }}"
               name="{{ $description }}"
               value="{{ old($description, $descriptionValue ?? '') }}"
               placeholder="{{ __('Specify description...') }}">
        @error($description)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    @endif

    {{ $slot }}
</div>
@else
<div class="mb-3">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {!! $label !!}
            @if($required) <span class="text-danger">*</span> @endif
        </label>
    @endif

    @if($value)
        <div class="mb-2 d-flex gap-1 flex-wrap">
            <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $value) }}', '{!! $cleanLabel !!}')" class="btn btn-success btn-sm text-white">
                <i class="bi bi-eye-fill"></i> <span class="d-none d-sm-inline">{{ __('View') }}</span>
            </a>

            @if($pdfRoute)
                <a href="{{ $pdfRoute }}" download class="btn btn-danger btn-sm text-white">
                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                </a>
            @endif

            <!-- EDIT BUTTON -->
            <button type="button"
                    class="btn btn-warning btn-sm text-dark"
                    onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', {
                        detail: {
                            inputId: '{{ $id }}',
                            initialUrl: '{{ asset('storage/' . $value) }}'
                        }
                    }))">
                <i class="bi bi-pencil-square"></i> {{ __('Edit') }}
            </button>
        </div>
    @endif

    <div class="input-group input-group-sm">
        <input type="file"
               class="form-control form-control-sm @error($name) is-invalid @enderror"
               id="{{ $id }}"
               name="{{ $name }}"
               accept="image/*,application/pdf"
               multiple
               onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)"
               {{ $attributes }}>

        <button type="button"
                class="btn btn-outline-secondary"
                onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: '{{ $id }}' } }))">
            <i class="bi bi-camera"></i>
        </button>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    @if($description)
        <input type="text"
               class="form-control form-control-sm mt-2 @error($description) is-invalid @enderror"
               id="{{ $description }}"
               name="{{ $description }}"
               value="{{ old($description, $descriptionValue ?? '') }}"
               placeholder="{{ __('Specify description...') }}">
        @error($description)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    @endif
</div>
@endif
