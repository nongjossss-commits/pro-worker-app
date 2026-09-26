{{--
    Public share link controls for one manual bundle (see ManualShareService).
    Expects: $bundle — one of ManualShareService::BUNDLES keys.
--}}
@php
    $shareLink = \App\Services\ManualShareService::link($bundle);
    $shareExpired = \App\Services\ManualShareService::isExpired($shareLink);
    $shareUrl = \App\Services\ManualShareService::url($bundle);
    $expiryOptions = [7 => __(':n days', ['n' => 7]), 30 => __(':n days', ['n' => 30]), 90 => __(':n days', ['n' => 90]), 0 => __('No expiry')];
@endphp
<div class="card-footer bg-white small">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold text-muted"><i class="bi bi-link-45deg"></i> {{ __('Public share link') }}</span>

        @if($shareUrl && ! $shareExpired)
            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('On') }}</span>
            <div class="input-group input-group-sm flex-grow-1" style="max-width: 560px;">
                <select class="form-select js-manual-share-lang" style="max-width: 110px;" aria-label="{{ __('Language') }}">
                    @foreach(\App\Services\ManualShareService::LANGS as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" class="form-control js-manual-share-url" readonly value="{{ $shareUrl }}" data-base="{{ $shareUrl }}">
                <button type="button" class="btn btn-outline-secondary js-manual-share-copy"><i class="bi bi-clipboard"></i> {{ __('Copy') }}</button>
                <a class="btn btn-outline-secondary js-manual-share-open" href="{{ $shareUrl }}" target="_blank" rel="noopener" title="{{ __('Open as a visitor sees it') }}"><i class="bi bi-box-arrow-up-right"></i></a>
            </div>
            <form method="POST" action="{{ route('super-admin.manuals.share.generate', $bundle) }}" class="d-inline-flex gap-1"
                  data-confirm="{{ __('Create a new link? The current link will stop working immediately.') }}" data-confirm-danger>
                @csrf
                <select name="expires_in" class="form-select form-select-sm" style="width: auto;" aria-label="{{ __('Link expires after') }}">
                    @foreach($expiryOptions as $days => $label)
                        <option value="{{ $days }}" @selected($days === 30)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-warning text-nowrap"><i class="bi bi-arrow-repeat"></i> {{ __('New link') }}</button>
            </form>
            <form method="POST" action="{{ route('super-admin.manuals.share.revoke', $bundle) }}" class="d-inline"
                  data-confirm="{{ __('Turn off this link? Anyone who has it will no longer be able to open the manual.') }}" data-confirm-danger>
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> {{ __('Turn off link') }}</button>
            </form>
        @else
            @if($shareExpired)
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('Expired') }}</span>
            @else
                <span class="badge bg-light text-muted border">{{ __('Off') }}</span>
            @endif
            <form method="POST" action="{{ route('super-admin.manuals.share.generate', $bundle) }}" class="d-inline-flex gap-1">
                @csrf
                <select name="expires_in" class="form-select form-select-sm" style="width: auto;" aria-label="{{ __('Link expires after') }}">
                    @foreach($expiryOptions as $days => $label)
                        <option value="{{ $days }}" @selected($days === 30)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap"><i class="bi bi-share"></i> {{ __('Create share link') }}</button>
            </form>
        @endif

        {{-- Standalone .html file (images embedded) to send to a customer --}}
        <div class="dropdown ms-auto">
            <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-filetype-html"></i> {{ __('Download HTML file') }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach(\App\Services\ManualShareService::LANGS as $code => $label)
                    <li><a class="dropdown-item" href="{{ route('super-admin.manuals.download', ['bundle' => $bundle, 'lang' => $code]) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
    @if($shareLink && $shareUrl)
        <div class="mt-1 {{ $shareExpired ? 'text-danger' : 'text-muted' }}">
            <i class="bi bi-clock-history"></i>
            @if($shareLink['expires_at'] === null)
                {{ __('This link never expires.') }}
            @elseif($shareExpired)
                {{ __('This link expired on :date — visitors now see an "expired" page. Create a new link to share again.', ['date' => $shareLink['expires_at']->format('d/m/Y H:i')]) }}
            @else
                {{ __('This link works until :date (:left).', ['date' => $shareLink['expires_at']->format('d/m/Y H:i'), 'left' => $shareLink['expires_at']->diffForHumans()]) }}
            @endif
        </div>
    @endif
    <div class="text-muted mt-1">
        <i class="bi bi-info-circle"></i>
        {{ __('Anyone with the link can view and print this manual without logging in — they see this manual page only, nothing else in the program.') }}
        {{ __('The HTML file opens by double-click on any computer (images included) — good for email or LINE.') }}
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('change', function (e) {
    const select = e.target.closest('.js-manual-share-lang');
    if (!select) return;
    const footer = select.closest('.card-footer');
    const input = footer.querySelector('.js-manual-share-url');
    const url = new URL(input.dataset.base);
    if (select.value === 'th') url.searchParams.delete('lang'); else url.searchParams.set('lang', select.value);
    input.value = url.toString();
    footer.querySelector('.js-manual-share-open').href = url.toString();
});
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-manual-share-copy');
    if (!btn) return;
    const input = btn.closest('.card-footer').querySelector('.js-manual-share-url');
    const done = () => (window.appToast ? appToast(@json(__('Link copied'))) : null);
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(done);
    } else {
        input.select();
        document.execCommand('copy');
        done();
    }
});
</script>
@endpush
@endonce
