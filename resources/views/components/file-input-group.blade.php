@props([
    'id',
    'name',
    'label' => null,
    'value' => null, // The file path
    'pdfRoute' => null,
    'description' => null,
    'descriptionValue' => null,
    'required' => false
])

<div class="mb-3">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if($required) <span class="text-danger">*</span> @endif
        </label>
    @endif

    @if($value)
        <div class="mb-2 d-flex gap-1 flex-wrap">
            <a href="{{ asset('storage/' . $value) }}" target="_blank" class="btn btn-success btn-sm text-white">
                <i class="bi bi-eye-fill"></i> <span class="d-none d-sm-inline">{{ __('View') }}</span>
            </a>

            @if($pdfRoute)
                <a href="{{ $pdfRoute }}" target="_blank" class="btn btn-danger btn-sm text-white">
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
