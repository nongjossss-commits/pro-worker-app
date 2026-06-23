{{--
    User Manual button — renders in the navbar (left of the language switcher).

    Usage (drop this 1 line in any page's content section):
        <x-help-button manual="employers" title="ข้อมูลนายจ้าง" />

    Props:
        - $manual : string  REQUIRED — matches a partial at resources/views/manuals/{$manual}.blade.php
        - $title  : string  REQUIRED — modal title (shown in Thai for end-users)

    Behavior:
        - Trigger button is @push'd into the `navbar-help` stack (rendered by
          layouts/app.blade.php inside the top-right toolbar).
        - Modal stays inline where the component is called — Bootstrap modals
          work regardless of DOM location because they portal to <body>.
        - If the manual partial doesn't exist, nothing renders (graceful) so a
          new menu without a written manual doesn't crash the page.
--}}
@props(['manual', 'title' => 'คู่มือการใช้งาน'])

@php
    $manualView = 'manuals.' . $manual;
    $manualExists = view()->exists($manualView);
    $modalId = 'helpModal-' . $manual;
@endphp

@if($manualExists)
    {{-- Trigger button — pushed to the navbar slot. --}}
    @push('navbar-help')
        <button type="button"
                class="btn btn-outline-secondary"
                data-bs-toggle="modal"
                data-bs-target="#{{ $modalId }}"
                title="{{ __('User Manual') }}">
            <i class="bi bi-book-half me-1"></i>
            <span class="d-none d-md-inline">{{ __('User Manual') }}</span>
        </button>
    @endpush

    {{-- Modal — stays inline at the call-site; Bootstrap handles z-index/portal. --}}
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="{{ $modalId }}-label">
                        <i class="bi bi-book-half me-2"></i>
                        {{ __('User Manual') }} — {{ $title }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body manual-body">
                    @include($manualView)
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        <i class="bi bi-info-circle"></i>
                        {{ __('Need more help? Contact your administrator.') }}
                    </small>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('Got it') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Manual content styling — scoped under .manual-body so it can't leak. --}}
    @once
        <style>
            .manual-body { line-height: 1.7; }
            .manual-body h4 { font-size: 1.1rem; font-weight: bold; margin-top: 1.2rem; margin-bottom: 0.6rem; color: var(--bs-primary); padding-bottom: 4px; border-bottom: 2px solid var(--bs-primary); }
            .manual-body h5 { font-size: 1rem; font-weight: bold; margin-top: 1rem; margin-bottom: 0.4rem; }
            .manual-body ul, .manual-body ol { padding-left: 1.5rem; }
            .manual-body li { margin-bottom: 0.3rem; }
            .manual-body .manual-step { background: #f8fafc; border-left: 4px solid var(--bs-primary); padding: 10px 14px; margin: 10px 0; border-radius: 0 4px 4px 0; }
            .manual-body .manual-tip { background: #fff8e1; border-left: 4px solid #f59e0b; padding: 10px 14px; margin: 10px 0; border-radius: 0 4px 4px 0; }
            .manual-body .manual-warn { background: #fee2e2; border-left: 4px solid #dc2626; padding: 10px 14px; margin: 10px 0; border-radius: 0 4px 4px 0; }
            .manual-body .manual-role { display: inline-block; padding: 2px 8px; background: #e0e7ff; color: #3730a3; border-radius: 999px; font-size: 0.85rem; font-weight: bold; margin: 0 2px; }
            .manual-body kbd { background: #1f2937; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 0.85rem; }
            .manual-body dt { font-weight: bold; margin-top: 0.6rem; color: #1f2937; }
            .manual-body dd { margin-left: 1.2rem; margin-bottom: 0.4rem; color: #4b5563; }
        </style>
    @endonce
@endif
