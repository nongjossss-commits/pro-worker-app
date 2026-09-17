{{--
    "จัดทีม" (team assignment) — ported from Workflow's Manage Team modal
    (resources/views/workflow/index.blade.php), scoped by resolution_tab_id
    instead of production_order_id. Included ONCE per page (registration/
    index.blade.php and renewal/index.blade.php) — never inside the
    per-employee _employee_card.blade.php partial, since that's rendered
    once per employee and would duplicate this modal's ids.

    Required variables from the including view:
    - $teamRouteNamespace: 'production.registration' or 'production.renewal'
    - $currentTab: the current ResolutionTab
--}}
<div class="modal fade" id="manageEmployeeTeamModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i>{{ __('Manage Team') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="team_employee_id">
                <input type="hidden" id="team_employer_id">

                <div class="mb-4">
                    <label for="employee_team_name" class="form-label fw-bold text-dark">{{ __('Team Name / Batch') }}</label>
                    <input type="text" class="form-control form-control-lg" id="employee_team_name" placeholder="{{ __('e.g., Batch 1, Arrived 25/10') }}">
                    <div class="form-text text-muted">{{ __('Assign a team name to organize employees in this tab.') }}</div>
                </div>

                <div id="existing-employee-teams-wrapper" class="d-none">
                    <h6 class="fw-bold text-secondary mb-3 small text-uppercase">{{ __('Existing Teams in this Tab') }}</h6>
                    <div class="d-flex flex-wrap gap-2" id="existing-employee-teams-list">
                        <!-- Chips loaded via JS -->
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-outline-secondary" onclick="clearEmployeeTeam()" title="{{ __('Remove this employee from their team') }}">
                    <i class="bi bi-x-circle me-1"></i> {{ __('No Team') }}
                </button>
                <button type="button" class="btn btn-primary px-4" onclick="saveEmployeeTeam()">
                    <i class="bi bi-check-lg me-1"></i> {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const teamModalCsrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const teamUpdateUrlTemplate = @js(route($teamRouteNamespace . '.team.update', [$currentTab, '__EMPLOYEE__']));
    const teamRenameUrl = @js(route($teamRouteNamespace . '.team.rename', [$currentTab]));
    const teamDeleteUrl = @js(route($teamRouteNamespace . '.team.delete', [$currentTab]));
    const teamNamesUrl = @js(route($teamRouteNamespace . '.team.names', [$currentTab]));

    function buildTeamChip(name) {
        const group = document.createElement('div');
        group.className = 'btn-group';

        const badge = document.createElement('button');
        badge.className = 'btn btn-sm btn-outline-secondary rounded-start-pill px-3';
        badge.type = 'button';
        badge.innerText = name;
        badge.title = '{{ __('Click to select this team') }}';
        badge.onclick = () => { document.getElementById('employee_team_name').value = name; };
        group.appendChild(badge);

        const renameBtn = document.createElement('button');
        renameBtn.className = 'btn btn-sm btn-outline-secondary px-2';
        renameBtn.type = 'button';
        renameBtn.title = '{{ __('Rename team') }}';
        renameBtn.innerHTML = '<i class="bi bi-pencil-fill"></i>';
        renameBtn.onclick = () => window.renameEmployeeTeamPill(name);
        group.appendChild(renameBtn);

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-sm btn-outline-danger rounded-end-pill px-2';
        deleteBtn.type = 'button';
        deleteBtn.title = '{{ __('Delete team') }}';
        deleteBtn.innerHTML = '<i class="bi bi-trash-fill"></i>';
        deleteBtn.onclick = () => window.deleteEmployeeTeamPill(name);
        group.appendChild(deleteBtn);

        return group;
    }

    // Update ONE employee's team button in place — no full employer-list
    // reload. Reloading the whole list after every save was disruptive when
    // assigning teams to many employees one after another (lost scroll
    // position, every other card's open/closed state reset); this keeps
    // everything else on screen exactly as it was, and updates
    // data-team-name too so reopening the modal for this same employee
    // later shows the right value without needing a page reload.
    function updateTeamButtonUI(employeeId, teamName) {
        const btn = document.querySelector('button[onclick^="openManageEmployeeTeamModal(' + employeeId + ',"]');
        if (!btn) return;
        btn.dataset.teamName = teamName || '';
        btn.classList.toggle('btn-primary', !!teamName);
        btn.classList.toggle('btn-outline-primary', !teamName);
        const label = btn.querySelector('span');
        if (label) label.textContent = teamName || '{{ __('Team') }}';
    }

    // Rename/delete affect every employee under this employer who shares
    // the team name — update every matching button on screen, not just the
    // one the modal was opened from.
    function updateTeamButtonsByNameUI(employerId, oldName, newName) {
        document.querySelectorAll('button[onclick^="openManageEmployeeTeamModal"]').forEach(function (btn) {
            if (btn.dataset.employerId === String(employerId) && btn.dataset.teamName === oldName) {
                btn.dataset.teamName = newName || '';
                btn.classList.toggle('btn-primary', !!newName);
                btn.classList.toggle('btn-outline-primary', !newName);
                const label = btn.querySelector('span');
                if (label) label.textContent = newName || '{{ __('Team') }}';
            }
        });
    }

    function reloadChips() {
        const wrapper = document.getElementById('existing-employee-teams-wrapper');
        const list = document.getElementById('existing-employee-teams-list');
        list.innerHTML = '';
        wrapper.classList.add('d-none');

        const employerId = document.getElementById('team_employer_id').value;
        return fetch(teamNamesUrl + '?employer_id=' + encodeURIComponent(employerId), { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                const names = data.names || [];
                if (names.length > 0) {
                    wrapper.classList.remove('d-none');
                    names.forEach(name => list.appendChild(buildTeamChip(name)));
                }
            });
    }

    window.openManageEmployeeTeamModal = function (employeeId, btn) {
        document.getElementById('team_employee_id').value = employeeId;
        document.getElementById('team_employer_id').value = btn.dataset.employerId || '';
        document.getElementById('employee_team_name').value = btn.dataset.teamName || '';

        reloadChips();

        const modal = new bootstrap.Modal(document.getElementById('manageEmployeeTeamModal'));
        modal.show();
    };

    window.saveEmployeeTeam = function () {
        const employeeId = document.getElementById('team_employee_id').value;
        const teamName = document.getElementById('employee_team_name').value;

        fetch(teamUpdateUrlTemplate.replace('__EMPLOYEE__', employeeId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': teamModalCsrfToken },
            body: JSON.stringify({ team_name: teamName }),
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateTeamButtonUI(employeeId, teamName);
                    bootstrap.Modal.getInstance(document.getElementById('manageEmployeeTeamModal')).hide();
                    Swal.fire({ icon: 'success', title: '{{ __('Saved') }}', timer: 1200, showConfirmButton: false });
                } else {
                    Swal.fire('{{ __('Error') }}', data.message || '{{ __('Failed to assign team.') }}', 'error');
                }
            });
    };

    window.clearEmployeeTeam = function () {
        document.getElementById('employee_team_name').value = '';
        window.saveEmployeeTeam();
    };

    window.renameEmployeeTeamPill = function (oldName) {
        Swal.fire({
            title: '{{ __('Rename team') }}',
            input: 'text',
            inputValue: oldName,
            showCancelButton: true,
            confirmButtonText: '{{ __('Save') }}',
            cancelButtonText: '{{ __('Cancel') }}',
            inputValidator: (value) => {
                if (!value || !value.trim()) return '{{ __('Please enter a team name') }}';
            },
        }).then((result) => {
            if (!result.isConfirmed) return;
            const newName = result.value.trim();

            fetch(teamRenameUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': teamModalCsrfToken },
                body: JSON.stringify({ employer_id: document.getElementById('team_employer_id').value, old_name: oldName, new_name: newName }),
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const nameInput = document.getElementById('employee_team_name');
                        if (nameInput.value === oldName) nameInput.value = newName;
                        updateTeamButtonsByNameUI(document.getElementById('team_employer_id').value, oldName, newName);
                        reloadChips();
                        Swal.fire({ icon: 'success', title: '{{ __('Saved') }}', timer: 1200, showConfirmButton: false });
                    } else {
                        Swal.fire('{{ __('Error') }}', data.message || '{{ __('Failed to rename team.') }}', 'error');
                    }
                });
        });
    };

    window.deleteEmployeeTeamPill = function (name) {
        Swal.fire({
            icon: 'warning',
            title: '{{ __('Delete this team?') }}',
            text: '{{ __('This only removes the team label. Employees will NOT be deleted.') }}',
            showCancelButton: true,
            confirmButtonText: '{{ __('Delete') }}',
            confirmButtonColor: '#dc3545',
            cancelButtonText: '{{ __('Cancel') }}',
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch(teamDeleteUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': teamModalCsrfToken },
                body: JSON.stringify({ employer_id: document.getElementById('team_employer_id').value, name: name }),
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const nameInput = document.getElementById('employee_team_name');
                        if (nameInput.value === name) nameInput.value = '';
                        updateTeamButtonsByNameUI(document.getElementById('team_employer_id').value, name, '');
                        reloadChips();
                        Swal.fire({ icon: 'success', title: '{{ __('Deleted') }}', timer: 1200, showConfirmButton: false });
                    } else {
                        Swal.fire('{{ __('Error') }}', data.message || '{{ __('Failed to delete team.') }}', 'error');
                    }
                });
        });
    };
})();
</script>
