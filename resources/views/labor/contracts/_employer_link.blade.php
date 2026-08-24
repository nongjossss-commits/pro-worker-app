{{--
    Contract-level "which employer is this for" — deliberately separate
    from the template's freeform field_mapping (see
    LaborContractController::resolveEmployerLink()'s docblock for why).

    Expects: $canLinkEmployer (bool, from LaborContractController::canLinkEmployer()).
    Optional: $contract (for the edit form's prefill).

    Two modes:
    - Main office (has real Employer access): search-and-pick from the
      existing api-web.employers.list endpoint (same one
      resources/views/employees/modals/select_target_employer_modal.blade.php
      already uses) — sets a hidden employer_id, never free-typed, so the
      name can never drift from the actual Employer record.
    - External team (no Employer records of their own): plain free-text
      name only, employer_id stays null.
--}}
@php($contract = $contract ?? null)
<div class="mb-4">
    <label class="form-label fw-bold">{{ __('Employer') }} *</label>

    @if($canLinkEmployer)
        <div id="employerPickerWrap">
            <div id="employerPickerSearchBlock" style="{{ $contract?->employer_id ? 'display:none;' : '' }}">
                <input type="text" class="form-control" id="employer_search" placeholder="{{ __('Type to search...') }}" autocomplete="off">
                <div id="employer_search_results" class="list-group mt-1" style="max-height: 220px; overflow-y: auto; display: none;"></div>
            </div>
            <div id="employerPickerSelectedBlock" class="d-flex align-items-center gap-2" style="{{ $contract?->employer_id ? '' : 'display:none;' }}">
                <span class="fw-bold text-primary" id="employer_selected_display">{{ $contract?->employer?->employerNameTh ?? $contract?->employer_name_snapshot }}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="employer_change_btn">{{ __('Change') }}</button>
            </div>
            <input type="hidden" name="employer_id" id="employer_id_input" value="{{ old('employer_id', $contract?->employer_id) }}">
        </div>
    @else
        <input type="text" name="employer_name" class="form-control" placeholder="{{ __('Employer name (for search)') }}"
               value="{{ old('employer_name', $contract?->employer_name_snapshot) }}" required>
    @endif
</div>

@if($canLinkEmployer)
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('employer_search');
        const resultsBox = document.getElementById('employer_search_results');
        const searchBlock = document.getElementById('employerPickerSearchBlock');
        const selectedBlock = document.getElementById('employerPickerSelectedBlock');
        const selectedDisplay = document.getElementById('employer_selected_display');
        const idInput = document.getElementById('employer_id_input');
        const changeBtn = document.getElementById('employer_change_btn');
        if (!searchInput) return;

        let debounceTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) {
                resultsBox.style.display = 'none';
                return;
            }
            debounceTimer = setTimeout(() => fetchEmployers(q), 300);
        });

        function fetchEmployers(q) {
            fetch(`{{ route('api-web.employers.list') }}?search=${encodeURIComponent(q)}`)
                .then((r) => r.json())
                .then((data) => {
                    resultsBox.innerHTML = '';
                    if (!data.length) {
                        resultsBox.innerHTML = '<div class="list-group-item text-muted">{{ __('No employers found') }}</div>';
                        resultsBox.style.display = 'block';
                        return;
                    }
                    data.forEach((emp) => {
                        const name = [emp.employerNameTh, emp.employerNameEn].filter(Boolean).join(' / ');
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = `${name} (${emp.employerId || emp.id})`;
                        item.addEventListener('click', function (e) {
                            e.preventDefault();
                            selectEmployer(emp.id, name);
                        });
                        resultsBox.appendChild(item);
                    });
                    resultsBox.style.display = 'block';
                })
                .catch((err) => console.error('Failed to search employers', err));
        }

        function selectEmployer(id, name) {
            idInput.value = id;
            selectedDisplay.textContent = name;
            searchInput.value = '';
            resultsBox.style.display = 'none';
            searchBlock.style.display = 'none';
            selectedBlock.style.display = 'flex';
        }

        changeBtn.addEventListener('click', function () {
            idInput.value = '';
            searchBlock.style.display = 'block';
            selectedBlock.style.display = 'none';
        });
    });
    </script>
    @endpush
@endif
