@extends('layouts.app')

@section('title', __('WHT Inbox'))

@section('content')
<div class="content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">
            <i class="bi bi-inbox-fill text-warning me-2"></i>{{ __('WHT Inbox') }}
        </h2>
        <a href="{{ route('finance.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
        </a>
    </div>

    {{-- Alert/Description --}}
    <div class="alert alert-info d-flex align-items-start" role="alert">
        <i class="bi bi-info-circle-fill flex-shrink-0 me-2 mt-1"></i>
        <div>{{ __('Includes income that has been received but WHT certificate is still pending. Please follow up with the client.') }}</div>
    </div>

    {{-- Summary card --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <small class="text-muted">{{ __('Total WHT Outstanding') }}</small>
                    <h3 class="mb-0 text-warning fw-bold">฿{{ number_format($totalOutstanding, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">{{ __('Filter by Employer') }}</label>
            <select name="employer_id" class="form-select form-select-sm">
                <option value="">-- {{ __('All Employers') }} --</option>
                @foreach($employers as $emp)
                    <option value="{{ $emp->id }}" {{ $employerId == $emp->id ? 'selected' : '' }}>
                        {{ $emp->employerNameTh ?: $emp->employerNameEn }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">{{ __('Status') }}</label>
            <select name="status" class="form-select form-select-sm">
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>{{ __('WHT Pending') }}</option>
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('All Statuses') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('Filter') }}</button>
        </div>
        <div class="col-md-2">
            <a href="{{ route('finance.wht_inbox') }}" class="btn btn-sm btn-outline-secondary w-100">{{ __('Reset') }}</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Employer') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                    <th class="text-end">{{ __('WHT Amount') }}</th>
                    <th>{{ __('Paid On') }}</th>
                    <th class="text-center">{{ __('Days Outstanding') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    @php
                        $employer = $tx->productionOrder->employer ?? null;
                        $daysOutstanding = $tx->paid_at ? (int) $tx->paid_at->diffInDays(now()) : null;
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}</td>
                        <td>
                            <div class="fw-bold">{{ $employer->employerNameTh ?? '-' }}</div>
                            <small class="text-muted">{{ $employer->employerNameEn ?? '' }}</small>
                        </td>
                        <td class="text-end">฿{{ number_format($tx->amount, 2) }}</td>
                        <td class="text-end fw-bold text-warning">฿{{ number_format($tx->withholding_tax_amount, 2) }}</td>
                        <td>{{ $tx->paid_at ? $tx->paid_at->format('d/m/Y') : '-' }}</td>
                        <td class="text-center">
                            @if($daysOutstanding !== null)
                                <span class="badge bg-{{ $daysOutstanding > 30 ? 'danger' : ($daysOutstanding > 14 ? 'warning text-dark' : 'secondary') }}">
                                    {{ $daysOutstanding }} {{ __('Days unit') }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($tx->wht_status === 'received')
                                <span class="badge bg-success">{{ __('WHT Received') }}</span>
                            @elseif($tx->wht_status === 'no_certificate')
                                <span class="badge bg-secondary" title="{{ $tx->wht_no_cert_reason }}">
                                    <i class="bi bi-x-circle me-1"></i>{{ __('No WHT Certificate') }}
                                </span>
                            @elseif($tx->wht_status === 'pending')
                                <span class="badge bg-warning text-dark">{{ __('WHT Pending') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ $tx->wht_status }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($tx->wht_status === 'received' && $tx->wht_document_path)
                                <a href="{{ asset('storage/' . $tx->wht_document_path) }}" target="_blank" class="btn btn-sm btn-outline-info" title="{{ __('View WHT Document') }}">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            @elseif($tx->wht_status === 'no_certificate')
                                <small class="text-muted d-block" style="max-width:200px; white-space:normal;">
                                    <i class="bi bi-info-circle me-1"></i>{{ Str::limit($tx->wht_no_cert_reason, 60) }}
                                </small>
                            @else
                                <div class="d-flex flex-column flex-md-row gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-warning btn-upload-wht"
                                            data-bs-toggle="modal" data-bs-target="#whtUploadModal"
                                            data-transaction-id="{{ $tx->id }}"
                                            data-employer-name="{{ $employer->employerNameTh ?? '-' }}"
                                            data-wht-amount="{{ number_format($tx->withholding_tax_amount, 2) }}">
                                        <i class="bi bi-upload me-1"></i>{{ __('Upload WHT Document') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-no-wht"
                                            data-bs-toggle="modal" data-bs-target="#whtNoCertModal"
                                            data-transaction-id="{{ $tx->id }}"
                                            data-employer-name="{{ $employer->employerNameTh ?? '-' }}"
                                            data-wht-amount="{{ number_format($tx->withholding_tax_amount, 2) }}">
                                        <i class="bi bi-x-circle me-1"></i>{{ __('Client Did Not Provide WHT') }}
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                            {{ __('No outstanding WHT documents') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-end">
        {{ $transactions->links() }}
    </div>
</div>

{{-- Upload WHT Modal --}}
<div class="modal fade" id="whtUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="whtUploadForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Upload WHT Document') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">{{ __('Employer') }}</label>
                        <div id="whtUploadEmployer" class="fw-bold">-</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">{{ __('WHT Amount') }}</label>
                        <div id="whtUploadAmount" class="fw-bold text-warning">-</div>
                    </div>
                    <div class="mb-3">
                        <label for="wht_document" class="form-label">{{ __('Upload WHT Document') }}</label>
                        <input type="file" class="form-control" id="wht_document" name="wht_document" accept="image/png,image/jpeg,image/jpg,application/pdf" required>
                        <small class="text-muted">{{ __('Max size: 2MB. Allowed formats: PNG, JPG.') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check2-circle me-1"></i>{{ __('Mark as Received') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- No Certificate Modal — กรณีลูกค้ายืนยันว่าไม่ออกใบ ณ ที่จ่ายให้ --}}
<div class="modal fade" id="whtNoCertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="whtNoCertForm" method="POST">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>{{ __('No WHT Certificate') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small d-flex align-items-start">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2 mt-1"></i>
                        <div>{{ __('Are you sure the client will not issue a WHT certificate? This action will mark this transaction as resolved.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">{{ __('Employer') }}</label>
                        <div id="whtNoCertEmployer" class="fw-bold">-</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">{{ __('WHT Amount') }}</label>
                        <div id="whtNoCertAmount" class="fw-bold text-warning">-</div>
                    </div>
                    <div class="mb-3">
                        <label for="wht_no_cert_reason" class="form-label">{{ __('Reason for no WHT certificate') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="wht_no_cert_reason" name="reason" rows="3" required
                                  placeholder="{{ __('e.g. Client is an individual / Out-of-Scope Service / Confirmed by phone on...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check2-circle me-1"></i>{{ __('Confirm No Certificate') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Upload WHT — bind data
    const uploadForm = document.getElementById('whtUploadForm');
    document.querySelectorAll('.btn-upload-wht').forEach(btn => {
        btn.addEventListener('click', function () {
            const txId = this.dataset.transactionId;
            uploadForm.action = `/finance/wht-inbox/${txId}/received`;
            document.getElementById('whtUploadEmployer').textContent = this.dataset.employerName;
            document.getElementById('whtUploadAmount').textContent = '฿' + this.dataset.whtAmount;
        });
    });

    // No Certificate — bind data
    const noCertForm = document.getElementById('whtNoCertForm');
    document.querySelectorAll('.btn-no-wht').forEach(btn => {
        btn.addEventListener('click', function () {
            const txId = this.dataset.transactionId;
            noCertForm.action = `/finance/wht-inbox/${txId}/no-certificate`;
            document.getElementById('whtNoCertEmployer').textContent = this.dataset.employerName;
            document.getElementById('whtNoCertAmount').textContent = '฿' + this.dataset.whtAmount;
            document.getElementById('wht_no_cert_reason').value = '';
        });
    });
});
</script>
@endpush
