@extends('layouts.app')

@section('title', __('Import Employees'))

@section('content')
<div class="container-fluid py-4" x-data="importGrid()">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-grid-3x3-gap-fill me-2"></i>{{ __('Batch Create Employees') }}</h5>
                </div>
                <div class="card-body p-4">

                    @if(session('import_errors'))
                        <div class="alert alert-warning">
                            <strong>{{ __('Import completed with some warnings:') }}</strong>
                            <ul class="mb-0 mt-2">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                        <div>
                            {{ __('Fill in the table below to create multiple employees. You can attach a photo for each employee in the first column.') }}
                        </div>
                    </div>

                    <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf

                        <!-- Employer Selection -->
                        <div class="mb-4">
                            <label for="employer_id" class="form-label fw-bold required">{{ __('Select Employer') }}</label>
                            <select name="employer_id" id="employer_id" class="form-select w-50" required>
                                <option value="">-- {{ __('Select Employer') }} --</option>
                                @foreach($employers as $employer)
                                    <option value="{{ $employer->id }}">{{ $employer->employerNameTh }} ({{ $employer->employerNameEn }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Grid Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light text-center text-nowrap">
                                    <tr>
                                        <th style="width: 80px;">{{ __('Photo') }}</th>
                                        <th style="width: 100px;">{{ __('Title') }}</th>
                                        <th style="min-width: 150px;">{{ __('Name (TH)') }}</th>
                                        <th style="min-width: 150px;">{{ __('Name (EN)') }}</th>
                                        <th style="width: 130px;">{{ __('DOB') }}</th>
                                        <th style="width: 120px;">{{ __('Nationality') }}</th>
                                        <th style="width: 120px;">{{ __('Passport No') }}</th>
                                        <th style="width: 120px;">{{ __('Work Permit') }}</th>
                                        <th style="width: 100px;">{{ __('Pink Card') }}</th>
                                        <th style="width: 100px;">{{ __('Book Type') }}</th>
                                        <th style="width: 50px;"><i class="bi bi-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in rows" :key="row.id">
                                        <tr>
                                            <!-- Photo Upload Column -->
                                            <td class="text-center p-1 position-relative" style="background-color: #f8f9fa; cursor: pointer;" @click="$refs['file_' + row.id].click()">
                                                <div class="d-flex justify-content-center align-items-center" style="height: 60px; overflow: hidden;">
                                                    <template x-if="row.photoPreview">
                                                        <img :src="row.photoPreview" class="img-fluid" style="max-height: 60px;">
                                                    </template>
                                                    <template x-if="!row.photoPreview">
                                                        <div class="text-muted small">
                                                            <i class="bi bi-camera fs-4"></i>
                                                        </div>
                                                    </template>
                                                </div>
                                                <input type="file"
                                                       :name="'employees[' + index + '][photo]'"
                                                       :x-ref="'file_' + row.id"
                                                       class="d-none"
                                                       accept="image/*"
                                                       @change="handleFileChange($event, row)">
                                            </td>

                                            <!-- Data Columns -->
                                            <td>
                                                <select :name="'employees[' + index + '][title_th]'" class="form-select form-select-sm border-0" x-model="row.title_th">
                                                    <option value="">-</option>
                                                    <option value="นาย">นาย</option>
                                                    <option value="นาง">นาง</option>
                                                    <option value="นางสาว">นางสาว</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" :name="'employees[' + index + '][name_th]'" class="form-control form-control-sm border-0" placeholder="ชื่อไทย" x-model="row.name_th">
                                            </td>
                                            <td>
                                                <input type="text" :name="'employees[' + index + '][name_en]'" class="form-control form-control-sm border-0" placeholder="Name EN" x-model="row.name_en">
                                            </td>
                                            <td>
                                                <input type="date" :name="'employees[' + index + '][dob]'" class="form-control form-control-sm border-0" x-model="row.dob">
                                            </td>
                                            <td>
                                                <select :name="'employees[' + index + '][nationality]'" class="form-select form-select-sm border-0" x-model="row.nationality">
                                                    <option value="เมียนมา">เมียนมา</option>
                                                    <option value="กัมพูชา">กัมพูชา</option>
                                                    <option value="ลาว">ลาว</option>
                                                    <option value="เวียดนาม">เวียดนาม</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" :name="'employees[' + index + '][passport_no]'" class="form-control form-control-sm border-0" placeholder="PP1234" x-model="row.passport_no">
                                            </td>
                                            <td>
                                                <input type="text" :name="'employees[' + index + '][work_permit_no]'" class="form-control form-control-sm border-0" placeholder="WP.." x-model="row.work_permit_no">
                                            </td>
                                            <td>
                                                <input type="text" :name="'employees[' + index + '][pink_card_no]'" class="form-control form-control-sm border-0" placeholder="-" x-model="row.pink_card_no">
                                            </td>
                                            <td>
                                                <select :name="'employees[' + index + '][book_type]'" class="form-select form-select-sm border-0" x-model="row.book_type">
                                                    <option value="">-</option>
                                                    <template x-if="row.nationality === 'เมียนมา'">
                                                        <optgroup label="Myanmar">
                                                            <option value="PJ">PJ</option>
                                                            <option value="CI">CI</option>
                                                        </optgroup>
                                                    </template>
                                                    <template x-if="row.nationality === 'กัมพูชา'">
                                                        <optgroup label="Cambodia">
                                                            <option value="เล่ม TD">TD</option>
                                                            <option value="เล่มอินเตอร์">Inter</option>
                                                        </optgroup>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-light text-danger" @click="removeRow(index)" x-show="rows.length > 1">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary" @click="addRow()">
                                <i class="bi bi-plus-lg me-1"></i> {{ __('Add Row') }}
                            </button>

                            <div>
                                <a href="{{ route('employees.index') }}" class="btn btn-light me-2">{{ __('Cancel') }}</a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-2"></i> {{ __('Create Employees') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <hr class="my-5">

                    <!-- Legacy CSV Import Fallback -->
                    <div class="accordion" id="csvAccordion">
                        <div class="accordion-item border-0 bg-light">
                            <h2 class="accordion-header" id="headingCsv">
                                <button class="accordion-button collapsed bg-light shadow-none text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCsv" aria-expanded="false" aria-controls="collapseCsv">
                                    <small><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Or import via CSV file (Legacy)') }}</small>
                                </button>
                            </h2>
                            <div id="collapseCsv" class="accordion-collapse collapse" aria-labelledby="headingCsv" data-bs-parent="#csvAccordion">
                                <div class="accordion-body">
                                    <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('Select Employer') }}</label>
                                            <select name="employer_id" class="form-select form-select-sm w-50" required>
                                                <option value="">-- {{ __('Select Employer') }} --</option>
                                                @foreach($employers as $employer)
                                                    <option value="{{ $employer->id }}">{{ $employer->employerNameTh }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('CSV File') }}</label>
                                            <input type="file" name="file" class="form-control form-control-sm" required>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-secondary">{{ __('Upload CSV') }}</button>
                                        <a href="{{ route('employees.template') }}" class="btn btn-sm btn-link">{{ __('Download Template') }}</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('importGrid', () => ({
            rows: [
                { id: 1, title_th: '', name_th: '', name_en: '', dob: '', nationality: 'เมียนมา', passport_no: '', work_permit_no: '', pink_card_no: '', book_type: '', photoPreview: null }
            ],
            nextId: 2,

            addRow() {
                this.rows.push({
                    id: this.nextId++,
                    title_th: '',
                    name_th: '',
                    name_en: '',
                    dob: '',
                    nationality: 'เมียนมา',
                    passport_no: '',
                    work_permit_no: '',
                    pink_card_no: '',
                    book_type: '',
                    photoPreview: null
                });
            },

            removeRow(index) {
                this.rows.splice(index, 1);
            },

            handleFileChange(event, row) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        row.photoPreview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    row.photoPreview = null;
                }
            }
        }));
    });
</script>
@endpush
@endsection
