{{--
    Shared tab bar for the consolidated "การเงิน / Finance" menu — Central
    Billing, Bills, Tax Invoices, and WHT Certificates each stay their own
    route/controller (unchanged), this just ties their index pages together
    visually as one menu instead of 4 separate top-level nav items.
--}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('labor.charges.*') ? 'active' : '' }}" href="{{ route('labor.charges.index') }}">
            <i class="bi bi-receipt me-1"></i>{{ __('Central Billing') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('labor.bills.*') ? 'active' : '' }}" href="{{ route('labor.bills.index') }}">
            <i class="bi bi-file-earmark-text me-1"></i>{{ __('Bills') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('labor.tax-invoices.*') ? 'active' : '' }}" href="{{ route('labor.tax-invoices.index') }}">
            <i class="bi bi-file-earmark-ruled me-1"></i>{{ __('Tax Invoices') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('labor.wht-certificates.*') ? 'active' : '' }}" href="{{ route('labor.wht-certificates.index') }}">
            <i class="bi bi-file-earmark-minus me-1"></i>{{ __('WHT Certificates') }}
        </a>
    </li>
</ul>
