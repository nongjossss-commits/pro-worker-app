<tr id="row-{{ $employee->id }}">
    <td class="text-center">
        <input type="checkbox" class="form-check-input import-checkbox" value="{{ $employee->id }}">
    </td>
    <td class="text-center">
        <img src="{{ $employee->photo_url }}" alt="Photo" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
    </td>
    <td>
        <div class="fw-bold">{{ $employee->employeeNameTh }}</div>
        <div class="text-muted small">{{ $employee->employeeNameEn }}</div>
    </td>
    <td class="text-center">
        @php
            $flag = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
        @endphp
        @if($flag)
            <img src="{{ asset('images/flags/'.strtolower($flag).'.png') }}" alt="{{ $employee->employeeNationality }}" width="24" class="me-1">
        @endif
        {{ $employee->employeeNationality }}
    </td>
    <td class="text-center">{{ $employee->employeePassport ?? '-' }}</td>
    <td class="text-center">{{ $employee->employeeWorkPermit ?? '-' }}</td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-individual" data-id="{{ $employee->id }}">
            <i class="bi bi-pencil-square"></i> {{ __('Edit') }}
        </button>
    </td>
</tr>
