{{-- Training Edition: PDF Templates (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-file-earmark-pdf-fill"></i> {{ __('PDF Templates') }} — {{ __('Create document templates with auto-fill fields') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"PDF Templates"</strong> မီနူးကို <strong>ဆွဲယူ ချထားခြင်း</strong> ဖြင့် ကွက်လပ်များ သတ်မှတ်၍
        စာရွက်စာတမ်းအမျိုးမျိုး (အလုပ်ခန့်စာချုပ်၊ လက်မှတ်များ၊ အစိုးရဖောင်များ) အတွက် <strong>PDF template များ</strong> ဖန်တီးရန် အသုံးပြုသည်။
        အစစ်အမှန် စာရွက်စာတမ်း ဖန်တီးသောအခါ စနစ်သည် ဒေတာကို အလိုအလျောက် ဖြည့်ပေးသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Template အသစ် ဖန်တီးရန် — နည်းလမ်း ၂ မျိုး</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/01-create-mode',
        'alt' => 'radio ရွေးစရာ ၂ ခု ပါသော template ဖန်တီးရေးစာမျက်နှာ',
        'caption' => 'Create Template — PDF အသစ် တင်ရန်၊ သို့မဟုတ် ရှိပြီးသားကို Clone ရန်',
        'callouts' => [
            '<strong>📤 PDF အသစ် တင်ရန်:</strong> ကွန်ပျူတာမှ PDF ကွက်လပ်ဖြင့် စတင်ရန်',
            '<strong>📋 ရှိပြီးသားမှ ကူးယူရန်:</strong> ရှိပြီးသား template ကို clone ပြီး အနည်းငယ် ချိန်ညှိရန်',
            '<strong>ရှာဖွေနိုင်သော dropdown:</strong> clone ရန် template ရှာပါ',
            '<strong>ကွက်လပ် အရေအတွက်:</strong> ကူးယူမည့် ကွက်လပ် အရေအတွက်ကို ပြသသည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>PDF Templates</strong> → "+ Create New Template"</li>
            <li>နည်းလမ်း ရွေးပါ: PDF အသစ် တင်ရန်၊ သို့မဟုတ် ရှိပြီးသားကို Clone ရန်</li>
            <li>အမည်ပေးပြီး အမျိုးအစား ရွေးပါ (Global / Employer)</li>
            <li>"Upload & Go to Builder" ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Builder တွင် ကွက်လပ်များ ဆွဲယူ ချထားရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/02-builder-drag',
        'alt' => 'PDF Builder — PDF ပေါ်သို့ ကွက်လပ်များ ဆွဲယူရန်',
        'caption' => 'Template Builder — PDF ပေါ်သို့ ကွက်လပ်များ ဆွဲယူပါ',
        'callouts' => [
            '<strong>ကွက်လပ် panel (ဘယ်ဘက်):</strong> ချထားနိုင်သော ကွက်လပ်များ (အလုပ်ရှင်အမည် / passport / လက်မှတ်)',
            '<strong>PDF preview (အလယ်):</strong> ကွက်လပ်ကို လိုချင်သော နေရာသို့ ဆွဲယူပါ',
            '<strong>Properties (ညာဘက်):</strong> အရွယ်အစား / font / ချိန်ညှိမှု ချိန်ညှိရန်',
            '<strong>Save:</strong> ကွက်လပ် မြေပုံကို သိမ်းဆည်းသည် → ဝန်ထမ်းများနှင့် အသုံးပြုရန် အသင့်',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Smart Quick Print — ကွက်လပ်ဖြင့် ပုံနှိပ်ခြင်း + ဒေတာဖြည့်ခြင်း</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/03-quick-print',
        'alt' => 'ကွက်လပ် ခွဲခြမ်းစိတ်ဖြာမှု ပြသသော Quick Print modal',
        'caption' => 'Quick Print — ပုံနှိပ်ခြင်းမပြုမီ ကွက်လပ်များ ခွဲခြမ်းစိတ်ဖြာသည်',
        'callouts' => [
            '<strong>Template ရွေးပါ:</strong> စနစ်သည် ၎င်း၏ ကွက်လပ်များကို ချက်ချင်း ခွဲခြမ်းစိတ်ဖြာသည်',
            '<strong>ကွက်လပ် ခွဲခြမ်းစိတ်ဖြာမှု:</strong> ဝန်ထမ်း/အလုပ်ရှင်/Delegate/Importer/သက်သေ အတွက် ကွက်လပ် ဘယ်နှစ်ခု',
            '<strong>ဝန်ထမ်း ကွက်လပ် ရှိပါက:</strong> ပိတ်ဆို့ပြီး ဝန်ထမ်းတစ်ဦးကို ဦးစွာ ရွေးရန် အကြံပြုသည်',
            '<strong>မရှိပါက:</strong> Target Employer/Delegate/Importer ရွေးပြီး ချက်ချင်း ပုံနှိပ်ပါ',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: template တစ်ခုကို clone လုပ်ပါက မူရင်း ဖျက်ခြင်းက သက်ရောက်ပါသလား?</dt>
        <dd>ဖြေ: မသက်ရောက်ပါ — စနစ်သည် ဖိုင် + ကွက်လပ် မြေပုံ နှစ်ခုစလုံးကို လုံးလုံး ကူးယူသောကြောင့် တစ်ခုနှင့်တစ်ခု လွတ်လပ်ကြသည်</dd>

        <dt>မေး: တင်ထားသော PDF သည် scan (ပုံ) ဖြစ်ပါက ရနိုင်ပါသလား?</dt>
        <dd>ဖြေ: နောက်ခံအဖြစ် သုံးနိုင်ပါသေးသည် — ကွက်လပ်နေရာများ အပေါ်တွင် ကွက်လပ်များ ဆွဲချပါ</dd>

        <dt>မေး: ထိုင်း font အကြောင်း ဘယ်လိုလဲ?</dt>
        <dd>ဖြေ: စနစ်သည် THSarabunNew + CP874 encoding ကို အသုံးပြုသည် — ထိုင်းဘာသာစကား အပြည့်အစုံ ထောက်ပံ့သည်</dd>
    </dl>
</section>
