@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Expense Categories') }}</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus"></i> {{ __('Add Category') }}
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
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Tax Deductible') }}</th>
                            <th>{{ __('WHT Default') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td>{{ $category->code ?? '-' }}</td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->description }}</td>
                                <td>
                                    @if($category->is_tax_deductible)
                                        <span class="badge bg-success">{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(($category->default_wht_type ?? 'none') === 'none')
                                        <span class="text-muted">—</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">
                                            {{ strtoupper($category->default_wht_type) }} {{ rtrim(rtrim($category->default_wht_rate ?? 0, '0'), '.') }}%
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
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('finance.expense-categories.update', $category) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('Edit Category') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-2">
                                                    <div class="col-md-4 mb-3">
                                                        <label>{{ __('Code') }}</label>
                                                        <input type="text" name="code" class="form-control" value="{{ $category->code }}" maxlength="20">
                                                    </div>
                                                    <div class="col-md-8 mb-3">
                                                        <label>{{ __('Name') }} *</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label>{{ __('Description') }}</label>
                                                    <textarea name="description" class="form-control">{{ $category->description }}</textarea>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-md-6 mb-3">
                                                        <label>{{ __('WHT Type') }}</label>
                                                        <select name="default_wht_type" class="form-select">
                                                            <option value="none" {{ ($category->default_wht_type ?? 'none') === 'none' ? 'selected' : '' }}>None</option>
                                                            <option value="pnd3" {{ ($category->default_wht_type ?? '') === 'pnd3' ? 'selected' : '' }}>ภ.ง.ด.3 (บุคคล)</option>
                                                            <option value="pnd53" {{ ($category->default_wht_type ?? '') === 'pnd53' ? 'selected' : '' }}>ภ.ง.ด.53 (นิติบุคคล)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label>{{ __('WHT Rate (%)') }}</label>
                                                        <input type="number" step="0.01" min="0" max="100" name="default_wht_rate" class="form-control" value="{{ $category->default_wht_rate ?? 0 }}">
                                                    </div>
                                                </div>
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" name="is_tax_deductible" class="form-check-input" value="1" {{ $category->is_tax_deductible ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ __('Tax Deductible (นำไปหักภาษีได้)') }}</label>
                                                </div>
                                                <div class="mb-3 form-check">
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
                        @endforeach
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
            <form action="{{ route('finance.expense-categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Expense Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4 mb-3">
                            <label>{{ __('Code') }}</label>
                            <input type="text" name="code" class="form-control" maxlength="20" placeholder="e.g., EXP-001">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label>{{ __('Name') }} *</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g., Visa Fee, Office Supplies">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>{{ __('Description') }}</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label>{{ __('WHT Type') }}</label>
                            <select name="default_wht_type" class="form-select">
                                <option value="none">None</option>
                                <option value="pnd3">ภ.ง.ด.3 (บุคคล)</option>
                                <option value="pnd53">ภ.ง.ด.53 (นิติบุคคล)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>{{ __('WHT Rate (%)') }}</label>
                            <input type="number" step="0.01" min="0" max="100" name="default_wht_rate" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_tax_deductible" class="form-check-input" value="1">
                        <label class="form-check-label">{{ __('Tax Deductible (นำไปหักภาษีได้)') }}</label>
                    </div>
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
