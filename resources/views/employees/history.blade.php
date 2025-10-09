@extends('layouts.app')

@section('title', 'ประวัติการจ้างงาน')

@section('content')
<div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">ประวัติการจ้างงาน</h2>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Photo</th>
                    <th scope="col">Name (EN / TH)</th>
                    <th scope="col">Passport</th>
                    <th scope="col">Nationality</th>
                    <th scope="col">Employer</th>
                    <th scope="col">Termination Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($terminatedEmployees as $employee)
                    <tr>
                        <th scope="row">{{ $loop->iteration }}</th>
                        <td class="align-middle text-center" style="width: 60px;">
                            @if($employee->employeePhoto)
                                <img src="{{ asset('storage/' . $employee->employeePhoto) }}" alt="Photo" class="img-fluid rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            @else
                                <div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center text-white" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            @endif
                        </td>
                        <td class="align-middle">
                            <div class="fw-bold">{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'No English Name' }}</div>
                            <div class="text-muted small">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'ไม่มีชื่อภาษาไทย' }}</div>
                        </td>
                        <td class="align-middle">{{ $employee->employeePassport ?? '-' }}</td>
                        <td class="align-middle">{{ $employee->employeeNationality ?? '-' }}</td>
                        <td class="align-middle">{{ $employee->employer->employerNameTh ?? 'N/A' }}</td>
                        <td class="align-middle">{{ $employee->termination_date ? \Carbon\Carbon::parse($employee->termination_date)->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">ไม่พบข้อมูลพนักงานที่ถูกเลิกจ้าง</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
     <div class="mt-4">
        {{ $terminatedEmployees->links() }}
    </div>
</div>
@endsection