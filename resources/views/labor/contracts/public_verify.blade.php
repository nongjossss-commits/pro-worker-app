<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Contract Verification') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 text-center">
                        @if($contract)
                            <i class="bi" style="font-size: 3rem; color: #16a34a;">&#10003;</i>
                            <h4 class="fw-bold mt-2 mb-3" style="color:#16a34a;">{{ __('This contract number is genuine.') }}</h4>
                            <table class="table table-borderless text-start mb-0">
                                <tr>
                                    <th class="text-muted fw-normal" style="width: 40%;">{{ __('Contract No.') }}</th>
                                    <td class="fw-bold font-monospace">{{ $contract->contract_no }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">{{ __('Issued at') }}</th>
                                    <td>{{ $contract->issued_at->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">{{ __('Employer') }}</th>
                                    <td>{{ $contract->employer_name_snapshot ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">{{ __('Issued by') }}</th>
                                    <td>{{ $companyProfile->name ?? config('app.name') }}</td>
                                </tr>
                            </table>
                        @else
                            <i class="bi" style="font-size: 3rem; color: #dc2626;">&#10007;</i>
                            <h4 class="fw-bold mt-2" style="color:#dc2626;">{{ __('Contract number not found.') }}</h4>
                            <p class="text-muted mb-0">{{ __('This number does not match any contract in our system.') }}</p>
                        @endif
                    </div>
                </div>
                <p class="text-center text-muted small mt-3">{{ $companyProfile->name ?? config('app.name') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
