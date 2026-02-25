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

    {{-- Filter Section --}}
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff') || auth()->user()->hasRole('caretaker'))
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light rounded">
            <form action="{{ route('admin.pdf-templates.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold">Filter by Employer / Type</label>
                    {{-- Reusing the searchable dropdown pattern --}}
                    @php
                        $selectedId = request('employer_id');
                        $selectedName = '';
                        if($selectedId === 'global') {
                            $selectedName = 'Global Templates Only';
                        } elseif($selectedId && $employer = $employers->firstWhere('id', $selectedId)) {
                            $selectedName = $employer->employerNameTh . ' (' . $employer->employerNameEn . ')';
                        }

                        // Prepare options: Global + Employers
                        // We structure options to include a specialized 'Global' entry
                        $employerOptions = collect([
                            [
                                'id' => 'global',
                                'name_th' => 'Global Templates Only (ส่วนกลาง)',
                                'name_en' => 'Global',
                                'search_str' => 'global templates only ส่วนกลาง'
                            ]
                        ])->merge(
                            $employers->map(fn($e) => [
                                'id' => $e->id,
                                'name_th' => $e->employerNameTh,
                                'name_en' => $e->employerNameEn,
                                'search_str' => strtolower($e->employerNameTh . ' ' . $e->employerNameEn)
                            ])
                        );
                    @endphp

                    <div x-data="filterEmployerSelector()" @click.outside="open = false">
                        <input type="hidden" name="employer_id" :value="selectedId">

                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Type to search employer or select Global..."
                                       x-model="search"
                                       @focus="open = true"
                                       @keydown.escape="open = false"
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                            </div>

                            <div class="form-text text-primary fw-bold mt-1" x-show="selectedName" style="display: none;">
                                <i class="bi bi-check-circle-fill me-1"></i> Filtering: <span x-text="selectedName"></span>
                            </div>

                            <div class="card position-absolute w-100 shadow mt-1 border-0"
                                 style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none;"
                                 x-show="open"
                                 x-transition>
                                <ul class="list-group list-group-flush">
                                    <template x-for="opt in filteredOptions" :key="opt.id">
                                        <li class="list-group-item list-group-item-action cursor-pointer d-flex justify-content-between align-items-center"
                                            @click="selectOption(opt)">
                                            <div>
                                                <div class="fw-bold" x-text="opt.name_th"></div>
                                                <div class="small text-muted" x-text="opt.name_en"></div>
                                            </div>
                                            <i class="bi bi-check2 text-primary" x-show="selectedId == opt.id"></i>
                                        </li>
                                    </template>
                                    <li class="list-group-item text-muted text-center" x-show="filteredOptions.length === 0">
                                        No results found
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.pdf-templates.index') }}" class="btn btn-outline-secondary w-100">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function filterEmployerSelector() {
            return {
                search: '',
                open: false,
                selectedId: '{{ $selectedId }}',
                selectedName: '{{ addslashes($selectedName) }}',
                options: @json($employerOptions),

                init() {
                    // Pre-fill search box if value selected
                    if(this.selectedName) {
                        this.search = this.selectedName;
                    }
                },

                get filteredOptions() {
                    if (this.search === '') return this.options;
                    const term = this.search.toLowerCase();
                    return this.options.filter(o => o.search_str.includes(term));
                },

                selectOption(opt) {
                    this.selectedId = opt.id;
                    this.selectedName = opt.name_th + ' (' + opt.name_en + ')';
                    this.search = opt.name_th; // Show Thai name in input
                    this.open = false;
                }
            }
        }
    </script>
    @endpush
    @endif

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

                                <a href="{{ route('admin.pdf-templates.file', ['pdf_template' => $template->id, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary me-2" title="Download Original">
                                    <i class="bi bi-download"></i>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-submit-swal').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const title = this.dataset.swalTitle || 'Are you sure?';
                const text = this.dataset.swalText || '';

                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
@endsection
