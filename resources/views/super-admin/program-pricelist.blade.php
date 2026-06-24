@php
    $brand = app(\App\Services\BrandService::class)->current();
    $primaryColor = $brand['primary'] ?? '#F97316';
    $appName = $brand['app_name'] ?? config('app.name', 'Pro-Worker');
    $logoUrl = $brand['logo_url'] ?? null;
    // Pull provider info so the issuer header on the pricelist matches the contract issuer
    $providerRow = \App\Models\SuperAdminSetting::where('key', 'program_provider_info')->value('value');
    $provider = $providerRow ? json_decode($providerRow, true) : [];
    $provider = is_array($provider) ? $provider : [];
@endphp
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $config['title'] ?? 'Pricelist' }} — {{ $appName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --pl-primary: {{ $primaryColor }};
            --pl-primary-soft: {{ $primaryColor }}1A;
            --pl-primary-dark: #B8860B;
        }
        body {
            background: #f5f5f5;
            font-family: 'Sukhumvit Set', 'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #333;
        }
        .pricelist-page {
            max-width: 1100px;
            margin: 24px auto;
            background: white;
            padding: 48px 56px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            border-radius: 8px;
        }
        .pl-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .pl-header .brand-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 20px;
            color: #555;
        }
        .pl-header .brand-row img {
            max-height: 56px;
            width: auto;
        }
        .pl-header .brand-row .brand-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--pl-primary);
        }
        .pl-header .divider {
            width: 80px;
            height: 3px;
            background: var(--pl-primary);
            margin: 0 auto 12px auto;
            border-radius: 2px;
        }
        .pl-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #5a4a00;
            letter-spacing: 1px;
            margin: 0;
        }
        .pl-header h1 .accent {
            color: var(--pl-primary);
        }

        table.pricelist {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2px;
            margin-top: 24px;
        }
        table.pricelist th,
        table.pricelist td {
            padding: 14px 12px;
            text-align: center;
            vertical-align: middle;
            background: #fdf4dc;
        }
        table.pricelist thead th {
            background: var(--pl-primary);
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
            line-height: 1.3;
        }
        table.pricelist thead th .sublabel {
            display: block;
            font-size: 0.7rem;
            opacity: 0.9;
            font-weight: 500;
            margin-bottom: 6px;
        }
        table.pricelist thead th .label-main {
            font-size: 1.05rem;
            font-weight: 800;
        }
        table.pricelist td.no-col {
            background: var(--pl-primary);
            color: white;
            font-weight: 700;
            width: 50px;
        }
        table.pricelist td.item-col {
            text-align: left;
            font-weight: 600;
            color: var(--pl-primary);
            background: #fffaee;
            width: 220px;
        }
        table.pricelist td.scope-col {
            text-align: left;
            color: #555;
            background: white;
            font-size: 0.88rem;
            width: 280px;
        }
        table.pricelist td.tier-price-cell {
            font-weight: 700;
            color: #333;
        }
        table.pricelist td.included {
            color: var(--pl-primary);
            font-weight: 600;
        }
        table.pricelist tr.row-total td {
            background: var(--pl-primary);
            color: white;
            font-weight: 800;
            font-size: 1.05rem;
            padding: 16px 12px;
        }
        table.pricelist tr.row-annual td {
            background: #d4a017;
            color: white;
            font-weight: 700;
        }
        table.pricelist tr.row-annual td.item-col {
            background: #d4a017;
            color: white;
        }
        table.pricelist tr.row-annual td.tier-price-cell .ann-size {
            display: block;
            font-size: 0.75rem;
            font-weight: 400;
            opacity: 0.85;
            margin-bottom: 2px;
        }

        .footer-note {
            margin-top: 28px;
            padding: 12px 16px;
            background: #fff8e1;
            border-left: 4px solid var(--pl-primary);
            border-radius: 4px;
            font-size: 0.85rem;
            color: #6b5800;
        }

        .print-controls {
            position: sticky;
            top: 0;
            background: white;
            padding: 14px 24px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            z-index: 100;
        }
        .print-controls .btn-print {
            background: var(--pl-primary);
            color: white;
            border: none;
            padding: 10px 24px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
        }
        .print-controls .btn-print:hover { opacity: 0.9; }

        /* Print styles */
        @media print {
            body { background: white; }
            .print-controls { display: none !important; }
            .pricelist-page {
                box-shadow: none;
                margin: 0 auto;
                padding: 20px 30px;
                max-width: 100%;
            }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <div>
            <strong>{{ __('Pricelist Preview') }}</strong> — {{ __('กด Ctrl+P (หรือ Cmd+P บน Mac) เพื่อบันทึกเป็น PDF') }}
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('super-admin.settings.index', ['tab' => 'program-pricelist']) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('Back to Settings') }}
            </a>
            <button class="btn-print" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> {{ __('Print / Save as PDF') }}
            </button>
        </div>
    </div>

    <div class="pricelist-page">
        <div class="pl-header">
            @if($logoUrl)
                <div class="brand-row">
                    <img src="{{ $logoUrl }}" alt="">
                </div>
            @endif
            @if(!empty($provider['name']))
                <div class="brand-row">
                    <span class="brand-name">{{ $provider['name'] }}</span>
                </div>
            @endif
            <div class="divider"></div>
            @php
                $title = $config['title'] ?? '';
                $subtitle = $config['subtitle'] ?? '';
            @endphp
            <h1>
                {{ $title }}
                @if($subtitle)
                    <span class="accent">{{ $subtitle }}</span>
                @endif
            </h1>
        </div>

        @php
            $tiers = $config['tiers'] ?? [];
            $features = $config['features'] ?? [];
            $currency = $config['currency'] ?? 'THB';
            $tierCount = count($tiers);
        @endphp

        @if($tierCount === 0)
            <div class="alert alert-warning text-center">
                {{ __('ยังไม่ได้ตั้งค่า tier ราคา — กรุณากลับไปตั้งค่าก่อน') }}
            </div>
        @else
            <table class="pricelist">
                <thead>
                    <tr>
                        <th class="no-col" style="background: var(--pl-primary);">No</th>
                        <th style="background: var(--pl-primary); text-align: left; padding-left: 16px;">Items</th>
                        <th style="background: var(--pl-primary); text-align: left; padding-left: 16px;">Scope of Work</th>
                        @foreach($tiers as $tier)
                            <th>
                                @if(!empty($tier['sublabel']))
                                    <span class="sublabel">{{ $tier['sublabel'] }}</span>
                                @endif
                                <span class="label-main">{{ $tier['label'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- Features rows --}}
                    @foreach($features as $idx => $feature)
                        <tr>
                            <td class="no-col">{{ $idx + 1 }}</td>
                            <td class="item-col">{{ $feature['name'] ?? '' }}</td>
                            <td class="scope-col">{{ $feature['scope'] ?? '' }}</td>
                            @foreach($tiers as $tier)
                                <td class="included">รวมแล้ว</td>
                            @endforeach
                        </tr>
                    @endforeach

                    {{-- TOTAL (Setup Fee) row --}}
                    <tr class="row-total">
                        <td colspan="3" style="text-align: right; padding-right: 24px;">TOTAL (ค่าแรกเข้า)</td>
                        @foreach($tiers as $tier)
                            <td>{{ number_format($tier['setup_fee'] ?? 0, 0) }}</td>
                        @endforeach
                    </tr>

                    {{-- ปีถัดไป (Annual) row --}}
                    <tr class="row-annual">
                        <td colspan="3" class="item-col" style="text-align: right; padding-right: 24px;">ปีถัดไป (รายปี)</td>
                        @foreach($tiers as $tier)
                            <td class="tier-price-cell">
                                @if(!empty($tier['sublabel']))
                                    <span class="ann-size">{{ $tier['sublabel'] }}</span>
                                @endif
                                {{ number_format($tier['annual_fee'] ?? 0, 0) }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>

            <div class="text-end small text-muted mt-2">
                {{ __('ราคาทุกช่อง แสดงเป็นสกุลเงิน') }} <strong>{{ $currency }}</strong>
            </div>

            @if(!empty($config['footer_note']))
                <div class="footer-note">
                    <i class="bi bi-info-circle me-1"></i>{{ $config['footer_note'] }}
                </div>
            @endif
        @endif
    </div>
</body>
</html>
