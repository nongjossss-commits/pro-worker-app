{{-- Language links for the manual print bar (admin view and public share link alike). --}}
<span class="manual-lang-switch">
    @foreach(\App\Services\ManualShareService::LANGS as $code => $label)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" class="{{ app()->getLocale() === $code ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</span>
<style>
    .manual-lang-switch { margin-left: 14px; white-space: nowrap; }
    .manual-lang-switch a { color: inherit; opacity: .75; text-decoration: none; margin: 0 5px; font-weight: 600; font-size: 13px; }
    .manual-lang-switch a:hover { opacity: 1; }
    .manual-lang-switch a.active { opacity: 1; text-decoration: underline; text-underline-offset: 3px; }
</style>
