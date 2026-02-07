@if($employees->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-calendar-x fs-1 opacity-25"></i>
        <p class="mt-2">{{ __('No appointments found for this date.') }}</p>
    </div>
@else
    <div class="container-fluid p-3">
        @foreach($employees as $employee)
             @include('production.registration._employee_card', [
                'employee' => $employee,
                'steps' => $steps,
                'show_employer' => true
             ])
        @endforeach
    </div>
@endif
