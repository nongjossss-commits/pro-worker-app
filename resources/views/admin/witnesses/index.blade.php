@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4 text-gray-800">Global Witnesses Management</h1>
    <p class="text-muted mb-4">Manage the 4 default witnesses used in PDF generation. Signatures set here will be available globally.</p>

    <div class="row">
        @foreach($witnesses as $witness)
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-left-primary">
                <div class="card-header bg-white font-weight-bold text-primary d-flex justify-content-between align-items-center">
                    <span>{{ ucfirst(str_replace('_', ' ', $witness->alias)) }}</span>
                    @if($witness->signature_path)
                        <span class="badge bg-success">Signature Set</span>
                    @else
                        <span class="badge bg-secondary">No Signature</span>
                    @endif
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.witnesses.update', $witness->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Name (Thai)</label>
                            <input type="text" name="name_th" class="form-control" value="{{ old('name_th', $witness->name_th) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Name (English)</label>
                            <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $witness->name_en) }}" required>
                        </div>

                        <hr>

                        <div class="mb-3" x-data="{ action: 'keep' }">
                            <label class="form-label font-weight-bold">Signature</label>

                            <!-- Current Signature Preview -->
                            @if($witness->signature_path)
                                <div class="mb-3 p-2 border rounded bg-light text-center">
                                    <img src="{{ Storage::url($witness->signature_path) }}" alt="Signature" style="max-height: 80px; max-width: 100%;">
                                </div>
                            @endif

                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="signature_action" id="sig_keep_{{ $witness->id }}" value="keep" x-model="action" checked>
                                <label class="btn btn-outline-secondary" for="sig_keep_{{ $witness->id }}">Keep Current</label>

                                <input type="radio" class="btn-check" name="signature_action" id="sig_gen_{{ $witness->id }}" value="generate" x-model="action">
                                <label class="btn btn-outline-primary" for="sig_gen_{{ $witness->id }}">Auto Generate</label>

                                <input type="radio" class="btn-check" name="signature_action" id="sig_upload_{{ $witness->id }}" value="upload" x-model="action">
                                <label class="btn btn-outline-info" for="sig_upload_{{ $witness->id }}">Upload File</label>
                            </div>

                            <div x-show="action === 'upload'" class="mt-2">
                                <input type="file" name="signature_file" class="form-control" accept="image/png, image/jpeg" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                                <small class="text-muted">Recommended: Transparent PNG, approx 300x150px.</small>
                            </div>

                            <div x-show="action === 'generate'" class="mt-2 alert alert-info py-2">
                                <i class="bi bi-magic"></i> A new unique signature will be generated upon saving.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
