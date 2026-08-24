@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Income Categories') }}</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus"></i> {{ __('Add Income Category') }}
        </button>
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
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('VAT Treatment') }}</th>
                            <th>{{ __('WHT Default') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $category->code ?? '-' }}</td>
                                <td>
                                    <div class="fw-bold">{{ $category->name }}</div>
                                    @if($category->description)
                                        <div class="small text-muted">{{ $category->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $vatLabel = match($category->default_vat_treatment) {
                                            'taxable' => ['Taxable (VAT)', 'success'],
                                            'exempt' => ['Exempt', 'secondary'],
                                            'zero_rate' => ['Zero-rate', 'info'],
                                            default => ['None', 'light text-dark'],
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $vatLabel[1] }}">{{ $vatLabel[0] }}</span>
                                </td>
                                <td>
                                    @if($category->default_wht_type === 'none')
                                        <span class="text-muted">—</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">
                                            {{ strtoupper($category->default_wht_type) }} {{ rtrim(rtrim($category->default_wht_rate, '0'), '.') }}%
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $category->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('finance.income-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Delete this category?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('finance.income-categories.update', $category) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('Edit Income Category') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                @include('financial.income_categories._form', ['category' => $category])
                                                <div class="form-check">
                                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ $category->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ __('Active') }}</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No income categories yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $categories->links() }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('finance.income-categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Income Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('financial.income_categories._form', ['category' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
