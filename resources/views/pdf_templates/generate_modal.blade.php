@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Generate Automated PDF</h5>
                </div>
                <div class="card-body p-4" x-data="pdfGenerator()">

                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        You have selected <strong>{{ count($employees) }}</strong> employees.
                    </div>

                    <form action="{{ route('admin.pdf-templates.generate.process') }}" method="POST" id="generateForm">
                        @csrf

                        <!-- Hidden Employee IDs -->
                        @foreach($employees as $id)
                            <input type="hidden" name="employees[]" value="{{ $id }}">
                        @endforeach

                        <!-- Template Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">1. Select Template</label>
                            <select name="template_id" class="form-select" required>
                                <option value="">-- Choose Template --</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">
                                        {{ $template->name }}
                                        ({{ $template->type === 'global' ? 'Global' : 'My Organization' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Output Option -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">2. Output Destination</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_type" id="outputDownload" value="download" x-model="outputType">
                                    <label class="form-check-label" for="outputDownload">
                                        Download immediately (Zip/PDF)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_type" id="outputSlot" value="save_to_slot" x-model="outputType">
                                    <label class="form-check-label" for="outputSlot">
                                        Save to Employee Record (Attachment Slot)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Slot Name Configuration -->
                        <div class="mb-4 p-3 bg-light rounded border" x-show="outputType === 'save_to_slot'" x-transition>
                            <label class="form-label fw-bold">Slot Name</label>
                            <p class="text-sm text-gray-500 mb-2">
                                Enter a name for this document slot (e.g., "Work Permit 2024").
                                If a slot with this name exists, it will be overwritten.
                            </p>

                            <!-- Autocomplete / Suggestions could go here -->
                            <input type="text" name="slot_name" class="form-control"
                                   :required="outputType === 'save_to_slot'"
                                   placeholder="e.g. Visa Application Form"
                                   list="slotSuggestions">

                            <datalist id="slotSuggestions">
                                @foreach($existingSlots as $slot)
                                    <option value="{{ $slot }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="{{ url()->previous() }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="isProcessing">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Generate Documents
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pdfGenerator', () => ({
            outputType: 'download',
            isProcessing: false,

            init() {
                document.getElementById('generateForm').addEventListener('submit', () => {
                    this.isProcessing = true;
                    // Note: If downloading, isProcessing won't auto-reset because page doesn't reload.
                    // We might need a timeout or cookie check to reset it, but for now simple is fine.
                    if (this.outputType === 'download') {
                        setTimeout(() => this.isProcessing = false, 3000);
                    }
                });
            }
        }));
    });
</script>
@endpush
@endsection
