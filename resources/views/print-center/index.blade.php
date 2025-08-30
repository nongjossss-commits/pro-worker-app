@extends('layouts.app')

@section('title', 'Smart Print Center')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="h3 mb-4">โรงพิมพ์อัจฉริยะ (Smart Print Center)</h1>

            <div class="card">
                <div class="card-header">
                    เลือกนายจ้าง
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="employer_id">นายจ้าง</label>
                        <select name="employer_id" id="employer_id" class="form-control">
                            <option value="">-- เลือกนายจ้าง --</option>
                            @foreach($employers as $employer)
                                <option value="{{ $employer->id }}">{{ $employer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="employee-list-container" class="mt-4" style="display: none;">
                        <h5 class="mb-3">รายชื่อลูกจ้าง</h5>
                        <ul id="employee-list" class="list-group">
                            <!-- Employee list will be populated here by JavaScript -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const employerSelect = document.getElementById('employer_id');
    const employeeListContainer = document.getElementById('employee-list-container');
    const employeeList = document.getElementById('employee-list');

    employerSelect.addEventListener('change', function () {
        const employerId = this.value;

        // Clear previous list and hide container
        employeeList.innerHTML = '';
        employeeListContainer.style.display = 'none';

        if (employerId) {
            // Fetch employees for the selected employer
            fetch(`/api/employers/${employerId}/employees`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(employees => {
                    if (employees.length > 0) {
                        employees.forEach(employee => {
                            const listItem = document.createElement('li');
                            listItem.className = 'list-group-item';

                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.className = 'form-check-input me-2';
                            checkbox.value = employee.id;
                            checkbox.id = `employee-${employee.id}`;

                            const label = document.createElement('label');
                            label.htmlFor = `employee-${employee.id}`;
                            label.textContent = `${employee.first_name} ${employee.last_name}`;

                            listItem.appendChild(checkbox);
                            listItem.appendChild(label);
                            employeeList.appendChild(listItem);
                        });
                        employeeListContainer.style.display = 'block';
                    } else {
                        const listItem = document.createElement('li');
                        listItem.className = 'list-group-item text-muted';
                        listItem.textContent = 'ไม่พบข้อมูลลูกจ้างสำหรับนายจ้างรายนี้';
                        employeeList.appendChild(listItem);
                        employeeListContainer.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error fetching employees:', error);
                    const listItem = document.createElement('li');
                    listItem.className = 'list-group-item text-danger';
                    listItem.textContent = 'เกิดข้อผิดพลาดในการดึงข้อมูลลูกจ้าง';
                    employeeList.appendChild(listItem);
                    employeeListContainer.style.display = 'block';
                });
        }
    });
});
</script>
@endpush
@endsection
