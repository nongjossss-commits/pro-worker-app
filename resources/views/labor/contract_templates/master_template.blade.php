{{--
    Blank, bilingual (Thai/English) master source document for the standard
    Pro Worker <-> Employer contract — a normal HTML page (not a server-
    generated PDF) that the admin views here and prints/"Save as PDF" from
    their own browser (see the "Print / Save as PDF" button below), then
    re-uploads that PDF as a real Contract Template via the builder.

    HTML/CSS was chosen after a first FPDF-drawn attempt produced garbled,
    overlapping text — getting Thai+English body copy to wrap and paginate
    correctly is what a browser's own layout engine already does reliably;
    hand-tracking Y-coordinates in FPDF was the wrong tool for a
    multi-paragraph bilingual document like this one.

    Thai wording is transcribed verbatim from the company's existing paper
    contract (provided by the user) — never paraphrased, only translated,
    with ONE deliberate exception: clause 4.1 was reworded at the user's
    explicit request because the original phrasing repeated "บอกเลิกสัญญา"
    three times in a way that read as confusing/circular. Same legal
    meaning (Employer may terminate; termination takes effect immediately;
    Employer pays the Contractor's actual costs incurred), clearer wording.

    The system already auto-draws the real contract number, company logo,
    and QR code on top of EVERY page of ANY uploaded template at real
    issuance time (see ProWorkerContractPdfService) — so this page reserves
    the same top-right/bottom-right corners via @page margins (applied
    uniformly to every printed page) rather than drawing anything there
    itself.

    No company logo image exists anywhere in this system yet (checked
    App\Models\CompanyProfile — no records), so the header below shows a
    plain placeholder box instead of guessing at the real graphic mark —
    still true as of this revision; the user's reference photo of the real
    logo can't be cropped/embedded without an actual image file to read.

    Gradient palette (navy→blue brand name, orange→tan contact band, teal
    accent line) is picked to match the user's reference photo of their
    real letterhead, not invented from scratch.
--}}
@extends('labor.layout')

@section('title', __('Master Contract'))

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
    :root {
        --ink: #1a1a1a;
        --muted: #6b6b6b;
        --accent: #0f2d5f;
        --accent-2: #3a7bd5;
        --accent-soft: #eef2f8;
        --warm-1: #f2994a;
        --warm-2: #f7dcb4;
        --teal: #15968d;
        --rule: #d9dde3;
    }

    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .mt-page-shell {
        background: #e9ebee;
        padding: 24px 0 60px;
    }

    .mt-toolbar {
        max-width: 210mm;
        margin: 0 auto 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    {{--
        Margins are symmetric left/right on purpose — an earlier version
        used an asymmetric 32mm right margin to "protect" the corner where
        the system draws the real contract number/QR later, but a full-
        width top margin (25mm) and bottom margin (28mm) already clear
        both corners on their own (they reserve the whole strip, not just
        the right end of it), so there was never a reason to also starve
        the right side of usable width — that was just making the page
        look left-heavy for no benefit.
    --}}
    .mt-doc {
        background: #fff;
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        padding: 25mm 20mm 28mm 20mm;
        box-shadow: 0 2px 10px rgba(0,0,0,.15);
        font-family: 'Sarabun', 'Noto Sans Thai', sans-serif;
        color: var(--ink);
        font-size: 14px;
        line-height: 1.6;
    }

    .nowrap { white-space: nowrap; }

    .mt-letterhead {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        position: relative;
        padding-bottom: 12px;
        margin-bottom: 0;
    }
    .mt-letterhead::after {
        content: "";
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent) 0%, var(--accent-2) 45%, var(--teal) 100%);
        border-radius: 2px;
    }

    .mt-logo-box {
        flex: 0 0 72px;
        width: 72px;
        height: 72px;
        border: 1.5px dashed #b7bec9;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9aa3b0;
        font-size: 10px;
        text-align: center;
        line-height: 1.3;
    }

    .mt-company {
        flex: 1 1 auto;
        min-width: 0;
    }
    .mt-company .name-th { font-size: 15.5px; font-weight: 600; color: var(--ink); }
    .mt-company .name-th .brand {
        display: block;
        font-size: 21px;
        font-weight: 700;
        background: linear-gradient(90deg, var(--accent) 0%, var(--accent-2) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: var(--accent-2);
        -webkit-text-fill-color: transparent;
        line-height: 1.3;
    }
    .mt-company .name-en { font-size: 11px; font-weight: 700; color: var(--accent); letter-spacing: .3px; margin-top: 3px; }
    .mt-company .tagline-th { font-size: 11.5px; color: var(--muted); margin-top: 5px; }
    .mt-company .tagline-en { font-size: 9.5px; font-style: italic; color: var(--muted); }

    .mt-contact {
        flex: 0 0 auto;
        text-align: right;
        font-size: 9.6px;
        color: var(--accent);
        max-width: 46mm;
        background: linear-gradient(135deg, var(--warm-1) 0%, var(--warm-2) 100%);
        border-radius: 10px;
        padding: 8px 12px;
        line-height: 1.5;
    }
    .mt-contact .th { font-weight: 700; }
    .mt-contact .en { font-style: italic; font-size: 8.8px; color: #3a2a12; }

    .mt-license {
        display: flex;
        gap: 10px;
        margin: 12px 0 16px;
    }
    .mt-license .badge-item {
        flex: 1 1 auto;
        background: var(--accent-soft);
        border-left: 3px solid var(--teal);
        border-radius: 6px;
        padding: 6px 10px;
    }
    .mt-license .label {
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--teal);
        font-weight: 700;
    }
    .mt-license .th { font-size: 10.5px; color: var(--ink); font-weight: 600; }
    .mt-license .en { font-size: 9px; color: var(--muted); font-style: italic; }

    .mt-title { text-align: center; margin: 6px 0 16px; }
    .mt-title .th { font-size: 18px; font-weight: 700; }
    .mt-title .en { font-size: 12px; font-weight: 700; color: var(--accent); margin-top: 2px; }

    .bi { margin-bottom: 7px; }
    .bi .th { font-size: 13.5px; }
    .bi .en { font-size: 10.5px; font-style: italic; color: var(--muted); }
    .bi.indent { padding-left: 14px; }

    {{--
        For a complete sentence (no blank to fill in) that just happens to
        end short of the margin — text-align-last:justify spreads its own
        word-spacing to reach full width, no .fill span needed since
        there's no blank here at all, just normal running text.
    --}}
    {{--
        text-align-last:justify (an earlier attempt at this) stretched
        word-gaps to fill the line, but a short sentence with few words
        means each gap has to stretch a lot — looked broken, not filled.
        Bold weight instead: reads as more substantial/deliberate without
        distorting normal word spacing.
    --}}
    .bi.bold-full .th { font-weight: 700; font-size: 15px; }
    .bi.bold-full .en { font-weight: 700; font-size: 11.5px; }

    {{--
        Fill-in blanks that stretch to the margin on their own, instead of
        a hand-counted run of dots that may fall short (or overflow) of
        the actual line width — the earlier "widen this dotted line"
        fix for ประเภทกิจการ was a one-off patch of the same underlying
        problem this solves generally: a .fill span is a flex child that
        grows to whatever space is left in its row, so the trailing dots
        on a blank always reach the right margin regardless of how long
        the label text next to it happens to be.
    --}}
    .fillable { display: flex; flex-wrap: wrap; align-items: baseline; row-gap: 2px; }
    {{--
        Two CSS-drawn approaches both failed: border-bottom:dotted renders
        as squares in most browsers, and a radial-gradient background
        renders blurry (anti-aliasing on a 1px circle at print resolution).
        Real "." text characters are what the original document actually
        used and always render crisp (it's text, not graphics) — repeat
        the character far more times than any blank could ever need, then
        clip with overflow:hidden so it exactly fills whatever width
        flex-grow ends up assigning this span, however long that is.
    --}}
    {{--
        width:0 is the important part here, not just min-width — a flex
        item's DEFAULT min-width is "auto", which means the browser still
        sizes it to fit its own content (the 424-dot string, unbreakable
        via white-space:nowrap) before flex-grow gets a say, blowing the
        row width far past the page and pushing everything after it
        (including the signature block at the very end of the document)
        off the page. width:0 forces sizing to come from flex-grow alone;
        overflow:hidden then clips the dot string to whatever that turns
        out to be, consistently on every line.
    --}}
    .fillable .fill {
        flex: 1 1 30px;
        width: 0;
        min-width: 30px;
        height: 1em;
        margin: 0 4px;
        overflow: hidden;
        white-space: nowrap;
        line-height: 1;
    }
    .fillable .fill::after {
        content: "........................................................................................................................................................................................................................................................................................................................................................................................................................................";
        color: #9aa1ab;
        letter-spacing: 1px;
    }
    .mt-dateline .fillable, .mt-sig-col .fillable { justify-content: flex-end; }

    .mt-dateline {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 4px;
    }
    .mt-dateline .bi { text-align: right; width: 78mm; }

    .clause { margin-top: 16px; margin-bottom: 8px; break-inside: avoid; }
    .clause .th { font-size: 15px; font-weight: 700; color: var(--accent); }
    .clause .en { font-size: 11px; font-weight: 700; font-style: italic; color: var(--accent); }

    {{-- More room before/between rows than a first pass — these lines
         need actual pen-signature space, not just printed-text spacing. --}}
    .mt-signatures { margin-top: 56px; }
    .mt-sig-row { display: flex; gap: 24px; margin-bottom: 52px; }
    .mt-sig-col { flex: 1 1 50%; }
    .mt-sig-col .th { font-size: 13px; }
    .mt-sig-col .paren { font-size: 13px; margin-top: 10px; }

    @media print {
        .no-print,
        .labor-topbar-strip { display: none !important; }
        #main-content { padding: 0 !important; }
        .mt-page-shell { background: #fff; padding: 0; }
        .mt-doc { box-shadow: none; margin: 0; width: auto; min-height: 0; padding: 0; }
        @page { size: A4; margin: 25mm 20mm 28mm 20mm; }
    }
</style>
@endpush

@section('content')
<div class="mt-page-shell">
    <div class="mt-toolbar no-print">
        <h5 class="mb-0 fw-bold">{{ __('View Master Contract') }}</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>{{ __('Print / Save as PDF') }}
            </button>
        </div>
    </div>
    <div class="alert alert-warning no-print" style="max-width:210mm;margin:0 auto 16px;">
        {{ __('No company logo has been uploaded to the system yet — the box in the header below is a placeholder. Print this page (or "Save as PDF") to get the actual file, then upload it via Contract Templates.') }}
    </div>

    <div class="mt-doc">
        <div class="mt-letterhead">
            <div class="mt-logo-box">LOGO</div>
            <div class="mt-company">
                <div class="name-th">บริษัท นำคนต่างด้าวมาทำงานในประเทศ<span class="brand">โปร เวิร์คเกอร์ เลเบอร์ จำกัด</span></div>
                <div class="name-en">FOREIGN WORKER EMPLOYMENT AGENCY PRO WORKER LABOUR COMPANY LIMITED</div>
                <div class="tagline-th">รับจัดหา-จัดส่งแรงงาน MOU กัมพูชา เมียนมา ลาว เวียดนาม</div>
                <div class="tagline-en">MOU Labor Recruitment &amp; Placement Services — Cambodia, Myanmar, Laos, Vietnam</div>
            </div>
            <div class="mt-contact">
                <div class="th">301 ซอยฉิมพลี 28 แขวงฉิมพลี<br>เขตตลิ่งชัน กรุงเทพมหานคร 10170</div>
                <div class="en">301 Soi Chimphli 28, Chimphli,<br>Taling Chan, Bangkok 10170</div>
                <div class="th mt-1">โทร. 063-841-4651, 080-678-6406, 086-669-6692</div>
                <div class="en">Tel. 063-841-4651, 080-678-6406, 086-669-6692</div>
            </div>
        </div>
        <div class="mt-license">
            <div class="badge-item">
                <div class="label">{{ __('Recruitment License') }}</div>
                <div class="th">นจ.0385/2567</div>
                <div class="en">License No. NJ.0385/2567</div>
            </div>
            <div class="badge-item">
                <div class="label">{{ __('Juristic Person Registration') }}</div>
                <div class="th">0105567142306</div>
                <div class="en">Registration No. 0105567142306</div>
            </div>
        </div>

        <div class="mt-title">
            <div class="th">สัญญาการนำคนต่างด้าวเข้ามาทำงานกับนายจ้างในประเทศ</div>
            <div class="en">Agreement on Bringing Foreign Workers to Work with a Domestic Employer</div>
        </div>

        @php
            $bi = function ($th, $en, $indent = false) {
                echo '<div class="bi' . ($indent ? ' indent' : '') . '"><div class="th">' . $th . '</div><div class="en">' . $en . '</div></div>';
            };
            // Fillable variant — for lines whose blank-fill dots should
            // always stretch to the page margin (see .fillable/.fill CSS)
            // instead of a hand-counted run of literal dots. $fill marks
            // where each blank goes — pass it into BOTH $th and $en so
            // the English line stretches the same way as the Thai one
            // right above it (feedback: they should visually match).
            $biF = function ($th, $en, $indent = false) {
                echo '<div class="bi' . ($indent ? ' indent' : '') . '"><div class="th fillable">' . $th . '</div><div class="en fillable">' . $en . '</div></div>';
            };
            $fill = '<span class="fill"></span>';
            $clause = function ($th, $en) {
                echo '<div class="clause"><div class="th">' . $th . '</div><div class="en">' . $en . '</div></div>';
            };
        @endphp

        {{-- Intro — เขียนที่/วันที่ moved to the right per feedback (common
             Thai contract dateline convention) instead of stacked full-width
             at the left margin. --}}
        <div class="mt-dateline">
            <div>
                @php
                    $biF('เขียนที่' . $fill, 'Made at' . $fill);
                    $biF('วันที่' . $fill . 'เดือน' . $fill . 'พ.ศ.' . $fill, 'Date' . $fill . 'Month' . $fill . 'Year B.E.' . $fill);
                @endphp
            </div>
        </div>

        <div style="height:6px"></div>

        @php
            // "โดย" was wrapping alone at the end of the line, orphaned
            // from the director's name on the line below it — forcing
            // the break ourselves keeps "โดย นางมาศวิมล มณีศิลป์" together
            // as one unit instead of leaving it to unpredictable wrap.
            // English mirrors the same split for the same reason.
            echo '<div class="bi bold-full">'
                . '<div class="th">สัญญาฉบับนี้ทำขึ้นระหว่าง บริษัทนำคนต่างด้าวมาทำงานในประเทศ <span class="nowrap">โปร เวิร์คเกอร์ เลเบอร์ จำกัด</span><br>โดย <span class="nowrap">นางมาศวิมล มณีศิลป์</span></div>'
                . '<div class="en">This Agreement is made between Pro Worker Labour Company Limited (Foreign Worker Employment Agency),<br>represented by Mrs. Massawimon Maneesin,</div>'
                . '</div>';
            echo '<div class="bi bold-full"><div class="th">สำนักงานตั้งอยู่เลขที่ 301 ซอยฉิมพลี 28 แขวงฉิมพลี เขตตลิ่งชัน กรุงเทพมหานคร</div><div class="en">with its office located at 301 Soi Chimphli 28, Chimphli, Taling Chan, Bangkok,</div></div>';
            $biF('ใบอนุญาตเลขที่ นจ.0385/2567 ออกให้วันที่' . $fill . 'ใช้ได้ถึงวันที่' . $fill,
                'License No. NJ.0385/2567 issued on' . $fill . 'valid until' . $fill);
            $biF('ซึ่งต่อไปในสัญญานี้เรียกว่า "ผู้รับจ้าง" ฝ่ายหนึ่ง กับ' . $fill,
                'hereinafter referred to as the "Contractor", of the one part, and' . $fill);
            $biF('ที่อยู่' . $fill,
                'Address' . $fill);
            $biF('โทร' . $fill . 'ประเภทกิจการ' . $fill . 'ซึ่งต่อไปในสัญญานี้เรียกว่า "ผู้ว่าจ้าง"',
                'Tel.' . $fill . 'Type of Business' . $fill . 'hereinafter referred to as the "Employer", of the other part.');
            $bi('โดยผู้ว่าจ้างมีความประสงค์ให้ผู้รับจ้างดำเนินการนำคนต่างด้าวมาทำงานกับนายจ้างในประเทศตามรายละเอียดและเงื่อนไขต่อไปนี้',
                'The Employer wishes to engage the Contractor to arrange for foreign workers to work with the domestic employer, subject to the details and conditions set out below.');
        @endphp

        {{-- Clause 1 --}}
        @php
            $clause('ข้อ 1. ข้อตกลงว่าจ้าง', 'Clause 1. Terms of Engagement');
            // English side is long enough to wrap onto a second line —
            // when it does, a flex-grow .fill span only stretches within
            // whichever wrapped row it lands on, so "nationality," was
            // getting orphaned alone on its own line with the blank
            // stuck short on the row above it (per user's screenshot). A
            // plain literal-dot blank on a forced new line (moderate
            // length, not full-width — explicit user preference here)
            // avoids that; Thai stays on the auto-stretch .fillable
            // version since it doesn't wrap.
            echo '<div class="bi"><div class="th fillable">ผู้ว่าจ้างตกลงจ้างและผู้รับจ้างตกลงรับจ้างให้บริการนำคนต่างด้าว สัญชาติ' . $fill . '</div>'
                . '<div class="en">The Employer agrees to engage, and the Contractor agrees to be engaged, to provide services for bringing in foreign workers of<br>.............................. nationality,</div></div>';
            $biF('เข้ามาทำงานในประเทศภายใต้ระบบการนำเข้า (MOU) โดยทำงานให้กับผู้ว่าจ้างในตำแหน่ง' . $fill,
                'to work in the country under the (MOU) import system, working for the Employer in the position of' . $fill);
            $biF('โดยมีสถานที่ประกอบการ' . $fill,
                'at the place of business located at' . $fill);
            $bi('จำนวน .......... คน แบ่งเป็น เพศชาย จำนวน .......... คน เพศหญิง จำนวน .......... คน มีกำหนดระยะเวลาดำเนินการไม่เกิน .......... วันทำการ ( บวกลบ 15-20 วัน หรือตามระยะเวลาการพิจารณาเอกสารของประเทศต้นทาง )',
                'totaling .......... persons, comprising .......... male and .......... female, with a processing period not exceeding .......... working days (plus or minus 15-20 days, or according to the document review period of the country of origin).');
        @endphp

        {{-- Clause 2 --}}
        @php
            $clause('ข้อ 2. อัตราค่าบริการและการชำระเงิน', 'Clause 2. Service Fees and Payment');
            $bi('ผู้ว่าจ้างตกลงชำระค่าบริการและค่าใช้จ่ายในการดำเนินการนำคนต่างด้าวเข้ามาทำงานในประเทศ โดยการชำระเงินค่าบริการและค่าใช้จ่ายให้กับผู้รับจ้าง โดยคิดอัตราค่าบริการตามกฎหมายแรงงานกำหนดดังนี้',
                'The Employer agrees to pay the Contractor the service fees and expenses for bringing foreign workers into the country to work, calculated at the rate prescribed by labor law, as follows:');
            $biF('2.1 ค่าบริการเป็นเงิน จำนวน' . $fill . 'บาท/คน (' . $fill . ')',
                '2.1 Service fee in the amount of' . $fill . 'Baht/person (' . $fill . ')', true);
            $biF('2.2 ค่าใช้จ่ายในการนำคนต่างด้าวมาทำงานในประเทศเป็น จำนวนเงิน' . $fill . 'บาท/คน (' . $fill . ')',
                '2.2 Expenses for bringing foreign workers to work in the country in the amount of' . $fill . 'Baht/person (' . $fill . ')', true);
            $bi('อาทิเช่น ค่าจัดเตรียมเอกสาร ค่าแปลเอกสาร ค่าเดินทาง ค่าที่พัก และค่าอาหาร',
                'such as document preparation, translation, travel, accommodation, and food.', true);
            $biF('2.3 ตกลงชำระโดยวิธีการ' . $fill,
                '2.3 Payment shall be made by the following method:' . $fill, true);
            $bi('โดยแบ่งการชำระเงินออกเป็น 3 งวด ดังนี้', 'Payment shall be made in 3 installments, as follows:');
            $bi('งวดที่ 1 วันรับเอกสารและทำสัญญาระหว่างผู้ว่าจ้างกับผู้รับจ้าง ชำระ 30%',
                'Installment 1: 30% payable on the date of receiving documents and signing this Agreement between the Employer and the Contractor.', true);
            $bi('งวดที่ 2 ก่อนวันที่ผู้ว่าจ้าง/ผู้รับจ้างเซ็นสัญญากับประเทศต้นทางของแรงงานต่างด้าว ชำระ 30%',
                'Installment 2: 30% payable before the Employer/Contractor signs the agreement with the foreign worker\'s country of origin.', true);
            $bi('งวดที่ 3 เมื่อผู้รับจ้างได้จัดส่งแรงงานต่างด้าวถึงสถานประกอบการของผู้ว่าจ้าง ชำระ 40%',
                'Installment 3: 40% payable when the Contractor has delivered the foreign workers to the Employer\'s place of business.', true);
            $bi('2.4 กรณีผู้รับจ้างไม่สามารถปฏิบัติตามสัญญาให้แล้วเสร็จ ให้คืนค่าบริการและค่าใช้จ่ายเต็มจำนวน',
                '2.4 If the Contractor is unable to complete performance under this Agreement, the service fees and expenses shall be refunded in full.', true);
        @endphp

        {{-- Clause 3 --}}
        @php
            $clause('ข้อ 3. รายละเอียดการจ้างแรงงานต่างด้าว', 'Clause 3. Details of Foreign Worker Employment');
            $bi('3.1 การจ้างแรงงานต่างด้าวให้เป็นไปตามสัญญาจ้างแรงงานของแต่ละสัญชาติ',
                '3.1 The employment of foreign workers shall be governed by the employment contract applicable to each nationality.', true);
        @endphp

        {{-- Clause 4 --}}
        @php
            $clause('ข้อ 4. การบอกเลิกสัญญา', 'Clause 4. Termination of Agreement');
            $bi('4.1 ผู้ว่าจ้างมีสิทธิบอกเลิกสัญญากับผู้รับจ้างได้ โดยเมื่อผู้ว่าจ้างแจ้งบอกเลิกสัญญาแล้ว ให้ถือว่าสัญญาฉบับนี้สิ้นผลบังคับทันที ทั้งนี้ ผู้ว่าจ้างต้องชำระค่าใช้จ่ายที่ผู้รับจ้างได้ดำเนินการไปแล้วตามจริงให้แก่ผู้รับจ้าง',
                '4.1 The Employer has the right to terminate this Agreement with the Contractor. Once the Employer gives notice of termination, this Agreement shall immediately cease to have effect. The Employer must then pay the Contractor for expenses actually incurred by the Contractor.', true);
            $bi('4.2 ผู้รับจ้างจะบอกเลิกสัญญากับผู้ว่าจ้างได้ กรณีที่ผู้ว่าจ้างไม่ชำระเงินตามสัญญา และผู้รับจ้างบอกกล่าวให้ฝ่ายนั้นชำระเงินภายในระยะเวลาพอสมควรแล้ว',
                '4.2 The Contractor may terminate this Agreement with the Employer if the Employer fails to make payment under this Agreement and the Contractor has given the Employer reasonable notice to make payment.', true);
        @endphp

        {{-- Clause 5 --}}
        @php
            $clause('ข้อ 5. กรณีคนต่างด้าวไม่สามารถทำงานครบตามสัญญาจ้าง หรือคนต่างด้าวทำให้นายจ้างได้รับความเสียหายโดยมิใช่ความผิดของนายจ้าง',
                'Clause 5. Where a Foreign Worker is Unable to Complete the Term of Employment, or Causes Damage to the Employer through No Fault of the Employer');
            $bi('5.1 ผู้รับจ้างจะนำคนต่างด้าวใหม่มาทดแทนให้นายจ้าง ในกรณีที่ผู้รับจ้างส่งคนงานถึงบริษัทหรือผู้ว่าจ้างแล้ว ผู้ว่าจ้างไม่พึงพอใจกับการทำงานหรือพฤติกรรมของแรงงานต่างด้าว ผู้ว่าจ้างสามารถแจ้งให้ทางผู้รับจ้างเปลี่ยน ยกเลิก หรือส่งแรงงานบุคคลนั้นกลับประเทศต้นทาง แล้วจะได้ส่งแรงงานใหม่ทดแทนจนเป็นที่พึงพอใจ หากบริษัทผู้รับจ้างไม่สามารถหาคนงานมาทดแทนได้จนเป็นที่พอใจ ผู้รับจ้างจะชดใช้ค่าเสียหายให้แก่นายจ้าง โดยจะคืนเงินค่าบริการนำเข้าแรงงานต่างด้าวเฉพาะส่วนที่เหลือหรือกลุ่มที่ขาดหายไปจำนวนที่ผู้ว่าจ้างได้จ่ายจริง',
                '5.1 The Contractor shall provide a replacement worker to the Employer. Where the Contractor has delivered a worker to the company or the Employer and the Employer is dissatisfied with the worker\'s performance or conduct, the Employer may notify the Contractor to replace, cancel, or return that worker to their country of origin, after which a new replacement worker shall be provided until satisfactory. If the Contractor is unable to provide a satisfactory replacement, the Contractor shall compensate the Employer for damages by refunding the portion of the worker-importation service fee corresponding to the remaining or missing workers, in the amount actually paid by the Employer.', true);
        @endphp

        <div style="height:10px"></div>
        @php
            $bi('คู่สัญญาทั้งสองฝ่ายต่างเข้าใจข้อความในสัญญานี้ จึงได้ลงลายมือชื่อไว้ต่อหน้าพยาน',
                'Both parties to this Agreement, having understood its contents, have hereunto set their signatures in the presence of witnesses.');
        @endphp

        @php
            // Signature blocks — the separate English "Signed...... Role"
            // line was removed per feedback (asked the signer to fill in
            // the same blank twice for no reason); one Thai signature
            // line + one (name-in-print) line underneath is enough. Both
            // now stretch via .fillable since there's plenty of leftover
            // width in each column once the extra line was dropped.
            $sigBlock = function ($role) use ($fill) {
                echo '<div class="mt-sig-col">'
                    . '<div class="th fillable">ลงชื่อ' . $fill . $role . '</div>'
                    . '<div class="paren fillable">(' . $fill . ')</div>'
                    . '</div>';
            };
        @endphp
        <div class="mt-signatures">
            <div class="mt-sig-row">
                @php $sigBlock('ผู้ว่าจ้าง'); $sigBlock('ผู้รับจ้าง'); @endphp
            </div>
            <div class="mt-sig-row">
                @php $sigBlock('พยาน'); $sigBlock('พยาน'); @endphp
            </div>
        </div>
    </div>
</div>
@endsection
