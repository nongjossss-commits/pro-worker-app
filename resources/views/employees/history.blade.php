@extends('layouts.app')
@section('title', 'ประวัติการจ้างงาน')
@section('content')
<div class="content-section">
    <h2 class="mb-4">ประวัติการจ้างงาน</h2>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Photo</th>
                    <th>Name (EN / TH)</th>
                    <th>Passport</th>
                    <th>Nationality</th>
                    <th>Last Employer</th>
                    <th>Termination Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($terminatedEmployees as $employee)
                    <tr>
                        <th>{{ $terminatedEmployees->firstItem() + $loop->index }}</th>
                        <td class="align-middle text-center" style="width: 60px;">
                            @if($employee->employeePhoto)
                                <img src="{{ asset('storage/' . $employee->employeePhoto) }}" alt="Photo" class="img-fluid rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            @else
                                <div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center text-white" style="width: 40px; height: 40px;"><i class="bi bi-person-fill"></i></div>
                            @endif
                        </td>
                        <td class="align-middle">
                            <div class="fw-bold">{{ $employee->employeeNameEn ?? 'N/A' }}</div>
                            <div class="text-muted small">{{ $employee->employeeNameTh }}</div>
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
    <div class="mt-4">{{ $terminatedEmployees->links() }}</div>
</div>
@endsection