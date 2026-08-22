@php
    $brand = app(\App\Services\BrandService::class)->current();
    $primaryColor = $brand['primary'] ?? '#F97316';
    $logoUrl = $brand['logo_url'] ?? null;

    $issueDate = $contract['date'] ?? date('Y-m-d');
    $serviceStart = $contract['service_start'] ?? '';
    $serviceYears = (int) ($contract['service_years'] ?? 1);
    $package = $contract['package'] ?? null;

    $serviceEnd = '';
    if ($serviceStart) {
        try {
            $serviceEnd = \Carbon\Carbon::parse($serviceStart)->addYears($serviceYears)->subDay()->format('Y-m-d');
        } catch (\Throwable $e) {}
    }

    $fmtDate = function ($d) {
        if (!$d) return '___________';
        try { return \Carbon\Carbon::parse($d)->format('d/m/Y'); } catch (\Throwable $e) { return $d; }
    };
    $fmtMoney = function ($n) {
        return number_format((float)($n ?? 0), 2);
    };

    // $langs (1 or 2 locale codes) and $langData (subset of config('contracts.service')
    // keyed by those codes) are supplied by SettingsController::programContractView().
    $langs = $langs ?? ['th'];
    $L = $langData;

    $pair = function (string $field) use ($L, $langs) {
        $parts = [];
        foreach ($langs as $lg) {
            $v = data_get($L[$lg] ?? [], $field);
            if ($v !== null && $v !== '') $parts[] = $v;
        }
        return implode(' / ', array_map('e', $parts));
    };

    $stack = function (string $field, bool $raw = false) use ($L, $langs) {
        $html = '';
        foreach ($langs as $i => $lg) {
            $v = data_get($L[$lg] ?? [], $field);
            if ($v === null || $v === '') continue;
            $out = $raw ? $v : e($v);
            $html .= '<div class="' . ($i === 0 ? 'lang-primary' : 'lang-secondary') . '">' . $out . '</div>';
        }
        return $html;
    };

    $renderClauseBody = function (string $lg, array $clause) use ($L, $serviceStart, $serviceEnd, $serviceYears, $fmtDate) {
        $body = $clause['body'];
        $body = str_replace('{{SERVICE_START}}', e($fmtDate($serviceStart)), $body);
        $body = str_replace('{{SERVICE_END}}', e($fmtDate($serviceEnd)), $body);
        $yearUnit = $L[$lg]['year_unit'] ?? '';
        $body = str_replace('{{SERVICE_YEARS}}', e($serviceYears . ' ' . $yearUnit), $body);
        return $body;
    };

    $renderIntro = function (string $lg) use ($L, $provider, $customer) {
        $body = $L[$lg]['intro'] ?? '';
        $body = str_replace('{{PROVIDER_NAME}}', e($provider['name'] ?: '____________________________'), $body);
        $body = str_replace('{{CUSTOMER_NAME}}', e($customer['name'] ?: '____________________________'), $body);
        return $body;
    };

    $clauseCount = count($L[$langs[0]]['clauses'] ?? []);

    // Legacy compatibility: the original single-language template always
    // showed a fixed English subtitle + "(Service Provider)"/"(Customer)"
    // suffix under the Thai title, even though the rest of the document was
    // Thai only. Preserve that exact appearance when Thai is the sole
    // selected language (the default), so existing users see zero change.
    $legacyMode = ($langs === ['th']);
@endphp
<!doctype html>
<html lang="{{ $langs[0] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $legacyMode ? 'สัญญาเช่าใช้บริการระบบ' : ($L[$langs[0]]['doc_title'] ?? 'Service Contract') }} — {{ $customer['name'] ?: 'ลูกค้า' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --pc-primary: {{ $primaryColor }}; }
        body { background: #f5f5f5; font-family: 'Sukhumvit Set', 'Sarabun', -apple-system, sans-serif; color: #1a1a1a; line-height: 1.65; font-size: 13.5px; }
        .contract-page { max-width: 880px; margin: 24px auto; background: white; padding: 50px 64px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); border-radius: 6px; }
        .doc-header { text-align: center; margin-bottom: 32px; }
        .doc-header img { max-height: 60px; margin-bottom: 12px; }
        .doc-header .doc-id { font-size: 0.78rem; color: #888; }
        .doc-header h1 { font-size: 1.5rem; font-weight: 800; color: #1a1a1a; margin: 12px 0 4px 0; letter-spacing: 0.5px; }
        .doc-header .subtitle { color: var(--pc-primary); font-weight: 600; font-size: 0.95rem; }
        .doc-divider { width: 100px; height: 3px; background: var(--pc-primary); margin: 12px auto 24px auto; border-radius: 2px; }

        .parties-box { background: #fafafa; border: 1px solid #e5e5e5; border-radius: 6px; padding: 16px 20px; margin-bottom: 24px; }
        .parties-box h6 { color: var(--pc-primary); font-weight: 700; margin-bottom: 8px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        .parties-box .party-row { display: grid; grid-template-columns: 130px 1fr; gap: 4px 16px; font-size: 0.88rem; }
        .parties-box .party-row .lbl { color: #666; }
        .parties-box .party-row .val { font-weight: 500; }

        .package-box { background: #e7f5e7; border: 2px solid #28a745; border-radius: 6px; padding: 14px 18px; margin-bottom: 24px; }
        .package-box h6 { color: #155724; font-weight: 800; margin-bottom: 8px; font-size: 0.9rem; }
        .package-box .pkg-row { display: grid; grid-template-columns: 160px 1fr; gap: 4px 16px; font-size: 0.9rem; }
        .package-box .pkg-row .lbl { color: #2d5a2d; }
        .package-box .pkg-row .val { font-weight: 700; color: #155724; }

        .clause { margin: 18px 0; }
        .clause-title { font-weight: 700; font-size: 0.98rem; color: var(--pc-primary); margin-bottom: 6px; }
        .clause-body { font-size: 0.88rem; text-align: justify; padding-left: 12px; }
        .clause-body ol, .clause-body ul { padding-left: 20px; margin: 4px 0; }
        .clause-body li { margin-bottom: 4px; }

        .exclusion-box { background: #f8d7da; border: 2px solid #dc3545; border-radius: 6px; padding: 14px 18px; margin: 18px 0; }
        .exclusion-box .ex-title { font-weight: 800; color: #721c24; margin-bottom: 6px; font-size: 0.95rem; }
        .exclusion-box .ex-body { font-size: 0.88rem; color: #721c24; }

        .security-box { background: #cfe2ff; border: 2px solid #0d6efd; border-radius: 6px; padding: 14px 18px; margin: 18px 0; }
        .security-box .sec-title { font-weight: 800; color: #052c65; margin-bottom: 6px; font-size: 0.95rem; }
        .security-box .sec-body { font-size: 0.88rem; color: #052c65; }
        .security-box .sec-body ul { padding-left: 20px; margin: 4px 0; }

        .signatures { margin-top: 48px; }
        .sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 32px; }
        .sig-block { text-align: center; }
        .sig-line { border-bottom: 1.5px solid #333; height: 60px; margin-bottom: 8px; }
        .sig-name { font-weight: 700; font-size: 0.9rem; }
        .sig-title { font-size: 0.82rem; color: #555; }
        .sig-date { font-size: 0.82rem; color: #888; margin-top: 4px; }
        .sig-role { font-size: 0.78rem; color: var(--pc-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }

        .print-controls { position: sticky; top: 0; background: white; padding: 12px 24px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); z-index: 100; }
        .btn-print { background: #28a745; color: white; border: none; padding: 8px 22px; font-weight: 600; border-radius: 6px; cursor: pointer; }

        .lang-secondary { margin-top: 6px; padding-top: 6px; border-top: 1px dashed #d8d8d8; }
        .clause-title .lang-secondary,
        .ex-title .lang-secondary,
        .sec-title .lang-secondary { margin-top: 2px; padding-top: 2px; font-size: 0.85em; opacity: 0.85; }
        .doc-header .lang-secondary { border-top: none; margin-top: 2px; }
        h6 .lang-secondary { font-size: 0.85em; opacity: 0.8; border-top: none; margin-top: 2px; }
        .sig-role .lang-secondary { border-top: none; font-size: 0.9em; margin-top: 2px; }

        @media print {
            body { background: white; font-size: 12px; }
            .print-controls { display: none !important; }
            .contract-page { box-shadow: none; margin: 0 auto; padding: 20px 30px; max-width: 100%; }
            .clause { page-break-inside: avoid; }
            .signatures { page-break-inside: avoid; }
            @page { size: A4; margin: 14mm; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <div>
            <strong>{{ __('Service Contract Preview') }}</strong> — {{ __('กด Ctrl+P เพื่อบันทึก PDF') }}
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('super-admin.settings.index', ['tab' => 'program-pricelist']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
            </a>
            <button class="btn-print" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> {{ __('Print / Save as PDF') }}
            </button>
        </div>
    </div>

    <div class="contract-page">
        <div class="doc-header">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="">@endif
            <div class="doc-id">{{ $pair('doc_id_label') }} SVC-{{ str_pad(crc32($customer['name'] . $issueDate) % 99999, 5, '0', STR_PAD_LEFT) }}</div>
            <h1>{!! $stack('doc_title') !!}</h1>
            @if($legacyMode)<div class="subtitle">Software-as-a-Service (SaaS) Subscription Agreement</div>@endif
            <div class="doc-divider"></div>
            <p class="text-muted small mb-0">{{ $pair('made_at_label') }} {{ $provider['address'] ?: '____________________________' }}</p>
            <p class="text-muted small">{{ $pair('date_label') }} {{ $fmtDate($issueDate) }}</p>
        </div>

        @php
            $introHtml = '';
            foreach ($langs as $i => $lg) {
                $introHtml .= '<div class="' . ($i === 0 ? 'lang-primary' : 'lang-secondary') . '">' . $renderIntro($lg) . '</div>';
            }
        @endphp
        <div class="mb-3">{!! $introHtml !!}</div>

        {{-- Parties --}}
        <div class="parties-box">
            <h6>@if($legacyMode){{ $L['th']['provider_box_title'] }} (Service Provider)@else{!! $stack('provider_box_title') !!}@endif</h6>
            <div class="party-row">
                <div class="lbl">{{ $pair('party_labels.company_name') }}</div>      <div class="val">{{ $provider['name'] ?: '—' }}</div>
                <div class="lbl">{{ $pair('party_labels.address') }}</div>          <div class="val">{{ $provider['address'] ?: '—' }}</div>
                <div class="lbl">{{ $pair('party_labels.tax_id') }}</div>   <div class="val">{{ $provider['tax_id'] ?: '—' }}</div>
                <div class="lbl">{{ $pair('party_labels.phone_email') }}</div> <div class="val">{{ $provider['phone'] ?: '—' }} / {{ $provider['email'] ?: '—' }}</div>
            </div>
        </div>

        <div class="parties-box">
            <h6>@if($legacyMode){{ $L['th']['customer_box_title'] }} (Customer)@else{!! $stack('customer_box_title') !!}@endif</h6>
            <div class="party-row">
                <div class="lbl">{{ $pair('party_labels.company_name') }}</div>      <div class="val">{{ $customer['name'] ?: '—' }}</div>
                <div class="lbl">{{ $pair('party_labels.address') }}</div>          <div class="val">{{ $customer['address'] ?: '—' }}</div>
                <div class="lbl">{{ $pair('party_labels.tax_id') }}</div>   <div class="val">{{ $customer['tax_id'] ?: '—' }}</div>
                <div class="lbl">{{ $pair('party_labels.phone_email') }}</div> <div class="val">{{ $customer['phone'] ?: '—' }} / {{ $customer['email'] ?: '—' }}</div>
            </div>
        </div>

        {{-- Package box --}}
        @if($package)
            <div class="package-box">
                <h6><i class="bi bi-box-seam me-1"></i> @if($legacyMode){{ $L['th']['package_box_title'] }} (Package Details)@else{!! $stack('package_box_title') !!}@endif</h6>
                <div class="pkg-row">
                    <div class="lbl">{{ $pair('package_labels.tier') }}</div>            <div class="val">{{ $package['sublabel'] }} — {{ $package['label'] }}</div>
                    <div class="lbl">{{ $pair('package_labels.setup_fee') }}</div> <div class="val">{{ $fmtMoney($package['setup_fee']) }} {{ $pair('currency_unit') }}</div>
                    <div class="lbl">{{ $pair('package_labels.annual_fee') }}</div>            <div class="val">{{ $fmtMoney($package['annual_fee']) }} {{ $pair('currency_unit') }}/{{ $pair('year_unit') }}</div>
                    <div class="lbl">{{ $pair('package_labels.start') }}</div>          <div class="val">{{ $fmtDate($serviceStart) }}</div>
                    <div class="lbl">{{ $pair('package_labels.end') }}</div>         <div class="val">{{ $fmtDate($serviceEnd) }} ({{ $serviceYears }} {{ $pair('year_unit') }})</div>
                </div>
            </div>
        @endif

        {{-- Clauses --}}
        @for ($i = 0; $i < $clauseCount; $i++)
            @php
                $type = $L[$langs[0]]['clauses'][$i]['type'] ?? 'clause';
                $boxClass = match($type) {
                    'exclusion' => 'exclusion-box',
                    'security' => 'security-box',
                    default => null,
                };
                $titleClass = match($type) {
                    'exclusion' => 'ex-title',
                    'security' => 'sec-title',
                    default => 'clause-title',
                };
                $bodyClass = match($type) {
                    'exclusion' => 'ex-body',
                    'security' => 'sec-body',
                    default => 'clause-body',
                };
                $icon = match($type) {
                    'exclusion' => '<i class="bi bi-shield-exclamation me-1"></i> ',
                    'security' => '<i class="bi bi-shield-lock-fill me-1"></i> ',
                    default => '',
                };
            @endphp
            <div class="{{ $boxClass ?? 'clause' }}">
                <div class="{{ $titleClass }}">
                    @foreach ($langs as $li => $lg)
                        @php
                            $clause = $L[$lg]['clauses'][$i] ?? null;
                            if (!$clause) continue;
                            $prefix = str_replace(':n', $i + 1, $L[$lg]['clause_prefix'] ?? '');
                        @endphp
                        <div class="{{ $li === 0 ? 'lang-primary' : 'lang-secondary' }}">
                            {!! $icon !!}{{ $prefix }} {!! $clause['title'] !!}
                        </div>
                    @endforeach
                </div>
                <div class="{{ $bodyClass }}">
                    @foreach ($langs as $li => $lg)
                        @php
                            $clause = $L[$lg]['clauses'][$i] ?? null;
                            if (!$clause) continue;
                        @endphp
                        <div class="{{ $li === 0 ? 'lang-primary' : 'lang-secondary' }}">
                            {!! $renderClauseBody($lg, $clause) !!}
                        </div>
                    @endforeach
                </div>
            </div>
        @endfor

        <p class="text-center mt-4">{!! $stack('closing_paragraph') !!}</p>

        {{-- Signatures --}}
        <div class="signatures">
            <div class="sig-grid">
                <div class="sig-block">
                    <div class="sig-role">{!! $stack('signature_labels.provider_role') !!}</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">({{ $provider['signer_name'] ?: '____________________________' }})</div>
                    <div class="sig-title">{{ $provider['signer_title'] ?: $pair('signature_labels.position_placeholder') }}</div>
                    <div class="sig-date">{{ $pair('date_label') }} ____ / ____ / ________</div>
                </div>
                <div class="sig-block">
                    <div class="sig-role">{!! $stack('signature_labels.customer_role') !!}</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">({{ $customer['signer_name'] ?: '____________________________' }})</div>
                    <div class="sig-title">{{ $customer['signer_title'] ?: $pair('signature_labels.position_placeholder') }}</div>
                    <div class="sig-date">{{ $pair('date_label') }} ____ / ____ / ________</div>
                </div>
            </div>

            <div class="sig-grid">
                <div class="sig-block">
                    <div class="sig-role">{!! $stack('signature_labels.witness_1') !!}</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">({{ $witnesses[0]['name'] ?? '' ?: '____________________________' }})</div>
                    <div class="sig-title">{{ $witnesses[0]['title'] ?? '' ?: $pair('signature_labels.position_placeholder') }}</div>
                </div>
                <div class="sig-block">
                    <div class="sig-role">{!! $stack('signature_labels.witness_2') !!}</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">({{ $witnesses[1]['name'] ?? '' ?: '____________________________' }})</div>
                    <div class="sig-title">{{ $witnesses[1]['title'] ?? '' ?: $pair('signature_labels.position_placeholder') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
