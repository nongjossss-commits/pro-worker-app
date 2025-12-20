@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">PDF Templates</h2>
        @can('create-pdf-templates')
        <a href="{{ route('admin.pdf-templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Create New Template
        </a>
        @endcan
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Template Name</th>
                            <th>Type</th>
                            <th>Owner (Employer)</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-gray-700">{{ $template->name }}</div>
                            </td>
                            <td>
                                @if($template->type === 'global')
                                    <span class="badge bg-success">Global</span>
                                @else
                                    <span class="badge bg-info">Employer</span>
                                @endif
                            </td>
                            <td>
                                {{ optional($template->employer)->employerNameTh ?? '-' }}
                            </td>
                            <td>{{ optional($template->creator)->name }}</td>
                            <td>{{ $template->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.pdf-templates.builder', $template) }}" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-pencil-square"></i> Builder
                                </a>

                                @can('delete-pdf-templates', $template)
                                <form action="{{ route('admin.pdf-templates.destroy', $template) }}" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                            data-swal-title="Are you sure?"
                                            data-swal-text="This template will be deleted permanently.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No templates found. Create one to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $templates->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
