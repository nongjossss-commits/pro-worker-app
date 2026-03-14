@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">{{ __('Upload New PDF Template') }}</h5>
                </div>
                <div class="card-body p-4"
                     x-data="{
                        type: '{{ old('type', 'global') }}',
                        employerId: '{{ old('employer_id') }}',
                        search: '',
                        open: false,
                        selectedEmployerName: '',
                        employers: @if(isset($employers) && (auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff'))) {{ $employers->map(fn($e) => [
                            'id' => $e->id,
                            'name' => $e->employerNameTh . ' (' . $e->employerNameEn . ')',
                            'search_str' => strtolower($e->employerNameTh . ' ' . $e->employerNameEn)
                        ])->values()->toJson() }} @else [] @endif,

                        init() {
                             // Initialize selected employer name if old value exists
                             if (this.employerId && this.employers.length > 0) {
                                 const emp = this.employers.find(e => e.id == this.employerId);
                                 if (emp) {
                                     this.selectedEmployerName = emp.name;
                                     this.search = emp.name;
                                 }
                             }
                        },

                        get filteredEmployers() {
                            if (this.search === '') return this.employers;
                            const term = this.search.toLowerCase();
                            return this.employers.filter(e => e.search_str.includes(term));
                        },

                        selectEmployer(emp) {
                            this.employerId = emp.id;
                            this.selectedEmployerName = emp.name;
                            this.search = emp.name;
                            this.open = false;
                        }
                     }"
                >
                    <form action="{{ route('admin.pdf-templates.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">{{ __('Template Name') }}</label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="{{ __('e.g. Work Permit Application Form') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Template Type') }}</label>
                            <select name="type" class="form-select" x-model="type">
                                <option value="global">{{ __('Global (All Employers)') }}</option>
                                @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff'))
                                    <option value="employer">{{ __('Specific Employer') }}</option>
                                @elseif(auth()->user()->hasRole('employer'))
                                    <option value="employer" {{ old('type') == 'employer' ? 'selected' : '' }}>{{ __('My Organization') }}</option>
                                @endif
                            </select>
                        </div>

                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff'))
                        <div class="mb-3" x-show="type === 'employer'" style="display: none;" x-transition>
                            <label class="form-label">{{ __('Select Employer') }}</label>

                            <!-- Hidden Input for Form Submission -->
                            <input type="hidden" name="employer_id" :value="employerId">

                            <!-- Searchable Dropdown -->
                            <div class="position-relative" @click.outside="open = false; search = selectedEmployerName">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text"
                                           class="form-control"
                                           placeholder="{{ __('Type to search employer...') }}"
                                           x-model="search"
                                           @focus="open = true; search = ''"
                                           @keydown.escape="open = false"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                                </div>

                                <div class="card position-absolute w-100 shadow mt-1 border-0"
                                     style="z-index: 1050; max-height: 250px; overflow-y: auto;"
                                     x-show="open"
                                     x-transition>
                                    <ul class="list-group list-group-flush">
                                        <template x-for="emp in filteredEmployers" :key="emp.id">
                                            <li class="list-group-item list-group-item-action cursor-pointer"
                                                @click="selectEmployer(emp)"
                                                :class="{'active': employerId == emp.id}">
                                                <span x-text="emp.name"></span>
                                            </li>
                                        </template>
                                        <li class="list-group-item text-muted text-center" x-show="filteredEmployers.length === 0">{{ __('No employers found') }}</li>
                                    </ul>
                                </div>
                            </div>
                            @error('employer_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        @else
                            <input type="hidden" name="employer_id" value="{{ auth()->user()->employer->id ?? '' }}">
                        @endif

                        <div class="mb-4">
                            <label class="form-label">{{ __('Upload PDF File') }}</label>
                            <input type="file" name="file" class="form-control" accept="application/pdf" required>
                            <div class="form-text">Max size: 10MB. Must be a valid PDF file.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.pdf-templates.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Upload & Go to Builder') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
