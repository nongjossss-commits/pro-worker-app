{{-- resources/views/workflow/partials/create_modal.blade.php --}}
<div class="modal fade" id="createJobModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('workflow.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="is_pre_production" id="create_job_is_pre_production" value="0">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Create New Job') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('Work Type') }}</label>
                    <select name="work_type_id" class="form-select" required>
                        @foreach($tabs as $tab)
                            <option value="{{ $tab->id }}" data-name="{{ $tab->name }}" {{ isset($activeTab) && $activeTab->id == $tab->id ? 'selected' : '' }}>
                                {{ $tab->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Employer') }}</label>
                    {{-- Ideally a select2 or live search --}}
                    <select name="employer_id" class="form-select" required>
                        <option value="">{{ __('Select Employer...') }}</option>
                        @foreach(\App\Models\Employer::orderBy('employerNameTh')->limit(200)->get() as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->employerNameTh }} / {{ $emp->employerNameEn }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Project Name / Batch Name') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                    <input type="text" name="project_name" class="form-control" placeholder="{{ __('e.g. Batch 1 - Jan 2024') }}">
                    <div class="form-text text-muted">{{ __('Use this to create multiple batches for MOU Import.') }}</div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Create Job') }}</button>
            </div>
        </form>
    </div>
</div>
