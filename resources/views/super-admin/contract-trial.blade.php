@php
    $brand = app(\App\Services\BrandService::class)->current();
    $primaryColor = $brand['primary'] ?? '#F97316';
    $logoUrl = $brand['logo_url'] ?? null;
    $appName = $brand['app_name'] ?? 'Pro-Worker';

    $issueDate = $contract['date'] ?? date('Y-m-d');
    $trialStart = $contract['trial_start'] ?? '';
    $trialEnd = $contract['trial_end'] ?? '';
    $testUrl = $contract['test_server_url'] ?? '';

    $fmtDate = function ($d) {
        if (!$d) return '___________';
        try { return \Carbon\Carbon::parse($d)->format('d/m/Y'); } catch (\Throwable $e) { return $d; }
    };
@endphp
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>สัญญาทดลองใช้บริการ — {{ $customer['name'] ?: 'ลูกค้า' }}</title>
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

        .clause { margin: 18px 0; }
        .clause-title { font-weight: 700; font-size: 0.98rem; color: var(--pc-primary); margin-bottom: 6px; }
        .clause-body { font-size: 0.88rem; text-align: justify; padding-left: 12px; }
        .clause-body ol { padding-left: 20px; margin: 4px 0; }
        .clause-body li { margin-bottom: 4px; }

        .warning-box { background: #fff3cd; border: 2px solid #ffc107; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .warning-box .warn-title { font-weight: 800; color: #856404; margin-bottom: 8px; font-size: 0.95rem; }
        .warning-box .warn-body { font-size: 0.88rem; color: #6b5800; }

        .exclusion-box { background: #f8d7da; border: 2px solid #dc3545; border-radius: 6px; padding: 14px 18px; margin: 18px 0; }
        .exclusion-box .ex-title { font-weight: 800; color: #721c24; margin-bottom: 6px; font-size: 0.95rem; }
        .exclusion-box .ex-body { font-size: 0.88rem; color: #721c24; }

        .signatures { margin-top: 48px; }
        .sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 32px; }
        .sig-block { text-align: center; }
        .sig-line { border-bottom: 1.5px solid #333; height: 60px; margin-bottom: 8px; }
        .sig-name { font-weight: 700; font-size: 0.9rem; }
        .sig-title { font-size: 0.82rem; color: #555; }
        .sig-date { font-size: 0.82rem; color: #888; margin-top: 4px; }
        .sig-role { font-size: 0.78rem; color: var(--pc-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }

        .print-controls { position: sticky; top: 0; background: white; padding: 12px 24px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); z-index: 100; }
        .btn-print { background: var(--pc-primary); color: white; border: none; padding: 8px 22px; font-weight: 600; border-radius: 6px; cursor: pointer; }

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
            <strong>{{ __('Trial Contract Preview') }}</strong> — {{ __('กด Ctrl+P เพื่อบันทึก PDF') }}
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
            <div class="doc-id">เลขที่สัญญา: TRIAL-{{ str_pad(crc32($customer['name'] . $issueDate) % 99999, 5, '0', STR_PAD_LEFT) }}</div>
            <h1>สัญญาทดลองใช้บริการระบบ</h1>
            <div class="subtitle">Trial Service Agreement</div>
            <div class="doc-divider"></div>
            <p class="text-muted small mb-0">ทำที่ {{ $provider['address'] ?: '____________________________' }}</p>
            <p class="text-muted small">วันที่ {{ $fmtDate($issueDate) }}</p>
        </div>

        <p>
            สัญญาฉบับนี้จัดทำขึ้นระหว่าง <strong>{{ $provider['name'] ?: '____________________________' }}</strong>
            ซึ่งต่อไปนี้จะเรียกว่า <em>"ผู้ให้บริการ"</em> ฝ่ายหนึ่ง
            กับ <strong>{{ $customer['name'] ?: '____________________________' }}</strong>
            ซึ่งต่อไปนี้จะเรียกว่า <em>"ผู้รับบริการ"</em> อีกฝ่ายหนึ่ง
            โดยทั้งสองฝ่ายตกลงกันมีข้อความดังต่อไปนี้
        </p>

        {{-- Parties --}}
        <div class="parties-box">
            <h6>ผู้ให้บริการ (Provider)</h6>
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

        {{-- Clause 1 - Scope --}}
        <div class="clause">
            <div class="clause-title">ข้อ 1. ขอบเขตและวัตถุประสงค์</div>
            <div class="clause-body">
                ผู้ให้บริการตกลงเปิดให้ผู้รับบริการ <strong>ทดลองใช้งานระบบบริหารจัดการแรงงาน (Pro-Worker)</strong>
                บนสภาพแวดล้อมทดสอบ (Test Server) เพื่อวัตถุประสงค์ในการประเมินฟีเจอร์ ทดสอบความเสถียร
                และพิจารณาความเหมาะสมของระบบกับการใช้งานจริงในองค์กรของผู้รับบริการเท่านั้น
                การทดลองใช้นี้<strong>ไม่ใช่</strong>การให้บริการเชิงพาณิชย์
            </div>
        </div>

        {{-- Clause 2 - Trial Period --}}
        <div class="clause">
            <div class="clause-title">ข้อ 2. ระยะเวลาทดลองใช้</div>
            <div class="clause-body">
                สัญญานี้มีผลตั้งแต่วันที่ <strong>{{ $fmtDate($trialStart) }}</strong>
                ถึงวันที่ <strong>{{ $fmtDate($trialEnd) }}</strong>
                @if($testUrl)
                    โดยผู้รับบริการสามารถเข้าถึงระบบทดสอบได้ที่ <code>{{ $testUrl }}</code>
                @endif
                เมื่อครบกำหนดระยะเวลาทดลอง ผู้ให้บริการขอสงวนสิทธิ์ในการระงับการเข้าถึงโดยมิต้องแจ้งให้ทราบล่วงหน้า
            </div>
        </div>

        {{-- Clause 3 - Free --}}
        <div class="clause">
            <div class="clause-title">ข้อ 3. ค่าบริการ</div>
            <div class="clause-body">
                การทดลองใช้ตามสัญญานี้ <strong>ไม่มีค่าบริการ (Free of Charge)</strong>
                ผู้รับบริการไม่ต้องชำระเงินใดๆ แก่ผู้ให้บริการในช่วงระยะเวลาทดลอง
                หากภายหลังผู้รับบริการประสงค์จะใช้งานเชิงพาณิชย์ ทั้งสองฝ่ายจะจัดทำ "สัญญาเช่าใช้บริการระบบ" แยกต่างหาก
            </div>
        </div>

        {{-- Clause 4 - DISCLAIMER (Most important!) --}}
        <div class="warning-box">
            <div class="warn-title"><i class="bi bi-exclamation-triangle-fill me-1"></i> ข้อ 4. การปฏิเสธความรับผิดและไม่รับประกัน (Disclaimer & No Warranty)</div>
            <div class="warn-body">
                ผู้ให้บริการขอแจ้งให้ผู้รับบริการรับทราบและตกลงโดยชัดแจ้งว่า:
                <ol class="mb-0 mt-2">
                    <li><strong>ไม่รับประกันข้อมูลใดๆ ทั้งสิ้น</strong> — ข้อมูลที่ผู้รับบริการบันทึกในระบบทดสอบ
                        อาจสูญหาย เสียหาย หรือถูกลบโดยผู้ให้บริการได้ทุกเมื่อ โดยไม่ต้องแจ้งให้ทราบล่วงหน้า</li>
                    <li><strong>ไม่รับประกันความพร้อมใช้งานของระบบ (No SLA)</strong> — Server ทดสอบอาจมีความล่าช้า
                        (Latency) ความเร็วในการตอบสนองที่แตกต่างจาก Production Server หรือหยุดทำงานเพื่อบำรุงรักษาได้ทุกเมื่อ</li>
                    <li><strong>ไม่รับผิดในความเสียหาย</strong> ไม่ว่าทางตรงหรือทางอ้อม ที่อาจเกิดจากการใช้งานระบบทดสอบ
                        รวมถึงความสูญเสียทางธุรกิจ ข้อมูลสูญหาย หรือเหตุอื่นใด</li>
                    <li>ผู้รับบริการตกลง <strong>ไม่ใช้ข้อมูลจริง (Real Data)</strong> ของบุคคลธรรมดาหรือลูกค้าจริงในระบบทดสอบ
                        ควรใช้ข้อมูลตัวอย่างหรือข้อมูลทดสอบเท่านั้น</li>
                </ol>
            </div>
        </div>

        {{-- Clause 5 - FINANCE EXCLUSION (User requested!) --}}
        <div class="exclusion-box">
            <div class="ex-title"><i class="bi bi-shield-exclamation me-1"></i> ข้อ 5. ขอบเขตที่ไม่รวมในการให้บริการ — ฟีเจอร์การเงิน (Finance Feature Exclusion)</div>
            <div class="ex-body">
                ผู้รับบริการรับทราบและตกลงโดยชัดแจ้งว่า <strong>ฟีเจอร์การเงิน (Finance Module)</strong>
                ซึ่งรวมถึงแต่ไม่จำกัดเพียง: ระบบใบเสนอราคา, ใบแจ้งหนี้, ใบกำกับภาษี, ใบเสร็จ, การคำนวณภาษีมูลค่าเพิ่ม (VAT),
                ภาษีหัก ณ ที่จ่าย (WHT), ภ.พ.30, ภ.ง.ด.3/53, รายงานภาษี, การกระทบยอดบัญชีธนาคาร, สมุดบัญชี (Ledger),
                และรายงานการเงินทั้งหมด <strong>ไม่รวมอยู่ในขอบเขตของสัญญาทดลองใช้ฉบับนี้</strong>
                และจะ <strong>ไม่เปิดให้ใช้งาน</strong> ในระยะทดลอง เนื่องจากฟีเจอร์ดังกล่าวเป็นโมดูลที่มีความละเอียดอ่อน
                ต้องทำงานควบคู่กับเรื่องภาษี ระบบบัญชี และข้อบังคับทางกฎหมาย จึงจัดเป็นบริการแยกต่างหาก
                หากผู้รับบริการประสงค์จะใช้งานฟีเจอร์การเงิน จะต้องทำสัญญาเพิ่มเติม (Add-on Agreement) ในภายหลัง
            </div>
        </div>

        {{-- Clause 6 - Confidentiality --}}
        <div class="clause">
            <div class="clause-title">ข้อ 6. การรักษาความลับ</div>
            <div class="clause-body">
                ทั้งสองฝ่ายตกลงที่จะรักษาความลับเกี่ยวกับข้อมูลทางธุรกิจ ระบบ ฟีเจอร์ และเทคโนโลยีที่ได้รับทราบจากการทดลองใช้
                ผู้รับบริการจะไม่เปิดเผย ทำสำเนา หรือใช้ประโยชน์เชิงพาณิชย์จากข้อมูลของผู้ให้บริการ
                และจะไม่กระทำการใดอันเป็นการละเมิดทรัพย์สินทางปัญญาของผู้ให้บริการ
            </div>
        </div>

        {{-- Clause 7 - Termination --}}
        <div class="clause">
            <div class="clause-title">ข้อ 7. การสิ้นสุดสัญญา</div>
            <div class="clause-body">
                สัญญานี้สิ้นสุดลงโดยอัตโนมัติเมื่อครบกำหนดระยะเวลาตามข้อ 2.
                หรือเมื่อฝ่ายใดฝ่ายหนึ่งบอกเลิกสัญญาโดยแจ้งเป็นลายลักษณ์อักษรล่วงหน้า 3 วันทำการ
                เมื่อสัญญาสิ้นสุด ผู้ให้บริการมีสิทธิลบข้อมูลทั้งหมดในระบบทดสอบโดยไม่ต้องเก็บสำรอง
            </div>
        </div>

        <p class="text-center mt-4">
            สัญญานี้ทำขึ้นเป็นสองฉบับ มีข้อความถูกต้องตรงกัน คู่สัญญาทั้งสองฝ่ายได้อ่านและเข้าใจข้อความโดยตลอดแล้ว
            จึงได้ลงลายมือชื่อไว้ต่อหน้าพยานเป็นสำคัญ
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
