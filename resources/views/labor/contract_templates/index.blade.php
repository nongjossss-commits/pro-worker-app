@extends('labor.layout')

@section('title', __('Pro Worker Contract Templates'))

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Pro Worker Contract Templates') }} (จัดการเทมเพลตสัญญา)</h1>
        <div class="d-flex gap-2">
            <button type="submit" form="contractTemplateExportForm" id="contractTemplateExportBtn" class="btn btn-outline-secondary" disabled
                    title="{{ __('Tick templates in the list below first') }}">
                <i class="bi bi-download"></i> {{ __('Export Selected') }}
            </button>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importContractTemplateModal">
                <i class="bi bi-upload"></i> {{ __('Import') }}
            </button>
            <a href="{{ route('labor.contract-templates.create') }}" class="btn btn-primary">
                <i class="bi bi-plus"></i> {{ __('New Template') }}
            </a>
        </div>
    </div>

    {{-- Export/Import — settings-portability feature: lets a Super Admin
         download the selected templates' field positions (+ background PDF)
         as one JSON file to hand off/re-upload into another install of this
         program, instead of rebuilding the same layout from scratch there.
         Checkboxes further down are `form="contractTemplateExportForm"`-linked
         to this GET form rather than nested inside it. --}}
    <form id="contractTemplateExportForm" method="GET" action="{{ route('labor.contract-templates.export') }}"></form>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('danger'))
        <div class="alert alert-danger">{{ session('danger') }}</div>
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

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 1%;">
                                <input type="checkbox" class="form-check-input" id="contractTemplateSelectAll" title="{{ __('Select all') }}">
                            </th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Fields') }}</th>
                            <th>{{ __('Created By') }}</th>
                            <th>{{ __('Created') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input contract-template-checkbox" name="ids[]" value="{{ $template->id }}" form="contractTemplateExportForm">
                                </td>
                                <td>{{ $template->name }}</td>
                                <td>{{ count($template->field_mapping ?? []) }}</td>
                                <td>{{ $template->creator->name ?? '-' }}</td>
                                <td>{{ $template->created_at->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('labor.contract-templates.builder', $template) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i> {{ __('Edit Fields') }}
                                    </a>
                                    <form action="{{ route('labor.contract-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this template?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('No templates yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $templates->links() }}
        </div>
    </div>

    {{-- Import Template Modal — uploads a JSON file produced by "Export
         Selected" above (from this install or another one running this
         same program) and recreates the templates it contains. --}}
    <div class="modal fade" id="importContractTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('labor.contract-templates.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> {{ __('Import Templates') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-bold">{{ __('Export file (.json)') }}</label>
                    <input type="file" name="file" accept=".json" class="form-control" required>
                    <div class="form-text">
                        {{ __('This creates new templates — it never overwrites an existing one. A template whose name is already used here is skipped. Any image/stamp/signature fields need to be re-uploaded manually afterward in the builder.') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> {{ __('Import') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const exportBtn = document.getElementById('contractTemplateExportBtn');
        const selectAll = document.getElementById('contractTemplateSelectAll');
        const rowCheckboxes = document.querySelectorAll('.contract-template-checkbox');

        function refreshExportBtn() {
            if (!exportBtn) return;
            exportBtn.disabled = ![...rowCheckboxes].some(cb => cb.checked);
        }

        rowCheckboxes.forEach(cb => cb.addEventListener('change', refreshExportBtn));

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                rowCheckboxes.forEach(cb => { cb.checked = selectAll.checked; });
                refreshExportBtn();
            });
        }

        refreshExportBtn();
    });
</script>
@endpush
@endsection
