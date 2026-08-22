{{--
    All-Manuals bundle — a single printable HTML page that includes every
    manual partial in order. Users hit Ctrl+P (or the browser "Print to PDF")
    to produce a training booklet branded with their installation's logo + name.
--}}
@php $brand = \App\Services\BrandService::current(); @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('User Manual') }} — {{ $brand['app_name'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --brand: {{ $brand['primary_color'] }}; }
        @page { size: A4; margin: 18mm 16mm; }
        body { font-family: 'Sarabun', sans-serif; color: #1f2937; font-size: 14px; line-height: 1.7; max-width: 900px; margin: 0 auto; padding: 24px; }

        .cover { text-align: center; padding: 60px 20px; border-bottom: 4px solid var(--brand); margin-bottom: 40px; page-break-after: always; }
        .cover img { max-height: 100px; margin-bottom: 20px; }
        .cover h1 { font-size: 32px; color: var(--brand); margin: 0 0 10px; }
        .cover .sub { font-size: 18px; color: #6b7280; margin-bottom: 30px; }
        .cover .meta { font-size: 13px; color: #9ca3af; }

        .toc { page-break-after: always; }
        .toc h2 { color: var(--brand); border-bottom: 2px solid var(--brand); padding-bottom: 6px; }
        .toc ol { padding-left: 20px; font-size: 15px; }
        .toc li { margin-bottom: 6px; }
        .toc a { color: #1f2937; text-decoration: none; }

        .manual-section { page-break-before: always; padding-top: 20px; }
        .manual-section > h2 { color: var(--brand); border-bottom: 3px solid var(--brand); padding-bottom: 8px; font-size: 22px; margin-bottom: 16px; }

        /* Reuse the same classes the in-app modal uses so manuals look identical. */
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
    <h1>{{ __('User Manual') }}</h1>
    <div class="sub">{{ $brand['app_name'] }}</div>
    <div class="meta">
        {{ __('Training booklet for new staff and end-users') }}<br>
        {{ __('Generated on') }} {{ now()->format('d/m/Y H:i') }}
    </div>
    <div style="margin-top: 30px; padding: 12px 18px; background: #eef2ff; border-left: 4px solid #6366f1; border-radius: 4px; text-align: left; font-size: 13px; color: #3730a3; max-width: 600px; margin-left: auto; margin-right: auto;">
        <strong>{{ __('Note:') }}</strong>
        {{ __('Finance feature (Ledger, Tax Invoices, Financial Profiles, etc.) is distributed in a SEPARATE Finance Manual Bundle — download it from Super Admin Settings if your subscription includes the Finance module.') }}
    </div>
</div>

{{-- TOC --}}
@php
    // Defined order — matches the sidebar grouping so reading top-to-bottom
    // feels like walking down the left menu from top.
    $sections = [
        ['key' => 'dashboard',                'title' => 'แดชบอร์ด / Dashboard',                        'title_en' => 'Dashboard',                'title_zh' => '仪表板',           'title_my' => 'ဒက်ရှ်ဘုတ်'],
        ['key' => 'notifications',            'title' => 'การแจ้งเตือน / Notifications',                 'title_en' => 'Notifications',            'title_zh' => '通知',             'title_my' => 'အသိပေးချက်များ'],
        ['key' => 'activity_logs',            'title' => 'ประวัติการกระทำ / Activity Logs',              'title_en' => 'Activity Logs',            'title_zh' => '操作日志',         'title_my' => 'လုပ်ဆောင်ချက် မှတ်တမ်း'],
        ['key' => 'incomplete_data',          'title' => 'ข้อมูลไม่ครบ / Incomplete Data',               'title_en' => 'Incomplete Data',          'title_zh' => '数据不完整',       'title_my' => 'ဒေတာမပြည့်စုံ'],
        ['key' => 'ticket_inbox',             'title' => 'กล่องรับเรื่อง / Ticket Inbox',                'title_en' => 'Ticket Inbox',             'title_zh' => '工单收件箱',       'title_my' => 'တောင်းဆိုချက် စာပုံး'],
        ['key' => 'employer_ticket',          'title' => 'ส่งคำขอ / Employer Ticket',                    'title_en' => 'Employer Ticket',          'title_zh' => '提交请求',         'title_my' => 'အလုပ်ရှင် တောင်းဆိုချက်'],
        ['key' => 'employers',                'title' => 'ข้อมูลนายจ้าง / Employers',                    'title_en' => 'Employers',                'title_zh' => '雇主',             'title_my' => 'အလုပ်ရှင်များ'],
        ['key' => 'employees',                'title' => 'ข้อมูลลูกจ้าง / Employees',                    'title_en' => 'Employees',                'title_zh' => '雇员',             'title_my' => 'ဝန်ထမ်းများ'],
        ['key' => 'employment_history',       'title' => 'ประวัติการจ้างงาน / Employment History',       'title_en' => 'Employment History',       'title_zh' => '雇用历史',         'title_my' => 'အလုပ်ခန့်ထားမှု မှတ်တမ်း'],
        ['key' => 'group_team',               'title' => 'กลุ่มและทีม / Group & Team',                   'title_en' => 'Group & Team',             'title_zh' => '分组与团队',       'title_my' => 'အုပ်စုနှင့် အသင်း'],
        // Sales intentionally moved to the Finance bundle — Sales generates
        // quotations/invoices which are part of the Finance flow, sold as part
        // of the same add-on package.
        ['key' => 'production',               'title' => 'งานเตรียมการ / P Production',                  'title_en' => 'P Production',             'title_zh' => 'P Production',     'title_my' => 'P Production'],
        ['key' => 'workflow',                 'title' => 'ขั้นตอนงาน / Workflow',                        'title_en' => 'Workflow',                 'title_zh' => 'Workflow',         'title_my' => 'Workflow'],
        ['key' => 'registration_resolution',  'title' => 'มติลงทะเบียน / Registration Resolution',       'title_en' => 'Registration Resolution',  'title_zh' => '登记决议',         'title_my' => 'မှတ်ပုံတင် ဆုံးဖြတ်ချက်'],
        ['key' => 'renewal_resolution',       'title' => 'มติต่ออายุ / Renewal Resolution',              'title_en' => 'Renewal Resolution',       'title_zh' => '续签决议',         'title_my' => 'သက်တမ်းတိုး ဆုံးဖြတ်ချက်'],
        ['key' => 'importers',                'title' => 'บริษัทนำเข้า / Importers',                     'title_en' => 'Importers',                'title_zh' => '劳务进口代理公司', 'title_my' => 'တင်သွင်းသူများ'],
        ['key' => 'agents',                   'title' => 'ตัวแทน / Agents',                              'title_en' => 'Agents',                   'title_zh' => '劳务中介',         'title_my' => 'အေးဂျင့်များ'],
        ['key' => 'delegates',                'title' => 'ผู้แทน / Delegates',                           'title_en' => 'Delegates',                'title_zh' => '授权代表',         'title_my' => 'ကိုယ်စားလှယ်များ'],
        // Finance + Financial Profiles intentionally excluded — distributed in
        // the separate Finance Manual Bundle (sold as an add-on module).
        ['key' => 'user_management',          'title' => 'จัดการผู้ใช้งาน / User Management',            'title_en' => 'User Management',          'title_zh' => '用户管理',         'title_my' => 'အသုံးပြုသူ စီမံခန့်ခွဲမှု'],
        ['key' => 'pdf_templates',            'title' => 'แม่แบบ PDF / PDF Templates',                   'title_en' => 'PDF Templates',            'title_zh' => 'PDF 模板',         'title_my' => 'PDF Template များ'],
        ['key' => 'central_trash',            'title' => 'ถังขยะกลาง / Central Trash',                   'title_en' => 'Central Trash',            'title_zh' => '回收站',           'title_my' => 'ဗဟိုအမှိုက်ပုံး'],
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
        @foreach($sections as $i => $s)
            @if(view()->exists('manuals.' . $s['key']))
                <li><a href="#sec-{{ $s['key'] }}">{{ $sectionTitle($s) }}</a></li>
            @endif
        @endforeach
    </ol>
</div>

{{-- All manual sections inline --}}
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
