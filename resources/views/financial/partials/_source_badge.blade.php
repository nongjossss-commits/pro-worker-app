{{--
    Source badge: which menu/tab a bill (ProductionOrder) came from.
    Red when that tab has been deleted, so Finance can see at a glance that
    the bill is real but its working tab is gone — and decide whether to keep
    collecting or credit-note the remaining balance.
    Expects: $order (ProductionOrder, with resolutionTab / workType withTrashed eager-loaded)
--}}
@if($order)
    @php $src = $order->financeSource(); @endphp
    @if($src['deleted'])
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1"
              title="{{ __('The work tab this bill came from has been deleted. The bill and its payments are kept.') }}">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $src['menu'] }} › {{ $src['tab'] ?? __('Unknown tab') }}
            · {{ __('Tab deleted') }}@if($src['deleted_at']) {{ $src['deleted_at']->format('d/m/Y') }}@endif
        </span>
    @else
        <span class="badge bg-light text-secondary border mt-1">
            {{ $src['menu'] }}@if($src['tab']) › {{ $src['tab'] }}@endif
        </span>
    @endif
@endif
