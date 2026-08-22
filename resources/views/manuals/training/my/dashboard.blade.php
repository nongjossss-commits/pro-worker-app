{{-- Training Edition: Dashboard (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }} — {{ __('System-wide overview and summary') }}
    </h3>
    <p class="training-intro-desc">
        <strong>Dashboard</strong> သည် အသုံးပြုသူ အကောင့်ဝင်ပြီးနောက် ပထမဆုံးမြင်ရသော စာမျက်နှာဖြစ်သည် — ဝန်ထမ်း/အလုပ်ရှင် အရေအတွက်၊
        ဆိုင်းငံ့ထားသော အလုပ်များ၊ လတ်တလော အကြောင်းကြားချက်များ၏ <strong>အနှစ်ချုပ်</strong>နှင့် မကြာခဏ အသုံးပြုသော မီနူးများသို့ <strong>ဖြတ်လမ်းလင့်များ</strong> ပြသသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker</span>
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Dashboard ဖွင့်ပြီး ခြုံငုံသုံးသပ်ချက် ကြည့်ရှုရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/01-overview',
        'alt' => 'အနှစ်ချုပ်ကတ် + ဖြတ်လမ်းလင့်များ ပါသော Dashboard စာမျက်နှာ',
        'caption' => 'Dashboard — အနှစ်ချုပ်ကတ်များ + ဖြတ်လမ်းလင့်များ + လတ်တလော လှုပ်ရှားမှု',
        'callouts' => [
            '<strong>အနှစ်ချုပ်ကတ်များ:</strong> အလုပ်ရှင်/ဝန်ထမ်း/ဆိုင်းငံ့ထားသော အလုပ် အရေအတွက်',
            '<strong>သက်တမ်းကုန်ဆုံးမှု သတိပေးချက်များ:</strong> သက်တမ်းကုန်ဆုံးခါနီး ဝန်ထမ်းများ (60/30/7 ရက်)',
            '<strong>ဖြတ်လမ်းလင့်များ:</strong> မကြာခဏ အသုံးပြုသော မီနူးများသို့',
            '<strong>လတ်တလော အကြောင်းကြားချက်များ:</strong> နောက်ဆုံး ၅ ခု',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အကောင့်ဝင်ပါ → Dashboard သို့ အလိုအလျောက် ရောက်ရှိပါမည်</li>
            <li>အပေါ်ပိုင်းရှိ အနှစ်ချုပ်ကတ်များကို စစ်ဆေးပါ</li>
            <li>လိုအပ်သော မီနူးသို့ သွားရန် ကတ် သို့မဟုတ် ဖြတ်လမ်းလင့်ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">အခန်းကဏ္ဍတစ်ခုစီ မတူညီသော Dashboard မြင်ရသည်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/02-role-variants',
        'alt' => 'Admin / Caretaker / Employer အတွက် Dashboard အမျိုးမျိုး',
        'caption' => 'အခန်းကဏ္ဍအလိုက် Dashboard — မတူညီသော ဒေတာ မြင်ရသည်',
        'callouts' => [
            '<strong>Admin/Staff:</strong> စနစ်တစ်ခုလုံး၏ ဒေတာအားလုံးကို မြင်ရသည်',
            '<strong>Caretaker:</strong> ၎င်းစီမံသော အလုပ်ရှင်+ဝန်ထမ်းများကိုသာ မြင်ရသည်',
            '<strong>Employer:</strong> မိမိ၏ ဝန်ထမ်းများကိုသာ မြင်ရသည်',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: အနှစ်ချုပ်ကတ်ရှိ ကိန်းဂဏန်းများ မှန်ကန်ပုံ မပေါ်ပါ?</dt>
        <dd>ဖြေ: Cache သည် 60 စက္ကန့်တိုင်း update ဖြစ်သည် — ပြန်လည်ဖွင့်ပါ သို့မဟုတ် ခဏစောင့်ပါ</dd>

        <dt>မေး: Caretaker ဘာကြောင့် ဒေတာ နည်းနည်းသာ မြင်ရသနည်း?</dt>
        <dd>ဖြေ: Caretaker သည် employer_caretaker pivot မှတစ်ဆင့် သတ်မှတ်ထားသော အလုပ်ရှင်များကိုသာ မြင်ရသည်</dd>
    </dl>
</section>
