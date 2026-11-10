@extends('labor.layout')

@section('title', __('Pro Worker Contract Templates'))

@section('content')
@include('labor.contracts._pwc_styles')
<div class="container-fluid pwc">
    <div class="pwc-hero">
        <div class="pwc-hero-main">
            <div class="pwc-hero-icon"><i class="bi bi-layout-text-sidebar-reverse"></i></div>
            <div>
                <h1 class="pwc-hero-title">{{ __('Pro Worker Contract Templates') }}</h1>
                @if(app()->getLocale() !== 'th')<p class="pwc-hero-sub">จัดการเทมเพลตสัญญา</p>@endif
            </div>
        </div>
        <div class="pwc-hero-actions">
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

    <div class="card pwc-panel mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle pwc-table">
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
                                <td><div class="pwc-tpl-name"><span class="pwc-chip-icon"><i class="bi bi-file-earmark-richtext"></i></span>{{ $template->name }}</div></td>
                                <td><span class="pwc-count"><i class="bi bi-input-cursor-text"></i>{{ count($template->field_mapping ?? []) }}</span></td>
                                <td><span class="pwc-meta"><i class="bi bi-person"></i>{{ $template->creator->name ?? '-' }}</span></td>
                                <td><span class="pwc-meta"><i class="bi bi-calendar3"></i>{{ $template->created_at->format('d/m/Y') }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('labor.contract-templates.builder', $template) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i> {{ __('Edit Fields') }}
                                    </a>
                                    <form action="{{ route('labor.contract-templates.destroy', $template) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this template?') }}" data-confirm-danger>
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5"><i class="bi bi-file-earmark-x d-block fs-1 mb-2 opacity-50"></i>{{ __('No templates yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($templates->hasPages())<div class="px-3 pt-3">{{ $templates->links() }}</div>@endif
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
