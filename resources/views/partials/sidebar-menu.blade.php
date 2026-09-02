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

@if(\App\Facades\SuperAdmin::isVisible('duplicate_records'))
@can('manage-tickets')
<a href="{{ route('admin.duplicate-records.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.duplicate-records.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
    <div class="d-flex justify-content-between align-items-center">
        <span><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('Duplicate Records') }}</span>
        @if(isset($duplicateRecordCount) && $duplicateRecordCount > 0)
            <span class="badge bg-danger rounded-pill">{{ $duplicateRecordCount }}</span>
        @endif
    </div>
</a>
@endcan
@endif

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
@php
    $isFinanceActive = request()->routeIs('finance.index') || request()->routeIs('finance.create');
    $isFinanceSubMenuActive = request()->routeIs('finance.bank-accounts.*') || request()->routeIs('finance.expense-categories.*') || request()->routeIs('finance.expenses.*') || request()->routeIs('finance.profiles.*') || request()->routeIs('finance.books.*') || request()->routeIs('finance.books-reports.*');
@endphp

<div class="list-group-item list-group-item-action p-0 {{ $isFinanceActive || $isFinanceSubMenuActive ? 'active-parent' : '' }}" style="border: none;">
    <div class="d-flex align-items-stretch w-100">
        {{-- Left side: Clickable link to Finance Index --}}
        <a href="{{ route('finance.index') }}" class="flex-grow-1 d-flex align-items-center {{ $isFinanceActive ? 'active text-white' : 'text-dark' }}" style="padding: 0.75rem 1.25rem; text-decoration: none; {{ $isFinanceActive ? 'background-color: var(--bs-primary);' : '' }}">
            <i class="bi bi-cash-coin me-2"></i>{{ __('Finance') }}
        </a>

        {{-- Right side: Clickable toggle for sub-menus --}}
        <button class="btn btn-link shadow-none {{ $isFinanceActive ? 'text-white' : 'text-dark' }} d-flex align-items-center justify-content-center" type="button" data-bs-toggle="collapse" data-bs-target="#financeSubMenu" aria-expanded="{{ $isFinanceSubMenuActive ? 'true' : 'false' }}" aria-controls="financeSubMenu" style="width: 40px; padding: 0; border-radius: 0; {{ $isFinanceActive ? 'background-color: var(--bs-primary);' : '' }}" onclick="this.querySelector('i').classList.toggle('bi-chevron-down'); this.querySelector('i').classList.toggle('bi-chevron-up');">
            <i class="bi bi-chevron-{{ $isFinanceSubMenuActive ? 'up' : 'down' }}"></i>
        </button>
    </div>
</div>

<div class="collapse {{ $isFinanceSubMenuActive ? 'show' : '' }}" id="financeSubMenu">
    @php
        // Badge: นับ transactions ที่ paid > 0 แต่ wht ยังไม่ได้รับ
        $whtPendingCount = \App\Models\FinancialTransaction::where('paid_amount', '>', 0)
            ->where('withholding_tax_amount', '>', 0)
            ->where('wht_status', 'pending')
            ->count();
    @endphp
    <a href="{{ route('finance.books.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('finance.books.*') ? 'active' : '' }}" style="padding-left: 3rem; border-top: none;">
        <i class="bi bi-journal-text me-2"></i>{{ __('Income & Expense Books') }}
    </a>
    <a href="{{ route('finance.wht_inbox') }}" class="list-group-item list-group-item-action {{ request()->routeIs('finance.wht_inbox') || request()->routeIs('finance.wht_received') ? 'active' : '' }}" style="padding-left: 3rem;">
        <i class="bi bi-inbox-fill me-2"></i>{{ __('WHT Inbox') }}
        @if($whtPendingCount > 0)
            <span class="badge bg-warning text-dark ms-1">{{ $whtPendingCount }}</span>
        @endif
    </a>
    <a href="{{ route('finance.bank-accounts.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('finance.bank-accounts.*') ? 'active' : '' }}" style="padding-left: 3rem;">
        <i class="bi bi-bank me-2"></i>{{ __('Bank Accounts') }}
    </a>
    <a href="{{ route('finance.expense-categories.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('finance.expense-categories.*') ? 'active' : '' }}" style="padding-left: 3rem;">
        <i class="bi bi-tags me-2"></i>{{ __('Expense Categories') }}
    </a>
    @if(\App\Facades\SuperAdmin::isVisible('financial_profiles'))
    <a href="{{ route('finance.profiles.builder') }}" class="list-group-item list-group-item-action {{ request()->routeIs('finance.profiles.*') ? 'active' : '' }}" style="padding-left: 3rem;">
        <i class="bi bi-file-earmark-person me-2"></i>{{ __('Financial Profiles') }}
    </a>
    @endif
</div>
@endhasanyrole
@endif

@hasanyrole('admin|super-admin|staff')
@if(\App\Facades\SuperAdmin::isVisible('sales'))
<a href="{{ route('sales.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('sales.*') ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill me-2"></i>{{ __('Read and Sale') }}
</a>
@endif
@endhasanyrole

{{-- V2.4-S14: Production & Workflow Menus --}}
@hasanyrole('admin|super-admin|staff|caretaker')
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
    <a href="{{ route('production.registration.redirect') }}" class="list-group-item list-group-item-action {{ request()->routeIs('production.registration.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
        <i class="bi bi-person-lines-fill me-2"></i>{{ __('Registration Resolution') }}
    </a>
    @endif
    @if(\App\Facades\SuperAdmin::isVisible('renewal_resolution'))
    <a href="{{ route('production.renewal.redirect') }}" class="list-group-item list-group-item-action {{ request()->routeIs('production.renewal.*') ? 'active' : '' }}" style="padding-left: 2.5rem;">
        <i class="bi bi-arrow-repeat me-2"></i>{{ __('Renewal Resolution') }}
    </a>
    @endif
@endif
@endhasanyrole

@if(auth()->user()->hasAnyRole(['super-admin', 'admin', 'staff']))
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
@endif

@if(\App\Facades\SuperAdmin::isVisible('user_management'))
@can('manage-users')
<a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-person-fill-gear me-2"></i>{{ __('User Management') }}</a>
@endcan
@endif

{{-- "Roles & Permissions" menu removed — merged into User Management page
     bottom section (still reachable via /admin/roles-permissions direct link
     for any existing bookmarks). --}}

@canany(['manage-roles', 'manage-settings', 'view-pdf-templates'])
<hr>
@endcanany

@if(\App\Facades\SuperAdmin::isVisible('pdf_templates'))
@can('view-pdf-templates')
<a href="{{ route('admin.pdf-templates.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.pdf-templates.*') ? 'active' : '' }}">
    <i class="bi bi-file-earmark-pdf-fill me-2"></i>{{ __('PDF Templates') }}
</a>
@endcan
@endif
@if(\App\Facades\SuperAdmin::isVisible('central_trash'))
@can('view-trash')
    <a class="list-group-item list-group-item-action {{ request()->routeIs('admin.trash.index') ? 'active' : '' }}" href="{{ route('admin.trash.index') }}">
        <i class="bi bi-trash-fill me-2"></i>
        {{ __('Central Trash') }}
    </a>
@endcan
@endif

@if(auth()->user()->hasRole('super-admin') || (auth()->user()->hasRole('admin') && auth()->user()->labor_access_level !== 'none'))
<hr>
<a class="list-group-item list-group-item-action {{ request()->routeIs('labor.*') ? 'active' : '' }}" href="{{ route('labor.dashboard') }}">
    <i class="bi bi-briefcase-fill me-2"></i>
    {{ __('Pro Walker Labour') }}
</a>
@endif

@role('super-admin')
<hr>
<a class="list-group-item list-group-item-action {{ request()->routeIs('super-admin.settings.index') ? 'active' : '' }}" href="{{ route('super-admin.settings.index') }}">
    <i class="bi bi-gear-fill me-2"></i>
    {{ __('Super Admin Settings') }}
</a>
@endrole
