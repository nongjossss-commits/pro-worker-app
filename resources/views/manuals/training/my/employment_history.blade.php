{{-- Training Edition: Employment History (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-badge"></i> {{ __('Employment History') }} — {{ __('Every employee ever, including terminated/contract-ended') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Employment History"</strong> မီနူးသည် စနစ်ထဲတွင် တစ်ချိန်ချိန် ရှိခဲ့ဖူးသော <strong>ဝန်ထမ်းအားလုံး</strong>ကို ပြသသည်၊
        အသုံးပြုနေဆဲ၊ အလုပ်ထုတ်ပြီး၊ စာချုပ်ကုန်ဆုံးပြီး၊ သို့မဟုတ် အလုပ်ရှင်အသစ်သို့ လွှဲပြောင်းပြီးသား ဖြစ်ဖြစ်။
        အတိတ်မှတ်တမ်း ပြန်လည်ကြည့်ရှုရန်၊ ဝန်ထမ်းဟောင်း ရှာဖွေရန်နှင့် ဝန်ထမ်းဟောင်းများကို အလုပ်ရှင်အသစ်သို့ <strong>လွှဲပြောင်း</strong>ရန် အသုံးပြုသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (ကိုယ်ပိုင်ကိုသာ)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">ဝန်ထမ်းဟောင်းများ ရှာဖွေရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/01-search-filter',
        'alt' => 'Employment History စာမျက်နှာ + စစ်ထုတ်ရေးဘား',
        'caption' => 'Employment History — အသုံးမပြုတော့သူများ အပါအဝင် ဝန်ထမ်းအားလုံးကို ပြသသည်',
        'callouts' => [
            '<strong>ရှာဖွေခြင်း:</strong> အမည် / နိုင်ငံကူးလက်မှတ်နံပါတ် ရိုက်ထည့်ပါ',
            '<strong>လူမျိုးဖြင့် စစ်ထုတ်ခြင်း:</strong> မြန်မာ / လာအို / ကမ္ဘောဒီးယား / ဗီယက်နမ်',
            '<strong>MOU အမျိုးအစားဖြင့် စစ်ထုတ်ခြင်း:</strong> အုပ်စု မည်သည့်ခုကိုမဆို ရွေးနိုင်သည်',
            '<strong>နိုင်ငံကူးလက်မှတ်ဖြင့် စစ်ထုတ်ခြင်း:</strong> CI / PJ / TD / International',
            '<strong>ပန်းရောင်ကတ်ဖြင့် စစ်ထုတ်ခြင်း:</strong> ရှိသည် / မရှိပါ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Employment History</strong></li>
            <li>ရှာဖွေမှု ရိုက်ထည့်ပါ သို့မဟုတ် အပေါ်ရှိ စစ်ထုတ်ချက်များ အသုံးပြုပါ</li>
            <li>"Filter" ကို နှိပ်ပါ — ရလဒ်တွင် အသုံးပြုနေဆဲ + အသုံးမပြုတော့သူ နှစ်မျိုးလုံး ပါဝင်သည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ဝန်ထမ်းဟောင်းများကို အလုပ်ရှင်အသစ်သို့ အစုလိုက် လွှဲပြောင်းရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/02-bulk-transfer',
        'alt' => 'Bulk Action ဘား + အလုပ်ရှင်လွှဲပြောင်းရေး modal',
        'caption' => 'Bulk Transfer — ဝန်ထမ်းများစွာကို အလုပ်ရှင်အသစ်သို့ ရွှေ့ရန်',
        'callouts' => [
            '<strong>ခြစ်ရန်:</strong> ဝန်ထမ်းများစွာ ရွေးရန်',
            '<strong>Bulk ဘား:</strong> အောက်ခြေတွင် ပေါ်နေသည်',
            '<strong>Transfer Employer:</strong> ခရီးဆုံး အလုပ်ရှင် ရွေးပါ',
            '<strong>သက်ရောက်မှု:</strong> ဤဝန်ထမ်းများ၏ notify_out မှတ်တမ်းများ အလိုအလျောက် ပယ်ဖျက်ခံရသည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>ရွှေ့လိုသော ဝန်ထမ်းများ၏ ခြစ်ဘောက်စ် ခြစ်ပါ</li>
            <li>Bulk ဘား → "Actions" → <strong>"Transfer Employer"</strong></li>
            <li>ခရီးဆုံး အလုပ်ရှင် ရွေးပါ → အတည်ပြုပါ</li>
            <li>စနစ်သည် ၎င်းတို့ကို လွှဲပြောင်းပေးပြီး notify_out မှတ်တမ်းများကို အလိုအလျောက် ပယ်ဖျက်ပေးပါလိမ့်မည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Export + Batch PDF</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/03-export-pdf',
        'alt' => 'Export CSV + Bulk PDF ခလုတ်များ',
        'caption' => 'Export + PDF — Bulk Actions ကို အသုံးပြု၍',
        'callouts' => [
            '<strong>Export CSV:</strong> ချက်ချင်း ဒေါင်းလုဒ်ဆွဲသည် (စစ်ထုတ်ချက်အတိုင်း)',
            '<strong>Advanced Export:</strong> ကိုယ်ပိုင် ကော်လံများ ရွေးချယ်ရန်',
            '<strong>Automated PDF:</strong> နမူနာမှ လူများစွာအတွက် PDF တစ်ပြိုင်နက် ဖန်တီးရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>လိုအပ်သော ဒေတာကို စစ်ထုတ်ပါ</li>
            <li>(ညာဘက်အပေါ်ထောင့်) "Export CSV" ကို နှိပ်ပါ — ချက်ချင်း ဒေါင်းလုဒ်ဆွဲသည်</li>
            <li>သို့မဟုတ် Bulk Action → "Advanced Export" / "Automated PDF" ကို အသုံးပြုပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: "Employees" မီနူးနှင့် ဘယ်လိုကွာခြားသနည်း?</dt>
        <dd>ဖြေ: Employees = အသုံးပြုနေဆဲကိုသာ၊ Employment History = အလုပ်ထုတ်ပြီး/စာချုပ်ကုန်ဆုံးပြီး/notify_out ခံရသူများ အပါအဝင် လူတိုင်း</dd>

        <dt>မေး: အမှိုက်ပုံးရှိ ဝန်ထမ်းများ ဤနေရာတွင် ပေါ်ပါသလား?</dt>
        <dd>ဖြေ: မပေါ်ပါ — "Central Trash" ကို သွားပါ — ထိုနေရာမှ ပြန်လည်ရယူနိုင်သည်</dd>
    </dl>
</section>
