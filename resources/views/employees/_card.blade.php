@php
    // This logic is now inside the component for reusability
    $flagCodes = ['เมียนมา' => 'mm', 'ลาว' => 'la', 'กัมพูชา' => 'kh', 'เวียดนาม' => 'vn'];
    $nationality = $employee->employeeNationality ?? null;
    $flagCode = $nationality ? ($flagCodes[$nationality] ?? null) : null;
@endphp

<div class="card employee-card mb-3" id="employee-card-{{ $employee->id }}">
    <div class="card-body d-flex justify-content-between align-items-start gap-3">
        <div class="d-flex align-items-center flex-grow-1">
            <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo">
            <div class="flex-grow-1">
                <p class="mb-0">
                    <strong>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'No English Name' }}</strong>
                    @if($nationality)
                        <span class="text-muted small"> - {{ $nationality }}</span>
                        @if($flagCode)
                            <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" class="ms-1" style="width: 20px; vertical-align: middle;">
                        @endif
                    @endif
                </p>
                <p class="mb-1 text-muted small">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'ไม่มีชื่อภาษาไทย' }}</p>
                <p class="mb-1 text-muted small"><strong>นายจ้าง:</strong> {{ $employee->employer->employerNameTh ?? 'N/A' }}</p>
                <p class="mb-0 text-muted small">Passport: {{ $employee->employeePassport ?? '-' }}</p>
            </div>
        </div>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('employers.employees.edit', ['employer' => $employee->employer, 'employee' => $employee]) }}" class="btn btn-outline-primary" title="แก้ไข">
                <i class="bi bi-pencil-fill"></i>
            </a>
            <button type="button" class="btn btn-outline-danger delete-employee-btn" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-url="{{ route('employers.employees.destroy', ['employer' => $employee->employer, 'employee' => $employee]) }}" title="ลบ">
                <i class="bi bi-trash-fill"></i>
            </button>
        </div>
    </div>
</div>
