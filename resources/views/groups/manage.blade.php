@extends('layouts.app')

@section('title', 'Manage Groups & Teams')

@section('content')
<div class="container-fluid" x-data="groupTeamManager()">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ $type === 'affiliated' ? route('groups.affiliated.index') : route('groups.index') }}" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
            </a>
            <h1 class="h3 mt-2 text-gray-800">
                @if($type === 'affiliated')
                    <span class="text-muted">{{ __('Employer') }}:</span> {{ $employer->employerNameTh }} ({{ $employer->employerNameEn }})
                @else
                    {{ __('Independent Groups Management') }}
                @endif
            </h1>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <i class="bi bi-plus-lg"></i> {{ __('Create New Group') }}
        </button>
    </div>

    <!-- Groups Tabs -->
    <ul class="nav nav-tabs mb-3" id="groupTabs" role="tablist">
        @foreach($groups as $index => $group)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $index === 0 || request('active_group') == $group->id ? 'active' : '' }}"
                    id="group-tab-{{ $group->id }}"
                    data-bs-toggle="tab"
                    data-bs-target="#group-content-{{ $group->id }}"
                    type="button"
                    role="tab"
                    aria-controls="group-content-{{ $group->id }}"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                {{ $group->name }}
            </button>
        </li>
        @endforeach
        @if($groups->isEmpty())
        <li class="nav-item">
            <span class="nav-link disabled">{{ __('No Groups Created Yet') }}</span>
        </li>
        @endif
    </ul>

    <!-- Groups Content -->
    <div class="tab-content" id="groupTabsContent">
        @forelse($groups as $index => $group)
        <div class="tab-pane fade {{ $index === 0 || request('active_group') == $group->id ? 'show active' : '' }}"
             id="group-content-{{ $group->id }}"
             role="tabpanel"
             aria-labelledby="group-tab-{{ $group->id }}">

            <div class="d-flex justify-content-between align-items-center my-3 bg-light p-3 rounded">
                <h4 class="mb-0">{{ $group->name }} <span class="text-muted fs-6">({{ __('Group') }})</span></h4>
                <button class="btn btn-outline-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#createTeamModal"
                        @click="setGroupId({{ $group->id }})">
                    <i class="bi bi-plus-circle"></i> {{ __('Create Team') }}
                </button>
            </div>

            <!-- Teams List (Accordion) -->
            <div class="accordion" id="accordionGroup{{ $group->id }}">
                @forelse($group->teams as $team)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTeam{{ $team->id }}">
                        <button class="accordion-button {{ request('active_team') == $team->id ? '' : 'collapsed' }}"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseTeam{{ $team->id }}"
                                aria-expanded="{{ request('active_team') == $team->id ? 'true' : 'false' }}"
                                aria-controls="collapseTeam{{ $team->id }}">
                            <span class="fw-bold">{{ $team->name }}</span>
                            <span class="badge bg-secondary ms-2 rounded-pill">{{ $team->employees->count() }} {{ __('Members') }}</span>
                        </button>
                    </h2>
                    <div id="collapseTeam{{ $team->id }}"
                         class="accordion-collapse {{ request('active_team') == $team->id ? 'show' : '' }}"
                         aria-labelledby="headingTeam{{ $team->id }}">
                        <div class="accordion-body">
                            <!-- Team Actions -->
                            <div class="d-flex justify-content-end mb-3">
                                <button class="btn btn-sm btn-success"
                                        @click="openAddMemberModal({{ $group->id }}, {{ $team->id }}, '{{ $team->name }}')">
                                    <i class="bi bi-person-plus-fill"></i> {{ __('Add Member') }}
                                </button>
                            </div>

                            <!-- Members Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Photo') }}</th>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Passport') }}</th>
                                            <th class="text-end">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($team->employees as $member)
                                        <tr>
                                            <td>
                                                <img src="{{ $member->photo_url }}" class="rounded-circle" width="32" height="32" alt="avatar">
                                            </td>
                                            <td>
                                                <div>{{ $member->employeeNameTh }}</div>
                                                <small class="text-muted">{{ $member->employeeNameEn }}</small>
                                            </td>
                                            <td>{{ $member->employeePassport }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('groups.teams.members.remove', ['team' => $team->id, 'employee' => $member->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to remove this member?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                {{ __('No members in this team yet.') }}
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="alert alert-secondary text-center">
                    {{ __('No teams created in this group yet.') }}
                </div>
                @endforelse
            </div>

        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-collection fs-1 d-block mb-3"></i>
            <p>{{ __('Get started by creating a new Group tab above.') }}</p>
        </div>
        @endforelse
    </div>

    <!-- Modals -->

    <!-- Create Group Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('groups.store') }}" method="POST" class="modal-content">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                @if($employer)
                    <input type="hidden" name="employer_id" value="{{ $employer->id }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Create New Group') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Group Name') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Passport Work, Start Date Group" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Team Modal -->
    <div class="modal fade" id="createTeamModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form :action="`/groups/${selectedGroupId}/teams`" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Create New Team') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Team Name') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Team A, Start Nov 12" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ __('Add Member to') }} <span x-text="selectedTeamName" class="fw-bold"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Search -->
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text"
                               class="form-control"
                               placeholder="{{ __('Search employee by name or passport...') }}"
                               x-model="searchTerm"
                               @input.debounce.500ms="searchEmployees()">
                    </div>

                    <!-- Results List -->
                    <div class="list-group overflow-auto" style="max-height: 400px;">
                        <template x-if="isLoading">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status"></div>
                            </div>
                        </template>

                        <template x-for="employee in searchResults" :key="employee.id">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img :src="employee.photo" class="rounded-circle me-3" width="40" height="40">
                                    <div>
                                        <div class="fw-bold" x-text="employee.name"></div>
                                        <div class="text-muted small">
                                            <i class="bi bi-passport"></i> <span x-text="employee.passport"></span>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary"
                                        @click="addMember(employee.id)"
                                        :disabled="addingMemberId === employee.id">
                                    <span x-show="addingMemberId !== employee.id">{{ __('Add') }}</span>
                                    <span x-show="addingMemberId === employee.id" class="spinner-border spinner-border-sm"></span>
                                </button>
                            </div>
                        </template>

                        <template x-if="!isLoading && searchResults.length === 0 && searchTerm.length > 0">
                            <div class="text-center text-muted py-3">
                                {{ __('No employees found or all matched employees are already in a team in this group.') }}
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('groupTeamManager', () => ({
            selectedGroupId: null,
            selectedTeamId: null,
            selectedTeamName: '',
            searchTerm: '',
            searchResults: [],
            isLoading: false,
            addingMemberId: null,
            employerId: {{ $employer ? $employer->id : 'null' }},
            hasChanges: false, // Track if members were added

            setGroupId(id) {
                this.selectedGroupId = id;
            },

            openAddMemberModal(groupId, teamId, teamName) {
                this.selectedGroupId = groupId;
                this.selectedTeamId = teamId;
                this.selectedTeamName = teamName;
                this.searchTerm = '';
                this.searchResults = [];
                this.hasChanges = false;

                const modalEl = document.getElementById('addMemberModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();

                // Reload page on close if changes were made
                modalEl.addEventListener('hidden.bs.modal', () => {
                    if (this.hasChanges) {
                        window.location.reload();
                    }
                }, { once: true });

                // Load initial suggestions (optional)
                this.searchEmployees();
            },

            searchEmployees() {
                this.isLoading = true;

                const params = new URLSearchParams({
                    term: this.searchTerm,
                    group_id: this.selectedGroupId
                });

                if (this.employerId) {
                    params.append('employer_id', this.employerId);
                }

                fetch(`{{ route('api-web.groups.employees.search') }}?${params.toString()}`)
                    .then(res => res.json())
                    .then(data => {
                        this.searchResults = data;
                        this.isLoading = false;
                    });
            },

            addMember(employeeId) {
                this.addingMemberId = employeeId;

                fetch(`/groups/teams/${this.selectedTeamId}/members`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ employee_id: employeeId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Member added successfully', 'success');
                        // Remove from search results to prevent double add
                        this.searchResults = this.searchResults.filter(e => e.id !== employeeId);
                        // Mark that changes were made so we reload on close
                        this.hasChanges = true;
                    } else {
                        showToast(data.message, 'danger');
                    }
                })
                .catch(err => {
                    showToast('Error adding member', 'danger');
                })
                .finally(() => {
                    this.addingMemberId = null;
                });
            }
        }));
    });
</script>
@endpush
@endsection
