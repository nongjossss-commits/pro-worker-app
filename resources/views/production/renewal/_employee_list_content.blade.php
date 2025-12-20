@foreach($employees as $employee)
    @include('production.renewal._employee_card', ['employee' => $employee, 'steps' => $steps, 'loop' => $loop])
@endforeach
