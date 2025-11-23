{{-- resources/views/employees/modals/select_target_employer_modal.blade.php --}}
<div class="modal fade" id="selectTargetEmployerModal" tabindex="-1" aria-labelledby="selectTargetEmployerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selectTargetEmployerModalLabel">{{ __('Select Target Employer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Please select the employer for this Job Ticket.') }}</p>
                <div class="mb-3">
                    <label for="target_employer_search" class="form-label">{{ __('Search Employer') }}</label>
                    <input type="text" class="form-control" id="target_employer_search" placeholder="{{ __('Type to search...') }}">
                    <div id="target_employer_results" class="list-group mt-2" style="max-height: 200px; overflow-y: auto; display: none;">
                        {{-- Results injected via JS --}}
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('Selected Employer') }}:</label>
                    <div id="selected_target_employer_display" class="fw-bold text-primary">
                        {{ __('-- None Selected --') }}
                    </div>
                    <input type="hidden" id="selected_target_employer_id">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="confirmTargetEmployerBtn" disabled>{{ __('Confirm & Create Ticket') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('target_employer_search');
    const resultsContainer = document.getElementById('target_employer_results');
    const selectedDisplay = document.getElementById('selected_target_employer_display');
    const selectedInput = document.getElementById('selected_target_employer_id');
    const confirmBtn = document.getElementById('confirmTargetEmployerBtn');

    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 2) {
            resultsContainer.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchEmployers(query);
        }, 300);
    });

    function fetchEmployers(query) {
        // Use existing API endpoint or similar
        fetch(`{{ route('api-web.employers.list') }}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                resultsContainer.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(emp => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = `${emp.employerNameTh || ''} ${emp.employerNameEn || ''} (${emp.employerId || 'N/A'})`;
                        item.dataset.id = emp.id;
                        item.dataset.name = `${emp.employerNameTh || ''} ${emp.employerNameEn || ''}`;

                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            selectEmployer(this.dataset.id, this.dataset.name);
                        });

                        resultsContainer.appendChild(item);
                    });
                    resultsContainer.style.display = 'block';
                } else {
                    resultsContainer.innerHTML = '<div class="list-group-item text-muted">No employers found</div>';
                    resultsContainer.style.display = 'block';
                }
            })
            .catch(err => console.error('Error fetching employers:', err));
    }

    function selectEmployer(id, name) {
        selectedInput.value = id;
        selectedDisplay.textContent = name;
        confirmBtn.disabled = false;
        resultsContainer.style.display = 'none';
        searchInput.value = ''; // Clear search
    }

    confirmBtn.addEventListener('click', function() {
        const targetEmployerId = selectedInput.value;
        const employeeIds = window.pendingTicketEmployeeIds;

        if (!targetEmployerId || !employeeIds || employeeIds.length === 0) {
            alert('Missing data');
            return;
        }

        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('employees.bulk_to_ticket') }}';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        // Add selected Employee IDs
        employeeIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'employee_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        // Add Target Employer ID
        const empInput = document.createElement('input');
        empInput.type = 'hidden';
        empInput.name = 'target_employer_id';
        empInput.value = targetEmployerId;
        form.appendChild(empInput);

        document.body.appendChild(form);
        form.submit();
    });

    // Reset modal on close
    const modalEl = document.getElementById('selectTargetEmployerModal');
    modalEl.addEventListener('hidden.bs.modal', function () {
        searchInput.value = '';
        selectedInput.value = '';
        selectedDisplay.textContent = '{{ __('-- None Selected --') }}';
        confirmBtn.disabled = true;
        resultsContainer.style.display = 'none';
        // Clean up global variable
        window.pendingTicketEmployeeIds = null;
    });
});
</script>
@endpush
