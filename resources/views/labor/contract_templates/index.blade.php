@extends('labor.layout')

@section('title', __('Pro Worker Contract Templates'))

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Pro Worker Contract Templates') }} (จัดการเทมเพลตสัญญา)</h1>
        <a href="{{ route('labor.contract-templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus"></i> {{ __('New Template') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
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
                                <td colspan="5" class="text-center text-muted py-4">{{ __('No templates yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $templates->links() }}
        </div>
    </div>
</div>
@endsection
