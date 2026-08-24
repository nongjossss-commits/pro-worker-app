@extends('labor.layout')

@section('title', __('Company Documents'))

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="fw-bold mb-0">{{ __('Company Documents') }} (ดาวน์โหลดเอกสารบริษัท)</h4>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @role('super-admin')
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">{{ __('Upload a Document') }}</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('labor.company-documents.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small">{{ __('Title') }} *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('Document Type') }}</label>
                    <input type="text" name="document_type" class="form-control" placeholder="{{ __('e.g. Company Registration') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('File') }} *</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Upload') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endrole

    @if(!auth()->user()->labor_team_id)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ __('You have not been assigned to a Pro Walker Labor team yet. Please contact a Super Admin before downloading documents.') }}
        </div>
    @else
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                            @role('super-admin')
                            <th class="text-end">{{ __('Manage') }}</th>
                            @endrole
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td class="text-muted small">{{ $doc->document_type ?: '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('labor.company-documents.download', $doc) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>{{ __('Download') }}
                                </a>
                            </td>
                            @role('super-admin')
                            <td class="text-end">
                                <form method="POST" action="{{ route('labor.company-documents.destroy', $doc) }}" onsubmit="return confirm('{{ __('Remove this document?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                            @endrole
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('No documents yet.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
