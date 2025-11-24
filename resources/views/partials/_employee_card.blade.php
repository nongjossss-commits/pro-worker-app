@php
    $employerName = $employee->employer->employerNameTh ?? 'N/A';
@endphp

<div id="employee-card-{{ $employee->id }}" class="employee-card card mb-3"
     draggable="true"
     ondragstart="window.startDragGlobal(event, 'employee', {
        id: {{ $employee->id }},
        title: '{{ addslashes($employee->employeeNameTh) }}',
        subtitle: '{{ addslashes($employee->employeeNameEn) }}'
     })">
    <div class="card-body d-flex align-items-center">
        <div class="me-3">
            <input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-id="{{ $employee->id }}" data-employer-id="{{ $employee->employer_id }}">
        </div>

        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-profile.png') }}"
            alt="Photo" class="employee-photo-thumb" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; margin-right: 1rem;">

        <div class="employee-info flex-grow-1 position-relative">
            {{-- Group & Team Tags --}}
            @if($employee->teams && $employee->teams->count() > 0 && !($hideTeamTags ?? false))
                <div class="position-absolute top-0 end-0 mt-0 me-2 d-flex flex-wrap justify-content-end gap-1" style="max-width: 250px;">
                    @foreach($employee->teams as $team)
                        @if($team->group)
                        <a href="{{ route('groups.locate_member', ['group' => $team->group->id, 'employee' => $employee->id]) }}"
                           class="badge text-decoration-none"
                           style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: normal; font-size: 0.75rem;"
                           title="{{ __('Group') }}: {{ $team->group->name }} | {{ __('Team') }}: {{ $team->name }}"
                           onclick="event.preventDefault(); Swal.fire({
                               title: '{{ __('Go to group') }}',
                               text: '{{ __('Do you want to go to group') }}: {{ $team->group->name }}?',
                               icon: 'question',
                               showCancelButton: true,
                               confirmButtonText: '{{ __('Go') }}',
                               cancelButtonText: '{{ __('Cancel') }}'
                           }).then((result) => { if(result.isConfirmed) window.location.href = this.href; })">
                            <i class="bi bi-tag-fill me-1"></i>{{ $team->group->name }}
                        </a>
                        @endif
                    @endforeach
                </div>
            @endif

            <span class="employee-name-en">
                @if(isset($pagination) && $pagination instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ ($pagination->currentPage() - 1) * $pagination->perPage() + $loop->iteration }}.
                @else
                    {{ $loop->iteration }}.
                @endif
                {{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? __('No English Name') }}
            </span>

            <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employee" data-model-id="{{ $employee->id }}" title="{{ __('Preview Data') }}">
                <i class="bi bi-search"></i>
            </button>

            @if($employee->employeeNationality)
                @php
                    $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                @endphp
                @if($countryCode)
                    <span class="badge bg-light text-dark ms-2 d-inline-flex align-items-center">
                        <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" style="width: 16px; height: 12px; margin-right: 5px;">
                        <span>{{ $employee->employeeNationality }}</span>
                    </span>
                @endif
            @endif

            <span class="employee-name-th d-block">
                {{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'N/A' }} ({{ $employee->job_title ?? 'N/A' }})
            </span>

            <span class="employer-name d-block text-muted">
                {{ __('Employer:') }} {{ $employerName }}
                @if($employee->employer)
                <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employer" data-model-id="{{ $employee->employer->id }}" title="{{ __('Preview Data') }}">
                    <i class="bi bi-search"></i>
                </button>
                @endif
            </span>

            <div class="document-details small mt-2">
                Passport: {{ $employee->employeePassport ?? '-' }} ({{ __('Expires:') }} {{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : '-' }})
                <br>
                Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) | 90-Day: {{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}
            </div>
        </div>

        <div class="employee-actions">
            @if(isset($isTrashView) && $isTrashView)
                @include('admin.trash._action_buttons', ['modelName' => 'employees', 'item' => $employee])
            @else
                <div class="d-flex align-items-center gap-2">
                    <x-employee-action-buttons :employee="$employee" :show-locate-button="($showLocateButton ?? false)" />
                    @if(isset($currentTeamId))
                        <form action="{{ route('groups.teams.members.remove', ['team' => $currentTeamId, 'employee' => $employee->id]) }}" method="POST" class="d-inline delete-member-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Remove from Team') }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
