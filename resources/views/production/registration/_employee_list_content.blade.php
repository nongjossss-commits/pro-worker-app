@foreach($employees as $employee)
    @include('production.registration._employee_card', ['employee' => $employee, 'steps' => $steps, 'loop' => $loop])
@endforeach
