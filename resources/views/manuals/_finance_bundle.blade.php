{{--
    Finance-only manual bundle — separate from the main manual bundle.
    Contains only the Finance feature documentation (Finance + Financial Profiles)
    so it can be distributed independently to customers who subscribe to the
    Finance module (sold as a separate package per Sales Contract).
--}}
@php $brand = \App\Services\BrandService::current(); @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Finance Manual') }} — {{ $brand['app_name'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --brand: {{ $brand['primary_color'] }}; }
        @page { size: A4; margin: 18mm 16mm; }
        body { font-family: 'Sarabun', sans-serif; color: #1f2937; font-size: 14px; line-height: 1.7; max-width: 900px; margin: 0 auto; padding: 24px; }

        .cover { text-align: center; padding: 60px 20px; border-bottom: 4px solid var(--brand); margin-bottom: 40px; page-break-after: always; }
        .cover img { max-height: 100px; margin-bottom: 20px; }
        .cover .badge-finance { display: inline-block; padding: 6px 16px; background: var(--brand); color: #fff; border-radius: 999px; font-size: 14px; font-weight: bold; margin-bottom: 16px; }
        .cover h1 { font-size: 32px; color: var(--brand); margin: 0 0 10px; }
        .cover .sub { font-size: 18px; color: #6b7280; margin-bottom: 30px; }
        .cover .meta { font-size: 13px; color: #9ca3af; }
        .cover .note { margin-top: 30px; padding: 14px 18px; background: #fff8e1; border-left: 4px solid #f59e0b; border-radius: 4px; text-align: left; font-size: 13px; color: #6b5800; }

        .toc { page-break-after: always; }
        .toc h2 { color: var(--brand); border-bottom: 2px solid var(--brand); padding-bottom: 6px; }
        .toc ol { padding-left: 20px; font-size: 15px; }
        .toc li { margin-bottom: 6px; }
        .toc a { color: #1f2937; text-decoration: none; }

        .manual-section { page-break-before: always; padding-top: 20px; }
        .manual-section > h2 { color: var(--brand); border-bottom: 3px solid var(--brand); padding-bottom: 8px; font-size: 22px; margin-bottom: 16px; }

        h4 { font-size: 1.15rem; font-weight: bold; margin-top: 1.2rem; margin-bottom: 0.6rem; color: var(--brand); padding-bottom: 4px; border-bottom: 2px solid var(--brand); }
        h5 { font-size: 1rem; font-weight: bold; margin-top: 1rem; margin-bottom: 0.4rem; }
        ul, ol { padding-left: 1.5rem; }
        li { margin-bottom: 0.3rem; }
        .manual-step { background: #f8fafc; border-left: 4px solid var(--brand); padding: 10px 14px; margin: 10px 0; border-radius: 0 4px 4px 0; }
        .manual-tip { background: #fff8e1; border-left: 4px solid #f59e0b; padding: 10px 14px; margin: 10px 0; border-radius: 0 4px 4px 0; }
        .manual-warn { background: #fee2e2; border-left: 4px solid #dc2626; padding: 10px 14px; margin: 10px 0; border-radius: 0 4px 4px 0; }
        .manual-role { display: inline-block; padding: 2px 8px; background: #e0e7ff; color: #3730a3; border-radius: 999px; font-size: 0.85rem; font-weight: bold; margin: 0 2px; }
        kbd { background: #1f2937; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 0.85rem; }
        dt { font-weight: bold; margin-top: 0.6rem; color: #1f2937; }
        dd { margin-left: 1.2rem; margin-bottom: 0.4rem; color: #4b5563; }
        code { background: #f3f4f6; padding: 1px 5px; border-radius: 3px; font-size: 0.9em; }

        .print-hint { background: var(--brand); color: #fff; padding: 12px 20px; text-align: center; position: sticky; top: 0; z-index: 100; }
        .print-hint button { background: #fff; color: var(--brand); border: 0; padding: 6px 14px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-left: 12px; }
        @media print { .print-hint { display: none; } }
    </style>
</head>
<body>

<div class="print-hint">
    <i class="bi bi-printer-fill"></i>
    {{ __('Press Ctrl+P (or Cmd+P on Mac) to print or save as PDF.') }}
    <button onclick="window.print()">{{ __('Print now') }}</button>
</div>

{{-- Cover --}}
<div class="cover">
    @if(!empty($brand['active_logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($brand['active_logo']))
        <img src="{{ asset('storage/' . $brand['active_logo']) }}" alt="Logo">
    @endif
    <div class="badge-finance"><i class="bi bi-cash-coin"></i> {{ __('Finance Module') }}</div>
    <h1>{{ __('Finance Manual') }}</h1>
    <div class="sub">{{ $brand['app_name'] }}</div>
    <div class="meta">
        {{ __('Add-on training booklet for the Finance feature') }}<br>
        {{ __('Generated on') }} {{ now()->format('d/m/Y H:i') }}
    </div>
    <div class="note">
        <strong>{{ __('Note:') }}</strong>
        {{ __('This booklet covers the Finance feature only — a separately licensed module. The main program manual (other features) is distributed independently and does not include Finance content.') }}
    </div>
</div>

{{-- TOC --}}
@php
    // Sales is grouped here because it generates quotations + invoices which
    // are part of the Finance flow (this entire bundle is the Finance add-on).
    $sections = [
        ['key' => 'sales',               'title' => 'การขายและใบเสนอราคา / Sales',      'title_en' => 'Sales',               'title_zh' => '销售',   'title_my' => 'ရောင်းချမှု'],
        ['key' => 'finance',             'title' => 'การเงิน / Finance',                'title_en' => 'Finance',             'title_zh' => '财务',   'title_my' => 'ငွေကြေး'],
        ['key' => 'financial_profiles',  'title' => 'โปรไฟล์การเงิน / Financial Profiles', 'title_en' => 'Financial Profiles', 'title_zh' => '财务档案', 'title_my' => 'ငွေကြေး Profile များ'],
    ];

    $locale = app()->getLocale();
    $sectionTitle = fn($s) => match($locale) {
        'en' => $s['title_en'], 'zh' => $s['title_zh'], 'my' => $s['title_my'], default => $s['title'],
    };
    $resolveManual = function($key) use ($locale) {
        return ($locale !== 'th' && view()->exists("manuals.{$locale}.{$key}"))
            ? "manuals.{$locale}.{$key}"
            : "manuals.{$key}";
    };
@endphp

<div class="toc">
    <h2><i class="bi bi-list-ol"></i> {{ __('Table of Contents') }}</h2>
    <ol>
        @foreach($sections as $s)
            @if(view()->exists('manuals.' . $s['key']))
                <li><a href="#sec-{{ $s['key'] }}">{{ $sectionTitle($s) }}</a></li>
            @endif
        @endforeach
    </ol>
</div>

{{-- All Finance sections inline --}}
@foreach($sections as $s)
    @if(view()->exists('manuals.' . $s['key']))
        <section class="manual-section" id="sec-{{ $s['key'] }}">
            <h2>{{ $sectionTitle($s) }}</h2>
            @include($resolveManual($s['key']))
        </section>
    @endif
@endforeach

</body>
</html>
