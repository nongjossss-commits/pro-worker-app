@extends('labor.layout')

@section('title', 'WHT Certificates - Pro Walker Labour')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('WHT Certificates') }} (ใบหัก ณ ที่จ่าย)</h4>
    <a href="{{ route('labor.wht-certificates.create') }}" class="btn btn-primary">
        <i class="bi bi-file-earmark-plus me-1"></i>{{ __('New Certificate') }}
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Cert No.') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Form') }}</th>
                    <th>{{ __('Payer') }}</th>
                    <th>{{ __('Payee') }}</th>
                    <th class="text-end">{{ __('WHT Amount') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $cert)
                <tr>
                    <td><a href="{{ route('labor.wht-certificates.show', $cert) }}">{{ $cert->cert_no }}</a></td>
                    <td>{{ $cert->type === 'received' ? __('Received') : __('Issued') }}</td>
                    <td>{{ strtoupper($cert->wht_type) }}</td>
                    <td>{{ $cert->payer_name }}</td>
                    <td>{{ $cert->payee_name }}</td>
                    <td class="text-end fw-bold">{{ number_format($cert->wht_amount, 2) }}</td>
                    <td class="text-center">
                        @if($cert->status === 'submitted')
                            <span class="badge bg-success">{{ __('Submitted') }}</span>
                        @elseif($cert->status === 'issued')
                            <span class="badge bg-primary">{{ __('Issued') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('Draft') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.wht-certificates.pdf', $cert) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-pdf"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No WHT certificates yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($certificates->hasPages())
    <div class="card-footer bg-white">{{ $certificates->links() }}</div>
    @endif
</div>
@endsection
