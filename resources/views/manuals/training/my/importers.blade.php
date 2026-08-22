{{-- Training Edition: Importers (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-box-seam-fill"></i> {{ __('Importers') }} — {{ __('Companies that import MOU labor from abroad') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Importers"</strong> မီနူးသည် နိုင်ငံခြားမှ လုပ်သားတင်သွင်းသည့် လုပ်ငန်းများ ကိုင်တွယ်သော
        <strong>လုပ်သားတင်သွင်းသော ကုမ္ပဏီများ</strong> (MOU Importers) ၏ ဒေတာကို သိမ်းဆည်းသည် — MOU စာရွက်စာတမ်းများတွင် <strong>လက်မှတ် + တံဆိပ်</strong> အသုံးပြုသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">မီနူးကို ဖွင့်ပြီး Importer စာရင်း ကြည့်ရှုရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'importers/01-list',
        'alt' => 'Importers စာရင်း',
        'caption' => 'Importers List',
        'callouts' => [
            '<strong>ကုမ္ပဏီအမည် (ထိုင်း/အင်္ဂလိပ်):</strong> စီးပွားရေးမှတ်ပုံတင်အတိုင်း',
            '<strong>မှတ်ပုံတင်နံပါတ်:</strong> Importer Registration Number',
            '<strong>လိပ်စာ:</strong> မှတ်ပုံတင်လိပ်စာ',
            '<strong>လက်မှတ် ၁/၂ + တံဆိပ်:</strong> Automated PDF များတွင် အသုံးပြုသည်',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Importer ဒေတာ ထည့်ရန် + ပြင်ဆင်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'importers/02-form',
        'alt' => 'Importer ဖောင်',
        'caption' => 'Importer Form — ဒေတာ + လက်မှတ်နေရာ ၂ ခု',
        'callouts' => [
            '<strong>အခြေခံအချက်အလက်:</strong> အမည် + အခွန် ID + လိပ်စာ',
            '<strong>လက်မှတ် ၁:</strong> အဓိက ခွင့်ပြုချက်ရှိ လက်မှတ်ရေးထိုးသူ',
            '<strong>လက်မှတ် ၂:</strong> ဒုတိယ ခွင့်ပြုချက်ရှိ လက်မှတ်ရေးထိုးသူ (ရွေးချယ်နိုင်)',
            '<strong>တံဆိပ်:</strong> ကုမ္ပဏီတံဆိပ်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>"+ Add Importer" ကို နှိပ်ပါ</li>
            <li>ဒေတာ ဖြည့်ပြီး လက်မှတ် ၁ (အဓိက) တင်ပါ</li>
            <li>တံဆိပ် + လက်မှတ် ၂ (ရွေးချယ်နိုင်) တင်ပါ</li>
            <li>Save ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: Importer ကို မည်သည့်နေရာတွင် အသုံးပြုသနည်း?</dt>
        <dd>ဖြေ: Importer ကွက်လပ်ပါရှိသော PDF Templates တွင် (MOU တင်သွင်းသည့် စာရွက်စာတမ်းများ) — ဖန်တီးသောအခါ စနစ်သည် ဒေတာ + လက်မှတ်ကို အလိုအလျောက် ဆွဲယူပေးသည်</dd>

        <dt>မေး: Importer နှင့် Agent ဘယ်လိုကွာခြားသနည်း?</dt>
        <dd>ဖြေ: Importer = လုပ်သားတင်သွင်းသော ကုမ္ပဏီ (MOU/စာရွက်စာတမ်းများတွင် အခန်းကဏ္ဍ ပါဝင်သည်)၊ Agent = ဖောက်သည်များ ဆွဲယူပေးသော အကြားလှုပ်ရှားသူ (ကော်မရှင်)</dd>

        <dt>မေး: ဘာကြောင့် လက်မှတ်နေရာ ၂ ခု ရှိသနည်း?</dt>
        <dd>ဖြေ: အချို့ စာရွက်စာတမ်းများတွင် ဒါရိုက်တာ ၂ ဦး လက်မှတ်ရေးထိုးရန် လိုအပ်သည် — ထိုအခြေအနေအတွက် "လက်မှတ် ၂" ကွက်လပ် ဖြစ်သည် (နှစ်ခုစလုံး ရွေးချယ်နိုင်သည်)</dd>
    </dl>
</section>
