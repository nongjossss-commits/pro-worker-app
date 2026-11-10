@if($groups->isEmpty())
    @include('partials.appointment_reminder._no_results')
@else
    @foreach($groups as $group)
        @include('partials.appointment_reminder._date_header', ['dateLabel' => $group->date_label, 'count' => $group->items->count()])
        @php($items = $group->items)
        @include('partials.appointment_reminder.day_list')
    @endforeach
@endif
