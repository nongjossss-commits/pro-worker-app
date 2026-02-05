@extends('layouts.app')
@section('title', 'ข้อมูลลูกจ้าง')

@push('styles')
{{-- Styles are now handled in app.css or inline for guarantee --}}
@endpush

@section('content')
<div class="p-4 p-md-5 content-section">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        {{ __('Employee List (Total: :total)', ['total' => $totalEmployees]) }}
    </h4>
    @can('create-employees')
        <div class="btn-group">
            <a href="{{ route('employees.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>{{ __('Add New') }}
            </a>
            <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('employees.create') }}"><i class="bi bi-person-plus me-2"></i>{{ __('Create Manually') }}</a></li>
                <li><a class="dropdown-item" href="{{ route('employees.import_view') }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Import from Excel/CSV') }}</a></li>
            </ul>
        </div>
    @endcan
</div>

<x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

<div class="card p-3 mb-3">
    <div class="d-flex flex-column flex-md-row flex-wrap justify-content-md-between align-items-center gap-3">
        <form method="GET" action="{{ route('employees.index') }}" class="d-flex flex-wrap align-items-center gap-2">
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
            <select name="insurance_type" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Insurance Type') }} --</option>
                <option value="none" {{ request('insurance_type') == 'none' ? 'selected' : '' }}>{{ __('No Insurance') }}</option>
                <option value="ประกันสังคม" {{ request('insurance_type') == 'ประกันสังคม' ? 'selected' : '' }}>{{ __('Social Security') }}</option>
                <option value="ประกันโรงพยาบาล" {{ request('insurance_type') == 'ประกันโรงพยาบาล' ? 'selected' : '' }}>{{ __('Hospital Insurance') }}</option>
                <option value="ประกันเอกชน" {{ request('insurance_type') == 'ประกันเอกชน' ? 'selected' : '' }}>{{ __('Private Insurance') }}</option>
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
            <select name="bank_account_status" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- สถานะบัญชีธนาคาร --</option>
                <option value="opened" {{ request('bank_account_status') == 'opened' ? 'selected' : '' }}>เปิดบัญชีแล้ว</option>
                <option value="not_opened" {{ request('bank_account_status') == 'not_opened' ? 'selected' : '' }}>ยังไม่เปิดบัญชี</option>
            </select>
            <input type="date" name="work_permit_expiry_date" class="form-control form-control-sm" value="{{ request('work_permit_expiry_date') }}" title="{{ __('Search by work permit expiry date') }}">
            <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
            <a href="{{ route('employees.index') }}" class="btn btn-sm btn-secondary">{{ __('Clear') }}</a>
        </form>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('employees.export', request()->query()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> {{ __('Export') }}
            </a>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('employees.index', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn {{ $currentView == 'card' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Card') }}</a>
                <a href="{{ route('employees.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn {{ $currentView == 'table' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Table') }}</a>
            </div>
            <div class="btn-group btn-group-sm">
                @foreach($perPageOptions as $option)
                    <a href="{{ route('employees.index', array_merge(request()->query(), ['per_page' => $option])) }}" class="btn {{ $currentPerPage == $option ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $option }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<x-bulk-action-bar />

<div id="employeeListContainer">
    @if($currentView === 'card')
        <div class="list-group">
            @forelse($employees as $employee)
                <div>
                @include('partials._employee_card', ['employee' => $employee, 'loop' => $loop, 'pagination' => $employees, 'showLocateButton' => true])
                </div>
            @empty
                <p class="text-center text-muted">{{ __('No employees found') }}</p>
            @endforelse
        </div>
    @else
        {{-- Table View --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="table-select-all-checkbox"></th>
                        <th style="width: 1%;"></th> {{-- Drag Handle Column --}}
                        <th scope="col">{{ __('Employee') }}</th>
                        <th scope="col">{{ __('All Nationalities') }}</th>
                        <th scope="col">{{ __('Employers') }}</th>
                        <th scope="col">{{ __('Passport') }}</th>
                        <th scope="col">{{ __('Work Permit') }}</th>
                        <th scope="col">{{ __('90-Day Report') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td><input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-id="{{ $employee->id }}" data-employer-id="{{ $employee->employer_id }}" data-name-th="{{ $employee->employeeNameTh }}" data-name-en="{{ $employee->employeeNameEn }}" data-photo="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}" data-employer-name="{{ $employee->employer->employerNameTh ?? 'N/A' }}"></td>
                        <td>
                            <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab"
                               draggable="true"
                               ondragstart="window.startDragGlobal(event, 'employee', {
                                    id: {{ $employee->id }},
                                    name: '{{ addslashes($employee->employeeNameTh) }} ({{ addslashes($employee->employeeNameEn) }})',
                                    subtitle: '{{ $employee->employeeNationality }}',
                                    url: '{{ route('employees.show', $employee->id) }}'
                                })"
                               title="{{ __('Drag') }}">
                            </i>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}" alt="Photo" class="employee-photo-thumb" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 0.75rem;">
                                <div>
                                    <div class="fw-bold">
                                        {{ $employee->employeeNameEn ?? 'N/A' }}
                                        <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employee" data-model-id="{{ $employee->id }}" title="{{ __('Preview Data') }}">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
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
                        <td class="text-muted">
                            {{ $employee->employer->employerNameTh ?? 'N/A' }}
                            @if(request('addrProvince') && $employee->employer)
                                @foreach($employee->employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                                    <div class="text-primary small fw-bold">{{ $label }}</div>
                                @endforeach
                            @endif
                            @if($employee->employer)
                                <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employer" data-model-id="{{ $employee->employer->id }}" title="{{ __('Preview Data') }}">
                                    <i class="bi bi-search"></i>
                                </button>
                            @endif
                        </td>
                        <td>{{ $employee->employeePassport ?? '-' }}</td>
                        <td>{{ $employee->employeeWorkPermit ?? '-' }}</td>
                        <td>{{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}</td>
                        <td class="text-nowrap">
                            <x-employee-action-buttons :employee="$employee" :show-locate-button="true" />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">{{ __('No employees found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
    </div>

    <div class="mt-4">
        {{ $employees->links() }}
    </div>
</div>

@include('partials._employee_action_modals')
<x-bulk-action-modals />

@push('scripts')
<x-bulk-action-scripts />
@endpush
@endsection
