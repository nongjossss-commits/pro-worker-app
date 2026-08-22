{{--
    TRAINING EDITION — Finance Bundle.
    Slide-friendly add-on training booklet for the Finance module (sold separately).
    Includes Sales (quotation flow) + Finance + Financial Profiles.
--}}
@php $brand = \App\Services\BrandService::current(); @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Finance Training Manual') }} — {{ $brand['app_name'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --brand: {{ $brand['primary_color'] }}; --brand-dark: color-mix(in srgb, var(--brand) 70%, black); }
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: 'Sarabun', sans-serif; color: #1f2937; font-size: 16px; line-height: 1.7; max-width: 1100px; margin: 0 auto; padding: 24px; background: #f8fafc; }

        .cover { background: white; text-align: center; padding: 70px 30px; border: 3px solid var(--brand); border-radius: 12px; margin-bottom: 30px; page-break-after: always; }
        .cover img { max-height: 120px; margin-bottom: 24px; }
        .cover .edition-badge { display: inline-block; padding: 8px 22px; background: #10b981; color: white; border-radius: 999px; font-size: 16px; font-weight: 800; margin-bottom: 24px; letter-spacing: 1px; }
        .cover h1 { font-size: 42px; color: var(--brand); margin: 0 0 12px; font-weight: 800; }
        .cover .sub { font-size: 20px; color: #6b7280; margin-bottom: 36px; }
        .cover .meta { font-size: 14px; color: #9ca3af; }
        .cover .note { margin-top: 36px; padding: 16px 22px; background: #ecfdf5; border-left: 6px solid #10b981; border-radius: 8px; text-align: left; font-size: 14px; color: #065f46; max-width: 700px; margin-left: auto; margin-right: auto; }

        .toc { background: white; padding: 40px 50px; border-radius: 12px; page-break-after: always; }
        .toc h2 { color: var(--brand); border-bottom: 3px solid var(--brand); padding-bottom: 10px; font-size: 28px; }
        .toc ol { padding-left: 24px; font-size: 18px; line-height: 2; }
        .toc a { color: #1f2937; text-decoration: none; }

        .manual-section { page-break-before: always; padding: 30px 40px; background: white; border-radius: 12px; margin-bottom: 30px; }
        .manual-section > h2 { color: white; background: var(--brand); padding: 16px 24px; border-radius: 8px; font-size: 26px; margin: -20px -20px 24px -20px; display: flex; align-items: center; gap: 12px; }

        .training-intro { background: linear-gradient(135deg, color-mix(in srgb, var(--brand) 8%, white), white); padding: 24px 28px; border-radius: 10px; border-left: 6px solid var(--brand); margin-bottom: 30px; }
        .training-intro-title { color: var(--brand-dark); font-size: 22px; font-weight: 800; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .training-intro-desc { font-size: 15px; line-height: 1.8; color: #374151; margin-bottom: 14px; }
        .training-role-row { display: flex; flex-wrap: wrap; gap: 8px; }
        .role-pill { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; }
        .role-pill.role-admin { background: #ddd6fe; color: #5b21b6; }
        .role-pill.role-readonly { background: #fef3c7; color: #92400e; }

        .training-slide { background: #fafbff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px 36px; margin: 28px 0; page-break-inside: avoid; position: relative; }
        .slide-number { position: absolute; top: -16px; left: 24px; background: var(--brand); color: white; padding: 8px 20px; border-radius: 999px; font-weight: 800; font-size: 14px; letter-spacing: 1.5px; }
        .slide-title { color: var(--brand-dark); font-size: 26px; font-weight: 800; margin: 12px 0 24px; padding-bottom: 12px; border-bottom: 2px dashed var(--brand); }
        .slide-instructions { background: white; padding: 16px 22px; border-radius: 8px; border-left: 4px solid #10b981; margin-top: 20px; }
        .slide-instructions ol { padding-left: 22px; line-height: 2; margin: 0; }
        .slide-instructions strong { color: var(--brand-dark); }
        .slide-tip { background: #fff8e1; padding: 14px 20px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-top: 16px; font-size: 14px; color: #6b5800; }
        .slide-warn { background: #fee2e2; padding: 14px 20px; border-radius: 8px; border-left: 4px solid #dc2626; margin-top: 16px; font-size: 14px; color: #7f1d1d; }
        .slide-faq dt { font-weight: 700; color: var(--brand-dark); margin-top: 14px; font-size: 16px; }
        .slide-faq dd { margin-left: 24px; color: #4b5563; line-height: 1.8; }

        .screenshot-block { margin: 20px 0; }
        .screenshot-figure { margin: 0; text-align: center; }
        .screenshot-img { max-width: 100%; height: auto; border: 2px solid #d1d5db; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .screenshot-caption { font-size: 13px; color: #6b7280; margin-top: 10px; font-style: italic; }
        .screenshot-placeholder { background: repeating-linear-gradient(45deg, #f3f4f6, #f3f4f6 10px, #e5e7eb 10px, #e5e7eb 20px); border: 3px dashed #9ca3af; border-radius: 12px; min-height: 280px; display: flex; align-items: center; justify-content: center; padding: 30px; }
        .screenshot-placeholder-inner { text-align: center; max-width: 500px; }
        .screenshot-placeholder-icon { font-size: 56px; color: #9ca3af; margin-bottom: 16px; }
        .screenshot-placeholder-title { font-weight: 800; color: #4b5563; font-size: 16px; margin-bottom: 8px; letter-spacing: 1px; }
        .screenshot-placeholder-desc { color: #6b7280; font-size: 14px; line-height: 1.7; margin-bottom: 14px; }
        .screenshot-placeholder-hint { font-size: 12px; color: #9ca3af; }
        .screenshot-placeholder-hint code { background: white; padding: 3px 10px; border-radius: 4px; color: #6366f1; }
        .screenshot-callouts { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 24px; margin-top: 14px; counter-reset: callout; list-style: none; padding-left: 16px; }
        .screenshot-callouts li { position: relative; padding-left: 36px; margin-bottom: 10px; line-height: 1.7; counter-increment: callout; }
        .screenshot-callouts li::before { content: counter(callout); position: absolute; left: 0; top: 2px; width: 26px; height: 26px; background: var(--brand); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; }

        .print-hint { background: #10b981; color: white; padding: 14px 24px; text-align: center; position: sticky; top: 0; z-index: 100; border-radius: 8px 8px 0 0; }
        .print-hint button { background: white; color: #10b981; border: 0; padding: 8px 18px; border-radius: 6px; font-weight: 800; cursor: pointer; margin-left: 16px; }
        @media print {
            body { background: white; padding: 0; }
            .print-hint { display: none; }
            .manual-section, .cover, .toc { background: white; }
        }
    </style>
</head>
<body>

<div class="print-hint">
    <i class="bi bi-cash-coin"></i>
    {{ __('FINANCE TRAINING EDITION') }} — {{ __('Press Ctrl+P to print or save as PDF.') }}
    <button onclick="window.print()">{{ __('Print now') }}</button>
</div>

{{-- Cover --}}
<div class="cover">
    @if(!empty($brand['active_logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($brand['active_logo']))
        <img src="{{ asset('storage/' . $brand['active_logo']) }}" alt="Logo">
    @endif
    <div class="edition-badge">💰 FINANCE TRAINING EDITION</div>
    <h1>{{ __('คู่มือฝึกอบรมการเงิน') }}</h1>
    <div class="sub">{{ $brand['app_name'] }}</div>
    <div class="meta">
        {{ __('Add-on training booklet — สำหรับลูกค้าที่สมัครโมดูล Finance') }}<br>
        {{ __('Generated on') }} {{ now()->format('d/m/Y H:i') }}
    </div>
    <div class="note">
        <strong>{{ __('สำคัญ:') }}</strong>
        {{ __('คู่มือฝึกอบรมเล่มนี้ครอบคลุมเฉพาะโมดูล Finance ที่ขายเป็น add-on แยกต่างหาก — รวม Sales (ใบเสนอราคา) + Finance (Ledger/Tax Invoice/WHT) + Financial Profiles (Master Data)') }}
    </div>
</div>

{{-- TOC --}}
@php
    $sections = [
        ['key' => 'sales',               'title' => '🛒 การขาย — Sales (ใบเสนอราคา)',              'title_en' => '🛒 Sales (Quotations)',        'title_zh' => '🛒 销售(报价单)',              'title_my' => '🛒 ရောင်းချမှု'],
        ['key' => 'finance',             'title' => '💰 การเงิน — Finance (Ledger / Tax / WHT)',    'title_en' => '💰 Finance (Ledger / Tax / WHT)', 'title_zh' => '💰 财务(账簿 / 税务 / 预扣税)', 'title_my' => '💰 ငွေကြေး'],
        ['key' => 'financial_profiles',  'title' => '🏷️ โปรไฟล์การเงิน — Financial Profiles',       'title_en' => '🏷️ Financial Profiles',         'title_zh' => '🏷️ 财务档案',                 'title_my' => '🏷️ ငွေကြေး Profile များ'],
    ];

    $locale = app()->getLocale();
    $sectionTitle = fn($s) => match($locale) {
        'en' => $s['title_en'], 'zh' => $s['title_zh'], 'my' => $s['title_my'], default => $s['title'],
    };
    $resolveManual = function($key) use ($locale) {
        return ($locale !== 'th' && view()->exists("manuals.training.{$locale}.{$key}"))
            ? "manuals.training.{$locale}.{$key}"
            : "manuals.training.{$key}";
    };
@endphp

<div class="toc">
    <h2><i class="bi bi-list-ol"></i> {{ __('Table of Contents') }}</h2>
    <ol>
        @foreach($sections as $s)
            @if(view()->exists('manuals.training.' . $s['key']))
                <li><a href="#sec-{{ $s['key'] }}">{{ $sectionTitle($s) }}</a></li>
            @endif
        @endforeach
    </ol>
</div>

{{-- All training sections inline --}}
@foreach($sections as $s)
    @if(view()->exists('manuals.training.' . $s['key']))
        <section class="manual-section" id="sec-{{ $s['key'] }}">
            <h2><i class="bi bi-book-fill"></i> {{ $sectionTitle($s) }}</h2>
            @include($resolveManual($s['key']))
        </section>
    @endif
@endforeach

</body>
</html>
