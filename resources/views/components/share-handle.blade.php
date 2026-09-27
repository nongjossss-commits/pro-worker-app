{{--
  Drag / share handle for an employee or employer card.
  Drag it into LINE (or any chat) → readable text; click → copy text /
  copy, save or share a card image. Behaviour: partials/_share_card_scripts.

  <x-share-handle type="employee" :id="$employee->id" :name="$employee->employeeNameEn" context="item:12" />
  context: "item:{production_item id}" (Workflow / Pre-Production) or
  "tab:{resolution_tab id}" (Registration / Renewal) — so the request number,
  appointment, team and remarks come from that menu, not the employee record.
--}}
@props(['type', 'id', 'name' => '', 'notification' => null, 'context' => null, 'class' => 'btn btn-sm btn-light border'])
<button type="button" {{ $attributes->merge(['class' => $class]) }}
        draggable="true"
        data-share-handle data-share-menu
        data-share-type="{{ $type }}" data-share-id="{{ $id }}"
        @if($notification) data-share-notification="{{ $notification }}" @endif
        @if($context) data-share-context="{{ $context }}" @endif
        data-share-name="{{ $name }}"
        title="{{ __('Drag into a chat, or click to copy / share') }}">
    <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
</button>
