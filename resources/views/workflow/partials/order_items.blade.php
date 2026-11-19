{{-- resources/views/workflow/partials/order_items.blade.php --}}

@php
    $steps = $steps ?? $order->workType->workflowSteps ?? collect();
@endphp

@if($groupedItems->isEmpty())
    <div class="text-center py-4 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        {{ __('No employees in this job yet.') }}
    </div>
@else
    @foreach($groupedItems as $groupName => $items)
        @if($groupName)
            <div class="d-flex align-items-center mb-2 mt-4 px-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                    <i class="bi bi-collection-fill" style="font-size: 0.75rem;"></i>
                </div>
                <h6 class="fw-bold text-dark mb-0">{{ $groupName }}</h6>
                <span class="badge bg-secondary bg-opacity-10 text-secondary ms-2 rounded-pill">{{ $items->count() }}</span>
            </div>
        @endif

        <div class="item-list">
            @foreach($items as $item)
                {{-- data-status + data-terminated drive the Select-All
                     filter in _index_scripts.blade.php so cancelled and
                     terminated rows are skipped. --}}
                @php
                    // found by a person search although finished > 24h ago / cancelled
                    $revealedBySearch = ($searchingPeople ?? false)
                        && ($item->status === 'cancelled'
                            || ($item->status === 'completed' && (! $item->completed_at || $item->completed_at->lt(now()->subHours(24)))));
                @endphp
                <div class="item-card-wrapper mb-3 {{ $item->status === 'cancelled' ? 'status-cancelled' : '' }} {{ $revealedBySearch ? 'search-reveal' : '' }}"
                     data-status="{{ $item->status }}"
                     data-terminated="{{ ($item->employee && $item->employee->terminated_at) ? 'true' : 'false' }}">
                    @if($revealedBySearch)
                        <div class="search-reveal-note {{ $item->status === 'cancelled' ? 'is-cancelled' : 'is-done' }}">
                            <i class="bi {{ $item->status === 'cancelled' ? 'bi-x-circle-fill' : 'bi-check-circle-fill' }} me-1"></i>
                            {{ $item->status === 'cancelled' ? __('Cancelled — shown because you searched for this employee') : __('Finished — shown because you searched for this employee') }}
                            @if($item->status === 'completed' && $item->completed_at)
                                <span class="opacity-75">({{ $item->completed_at->format('d/m/Y') }})</span>
                            @endif
                        </div>
                    @endif
                    @php $itemOrder = ($item->production_order_id === $order->id || ! $item->relationLoaded('order')) ? $order : ($item->order ?? $order); @endphp
                    @if($itemOrder->id !== $order->id)
                        {{-- team filter across this employer's cards (แจ้งเข้า บต.52/55/56) --}}
                        <div class="search-reveal-note is-other-card">
                            <i class="bi bi-diagram-2 me-1"></i>{{ __('From another card: :name', ['name' => $itemOrder->project_name ?: ('#' . $itemOrder->id)]) }}
                            @if($itemOrder->status === 'completed')<span class="opacity-75">· {{ __('Finished') }}</span>@endif
                        </div>
                    @endif
                    @include('workflow.partials._item_card', [
                        'item' => $item,
                        'steps' => $steps,
                        'order' => $itemOrder,
                        'isHistory' => $isHistory ?? false
                    ])
                </div>
            @endforeach
        </div>
    @endforeach
@endif

@if(isset($hasHistory) && $hasHistory)
<script>
    (function(){
        const btn = document.querySelector('.history-btn-{{ $order->id }}');
        if(btn) btn.style.display = 'inline-block';
    })();
</script>
@endif
