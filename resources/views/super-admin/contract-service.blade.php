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
@endphp
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>สัญญาเช่าใช้บริการระบบ — {{ $customer['name'] ?: 'ลูกค้า' }}</title>
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
        .clause-body ol { padding-left: 20px; margin: 4px 0; }
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
            <div class="doc-id">เลขที่สัญญา: SVC-{{ str_pad(crc32($customer['name'] . $issueDate) % 99999, 5, '0', STR_PAD_LEFT) }}</div>
            <h1>สัญญาเช่าใช้บริการระบบบริหารจัดการแรงงาน</h1>
            <div class="subtitle">Software-as-a-Service (SaaS) Subscription Agreement</div>
            <div class="doc-divider"></div>
            <p class="text-muted small mb-0">ทำที่ {{ $provider['address'] ?: '____________________________' }}</p>
            <p class="text-muted small">วันที่ {{ $fmtDate($issueDate) }}</p>
        </div>

        <p>
            สัญญาฉบับนี้จัดทำขึ้นระหว่าง <strong>{{ $provider['name'] ?: '____________________________' }}</strong>
            ซึ่งต่อไปนี้จะเรียกว่า <em>"ผู้ให้บริการ"</em> ฝ่ายหนึ่ง
            กับ <strong>{{ $customer['name'] ?: '____________________________' }}</strong>
            ซึ่งต่อไปนี้จะเรียกว่า <em>"ผู้รับบริการ"</em> อีกฝ่ายหนึ่ง
            โดยทั้งสองฝ่ายตกลงเช่าใช้บริการระบบบนหลักการและเงื่อนไขดังต่อไปนี้
        </p>

        {{-- Parties --}}
        <div class="parties-box">
            <h6>ผู้ให้บริการ (Service Provider)</h6>
            <div class="party-row">
                <div class="lbl">ชื่อบริษัท:</div>      <div class="val">{{ $provider['name'] ?: '—' }}</div>
                <div class="lbl">ที่อยู่:</div>          <div class="val">{{ $provider['address'] ?: '—' }}</div>
                <div class="lbl">เลขผู้เสียภาษี:</div>   <div class="val">{{ $provider['tax_id'] ?: '—' }}</div>
                <div class="lbl">โทรศัพท์ / อีเมล:</div> <div class="val">{{ $provider['phone'] ?: '—' }} / {{ $provider['email'] ?: '—' }}</div>
            </div>
        </div>

        <div class="parties-box">
            <h6>ผู้รับบริการ (Customer)</h6>
            <div class="party-row">
                <div class="lbl">ชื่อบริษัท:</div>      <div class="val">{{ $customer['name'] ?: '—' }}</div>
                <div class="lbl">ที่อยู่:</div>          <div class="val">{{ $customer['address'] ?: '—' }}</div>
                <div class="lbl">เลขผู้เสียภาษี:</div>   <div class="val">{{ $customer['tax_id'] ?: '—' }}</div>
                <div class="lbl">โทรศัพท์ / อีเมล:</div> <div class="val">{{ $customer['phone'] ?: '—' }} / {{ $customer['email'] ?: '—' }}</div>
            </div>
        </div>

        {{-- Package box --}}
        @if($package)
            <div class="package-box">
                <h6><i class="bi bi-box-seam me-1"></i> รายละเอียดแพ็คเกจ (Package Details)</h6>
                <div class="pkg-row">
                    <div class="lbl">แพ็คเกจ:</div>            <div class="val">{{ $package['sublabel'] }} — {{ $package['label'] }}</div>
                    <div class="lbl">ค่าแรกเข้า (ครั้งเดียว):</div> <div class="val">{{ $fmtMoney($package['setup_fee']) }} บาท</div>
                    <div class="lbl">ค่ารายปี:</div>            <div class="val">{{ $fmtMoney($package['annual_fee']) }} บาท/ปี</div>
                    <div class="lbl">เริ่มสัญญา:</div>          <div class="val">{{ $fmtDate($serviceStart) }}</div>
                    <div class="lbl">สิ้นสุดสัญญา:</div>         <div class="val">{{ $fmtDate($serviceEnd) }} ({{ $serviceYears }} ปี)</div>
                </div>
            </div>
        @endif

        {{-- Clause 1 - Definitions --}}
        <div class="clause">
            <div class="clause-title">ข้อ 1. คำนิยาม (Definitions)</div>
            <div class="clause-body">
                <ol>
                    <li><strong>"ระบบ"</strong> หมายถึง ระบบบริหารจัดการแรงงานต่างด้าว Pro-Worker ที่ผู้ให้บริการพัฒนาขึ้น
                        รวมถึงโมดูลย่อยทั้งหมด ได้แก่ จัดการนายจ้าง, ลูกจ้าง, มติลงทะเบียน, มติต่ออายุ, MOU, Workflow,
                        Documents PDF, ระบบแจ้งเตือน, Activity Log, ฯลฯ <strong>ยกเว้นโมดูลที่ระบุไว้ในข้อ 8</strong></li>
                    <li><strong>"การให้บริการ"</strong> หมายถึง การให้สิทธิเข้าถึงและใช้งานระบบในรูปแบบ Software-as-a-Service (SaaS)</li>
                    <li><strong>"ข้อมูล"</strong> หมายถึง ข้อมูลทั้งหมดที่ผู้รับบริการนำเข้าหรือสร้างขึ้นในระบบ</li>
                </ol>
            </div>
        </div>

        {{-- Clause 2 - Scope --}}
        <div class="clause">
            <div class="clause-title">ข้อ 2. ขอบเขตการให้บริการ</div>
            <div class="clause-body">
                ผู้ให้บริการตกลงให้สิทธิแก่ผู้รับบริการในการเข้าถึงและใช้งานระบบบนสภาพแวดล้อม Production
                ตามแพ็คเกจที่ระบุข้างต้น โดยรวมถึงการสนับสนุนการใช้งานพื้นฐาน (Basic Support)
                ผ่านช่องทางอีเมลหรือช่องทางที่ผู้ให้บริการกำหนด
            </div>
        </div>

        {{-- Clause 3 - Term --}}
        <div class="clause">
            <div class="clause-title">ข้อ 3. ระยะเวลาและการต่ออายุ</div>
            <div class="clause-body">
                สัญญานี้มีผลตั้งแต่วันที่ <strong>{{ $fmtDate($serviceStart) }}</strong>
                ถึงวันที่ <strong>{{ $fmtDate($serviceEnd) }}</strong>
                รวมระยะเวลา <strong>{{ $serviceYears }} ปี</strong>
                และจะต่ออายุอัตโนมัติเป็นรายปี เว้นแต่ฝ่ายใดฝ่ายหนึ่งจะแจ้งความประสงค์ไม่ต่ออายุ
                เป็นลายลักษณ์อักษรล่วงหน้าไม่น้อยกว่า 30 วันก่อนวันสิ้นสุดสัญญา
            </div>
        </div>

        {{-- Clause 4 - Fees --}}
        <div class="clause">
            <div class="clause-title">ข้อ 4. ค่าบริการและการชำระเงิน</div>
            <div class="clause-body">
                ผู้รับบริการตกลงชำระค่าบริการตามแพ็คเกจที่ระบุข้างต้น โดยแยกเป็น
                <strong>ค่าแรกเข้า</strong> (ชำระครั้งเดียวเมื่อเริ่มสัญญา)
                และ <strong>ค่ารายปี</strong> (ชำระล่วงหน้าก่อนเริ่มแต่ละรอบปี)
                ราคาดังกล่าวยังไม่รวมภาษีมูลค่าเพิ่ม (VAT 7%)
                การชำระล่าช้าเกิน 15 วันนับจากวันครบกำหนด ผู้ให้บริการสงวนสิทธิ์ระงับการเข้าถึงระบบจนกว่าจะชำระเสร็จสิ้น
            </div>
        </div>

        {{-- Clause 5 - SLA --}}
        <div class="clause">
            <div class="clause-title">ข้อ 5. ระดับการให้บริการ (Service Level Agreement)</div>
            <div class="clause-body">
                ผู้ให้บริการมุ่งมั่นให้บริการระบบมีความพร้อมใช้งาน (Uptime) ไม่น้อยกว่า <strong>99.5% ต่อเดือน</strong>
                ยกเว้นช่วงบำรุงรักษาที่กำหนดล่วงหน้า (Planned Maintenance) ซึ่งจะแจ้งให้ผู้รับบริการทราบไม่น้อยกว่า 48 ชั่วโมง
                และเหตุสุดวิสัย (Force Majeure) ที่อยู่นอกเหนือการควบคุม
            </div>
        </div>

        {{-- Clause 6 - SECURITY (International Standard) --}}
        <div class="security-box">
            <div class="sec-title"><i class="bi bi-shield-lock-fill me-1"></i> ข้อ 6. มาตรฐานความปลอดภัยและการป้องกันข้อมูล</div>
            <div class="sec-body">
                ผู้ให้บริการจัดมาตรการความปลอดภัยตามมาตรฐานสากล ดังนี้:
                <ul class="mb-0 mt-1">
                    <li><strong>การเข้ารหัสในการสื่อสาร (TLS 1.2+)</strong> สำหรับการรับ-ส่งข้อมูลทุกครั้ง</li>
                    <li><strong>การเข้ารหัสข้อมูลที่จัดเก็บ (Encryption at Rest)</strong> สำหรับฐานข้อมูลและไฟล์แนบ</li>
                    <li><strong>การควบคุมการเข้าถึงตามบทบาท (Role-Based Access Control / RBAC)</strong> ผ่าน Spatie Permission</li>
                    <li><strong>การสำรองข้อมูลรายวัน (Daily Backup)</strong> เก็บย้อนหลังอย่างน้อย 30 วัน</li>
                    <li><strong>บันทึกร่องรอย (Audit Log)</strong> ทุกการเปลี่ยนแปลงข้อมูลถูกบันทึกพร้อม timestamp + user</li>
                    <li><strong>นโยบายรหัสผ่าน</strong> (bcrypt hashing, ขั้นต่ำ 8 ตัวอักษร, lockout ป้องกัน brute-force)</li>
                    <li><strong>การป้องกัน CSRF, XSS, SQL Injection</strong> ตามแนวทาง OWASP Top 10</li>
                    <li>มุ่งสู่ความสอดคล้องกับมาตรฐาน <strong>ISO/IEC 27001</strong> (Information Security Management)</li>
                </ul>
            </div>
        </div>

        {{-- Clause 7 - PDPA --}}
        <div class="clause">
            <div class="clause-title">ข้อ 7. การคุ้มครองข้อมูลส่วนบุคคล (PDPA Compliance)</div>
            <div class="clause-body">
                การประมวลผลข้อมูลส่วนบุคคลภายในระบบเป็นไปตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)
                และสอดคล้องกับแนวทาง General Data Protection Regulation (GDPR) ของสหภาพยุโรป
                โดยผู้ให้บริการทำหน้าที่เป็น <strong>ผู้ประมวลผลข้อมูล (Data Processor)</strong>
                และผู้รับบริการเป็น <strong>ผู้ควบคุมข้อมูล (Data Controller)</strong>
                ผู้ให้บริการจะไม่นำข้อมูลของผู้รับบริการไปใช้นอกเหนือจากวัตถุประสงค์ของสัญญา
            </div>
        </div>

        {{-- Clause 8 - FINANCE EXCLUSION (User requested!) --}}
        <div class="exclusion-box">
            <div class="ex-title"><i class="bi bi-shield-exclamation me-1"></i> ข้อ 8. ขอบเขตที่ไม่รวมในการให้บริการ — ฟีเจอร์การเงิน (Finance Feature Exclusion)</div>
            <div class="ex-body">
                คู่สัญญาทั้งสองฝ่ายตกลงโดยชัดแจ้งว่า <strong>โมดูลฟีเจอร์การเงิน (Finance Module)</strong>
                ซึ่งรวมถึงแต่ไม่จำกัดเพียง:
                <ul class="mb-2 mt-1">
                    <li>ระบบใบเสนอราคา / ใบแจ้งหนี้ / ใบกำกับภาษี / ใบเสร็จรับเงิน (Quotation / Invoice / Tax Invoice / Receipt)</li>
                    <li>การคำนวณภาษีมูลค่าเพิ่ม (VAT) และภาษีหัก ณ ที่จ่าย (Withholding Tax / WHT)</li>
                    <li>รายงานภาษี ภ.พ.30, ภ.ง.ด.3, ภ.ง.ด.53</li>
                    <li>ใบรับรองหัก ณ ที่จ่าย (WHT Certificate)</li>
                    <li>การกระทบยอดบัญชีธนาคาร (Bank Reconciliation)</li>
                    <li>สมุดบัญชี (Ledger) และรายงานการเงินทั้งหมด</li>
                    <li>Monthly Bundle และ Audit Log ของฟีเจอร์การเงิน</li>
                </ul>
                <strong>ไม่รวมอยู่ในขอบเขตของสัญญาฉบับนี้</strong> และ <strong>จะไม่เปิดให้ใช้งาน</strong> ตลอดระยะเวลาสัญญา
                เนื่องจากฟีเจอร์ดังกล่าวเป็นโมดูลที่มีความละเอียดอ่อน ต้องทำงานควบคู่กับเรื่องภาษี ระบบบัญชี
                ข้อบังคับทางกฎหมายและภาษีในแต่ละประเทศ จึงจัดเป็นบริการแยกต่างหาก (Separate Package)
                หากผู้รับบริการประสงค์จะใช้งานฟีเจอร์การเงินในภายหลัง คู่สัญญาจะต้องจัดทำ
                <strong>สัญญาเพิ่มเติม (Add-on Service Agreement)</strong> และตกลงค่าบริการแยกต่างหาก
                ผู้ให้บริการสงวนสิทธิ์ในการปิดการเข้าถึงโมดูลการเงินผ่านระบบตั้งค่าของระบบ
            </div>
        </div>

        {{-- Clause 9 - IP --}}
        <div class="clause">
            <div class="clause-title">ข้อ 9. ทรัพย์สินทางปัญญา (Intellectual Property)</div>
            <div class="clause-body">
                สิทธิในทรัพย์สินทางปัญญาทั้งหมดของระบบ ซอร์สโค้ด การออกแบบ UI/UX โลโก้ และฟีเจอร์
                เป็นกรรมสิทธิ์ของผู้ให้บริการแต่เพียงผู้เดียว
                ข้อมูลที่ผู้รับบริการนำเข้าระบบเป็นกรรมสิทธิ์ของผู้รับบริการ ผู้ให้บริการไม่มีสิทธิ์นำไปใช้นอกเหนือจากการให้บริการ
            </div>
        </div>

        {{-- Clause 10 - Confidentiality --}}
        <div class="clause">
            <div class="clause-title">ข้อ 10. การรักษาความลับ (Confidentiality)</div>
            <div class="clause-body">
                ทั้งสองฝ่ายตกลงรักษาความลับเกี่ยวกับข้อมูลทางธุรกิจ ระบบ และเทคโนโลยีของอีกฝ่ายหนึ่ง
                แม้สัญญาจะสิ้นสุดลงแล้ว ข้อผูกพันในการรักษาความลับยังคงมีผลต่อไปอีก 3 ปี
            </div>
        </div>

        {{-- Clause 11 - Liability --}}
        <div class="clause">
            <div class="clause-title">ข้อ 11. การจำกัดความรับผิด (Limitation of Liability)</div>
            <div class="clause-body">
                ความรับผิดของผู้ให้บริการต่อผู้รับบริการ ไม่ว่าจะเกิดจากการละเมิดสัญญา การกระทำละเมิด
                หรือมูลเหตุอื่นใด รวมแล้วในแต่ละรอบปีสัญญา จะไม่เกิน <strong>มูลค่าค่าบริการที่ผู้รับบริการชำระจริงในรอบปีนั้น</strong>
                และผู้ให้บริการไม่รับผิดต่อความเสียหายทางอ้อม (Indirect / Consequential Damages)
            </div>
        </div>

        {{-- Clause 12 - Termination + Law --}}
        <div class="clause">
            <div class="clause-title">ข้อ 12. การสิ้นสุดสัญญาและกฎหมายที่ใช้บังคับ</div>
            <div class="clause-body">
                สัญญานี้สิ้นสุดลงเมื่อ
                (ก) ครบกำหนดระยะเวลาและไม่ได้ต่ออายุ
                (ข) ฝ่ายใดฝ่ายหนึ่งผิดสัญญาอย่างมีนัยสำคัญและไม่แก้ไขภายใน 30 วันหลังได้รับหนังสือบอกกล่าว
                (ค) ฝ่ายใดฝ่ายหนึ่งถูกฟ้องล้มละลายหรือเลิกกิจการ
                เมื่อสัญญาสิ้นสุด ผู้ให้บริการจะให้ผู้รับบริการ Export ข้อมูลภายใน 30 วันก่อนลบข้อมูลออกจากระบบอย่างถาวร
                สัญญานี้อยู่ภายใต้บังคับ <strong>กฎหมายแห่งราชอาณาจักรไทย</strong>
                ข้อพิพาทใดๆ ที่ไม่อาจระงับได้โดยการเจรจา ให้นำเสนอต่อ <strong>ศาลในกรุงเทพมหานคร</strong>
            </div>
        </div>

        <p class="text-center mt-4">
            สัญญานี้ทำขึ้นเป็นสองฉบับ มีข้อความถูกต้องตรงกัน คู่สัญญาทั้งสองฝ่ายได้อ่านและเข้าใจข้อความโดยตลอดแล้ว
            จึงได้ลงลายมือชื่อพร้อมประทับตรา (ถ้ามี) ไว้ต่อหน้าพยานเป็นสำคัญ
        </p>

        {{-- Signatures --}}
        <div class="signatures">
            <div class="sig-grid">
                <div class="sig-block">
                    <div class="sig-role">ผู้ให้บริการ</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">({{ $provider['signer_name'] ?: '____________________________' }})</div>
                    <div class="sig-title">{{ $provider['signer_title'] ?: 'ตำแหน่ง' }}</div>
                    <div class="sig-date">วันที่ ____ / ____ / ________</div>
                </div>
                <div class="sig-block">
                    <div class="sig-role">ผู้รับบริการ</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">({{ $customer['signer_name'] ?: '____________________________' }})</div>
                    <div class="sig-title">{{ $customer['signer_title'] ?: 'ตำแหน่ง' }}</div>
                    <div class="sig-date">วันที่ ____ / ____ / ________</div>
                </div>
            </div>

            <div class="sig-grid">
                <div class="sig-block">
                    <div class="sig-role">พยาน 1</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">({{ $witnesses[0]['name'] ?? '' ?: '____________________________' }})</div>
                    <div class="sig-title">{{ $witnesses[0]['title'] ?? '' ?: 'ตำแหน่ง' }}</div>
                </div>
                <div class="sig-block">
                    <div class="sig-role">พยาน 2</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">({{ $witnesses[1]['name'] ?? '' ?: '____________________________' }})</div>
                    <div class="sig-title">{{ $witnesses[1]['title'] ?? '' ?: 'ตำแหน่ง' }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
