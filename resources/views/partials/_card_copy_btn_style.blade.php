{{-- Small copy button next to a value on employee / work cards (.pv-copy — handler in layouts/_app_scripts). --}}
@once
<style>
    .pv-copy.wcf-copy, .emp-card-field .pv-copy {
        display: inline-flex; align-items: center; justify-content: center; flex: none;
        width: 1.35rem; height: 1.35rem; padding: 0; font-size: .72rem; line-height: 1;
        border: 1px solid var(--bs-border-color); border-radius: .35rem;
        background: var(--bs-body-bg); color: var(--bs-secondary-color); cursor: pointer;
    }
    .pv-copy.wcf-copy:hover, .emp-card-field .pv-copy:hover { color: var(--bs-primary); border-color: var(--bs-primary); }
    .pv-copy.wcf-copy.is-copied, .emp-card-field .pv-copy.is-copied { color: var(--bs-success); border-color: var(--bs-success); }
</style>
@endonce
