{{--
    Display mode button — sits right after the language switcher in the top
    bar (layouts/app, labor/layout). Uses window.appTheme from
    partials/_theme_init. Which icon/label shows and which item is ticked
    come from <html data-theme-mode> via CSS, so they are right on first
    paint without waiting for JS.
--}}
<div class="dropdown theme-switcher">
    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
            title="{{ __('Display mode') }}" aria-label="{{ __('Display mode') }}">
        <span data-theme-show="light"><i class="bi bi-sun-fill"></i><span class="d-none d-md-inline ms-1">{{ __('Light') }}</span></span>
        <span data-theme-show="dark"><i class="bi bi-moon-stars-fill"></i><span class="d-none d-md-inline ms-1">{{ __('Dark') }}</span></span>
        <span data-theme-show="system"><i class="bi bi-circle-half"></i><span class="d-none d-md-inline ms-1">{{ __('Device default') }}</span></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header">{{ __('Display mode') }}</h6></li>
        <li><button type="button" class="dropdown-item d-flex align-items-center gap-2" data-theme-set="light">
            <i class="bi bi-sun-fill"></i> {{ __('Light') }} <i class="bi bi-check2 ms-auto theme-check"></i></button></li>
        <li><button type="button" class="dropdown-item d-flex align-items-center gap-2" data-theme-set="dark">
            <i class="bi bi-moon-stars-fill"></i> {{ __('Dark') }} <i class="bi bi-check2 ms-auto theme-check"></i></button></li>
        <li><button type="button" class="dropdown-item d-flex align-items-center gap-2" data-theme-set="system">
            <i class="bi bi-circle-half"></i> {{ __('Device default') }} <i class="bi bi-check2 ms-auto theme-check"></i></button></li>
    </ul>
</div>

@once
<style>
    .theme-switcher [data-theme-show], .theme-switcher .theme-check { display: none; }
    html[data-theme-mode="light"] .theme-switcher [data-theme-show="light"],
    html[data-theme-mode="dark"] .theme-switcher [data-theme-show="dark"],
    html[data-theme-mode="system"] .theme-switcher [data-theme-show="system"] { display: inline; }
    html[data-theme-mode="light"] .theme-switcher [data-theme-set="light"] .theme-check,
    html[data-theme-mode="dark"] .theme-switcher [data-theme-set="dark"] .theme-check,
    html[data-theme-mode="system"] .theme-switcher [data-theme-set="system"] .theme-check { display: inline-block; }
    .theme-switcher .dropdown-item { min-width: 190px; }
</style>
<script>
    document.addEventListener('click', function (e) {
        var item = e.target.closest('.theme-switcher [data-theme-set]');
        if (item && window.appTheme) window.appTheme.set(item.getAttribute('data-theme-set'));
    });
</script>
@endonce
