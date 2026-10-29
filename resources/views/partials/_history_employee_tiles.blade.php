{{--
    "Completed Work History" popup list (partials/_history_panel) — Workflow,
    Registration and Renewal resolution. Finished work needs no steps, so each
    person is shown as the same tile as the Employees menu (partials/_employee_card
    inside .emp-tile-grid), grouped under the team they were finished in.
    The card's checkbox feeds the page's selection bar (download, export, edit…).

    @include('partials._history_employee_tiles', [
        'groups' => collect(['team name' or '' => Collection<Employee>]),
        'historyEmployerId' => the job's employer — flags people notified out
                               or now with another employer,
    ])
--}}
@foreach($groups as $teamName => $groupEmployees)
    @if($groups->count() > 1 || $teamName !== '')
        <div class="d-flex align-items-center gap-2 mb-2 mt-3 history-team-heading">
            <i class="bi bi-people-fill text-primary"></i>
            <span class="fw-bold">{{ $teamName !== '' ? $teamName : __('No Team') }}</span>
            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">{{ $groupEmployees->count() }}</span>
        </div>
    @endif
    <div class="list-group emp-tile-grid">
        @foreach($groupEmployees as $employee)
            <div>
            @include('partials._employee_card', [
                'employee' => $employee,
                'loop' => $loop,
                'idPrefix' => 'history-',
                'historyEmployerId' => $historyEmployerId,
            ])
            </div>
        @endforeach
    </div>
@endforeach
