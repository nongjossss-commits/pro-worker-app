@if($items->{{ __('isEmpty())') }}<div class="text-center py-5 text-muted">
        <i class="bi bi-calendar-x fs-1 opacity-25"></i>
        <p class="mt-2">{{ __('No appointments found for this date.') }}</p>
    </div>
@else
    @foreach($items as $item)
         {{-- We reuse the item card, but we might want to pass a specific "context" --}}
         @include('workflow.partials._item_card', [
            'item' => $item,
            'steps' => $item->order->workType->steps, // We need to ensure steps are loaded or passed
            'order' => $item->order
         ])
    @endforeach
@endif
