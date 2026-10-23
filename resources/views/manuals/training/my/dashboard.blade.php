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
    <div class="slide-number">STEP 0</div>
    <h2 class="slide-title">Login + လုံခြုံရေး</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/03-login',
        'alt' => 'Login စာမျက်နှာ — အရောင်နှင့် logo သည် စနစ်၏ brand ဆက်တင်အတိုင်း',
        'caption' => 'Login စာမျက်နှာ — အရောင်နှင့် logo သည် စနစ်၏ brand ဆက်တင်အတိုင်း',
    ])

    <div class="slide-instructions">
        <ol>
            <li>Browser / app ပိတ်လျှင် → နောက်တစ်ကြိမ် ပြန် login ဝင်ရ</li>
            <li>ပရိုဂရမ်စာမျက်နှာ ၅ မိနစ်ထက်ပို မဖွင့်ထားလျှင် → အလိုအလျောက် logout</li>
            <li>Refresh သို့မဟုတ် tab အသစ် → ပုံမှန်သုံးနိုင်</li>
            <li>"Remember me" က email မှတ်၊ "Forgot your password?" က reset link ပို့ (၆၀ မိနစ်)</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Dashboard ဖွင့်ပြီး ခြုံငုံသုံးသပ်ချက် ကြည့်ရှုရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/01-overview',
        'alt' => 'ကိန်းဂဏန်းကတ်များ + ဇယားများပါသော အုပ်ချုပ်သူ Dashboard',
        'caption' => 'Dashboard — လုပ်ငန်း အကျဉ်းချုပ်: အဓိက ကိန်းဂဏန်းကတ် + လစဉ်ဇယား + tab အလိုက် ဆောင်ရွက်ဆဲ အလုပ်',
        'callouts' => [
            '<strong>ခေါင်းစီး:</strong> ရက်စွဲ + ထင်ရှားသော ကိန်းဂဏန်း (၁၂ လ ပြီးဆုံးမှု / နိုင်ငံသား / ဆောင်ရွက်ဆဲ tab / ၁၄ ရက် လုပ်ဆောင်ချက်)',
            '<strong>ကိန်းဂဏန်းကတ်:</strong> mouse တင် = အသေးစိတ် + တွက်နည်း',
            '<strong>ဇယားများ:</strong> လစဉ် ပြီးဆုံးမှု · အလုပ်သမားအသစ် နှင့် အလုပ်ထွက် · tab အလိုက် ဆောင်ရွက်ဆဲ (နှိပ်၍ဖွင့်) · နိုင်ငံသား · စာရွက်စာတမ်း / ၉၀ ရက် အစီရင်ခံ သတ်မှတ်ရက် · ထိပ်တန်း အလုပ်ရှင်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အကောင့်ဝင်ပါ → Dashboard သို့ အလိုအလျောက် ရောက်ရှိပါမည်</li>
            <li>အပေါ်ရှိ ကိန်းဂဏန်းကတ်များကို ကြည့်ပါ — အသေးစိတ်အတွက် ကတ် သို့မဟုတ် ဇယားပေါ် mouse တင်ပါ</li>
            <li>"tab အလိုက် ဆောင်ရွက်ဆဲ အလုပ်" ဘားကိုနှိပ်၍ ထို tab ကိုဖွင့်ပါ · ဒက်ရှ်ဘုတ်တွင် ငွေကြေးကိန်းဂဏန်း မပြပါ (ငွေကြေးမီနူးတွင် ကြည့်ပါ)</li>
            <li>ဘာသာစကားခလုတ်၏ နောက်ရှိ ခလုတ် = <strong>အလင်း / အမှောင် / စက်ပစ္စည်း အတိုင်း</strong> မုဒ် — refresh မလိုဘဲ ချက်ချင်း ပြောင်းပြီး စက်တစ်ခုချင်းစီ မှတ်ထားသည်</li>
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
