{{-- Training Edition: Delegates (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-vcard"></i> {{ __('Delegates') }} — {{ __('People authorized to sign on an employer\'s behalf') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Delegates"</strong> မီနူးသည် အလုပ်ရှင်ကိုယ်စား စာရွက်စာတမ်းအမျိုးမျိုး (ဥပမာ- အခွင့်အာဏာလွှဲစာ၊ တောင်းဆိုချက်ဖောင်များ) တွင်
        လက်မှတ်ရေးထိုးရန် <strong>ခွင့်ပြုချက်ရှိသော</strong> ပုဂ္ဂိုလ်များ၏ ဒေတာကို သိမ်းဆည်းသည် — အလုပ်ရှင်ကဲ့သို့ လက်မှတ် + တံဆိပ် + လိပ်စာနှင့်တကွ
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Delegates မီနူးကို ဖွင့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'delegates/01-list',
        'alt' => 'ကိုယ်စားလှယ်စာရင်း + စစ်ထုတ်ခြင်း',
        'caption' => 'Delegates List',
        'callouts' => [
            '<strong>အမည် ထိုင်း/အင်္ဂလိပ် + ရာထူး:</strong> ခွင့်ပြုချက်ရှိသော လက်မှတ်ရေးထိုးသူ',
            '<strong>ချိတ်ဆက်ထားသော အလုပ်ရှင်:</strong> မည်သည့်အလုပ်ရှင်(များ)နှင့် ချိတ်ဆက်ထားသည် (ရွေးချယ်နိုင်)',
            '<strong>+ Add Delegate:</strong> အသစ် ဖန်တီးရန်',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ကိုယ်စားလှယ်ဒေတာ ထည့်ရန် + ပြင်ဆင်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'delegates/02-form',
        'alt' => 'ကိုယ်စားလှယ် ဖန်တီးရန်/ပြင်ဆင်ရန် ဖောင်',
        'caption' => 'Delegate Form — ဒေတာနှင့် လက်မှတ်',
        'callouts' => [
            '<strong>ကိုယ်ရေးအချက်အလက်:</strong> အမည် + ရာထူး + အခွန် ID + လိပ်စာ',
            '<strong>လက်မှတ်:</strong> PNG တင်ရန် (နောက်ခံ ပွင့်လင်း)',
            '<strong>အခွင့်အာဏာလွှဲစာ:</strong> ကိုးကားစာ PDF ပူးတွဲရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>"+ Add Delegate" ကို နှိပ်ပါ</li>
            <li>ဒေတာ ဖြည့်ပြီး လက်မှတ် တင်ပါ</li>
            <li>Save ကို နှိပ်ပါ</li>
            <li>PDF ဖန်တီးသောအခါ အသုံးပြုပါ: Delegate ကွက်လပ်ကို သတ်မှတ်ပါ — စနစ်သည် အမည်/လက်မှတ်ကို အလိုအလျောက် ဆွဲယူပေးပါလိမ့်မည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: employer အခန်းကဏ္ဍ၏ sidebar ရှိ "Employee Info" နှင့် ဘယ်လိုကွာခြားသနည်း?</dt>
        <dd>ဖြေ: employer အခန်းကဏ္ဍ၏ sidebar တွင် "Employee Info" = Delegates (ကုမ္ပဏီ၏ ခွင့်ပြုချက်ရှိ လက်မှတ်ရေးထိုးသူများ)၊ "Employees" မီနူးမှာမူ = အမှန်တကယ် ရွှေ့ပြောင်းအလုပ်သမားများ ဖြစ်သည်</dd>

        <dt>မေး: ကိုယ်စားလှယ်တစ်ဦးသည် အလုပ်ရှင် မည်မျှအတွက် လက်မှတ်ရေးထိုးနိုင်သနည်း?</dt>
        <dd>ဖြေ: များစွာ — PDF ဖန်တီးသောအခါ လိုချင်သော Delegate ကို ရွေးနိုင်သည်</dd>
    </dl>
</section>
