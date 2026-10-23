{{-- Super Admin → Settings → "Employee Card Details": which employee details
     show on the employee tiles (EmployeeCardFieldsService). --}}
@php
    $cardFieldLabels = collect(\App\Services\EmployeeCardFieldsService::labels())->map(fn ($l) => __($l));
@endphp
<div class="card">
    <div class="card-body"
         x-data="{
            selected: @js(\App\Services\EmployeeCardFieldsService::selected()),
            defaults: @js(\App\Services\EmployeeCardFieldsService::DEFAULT_FIELDS),
            labels: @js($cardFieldLabels),
            max: {{ \App\Services\EmployeeCardFieldsService::MAX_FIELDS }},
            toggle(key) {
                const i = this.selected.indexOf(key);
                if (i >= 0) { this.selected.splice(i, 1); return; }
                if (this.selected.length >= this.max) {
                    appToast(@js(__('You can choose up to :n details.', ['n' => \App\Services\EmployeeCardFieldsService::MAX_FIELDS])), 'warning');
                    return;
                }
                this.selected.push(key);
            },
            move(i, d) {
                const j = i + d;
                if (j < 0 || j >= this.selected.length) return;
                [this.selected[i], this.selected[j]] = [this.selected[j], this.selected[i]];
            },
         }">
        <h5 class="card-title">
            <i class="bi bi-person-vcard me-2"></i> {{ __('Employee Card Details') }}
        </h5>
        <p class="text-muted small mb-3">
            {{ __('Choose which employee details appear on the employee cards (Employees, the employer page\'s Employees tab, Groups & Teams, Notified-Out Employees, Central Trash). Photo, names, nationality, employer, work status and buttons always show.') }}
            {{ __('The same details, each with a copy button, also show on the work cards in Workflow, Pre-Production, Registration Resolution and Renewal Resolution.') }}
        </p>

        <form action="{{ route('super-admin.settings.card-fields') }}" method="POST">
            @csrf
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="border rounded p-3 bg-body-tertiary h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">{{ __('Shown on the card (in this order)') }}</span>
                            <span class="badge" :class="selected.length >= max ? 'bg-warning text-dark' : 'bg-primary'" x-text="selected.length + ' / ' + max"></span>
                        </div>
                        <template x-if="!selected.length">
                            <div class="text-muted small py-3 text-center">{{ __('No details selected — cards show only the name, nationality and employer.') }}</div>
                        </template>
                        <ol class="list-group list-group-numbered">
                            <template x-for="(key, i) in selected" :key="key">
                                <li class="list-group-item d-flex align-items-center gap-2 py-2">
                                    <input type="hidden" name="fields[]" :value="key">
                                    <span class="flex-grow-1 small" x-text="labels[key]"></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" @click="move(i, -1)" :disabled="i === 0" title="{{ __('Move up') }}"><i class="bi bi-arrow-up"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" @click="move(i, 1)" :disabled="i === selected.length - 1" title="{{ __('Move down') }}"><i class="bi bi-arrow-down"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" @click="toggle(key)" title="{{ __('Remove') }}"><i class="bi bi-x-lg"></i></button>
                                </li>
                            </template>
                        </ol>
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-save-fill me-1"></i> {{ __('Save') }}</button>
                            <button type="button" class="btn btn-outline-secondary" @click="selected = [...defaults]" title="{{ __('Passport No., passport expiry, work permit type and 90-day report') }}">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('Reset to default') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        @foreach(\App\Services\EmployeeCardFieldsService::available() as $group => $fields)
                            <div class="col-md-6">
                                <div class="fw-bold small text-uppercase text-muted mb-1">{{ __($group) }}</div>
                                @foreach($fields as $key => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="card-field-{{ $key }}"
                                               :checked="selected.includes('{{ $key }}')" @change="toggle('{{ $key }}'); $el.checked = selected.includes('{{ $key }}')">
                                        <label class="form-check-label small" for="card-field-{{ $key }}">{{ __($label) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
