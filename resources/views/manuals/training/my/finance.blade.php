{{-- Training Edition: Finance (Add-on Module) (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-cash-coin"></i> {{ __('Finance') }} — {{ __('The office\'s accounting + tax + audit log system') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Finance"</strong> မီနူးသည် ရုံးတွင် ငွေစာရင်းစနစ် လိုအပ်သူများအတွက် <strong>add-on</strong> module ဖြစ်သည်။
        Ledger၊ Tax Invoices၊ WHT (ရင်းမြစ်မှ နုတ်ယူသော အခွန်)၊
        ภ.พ.30 / ภ.ง.ด.3/53၊ ဘဏ်ချိန်ညှိခြင်း၊ Monthly Bundle နှင့် Audit Log ပါဝင်သည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (manage-finance ခွင့်ပြုချက်ပေါ် မူတည်သည်)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Finance မီနူးကို ဖွင့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/01-main-dashboard',
        'alt' => 'အကျဉ်းချုပ်ကတ်များ + sub-menu များ ပါသော Finance ပင်မစာမျက်နှာ',
        'caption' => 'Finance Dashboard — အကျဉ်းချုပ်ကတ်များ + sub-menu လင့်ခ်များ',
        'callouts' => [
            '<strong>အကျဉ်းချုပ်ကတ်များ:</strong> ဤလ၏ စုစုပေါင်း၊ ဝင်ငွေ၊ ကုန်ကျစရိတ်၊ VAT၊ WHT',
            '<strong>Sub-menu များ:</strong> Ledger / Tax Invoices / WHT / Reports / Bank / Audit Log',
            '<strong>Monthly bundle:</strong> လကုန် စာရွက်စာတမ်းများ၏ ZIP ကို ဖန်တီးပေးသော ခလုတ်တစ်ချက်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Finance</strong></li>
            <li>ခြုံငုံ အခြေအနေကို နားလည်ရန် အကျဉ်းချုပ်ကတ်များ စစ်ဆေးပါ</li>
            <li>လိုအပ်သော လုပ်ငန်းအတွက် sub-menu ရွေးပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Ledger မှတ်တမ်း တင်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/02-ledger-entry',
        'alt' => 'ဝင်ငွေ/ကုန်ကျစရိတ် မှတ်တမ်းတင်ဖောင်',
        'caption' => 'Ledger Entry — VAT + WHT ပါသော ဝင်ငွေ / ကုန်ကျစရိတ်',
        'callouts' => [
            '<strong>အမျိုးအစား:</strong> ဝင်ငွေ / ကုန်ကျစရိတ်',
            '<strong>ရက်စွဲ:</strong> မှတ်တမ်းတင်သည့်ရက် (default = ယနေ့)',
            '<strong>ကုန်သွယ်ဖက်:</strong> ဖောက်သည် သို့မဟုတ် ရောင်းချသူ',
            '<strong>VAT:</strong> 7% (default) — VAT မပါ သို့မဟုတ် ပါ',
            '<strong>WHT:</strong> 3% (ဝန်ဆောင်မှု) / 5% (ပိုင်ဆိုင်မှု ငှားရမ်းခြင်း)',
            '<strong>ဘောင်ချာပုံ:</strong> ဘောင်ချာဓာတ်ပုံ ပူးတွဲနိုင်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Finance → Ledger → "+ Record Entry"</li>
            <li>အမျိုးအစား ရွေးပါ (ဝင်ငွေ/ကုန်ကျစရိတ်)</li>
            <li>ဖြည့်ပါ: ရက်စွဲ၊ ကုန်သွယ်ဖက်၊ ပမာဏ၊ VAT</li>
            <li>ဘောင်ချာ ပူးတွဲပါ (ရွေးချယ်နိုင်) → "Save" ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Tax Invoice ဖန်တီးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/03-tax-invoice',
        'alt' => 'အခွန်ဘောင်ချာ ဖန်တီးသောဖောင်',
        'caption' => 'Tax Invoice Form — profile ရွေးပြီး ဖောက်သည် + ငွေပေးချေမှု နည်းလမ်း ဖြည့်ရန်',
        'callouts' => [
            '<strong>ထုတ်ပေးသူ profile:</strong> ကျွန်ုပ်တို့ရုံး (Financial Profiles မှ)',
            '<strong>ဖောက်သည် အချက်အလက်:</strong> အမည် + အခွန် ID + လိပ်စာ',
            '<strong>VAT 7%:</strong> ထိုင်း default၊ ဒသမ ၂ နေရာ ထိ ရေတွက်သည်',
            '<strong>ငွေပေးချေမှု နည်းလမ်း:</strong> ငွေသား / လွှဲပြောင်း / PromptPay',
            '<strong>ဘဏ်အကောင့်:</strong> "လွှဲပြောင်း" ရွေးပါက → ဘဏ်အကောင့် ရွေးပါ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Tax Invoices → "+ Create New"</li>
            <li><strong>Biller Profile</strong> ကို ရွေးပါ</li>
            <li>ဖောက်သည် အချက်အလက် + ပမာဏ + VAT ဖြည့်ပါ</li>
            <li>ငွေပေးချေမှု နည်းလမ်း စစ်ဆေးပါ</li>
            <li>"Save & Issue" ကို နှိပ်ပါ → စနစ်သည် နံပါတ်ကို သော့ခတ်ပြီး PDF ဖန်တီးသည်</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>သတိပြုပါ:</strong> ထုတ်ပေးပြီးသား ဘောင်ချာကို ပြင်ဆင်၍ မရပါ — ပယ်ဖျက်ပြီး အသစ် ထုတ်ရမည်
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">လစဉ် အခွန်အစီရင်ခံစာများ (ภ.พ.30 / ภ.ง.ด.3/53)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/04-tax-reports',
        'alt' => 'Tax Reports စာမျက်နှာ — လ ရွေးပြီး ဒေါင်းလုဒ်ဆွဲရန်',
        'caption' => 'Tax Reports — အခွန်တင်သွင်းရန် လစဉ် အနှစ်ချုပ်များ',
        'callouts' => [
            '<strong>လ ရွေးပါ:</strong> လ dropdown',
            '<strong>ภ.พ.30:</strong> ထိုလအတွက် VAT (ဝင်ငွေ - ကုန်ကျစရိတ် VAT)',
            '<strong>ภ.ง.ด.3:</strong> ပုဂ္ဂိုလ်များအတွက် WHT',
            '<strong>ภ.ง.ด.53:</strong> ဥပဒေဆိုင်ရာ ပုဂ္ဂိုလ်များအတွက် WHT',
            '<strong>Export Excel:</strong> အခွန်တင်သွင်းသောအခါ အသုံးပြုရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Finance → Tax Reports</li>
            <li>လိုချင်သော လ ရွေးပါ</li>
            <li>အစီရင်ခံစာတစ်ခုစီအတွက် ဒေါင်းလုဒ် နှိပ်ပါ</li>
            <li>ရလာသော ဖိုင်ကို အခွန်တင်သွင်းရန် အသုံးပြုပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Monthly Bundle + ဘဏ်ချိန်ညှိခြင်း + Audit Log</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/05-monthly-bundle',
        'alt' => 'Monthly Bundle + Bank Reconciliation + Audit Log',
        'caption' => 'လကုန် ပိတ်သိမ်းခြင်း လုပ်ဆောင်ချက်များ အပြည့်အစုံ',
        'callouts' => [
            '<strong>Monthly Bundle:</strong> လတစ်လလုံး၏ စာရွက်စာတမ်းများ (ဝင်ငွေ + ကုန်ကျစရိတ် + ဘောင်ချာများ + WHT) ZIP ဖိုင်',
            '<strong>Bank Reconciliation:</strong> ဘဏ်စာရင်းရှင်း တင်ပါ → စနစ်သည် သင့်မှတ်တမ်းများနှင့် ကိုက်ညီစေသည်',
            '<strong>Audit Log:</strong> ငွေကြေးဒေတာ ပြောင်းလဲမှုတိုင်း၏ မှတ်တမ်း — မည်သူက မည်သည့်အရာကို ဘယ်အချိန်တွင် ပြောင်းလဲသနည်း',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>အကြံပြုချက်:</strong> လကုန်တွင် → Monthly Bundle ဖန်တီးပါ → Bank Reconcile လုပ်ပါ → Audit Log စစ်ဆေးပါ = လုပ်ငန်းစဉ်တစ်ခုတည်းဖြင့် လကုန် ပိတ်သိမ်းမှု အပြည့်အစုံ
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: Tax invoice နံပါတ်များသည် အစဉ်လိုက် ဖြစ်ပါသလား?</dt>
        <dd>ဖြေ: ဖြစ်ပါသည် — စနစ်သည် တူညီသော အခွန်နှစ်ရှိ နောက်ဆုံး ဘောင်ချာမှ ဆက်လက် နံပါတ်တပ်သည်၊ ကွာဟမှု မရှိပါ</dd>

        <dt>မေး: မှားယွင်းစွာ ထုတ်ပေးထားသော ဘောင်ချာကို ဖျက်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: <strong>ပယ်ဖျက်</strong>နိုင်ပါသည်၊ သို့သော် အမှန်တကယ် ဖျက်၍ မရပါ — အစဉ်လိုက်မှု ထိန်းသိမ်းရန် ဘောင်ချာနံပါတ် စနစ်တွင် ကျန်ရှိနေသည်</dd>

        <dt>မေး: WHT 3% နှင့် 5% ဘယ်လိုကွာခြားသနည်း?</dt>
        <dd>ဖြေ: 3% = ယေဘုယျ ဝန်ဆောင်မှု / 5% = ပိုင်ဆိုင်မှု ငှားရမ်းခြင်း၊ ပုဂ္ဂိုလ်ရေး လုပ်အားခ</dd>

        <dt>မေး: Finance မီနူးကို မမြင်ရပါ?</dt>
        <dd>ဖြေ: manage-finance role သို့မဟုတ် ပိုမြင့်သော role + Finance Module ပါဝင်သော subscription လိုအပ်သည်</dd>
    </dl>
</section>
