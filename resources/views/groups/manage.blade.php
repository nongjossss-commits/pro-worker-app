@extends('layouts.app')

@section('title', 'Manage Groups & Teams')

@section('content')
{{-- CSS Fix for Tailwind + Bootstrap Collapse Conflict & Enhanced UI --}}
<style>
    /* Fix for Tailwind's .collapse { visibility: collapse } conflicting with Bootstrap */
    /* Scope to .teams-accordion to prevent side effects on other accordions */
    .teams-accordion .accordion-collapse.collapse {
        visibility: visible !important;
    }
    .teams-accordion .accordion-collapse.collapsing {
        visibility: visible !important;
    }

    /* Enhanced UI Styles */
    .teams-accordion .accordion-button:not(.collapsed) {
        background-color: var(--bs-primary-light);
        color: white;
        box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
    }
    .teams-accordion .accordion-button:not(.collapsed)::after {
        filter: brightness(0) invert(1);
    }

    /* Highlight Animation for Employee Card */
    @keyframes highlightPulse {
        0% {
            box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.7);
            border-color: #f97316;
            background-color: #fff7ed;
            transform: scale(1.02);
        }
        50% {
            box-shadow: 0 0 0 10px rgba(249, 115, 22, 0);
            border-color: #f97316;
            background-color: #fff7ed;
            transform: scale(1.02);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(249, 115, 22, 0);
            border-color: #f97316; /* Keep border color */
            background-color: #fff7ed; /* Keep background color */
            transform: scale(1.02);
        }
    }

    .employee-card.highlighted {
        animation: highlightPulse 2s ease-out infinite;
        border: 2px solid #f97316 !important; /* Orange border */
        background-color: #fff7ed !important; /* Light orange background */
        z-index: 10; /* Ensure it stays on top */
    }
</style>

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

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
                <!-- Preserve active group/team if needed -->
                @if($activeGroup)
                    <input type="hidden" name="active_group" value="{{ $activeGroup->id }}">
                @endif
                <input type="hidden" name="active_team" value="{{ request('active_team') }}">

                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="nationality" class="form-select">
                        <option value="">-- {{ __('All Nationalities') }} --</option>
                        @foreach($nationalities as $nat)
                            <option value="{{ $nat }}" {{ request('nationality') == $nat ? 'selected' : '' }}>{{ $nat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="pink_card" class="form-select">
                        <option value="">-- {{ __('Pink Card Status') }} --</option>
                        <option value="has_card" {{ request('pink_card') == 'has_card' ? 'selected' : '' }}>{{ __('Has Pink Card') }}</option>
                        <option value="no_card" {{ request('pink_card') == 'no_card' ? 'selected' : '' }}>{{ __('No Pink Card') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="passport_type_myanmar" class="form-select">
                        <option value="">-- {{ __('Passport Type (Myanmar)') }} --</option>
                        <option value="PJ" {{ request('passport_type_myanmar') == 'PJ' ? 'selected' : '' }}>PJ</option>
                        <option value="PV" {{ request('passport_type_myanmar') == 'PV' ? 'selected' : '' }}>PV</option>
                        <option value="CI" {{ request('passport_type_myanmar') == 'CI' ? 'selected' : '' }}>CI</option>
                        <option value="CC" {{ request('passport_type_myanmar') == 'CC' ? 'selected' : '' }}>CC</option>
                        <option value="T.D." {{ request('passport_type_myanmar') == 'T.D.' ? 'selected' : '' }}>T.D.</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="passport_type_cambodia" class="form-select">
                        <option value="">-- {{ __('Passport Type (Cambodia)') }} --</option>
                        <option value="TD" {{ request('passport_type_cambodia') == 'TD' ? 'selected' : '' }}>TD</option>
                        <option value="P" {{ request('passport_type_cambodia') == 'P' ? 'selected' : '' }}>P</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ url()->current() }}{{ $activeGroup ? '?active_group=' . $activeGroup->id : '' }}" class="btn btn-secondary">{{ __('Clear') }}</a>
                </div>
            </form>
        </div>
    </div>

    <x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

    <!-- Bulk Action Bar -->
    <x-bulk-action-bar id="group-manage-bulk-bar">
        <li><a class="dropdown-item" href="#" id="bulk-advanced-edit-btn"><i class="bi bi-pencil-square me-2"></i>{{ __('Advanced Edit') }}</a></li>
        <li><a class="dropdown-item" href="#" id="bulk-advanced-export-btn"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Advanced Export') }}</a></li>
        <li><a class="dropdown-item" href="#" id="bulk-send-data-btn"><i class="bi bi-send me-2"></i>{{ __('Send Data') }}</a></li>
        <li><a class="dropdown-item" href="#" id="bulk-send-production-btn"><i class="bi bi-clipboard-data me-2"></i>{{ __('Send to P Production') }}</a></li>
        @can('manage-tickets')
        <li><a class="dropdown-item" href="#" id="bulk-generate-pdf-btn"><i class="bi bi-file-earmark-pdf me-2"></i>{{ __('Automated PDF') }}</a></li>
        @endcan
    </x-bulk-action-bar>

    <!-- Groups Tabs (Server Side) -->
    <ul class="nav nav-tabs mb-3">
        @foreach($allGroups as $group)
        <li class="nav-item position-relative">
            {{-- Using <a> instead of button for server-side switching --}}
            <a class="nav-link {{ ($activeGroup && $activeGroup->id == $group->id) ? 'active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['active_group' => $group->id]) }}">
                {{ $group->name }}
            </a>
            <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab position-absolute top-0 end-0 mt-1 me-1"
               style="font-size: 0.7rem;"
               draggable="true"
               data-drag-payload="{{ json_encode([
                   'title' => $group->name,
                   'subtitle' => 'Group',
                   'url' => request()->fullUrlWithQuery(['active_group' => $group->id])
               ]) }}"
               ondragstart="window.startDragGlobal(event, 'link', JSON.parse(this.dataset.dragPayload))"
               title="{{ __('Drag') }}"></i>
        </li>
        @endforeach
        @if($allGroups->isEmpty())
        <li class="nav-item">
            <span class="nav-link disabled">{{ __('No Groups Created Yet') }}</span>
        </li>
        @endif
    </ul>

    <!-- Groups Content (Only Active Group Rendered) -->
    <div class="tab-content bg-white border border-top-0 p-3 rounded-bottom shadow-sm">
        @if($activeGroup)
            <div class="tab-pane fade show active">
                <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-3 rounded">
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="mb-0">{{ $activeGroup->name }} <span class="text-muted fs-6">({{ __('Group') }})</span></h4>
                        <button class="btn btn-sm btn-outline-secondary"
                                @click="openEditGroupModal({{ $activeGroup->id }}, '{{ $activeGroup->name }}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('groups.destroy', $activeGroup->id) }}" method="POST" class="d-inline delete-group-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                    <button class="btn btn-outline-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#createTeamModal"
                            @click="setGroupId({{ $activeGroup->id }})">
                        <i class="bi bi-plus-circle"></i> {{ __('Create Team') }}
                    </button>
                </div>

                <!-- Teams List (Accordion) -->
                <div class="accordion shadow-sm teams-accordion" id="accordionGroup{{ $activeGroup->id }}">
                    @forelse($activeGroup->teams as $team)
                    <div class="accordion-item">
                        <h2 class="accordion-header position-relative" id="headingTeam{{ $team->id }}">
                            <button class="accordion-button {{ request('active_team') == $team->id ? '' : 'collapsed' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapseTeam{{ $team->id }}"
                                    aria-expanded="{{ request('active_team') == $team->id ? 'true' : 'false' }}"
                                    aria-controls="collapseTeam{{ $team->id }}">
                                <div class="d-flex align-items-center w-100">
                                    <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab me-2"
                                       draggable="true"
                                       data-drag-payload="{{ json_encode([
                                            'title' => $team->name,
                                            'subtitle' => 'Team in ' . $activeGroup->name,
                                            'url' => request()->fullUrlWithQuery(['active_group' => $activeGroup->id, 'active_team' => $team->id])
                                       ]) }}"
                                       ondragstart="window.startDragGlobal(event, 'link', JSON.parse(this.dataset.dragPayload))"
                                       @click.stop
                                       title="{{ __('Drag') }}"></i>
                                    <span class="fw-bold me-auto">{{ $team->name }}</span>
                                    <span class="badge bg-light text-dark border me-3">{{ $team->employees->count() }} {{ __('Members') }}</span>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseTeam{{ $team->id }}"
                             class="accordion-collapse collapse {{ request('active_team') == $team->id ? 'show' : '' }}"
                             aria-labelledby="headingTeam{{ $team->id }}">
                            <div class="accordion-body bg-white">
                                <!-- Team Actions -->
                                <div class="d-flex justify-content-end mb-3 gap-2">
                                    <button class="btn btn-sm btn-outline-warning"
                                            @click="openEditTeamModal({{ $team->id }}, '{{ $team->name }}')">
                                        <i class="bi bi-pencil"></i> {{ __('Edit Team') }}
                                    </button>
                                    <form action="{{ route('groups.teams.destroy', $team->id) }}" method="POST" class="d-inline delete-team-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> {{ __('Delete Team') }}
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-success"
                                            @click="openAddMemberModal({{ $activeGroup->id }}, {{ $team->id }}, '{{ $team->name }}')">
                                        <i class="bi bi-person-plus-fill"></i> {{ __('Add Member') }}
                                    </button>
                                </div>

                                <!-- Members Cards -->
                                <div class="employee-list" x-ignore>
                                    @if($type === 'independent')
                                        {{-- Group by Employer --}}
                                        @php
                                            $groupedEmployees = $team->employees->groupBy('employer_id');
                                        @endphp
                                        @forelse($groupedEmployees as $employerId => $members)
                                            @php
                                                $firstMember = $members->first();
                                                $employerName = $firstMember->employer ? ($firstMember->employer->employerNameTh . ' (' . $firstMember->employer->employerNameEn . ')') : __('Unknown Employer');
                                            @endphp
                                            <div class="mb-3">
                                                <h5 class="bg-light p-2 rounded border-start border-4 border-primary d-flex align-items-center flex-wrap">
                                                    <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab me-2"
                                                       draggable="true"
                                                       data-drag-payload="{{ json_encode([
                                                            'title' => $employerName . ' - ' . $team->name,
                                                            'count' => $members->count(),
                                                            'subtitle' => $members->count() . ' members',
                                                            'url' => '#'
                                                        ]) }}"
                                                       ondragstart="window.startDragGlobal(event, 'employees_bulk', JSON.parse(this.dataset.dragPayload))"
                                                       title="{{ __('Drag') }}"></i>
                                                    <i class="bi bi-building me-2"></i>{{ $employerName }}
                                                    @if(request('addrProvince') && $firstMember->employer)
                                                        @foreach($firstMember->employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                                                            <span class="badge bg-info text-white small ms-1" style="font-size: 0.7rem;">{{ $label }}</span>
                                                        @endforeach
                                                    @endif
                                                </h5>
                                                <div class="list-group">
                                                    @foreach($members as $member)
                                                        @php
                                                            $currentUrl = request()->fullUrlWithQuery([
                                                                'active_group' => $activeGroup->id,
                                                                'active_team' => $team->id,
                                                                'highlight_employee' => $member->id
                                                            ]);
                                                        @endphp
                                                        @include('partials._employee_card', [
                                                            'employee' => $member,
                                                            'loop' => $loop,
                                                            'showLocateButton' => true,
                                                            'hideTeamTags' => true,
                                                            'currentTeamId' => $team->id,
                                                            'elementId' => 'employee-card-team-' . $team->id . '-' . $member->id,
                                                            'dragUrl' => $currentUrl . '#employee-card-team-' . $team->id . '-' . $member->id,
                                                            'source_menu' => __('Group & Team')
                                                        ])
                                                    @endforeach
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center text-muted py-4 border rounded bg-light">
                                                {{ __('No members in this team yet.') }}
                                            </div>
                                        @endforelse
                                    @else
                                        {{-- No Grouping --}}
                                        <div class="list-group">
                                            @forelse($team->employees as $member)
                                                @php
                                                    $currentUrl = request()->fullUrlWithQuery([
                                                        'active_group' => $activeGroup->id,
                                                        'active_team' => $team->id,
                                                        'highlight_employee' => $member->id
                                                    ]);
                                                @endphp
                                                @include('partials._employee_card', [
                                                    'employee' => $member,
                                                    'loop' => $loop,
                                                    'showLocateButton' => true,
                                                    'hideTeamTags' => true,
                                                    'currentTeamId' => $team->id,
                                                    'elementId' => 'employee-card-team-' . $team->id . '-' . $member->id,
                                                    'dragUrl' => $currentUrl . '#employee-card-team-' . $team->id . '-' . $member->id,
                                                    'source_menu' => __('Group & Team')
                                                ])
                                            @empty
                                                <div class="text-center text-muted py-4 border rounded bg-light">
                                                    {{ __('No members in this team yet.') }}
                                                </div>
                                            @endforelse
                                        </div>
                                    @endif
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
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-collection fs-1 d-block mb-3"></i>
                <p>{{ __('Get started by creating a new Group tab above.') }}</p>
            </div>
        @endif
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
                        <input type="text" name="name" class="form-control" placeholder="{{ __('e.g. Passport Work, Start Date Group') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Group Modal -->
    <div class="modal fade" id="editGroupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form :action="`/groups/${selectedGroupId}`" method="POST" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Group') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Group Name') }}</label>
                        <input type="text" name="name" class="form-control" x-model="selectedGroupName" required>
                    </div>
                    <!-- Employer ID is intentionally omitted to prevent editing -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Team Modal -->
    <div class="modal fade" id="editTeamModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form :action="`/groups/teams/${selectedTeamId}`" method="POST" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Team') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Team Name') }}</label>
                        <input type="text" name="name" class="form-control" x-model="selectedTeamName" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
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
                        <input type="text" name="name" class="form-control" placeholder="{{ __('e.g. Team A, Start Nov 12') }}" required>
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
                               placeholder="{{ __('Search employee by name, passport, or employer...') }}"
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
                                    <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab me-2"
                                       draggable="true"
                                       :data-drag-payload="JSON.stringify({
                                            id: employee.id,
                                            title: employee.name,
                                            subtitle: employee.passport,
                                            photo_url: employee.photo,
                                            employer_name: employee.employer_name,
                                            url: '#'
                                       })"
                                       @dragstart="window.startDragGlobal($event, 'employee', JSON.parse($el.dataset.dragPayload))"
                                       title="{{ __('Drag') }}"></i>
                                    <img :src="employee.photo" class="rounded-circle me-3" width="40" height="40">
                                    <div>
                                        <div class="fw-bold" x-text="employee.name"></div>
                                        <div class="text-muted small">
                                            <i class="bi bi-passport"></i> <span x-text="employee.passport"></span>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-building"></i> <span x-text="employee.employer_name"></span>
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

@include('partials._employee_action_modals')
@include('employees.modals.advanced_export')
@include('employees.modals.select_target_employer_modal')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Highlighted Employee
        const highlightEmployeeId = "{{ request('highlight_employee') }}";
        const activeTeamId = "{{ request('active_team') }}";

        if (highlightEmployeeId && activeTeamId) {
            // Construct the ID based on the logic in manage.blade.php and _employee_card.blade.php
            // idPrefix is 'team-' . $team->id . '-'
            // cardId is 'employee-card-' . $idPrefix . $employee->id
            const cardId = `employee-card-team-${activeTeamId}-${highlightEmployeeId}`;
            const card = document.getElementById(cardId);

            if (card) {
                // Wait a brief moment for accordions/tabs to fully render if needed
                setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.classList.add('highlighted');

                    // Optional: Remove animation after some time, but keep style?
                    // User requested "prominently ... to avoid confusion", so keeping it highlighted is safer.
                    // We can stop the pulse animation after a while but keep the border.
                    setTimeout(() => {
                        card.style.animation = 'none'; // Stop pulsing
                        // The border and background defined in .employee-card.highlighted will remain
                    }, 6000);
                }, 500);
            }
        }

        // V4: Final refactored and consolidated delegated event listener for all delete forms
        document.body.addEventListener('submit', function(e) {
            const form = e.target;

            if (form.matches('.delete-group-form')) {
                e.preventDefault();
                Swal.fire({
                    title: '{{ __('Are you sure?') }}',
                    text: "{{ __('This will delete the entire group and all teams inside it. This action cannot be undone!') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Yes, delete group!') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else if (form.matches('.delete-team-form')) {
                e.preventDefault();
                Swal.fire({
                    title: '{{ __('Are you sure?') }}',
                    text: "{{ __('Do you want to delete this team? This action cannot be undone.') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Yes, delete team!') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else if (form.matches('.delete-member-form')) {
                e.preventDefault();
                Swal.fire({
                    title: '{{ __('Are you sure?') }}',
                    text: "{{ __('Do you want to remove this member from the team?') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Yes, remove!') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }
        });


        // Bulk Actions Handlers

        // Advanced Edit
        const bulkEditBtn = document.getElementById('bulk-advanced-edit-btn');
        if (bulkEditBtn) {
            bulkEditBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                // Create a form dynamically and submit POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('employees.bulk_edit.select_fields') }}';

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);

                const redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect_to';
                redirectInput.value = window.location.href;
                form.appendChild(redirectInput);

                selected.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employee_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            });
        }

        // Advanced Export
        const bulkExportBtn = document.getElementById('bulk-advanced-export-btn');
        if (bulkExportBtn) {
            bulkExportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                document.getElementById('export_employee_ids').value = JSON.stringify(selected);
                const modalEl = document.getElementById('advancedExportModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        }

        const bulkSendDataBtn = document.getElementById('bulk-send-data-btn');
        if (bulkSendDataBtn) {
            bulkSendDataBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
                const selected = Array.from(checkboxes).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                let employerIds = new Set();
                checkboxes.forEach(cb => {
                    const empId = cb.getAttribute('data-employer-id');
                    if (empId) employerIds.add(empId);
                });

                if (employerIds.size > 1) {
                     Swal.fire({
                        icon: 'warning',
                        title: '{{ __('Multiple Employers Selected') }}',
                        text: '{{ __('You selected employees from different employers. Please select employees from the same employer for one transaction.') }}'
                    });
                    return;
                }

                window.pendingTicketEmployeeIds = selected;
                const modalEl = document.getElementById('selectTargetEmployerModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        }

        const bulkSendProductionBtn = document.getElementById('bulk-send-production-btn');
        if (bulkSendProductionBtn) {
            bulkSendProductionBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
                const selected = Array.from(checkboxes).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                let employerIds = new Set();
                checkboxes.forEach(cb => {
                    const empId = cb.getAttribute('data-employer-id');
                    if (empId) employerIds.add(empId);
                });

                // REMOVED BLOCKING: Now allowing multiple employers to create Independent Project
                // if (employerIds.size > 1) { ... }

                const idsJson = encodeURIComponent(JSON.stringify(selected));
                const employerId = employerIds.size === 1 ? employerIds.values().next().value : '';

                let url = '{{ route("production.create") }}?employee_ids_json=' + idsJson;
                if(employerId) {
                    url += '&employer_id=' + employerId;
                }

                window.location.href = url;
            });
        }

        // Handle Bulk Generate PDF
        const bulkGeneratePdfBtn = document.getElementById('bulk-generate-pdf-btn');
        if (bulkGeneratePdfBtn) {
            bulkGeneratePdfBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = window.getGlobalSelectedIds(); // Use global helper because this page uses standardized checkboxes/logic

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                // Create form to post to generation modal setup
                const form = document.createElement('form');
                form.method = 'POST';
                // Use relative path to avoid protocol mismatch (http vs https) redirects which strip POST data
                form.action = '{{ route("admin.pdf-templates.generate.modal", [], false) }}';

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrf);

                selected.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employees[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            });
        }
    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('groupTeamManager', () => ({
            selectedGroupId: null,
            selectedGroupName: '',
            selectedTeamId: null,
            selectedTeamName: '',
            searchTerm: '',
            searchResults: [],
            isLoading: false,
            addingMemberId: null,
            employerId: {{ $employer ? $employer->id : 'null' }},
            hasChanges: false,

            setGroupId(id) {
                this.selectedGroupId = id;
            },

            openEditGroupModal(id, name) {
                this.selectedGroupId = id;
                this.selectedGroupName = name;
                new bootstrap.Modal(document.getElementById('editGroupModal')).show();
            },

            openEditTeamModal(id, name) {
                this.selectedTeamId = id;
                this.selectedTeamName = name;
                new bootstrap.Modal(document.getElementById('editTeamModal')).show();
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

                modalEl.addEventListener('hidden.bs.modal', () => {
                    if (this.hasChanges) {
                        const url = new URL(window.location.href);
                        url.searchParams.set('active_group', this.selectedGroupId);
                        url.searchParams.set('active_team', this.selectedTeamId);
                        window.location.href = url.toString();
                    }
                }, { once: true });

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
                        this.searchResults = this.searchResults.filter(e => e.id !== employeeId);
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
