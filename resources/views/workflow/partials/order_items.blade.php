{{-- resources/views/workflow/partials/order_items.blade.php --}}

@php
    $steps = $order->workType->steps ?? collect();
@endphp

<style>
    .employee-counter-reset {
        counter-reset: employee-counter;
    }
    .item-card-wrapper {
        counter-increment: employee-counter;
    }
    .item-sequence-number::before {
        content: counter(employee-counter);
    }
    .item-sequence-number {
        min-width: 35px;
        text-align: center;
        font-weight: bold;
        color: #6c757d;
        opacity: 0.5;
        font-size: 1.1rem;
    }
</style>

@if($groupedItems->isEmpty())
    <div class="text-center py-4 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        {{ __('No employees in this job yet.') }}
    </div>
@else
    <div class="employee-counter-reset">
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
                    @include('workflow.partials._item_card', ['item' => $item, 'steps' => $steps, 'order' => $order])
                @endforeach
            </div>
        @endforeach
    </div>
@endif
