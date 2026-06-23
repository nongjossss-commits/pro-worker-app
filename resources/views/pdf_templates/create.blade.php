@extends('layouts.app')

@php
    $cloneTemplatesJson = ($cloneTemplates ?? collect())->map(function ($t) {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'type' => $t->type,
            'field_count' => is_array($t->field_mapping) ? count($t->field_mapping) : 0,
            'search_str' => strtolower($t->name . ' ' . ($t->type === 'global' ? 'global' : 'employer')),
        ];
    })->values();
@endphp

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Upload New PDF Template</h5>
                </div>
                <div class="card-body p-4"
                     x-data="{
                        sourceMode: '{{ old('source_mode', 'upload') }}',
                        sourceTemplateId: '{{ old('source_template_id') }}',
                        cloneSearch: '',
                        cloneOpen: false,
                        cloneTemplates: {{ $cloneTemplatesJson->toJson() }},
                        type: '{{ old('type', 'global') }}',
                        employerId: '{{ old('employer_id') }}',
                        search: '',
                        open: false,
                        selectedEmployerName: '',
                        selectedCloneName: '',
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
                             if (this.sourceTemplateId && this.cloneTemplates.length > 0) {
                                 const t = this.cloneTemplates.find(x => x.id == this.sourceTemplateId);
                                 if (t) {
                                     this.selectedCloneName = t.name + ' (' + (t.type === 'global' ? 'Global' : 'Employer') + ' • ' + t.field_count + ' fields)';
                                     this.cloneSearch = t.name;
                                 }
                             }
                        },

                        get filteredEmployers() {
                            if (this.search === '') return this.employers;
                            const term = this.search.toLowerCase();
                            return this.employers.filter(e => e.search_str.includes(term));
                        },

                        get filteredCloneTemplates() {
                            if (this.cloneSearch === '') return this.cloneTemplates;
                            const term = this.cloneSearch.toLowerCase();
                            return this.cloneTemplates.filter(t => t.search_str.includes(term));
                        },

                        selectEmployer(emp) {
                            this.employerId = emp.id;
                            this.selectedEmployerName = emp.name;
                            this.search = emp.name;
                            this.open = false;
                        },

                        selectCloneTemplate(t) {
                            this.sourceTemplateId = t.id;
                            this.selectedCloneName = t.name + ' (' + (t.type === 'global' ? 'Global' : 'Employer') + ' • ' + t.field_count + ' fields)';
                            this.cloneSearch = t.name;
                            this.cloneOpen = false;
                        }
                     }"
                >
                    <form action="{{ route('admin.pdf-templates.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Source Mode Selector --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold">{{ __('How would you like to create this template?') }}</label>
                            <input type="hidden" name="source_mode" :value="sourceMode">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100"
                                         :class="sourceMode === 'upload' ? 'border-primary bg-light' : 'border-secondary'"
                                         style="cursor: pointer;"
                                         @click="sourceMode = 'upload'">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="source_mode_radio" id="modeUpload" value="upload" x-model="sourceMode">
                                            <label class="form-check-label fw-bold" for="modeUpload">
                                                <i class="bi bi-cloud-upload me-1"></i> {{ __('Upload new PDF') }}
                                            </label>
                                        </div>
                                        <div class="small text-muted mt-1">{{ __('Start from a blank PDF file from your computer') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100"
                                         :class="sourceMode === 'clone' ? 'border-success bg-light' : 'border-secondary'"
                                         :style="cloneTemplates.length === 0 ? 'cursor: not-allowed; opacity: 0.5;' : 'cursor: pointer;'"
                                         @click="if(cloneTemplates.length > 0) sourceMode = 'clone'">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="source_mode_radio" id="modeClone" value="clone" x-model="sourceMode" :disabled="cloneTemplates.length === 0">
                                            <label class="form-check-label fw-bold" for="modeClone">
                                                <i class="bi bi-files me-1"></i> {{ __('Copy from existing template') }}
                                            </label>
                                        </div>
                                        <div class="small text-muted mt-1" x-show="cloneTemplates.length > 0">{{ __('Reuse fields & layout from a saved template') }}</div>
                                        <div class="small text-warning mt-1" x-show="cloneTemplates.length === 0" style="display: none;">{{ __('No templates available to copy yet') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="e.g. Work Permit Application Form">
                            <div class="form-text" x-show="sourceMode === 'clone'" style="display: none;">{{ __('Give the cloned template a new name (e.g. add "(Copy)" or version suffix).') }}</div>
                        </div>

                        {{-- Clone Source Selector --}}
                        <div class="mb-3" x-show="sourceMode === 'clone'" style="display: none;" x-transition>
                            <label class="form-label">{{ __('Select Source Template') }} <span class="text-danger">*</span></label>
                            <input type="hidden" name="source_template_id" :value="sourceTemplateId">
                            <div class="position-relative" @click.outside="cloneOpen = false; cloneSearch = selectedCloneName.split(' (')[0] || cloneSearch">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text"
                                           class="form-control"
                                           placeholder="{{ __('Type to search saved templates...') }}"
                                           x-model="cloneSearch"
                                           @focus="cloneOpen = true; cloneSearch = ''"
                                           @keydown.escape="cloneOpen = false"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="cloneOpen = !cloneOpen"></button>
                                </div>
                                <div class="form-text text-success mt-1" x-show="selectedCloneName" style="display: none;">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    <span x-text="selectedCloneName"></span>
                                </div>
                                <div class="card position-absolute w-100 shadow mt-1 border-0"
                                     style="z-index: 1050; max-height: 280px; overflow-y: auto; display: none;"
                                     x-show="cloneOpen"
                                     x-transition>
                                    <ul class="list-group list-group-flush">
                                        <template x-for="t in filteredCloneTemplates" :key="t.id">
                                            <li class="list-group-item list-group-item-action cursor-pointer d-flex justify-content-between align-items-center"
                                                @click="selectCloneTemplate(t)"
                                                :class="{ 'active': sourceTemplateId == t.id }">
                                                <div>
                                                    <div class="fw-bold" x-text="t.name"></div>
                                                    <div class="small text-muted">
                                                        <span class="badge" :class="t.type === 'global' ? 'bg-success' : 'bg-info'" x-text="t.type === 'global' ? 'Global' : 'Employer'"></span>
                                                        <span class="ms-2"><i class="bi bi-tags"></i> <span x-text="t.field_count"></span> fields</span>
                                                    </div>
                                                </div>
                                                <i class="bi bi-check2 text-primary" x-show="sourceTemplateId == t.id"></i>
                                            </li>
                                        </template>
                                        <li class="list-group-item text-muted text-center" x-show="filteredCloneTemplates.length === 0">{{ __('No templates found') }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="alert alert-info py-2 small mt-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                {{ __('The PDF file, field positions, and metadata will be copied. You can adjust fields in the next step.') }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Template Type</label>
                            <select name="type" class="form-select" x-model="type">
                                <option value="global">Global (All Employers)</option>
                                @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff'))
                                    <option value="employer">Specific Employer</option>
                                @elseif(auth()->user()->hasRole('employer'))
                                    <option value="employer" {{ old('type') == 'employer' ? 'selected' : '' }}>My Organization</option>
                                @endif
                            </select>
                        </div>

                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff'))
                        <div class="mb-3" x-show="type === 'employer'" style="display: none;" x-transition>
                            <label class="form-label">Select Employer</label>

                            <!-- Hidden Input for Form Submission -->
                            <input type="hidden" name="employer_id" :value="employerId">

                            <!-- Searchable Dropdown -->
                            <div class="position-relative" @click.outside="open = false; search = selectedEmployerName">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text"
                                           class="form-control"
                                           placeholder="Type to search employer..."
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
                                        <li class="list-group-item text-muted text-center" x-show="filteredEmployers.length === 0">
                                            No employers found
                                        </li>
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

                        <div class="mb-4" x-show="sourceMode === 'upload'" x-transition>
                            <label class="form-label">Upload PDF File</label>
                            <input type="file" name="file" class="form-control" accept="application/pdf" :required="sourceMode === 'upload'">
                            <div class="form-text">Max size: 10MB. Must be a valid PDF file.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.pdf-templates.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary" x-show="sourceMode === 'upload'">
                                <i class="bi bi-cloud-upload me-1"></i> {{ __('Upload & Go to Builder') }}
                            </button>
                            <button type="submit" class="btn btn-success" x-show="sourceMode === 'clone'" style="display: none;">
                                <i class="bi bi-files me-1"></i> {{ __('Clone & Go to Builder') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
