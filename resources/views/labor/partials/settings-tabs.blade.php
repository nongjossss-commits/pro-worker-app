{{--
    Shared tab bar for the consolidated "Settings" menu — Manage Teams,
    Manage Members, and Manage Users each stay their own route/controller
    (unchanged), this just ties their index pages together visually as one
    menu. Mirrors resources/views/labor/partials/finance-tabs.blade.php.
--}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('labor.teams.*') ? 'active' : '' }}" href="{{ route('labor.teams.index') }}">
            <i class="bi bi-people-fill me-1"></i>{{ __('Manage Teams') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('labor.team-members.*') ? 'active' : '' }}" href="{{ route('labor.team-members.index') }}">
            <i class="bi bi-person-vcard me-1"></i>{{ __('Manage Members') }}
        </a>
    </li>
    @role('super-admin')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('labor.users.*') ? 'active' : '' }}" href="{{ route('labor.users.index') }}">
            <i class="bi bi-person-fill-gear me-1"></i>{{ __('Manage Users') }}
        </a>
    </li>
    @endrole
</ul>
