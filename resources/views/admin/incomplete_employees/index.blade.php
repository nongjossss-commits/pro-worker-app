@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('Incomplete Data') }}</h1>
            <p class="text-muted">{{ __('Employees with missing mandatory information.') }}</p>
        </div>
        <div>
            @role('admin')
            <a href="{{ route('admin.settings.completeness.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-gear-fill me-1"></i> {{ __('Configure Settings') }}
            </a>
            @endrole
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card p-3 mb-3">
        <div class="d-flex flex-column flex-md-row flex-wrap justify-content-md-between align-items-center gap-3">
            <form method="GET" action="{{ route('admin.incomplete_employees.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search') }}..." value="{{ request('search') }}" style="width: 200px;">
                <select name="nationality" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('All Nationalities') }} --</option>
                    <option value="เมียนมา" {{ request('nationality') == 'เมียนมา' ? 'selected' : '' }}>{{ __('Myanmar') }}</option>
                    <option value="ลาว" {{ request('nationality') == 'ลาว' ? 'selected' : '' }}>{{ __('Laos') }}</option>
                    <option value="กัมพูชา" {{ request('nationality') == 'กัมพูชา' ? 'selected' : '' }}>{{ __('Cambodia') }}</option>
                    <option value="เวียดนาม" {{ request('nationality') == 'เวียดนาม' ? 'selected' : '' }}>{{ __('Vietnam') }}</option>
                </select>
                <select name="mou_group" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('All MOU Types') }} --</option>
                    <option value="MOU" @if(request('mou_group') == 'MOU') selected @endif>{{ __('MOU') }}</option>
                    <option value="มติต่ออายุในประเทศ" @if(request('mou_group') == 'มติต่ออายุในประเทศ') selected @endif>{{ __('MOU Extension in Country') }}</option>
                    <option value="มติขึ้นทะเบียน" @if(request('mou_group') == 'มติขึ้นทะเบียน') selected @endif>{{ __('MOU Registration') }}</option>
                    <option value="อื่นๆ" @if(request('mou_group') == 'อื่นๆ') selected @endif>{{ __('Others') }}</option>
                </select>
                <select name="pink_card" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('Pink Card') }} --</option>
                    <option value="yes" {{ request('pink_card') == 'yes' ? 'selected' : '' }}>{{ __('Has Pink Card') }}</option>
                    <option value="no" {{ request('pink_card') == 'no' ? 'selected' : '' }}>{{ __('No Pink Card') }}</option>
                </select>
                <select name="passport_type_myanmar" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('Passport Type (Myanmar)') }} --</option>
                    <option value="CI" {{ request('passport_type_myanmar') == 'CI' ? 'selected' : '' }}>{{ __('CI Book') }}</option>
                    <option value="PJ" {{ request('passport_type_myanmar') == 'PJ' ? 'selected' : '' }}>{{ __('PJ Book') }}</option>
                </select>
                <select name="passport_type_cambodia" class="form-select form-select-sm" style="width: auto;">
                    <option value="">-- {{ __('Passport Type (Cambodia)') }} --</option>
                    <option value="เล่ม TD" {{ request('passport_type_cambodia') == 'เล่ม TD' ? 'selected' : '' }}>{{ __('TD Book') }}</option>
                    <option value="เล่มอินเตอร์" {{ request('passport_type_cambodia') == 'เล่มอินเตอร์' ? 'selected' : '' }}>{{ __('Inter Book') }}</option>
                </select>
                <input type="date" name="work_permit_expiry_date" class="form-control form-control-sm" value="{{ request('work_permit_expiry_date') }}" title="{{ __('Search by work permit expiry date') }}">
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('admin.incomplete_employees.index') }}" class="btn btn-sm btn-secondary">{{ __('Clear') }}</a>
            </form>
            <div class="d-flex align-items-center gap-2">
                {{-- View Toggle --}}
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.incomplete_employees.index', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn {{ $currentView == 'card' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Card') }}</a>
                    <a href="{{ route('admin.incomplete_employees.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn {{ $currentView == 'table' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Table') }}</a>
                </div>
                {{-- Per Page Toggle --}}
                <div class="btn-group btn-group-sm">
                    @foreach($perPageOptions as $option)
                        <a href="{{ route('admin.incomplete_employees.index', array_merge(request()->query(), ['per_page' => $option])) }}" class="btn {{ $currentPerPage == $option ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $option }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if($employees->isEmpty())
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0 me-2" style="font-size: 1.5rem;"></i>
            <div>
                <strong>{{ __('Great job!') }}</strong> {{ __('No employees found matching your criteria (or no missing data).') }}
            </div>
        </div>
    @else
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" style="font-size: 1.5rem;"></i>
            <div>
                {{ __('Found') }} <strong>{{ $totalIncomplete }}</strong> {{ __('employees matching criteria.') }}
            </div>
        </div>

        <x-bulk-action-bar id="incomplete-bulk-bar">
            <li><a class="dropdown-item" href="#" id="incomplete-bulk-advanced-export-btn"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Advanced Export') }}</a></li>
        </x-bulk-action-bar>

        @if($currentView === 'card')
            <div class="row g-3">
                @foreach($employees as $employee)
                    <div class="col-12 col-md-6 col-xl-4 position-relative">
                        @include('employees._employee_card', ['employee' => $employee, 'is_incomplete_view' => true])
                    </div>
                @endforeach
            </div>
        @else
            {{-- Table View --}}
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="table-select-all-checkbox"></th>
                                <th scope="col">{{ __('Employee') }}</th>
                                <th scope="col">{{ __('Nationality') }}</th>
                                <th scope="col">{{ __('Employer') }}</th>
                                <th scope="col">{{ __('Passport') }}</th>
                                <th scope="col">{{ __('Work Permit') }}</th>
                                <th scope="col">{{ __('90-Day Report') }}</th>
                                <th scope="col">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                            <tr>
                                <td><input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}"></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-profile.png') }}" alt="Photo" class="employee-photo-thumb" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 0.75rem;">
                                        <div>
                                            <div class="fw-bold">{{ $employee->employeeNameEn ?? 'N/A' }}</div>
                                            <div class="text-muted">{{ $employee->employeeNameTh ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                                    @endphp
                                    @if($countryCode)
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" class="me-2" style="width: 20px;">
                                            <span>{{ $employee->employeeNationality }}</span>
                                        </div>
                                    @else
                                        {{ $employee->employeeNationality ?? '-' }}
                                    @endif
                                </td>
                                <td class="text-muted">{{ optional($employee->employer)->employerNameTh ?? 'N/A' }}</td>
                                <td>{{ $employee->employeePassport ?? '-' }}</td>
                                <td>{{ $employee->employeeWorkPermit ?? '-' }}</td>
                                <td>{{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}</td>
                                <td class="text-nowrap">
                                    <x-employee-action-buttons :employee="$employee" :show-locate-button="true" />
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@include('employees.modals.advanced_export')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bulkExportBtn = document.getElementById('incomplete-bulk-advanced-export-btn');
        if (bulkExportBtn) {
            bulkExportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                document.getElementById('export_employee_ids').value = JSON.stringify(selected);
                const modalEl = document.getElementById('advancedExportModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        }
    });
</script>
@endpush
