{{-- resources/views/workflow/partials/create_modal.blade.php --}}
@php
    // ใช้ slug ของ MOU import สำหรับ JS toggle (ตรวจทั้ง 'mou' และ 'mou_import' เพื่อรองรับข้อมูลเก่า)
    $mouSlugs = ['mou', 'mou_import'];
    // หา id ของ MOU tab จาก $tabs เพื่อให้ JS เปรียบเทียบ
    $mouTabIds = collect($tabs ?? [])
        ->filter(fn($t) => in_array($t->slug, $mouSlugs))
        ->pluck('id')
        ->values()
        ->all();
@endphp
<div class="modal fade" id="createJobModal" tabindex="-1" aria-hidden="true"
     x-data="{ workTypeId: '{{ $activeTab->id ?? '' }}', isMou: false, maleCount: 0, femaleCount: 0, init() { this.checkMou(); }, checkMou() { this.isMou = {{ json_encode($mouTabIds) }}.includes(parseInt(this.workTypeId)); } }">
    <div class="modal-dialog">
        <form action="{{ route('workflow.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="is_pre_production" id="create_job_is_pre_production" value="0">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span x-show="!isMou">{{ __('Create New Job') }}</span>
                    <span x-show="isMou" x-cloak><i class="bi bi-card-checklist me-1"></i> {{ __('Create Demand Card (MOU Import)') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('Work Type') }}</label>
                    <select name="work_type_id" class="form-select" required x-model="workTypeId" @change="checkMou()">
                        @foreach($tabs as $tab)
                            <option value="{{ $tab->id }}" data-name="{{ $tab->name }}" data-slug="{{ $tab->slug }}" {{ isset($activeTab) && $activeTab->id == $tab->id ? 'selected' : '' }}>
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

                {{-- ฟิลด์เฉพาะ MOU Import — สัญชาติ + จำนวนชาย/หญิง --}}
                <div x-show="isMou" x-cloak>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        {{ __('MOU import is case-based — 1 employer can have multiple demand cards. Add employees inside each card.') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nationality') }} <span class="text-danger">*</span></label>
                        <select name="mou_nationality" class="form-select" :required="isMou">
                            <option value="">{{ __('Select Nationality...') }}</option>
                            <option value="myanmar">🇲🇲 {{ __('Myanmar') }}</option>
                            <option value="laos">🇱🇦 {{ __('Laos') }}</option>
                            <option value="cambodia">🇰🇭 {{ __('Cambodia') }}</option>
                            <option value="vietnam">🇻🇳 {{ __('Vietnam') }}</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Male Count') }}</label>
                            <input type="number" name="mou_male_count" class="form-control" min="0" max="9999" value="0" x-model="maleCount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Female Count') }}</label>
                            <input type="number" name="mou_female_count" class="form-control" min="0" max="9999" value="0" x-model="femaleCount">
                        </div>
                    </div>
                    <div class="mb-3 text-end small text-muted">
                        {{ __('Total') }}: <span class="fw-bold text-dark" x-text="(parseInt(maleCount) || 0) + (parseInt(femaleCount) || 0)"></span> {{ __('persons') }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Project Name / Batch Name') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                    <input type="text" name="project_name" class="form-control" placeholder="{{ __('e.g. Batch 1 - Jan 2024') }}">
                    <div class="form-text text-muted">{{ __('Use this to create multiple batches for MOU Import.') }}</div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="submit" class="btn btn-primary">
                    <span x-show="!isMou">{{ __('Create Job') }}</span>
                    <span x-show="isMou" x-cloak>{{ __('Create Demand Card') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
