@if(\App\Facades\SuperAdmin::isVisible('dashboard'))
@can('view-dashboard')
<a href="{{ route('dashboard') }}" class="list-group-item list-group-item-action"><i class="bi bi-pie-chart-fill me-2"></i>{{ __('Dashboard') }}</a>
@endcan
@endif

@if(\App\Facades\SuperAdmin::isVisible('activity_logs'))
@hasanyrole('admin|super-admin')
<a href="{{ route('admin.activity-logs.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
    <i class="bi bi-activity me-2"></i>{{ __('Activity Logs') }}
</a>
@endhasanyrole
@endif

@if(\App\Facades\SuperAdmin::isVisible('notifications'))
@can('view-notifications')
    <a href="{{ route('notifications.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-bell-fill me-2"></i>{{ __('Notifications') }}</span>
            @if(isset($totalNotificationCount) && $totalNotificationCount > 0)
                <span class="badge bg-danger rounded-pill">{{ $totalNotificationCount }}</span>
            @endif
        </div>
    </a>
    @endif {{-- End check for notifications --}}
    @if(\App\Facades\SuperAdmin::isVisible('incomplete_data'))
    @can('manage-tickets')
    <a href="{{ route('admin.incomplete_employees.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.incomplete_employees.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-exclamation-octagon-fill me-2"></i>{{ __('Incomplete Data') }}</span>
            @if(isset($incompleteCount) && $incompleteCount > 0)
                <span class="badge bg-warning text-dark rounded-pill">{{ $incompleteCount }}</span>
            @endif
        </div>
    </a>
    @endcan
    @endif
@endcan
{{-- START V2.4: Smart Ticket Links --}}
{{-- V2.4: Admin/Staff Ticket Inbox --}}
{{-- Visible if the user has 'manage-tickets' permission. This takes precedence. --}}
@can('manage-tickets')
@if(\App\Facades\SuperAdmin::isVisible('ticket_inbox'))
<a href="{{ route('admin.tickets.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.tickets.index') ? 'active' : '' }}">
    <div class="d-flex justify-content-between align-items-center">
        <span><i class="bi bi-inbox-fill me-2"></i>{{ __('Ticket Inbox') }}</span>
        @if(isset($adminTicketUnreadCount) && $adminTicketUnreadCount > 0)
            <span class="badge bg-danger rounded-pill">{{ $adminTicketUnreadCount }}</span>
        @endif
    </div>
</a>
<a href="{{ route('admin.tickets.create') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.tickets.create') ? 'active' : '' }}" style="padding-left: 2.5rem;">
    <i class="bi bi-plus-circle me-2"></i>{{ __('Create New Ticket (Admin)') }}
</a>
@endif
@else
{{-- V2.4: Employer Ticket Menu --}}
{{-- Visible ONLY if the user CANNOT 'manage-tickets' AND is an 'employer'. --}}
@if(\App\Facades\SuperAdmin::isVisible('employer_ticket'))
@role('employer')
<a href="{{ route('tickets.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
    <div class="d-flex justify-content-between align-items-center">
        <span><i class="bi bi-ticket-detailed-fill me-2"></i>{{ __('Submit Request/Track Work') }}</span>
        @if(isset($employerTicketUnreadCount) && $employerTicketUnreadCount > 0)
            <span class="badge bg-danger rounded-pill">{{ $employerTicketUnreadCount }}</span>
        @endif
    </div>
</a>
@endrole
@endif
@endcan
{{-- END V2.4: Smart Ticket Links --}}
<hr>
@if(\App\Facades\SuperAdmin::isVisible('employers'))
@can('view-employers')
<a href="{{ route('employers.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employers.*') ? 'active' : '' }}"><i class="bi bi-person-vcard-fill me-2"></i>{{ __('Employers') }}</a>
@endcan
@endif

@if(\App\Facades\SuperAdmin::isVisible('employees'))
@can('view-employees')
<a href="{{ route('employees.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employees.*') ? 'active' : '' }}">
    <i class="bi bi-people-fill me-2"></i>{{ __('Employees') }}
</a>
<a href="{{ route('employees.history') }}" class="list-group-item list-group-item-action {{ request()->routeIs('employees.history') ? 'active' : '' }}" style="padding-left: 2.5rem;">
    <i class="bi bi-person-badge me-2"></i>{{ __('Employment History') }}
</a>
<a href="{{ route('groups.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('groups.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
    <i class="bi bi-people-fill me-2"></i>{{ __('Group & Team') }}
</a>
@endcan
@endif

@if(\App\Facades\SuperAdmin::isVisible('finance'))
@hasanyrole('admin|super-admin')
<a href="{{ route('finance.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('finance.*') ? 'active' : '' }}">
    <i class="bi bi-cash-coin me-2"></i>{{ __('Finance') }}
</a>
@endhasanyrole
@endif

{{-- V2.4-S14: Production & Workflow Menus --}}
@if(Route::has('production.index'))
    @if(\App\Facades\SuperAdmin::isVisible('production'))
    <a href="{{ route('production.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('production.*') ? 'active' : '' }}">
        <i class="bi bi-clipboard-data-fill me-2"></i>{{ __('P Production') }}
    </a>
    @endif
    @if(\App\Facades\SuperAdmin::isVisible('workflow'))
    <a href="{{ route('workflow.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('workflow.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
        <i class="bi bi-diagram-3-fill me-2"></i>{{ __('Workflow') }}
    </a>
    @endif
    @if(\App\Facades\SuperAdmin::isVisible('registration_resolution'))
    <a href="{{ route('production.registration.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('production.registration.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
        <i class="bi bi-person-lines-fill me-2"></i>{{ __('Registration Resolution') }}
    </a>
    @endif
    @if(\App\Facades\SuperAdmin::isVisible('renewal_resolution'))
    <a href="{{ route('production.renewal.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('production.renewal.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
        <i class="bi bi-arrow-repeat me-2"></i>{{ __('Renewal Resolution') }}
    </a>
    @endif
@endif

@if(\App\Facades\SuperAdmin::isVisible('importers'))
@can('view-importers')
<a href="{{ route('importers.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('importers.*') ? 'active' : '' }}"><i class="bi bi-box-arrow-in-down-left me-2"></i>{{ __('Importers') }}</a>
@endcan
@endif
@if(\App\Facades\SuperAdmin::isVisible('agents'))
@can('view-agents')
<a href="{{ route('agents.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('agents.*') ? 'active' : '' }}"><i class="bi bi-person-square me-2"></i>{{ __('Agents') }}</a>
@endcan
@endif
@if(\App\Facades\SuperAdmin::isVisible('delegates'))
@can('view-delegates')
<a href="{{ route('delegates.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('delegates.*') ? 'active' : '' }}"><i class="bi bi-people-fill me-2"></i>{{ __('Delegates') }}</a>
@endcan
@endif

@if(\App\Facades\SuperAdmin::isVisible('user_management'))
@can('manage-users')
<a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-person-fill-gear me-2"></i>{{ __('User Management') }}</a>
@endcan
@endif

@canany(['manage-roles', 'manage-settings'])
<hr>
@if(\App\Facades\SuperAdmin::isVisible('roles_permissions'))
<a href="{{ route('admin.roles_permissions.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.roles_permissions.*') ? 'active' : '' }}"><i class="bi bi-shield-lock-fill me-2"></i>{{ __('Roles & Permissions') }}</a>
@endif
@if(\App\Facades\SuperAdmin::isVisible('pdf_templates'))
@can('view-pdf-templates')
<a href="{{ route('admin.pdf-templates.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.pdf-templates.*') ? 'active' : '' }}">
    <i class="bi bi-file-earmark-pdf-fill me-2"></i>{{ __('PDF Templates') }}
</a>
@endcan
@endif
@endcanany
@if(\App\Facades\SuperAdmin::isVisible('central_trash'))
@can('view-trash')
    <a class="list-group-item list-group-item-action {{ request()->routeIs('admin.trash.index') ? 'active' : '' }}" href="{{ route('admin.trash.index') }}">
        <i class="bi bi-trash-fill me-2"></i>
        {{ __('Central Trash') }}
    </a>
@endcan
@endif

@role('super-admin')
<hr>
<a class="list-group-item list-group-item-action {{ request()->routeIs('super-admin.settings.index') ? 'active' : '' }}" href="{{ route('super-admin.settings.index') }}">
    <i class="bi bi-gear-fill me-2"></i>
    {{ __('Super Admin Settings') }}
</a>
@endrole
