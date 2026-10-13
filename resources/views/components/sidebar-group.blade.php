{{--
    Collapsible group in the main sidebar (partials/sidebar-menu). The items
    inside keep their own permission checks; a group whose items all render
    empty for this user is not shown at all. Only one group is open at a time
    (data-bs-parent), the group holding the current page opens by itself, and
    the header shows the total of the count badges inside it so they stay
    visible while the group is closed.

    <x-sidebar-group id="data" icon="bi-database" :label="__('Data')"> … links … </x-sidebar-group>
--}}
@props(['id', 'icon', 'label'])

@if($slot->hasActualContent())
    @php
        $html = (string) $slot;
        // The current page's link (or the Finance parent) is inside → start open.
        $open = (bool) preg_match('/class="[^"]*\blist-group-item\b[^"]*\b(active|active-parent)\b/', $html);
        // Sum of the numeric badges inside (notifications, incomplete, tickets…).
        preg_match_all('/<span class="badge[^"]*">\s*(\d+)\s*<\/span>/', $html, $m);
        $badgeTotal = array_sum(array_map('intval', $m[1] ?? []));
    @endphp
    <div class="sidebar-group" data-sidebar-group>
        <button type="button"
                class="list-group-item list-group-item-action sidebar-group-toggle d-flex align-items-center {{ $open ? 'has-active' : 'collapsed' }}"
                data-bs-toggle="collapse" data-bs-target="#sidebar-group-{{ $id }}"
                aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="sidebar-group-{{ $id }}">
            <i class="bi {{ $icon }} me-2"></i>
            <span class="flex-grow-1 text-start">{{ $label }}</span>
            @if($badgeTotal > 0)
                <span class="badge bg-danger rounded-pill me-2 sidebar-group-badge">{{ $badgeTotal }}</span>
            @endif
            <i class="bi bi-chevron-down sidebar-group-chevron"></i>
        </button>
        <div class="collapse sidebar-group-body {{ $open ? 'show' : '' }}" id="sidebar-group-{{ $id }}" data-bs-parent="#main-nav">
            {{ $slot }}
        </div>
    </div>
@endif

@once
<style>
    .sidebar-group-toggle { font-weight: 600; border: 0; }
    .sidebar-group-toggle.has-active { color: var(--bs-primary); }
    .sidebar-group-chevron { font-size: .8rem; transition: transform .2s ease; }
    .sidebar-group-toggle[aria-expanded="true"] .sidebar-group-chevron { transform: rotate(180deg); }
    /* Items inside a group sit one step in, under the group title. */
    .sidebar-group-body > .list-group-item { padding-left: 2.6rem; }
    .sidebar-group-body { border-left: 3px solid var(--bs-primary-border-subtle); margin-left: 1rem; }
</style>
@endonce
