<form id="employeeEditForm" action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="_previous" value="{{ url()->previous() }}">

    {{-- Unified Form Fields --}}
    @include('employees.partials._form_fields', [
        'prefix' => 'edit_',
        'employee' => $employee
    ])
</form>
