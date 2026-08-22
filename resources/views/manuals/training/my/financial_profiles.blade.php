{{-- Training Edition: Financial Profiles (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-vcard-fill"></i> {{ __('Financial Profiles') }} — {{ __('The master template for billers + customers') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Financial Profiles"</strong> မီနူးသည် Biller (ထုတ်ပေးသူ = ကျွန်ုပ်တို့ရုံး) + Customer (ပုံမှန် ဖောက်သည်) များအတွက်
        <strong>master ဒေတာ</strong> သိမ်းဆည်းသည့်နေရာ ဖြစ်သည်။
        ဘောင်ချာ/ငွေလက်ခံဖြေ ထုတ်ပေးတိုင်း၊ စနစ်သည် ဤ profile များထဲမှ ရွေးချယ်ခွင့် ပေးသည် — အချက်အလက်တူများကို ထပ်ခါထပ်ခါ ရိုက်ရန် မလိုပါ။
        <strong>ဘဏ်အကောင့်</strong>၊ <strong>လိုဂို</strong>၊ နှင့် <strong>လက်မှတ်</strong> ပါဝင်သည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (manage-finance)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Financial Profiles မီနူးကို ဖွင့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/01-list',
        'alt' => 'Biller / Customer ခွဲထားသော Profile စာရင်း',
        'caption' => 'Financial Profiles List — အမျိုးအစား ၂ မျိုး: Biller + Customer',
        'callouts' => [
            '<strong>Biller profile များ:</strong> ကျွန်ုပ်တို့ရုံး (အမည်ခြားနှင့် ဘောင်ချာထုတ်ပါက အများအပြား ရှိနိုင်သည်)',
            '<strong>Customer profile များ:</strong> မကြာခဏ ဘောင်ချာထုတ်ပေးသော ပုံမှန်ဖောက်သည်များ',
            '<strong>+ Create New:</strong> profile အသစ် ထည့်ရန်',
            '<strong>Edit / Delete:</strong> စီမံခန့်ခွဲရေးခလုတ်များ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → Finance → <strong>Financial Profiles</strong></li>
            <li>Biller သို့မဟုတ် Customer အမျိုးအစား ရွေးပါ</li>
            <li>ရှိပြီးသား profile များ စာရင်းကို ကြည့်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Biller Profile ဖန်တီးရန် (ထုတ်ပေးသူ)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/02-biller-builder',
        'alt' => 'ကွက်လပ်အားလုံး ပါသော Biller Builder စာမျက်နှာ',
        'caption' => 'Biller Profile Builder — ဘောင်ချာ ထုတ်ပေးသူ၏ အချက်အလက်',
        'callouts' => [
            '<strong>ကုမ္ပဏီအမည် + အခွန် ID:</strong> အရေးကြီးဆုံး အပိုင်း',
            '<strong>လိပ်စာ:</strong> မှတ်ပုံတင်လိပ်စာ',
            '<strong>လိုဂို:</strong> PNG/JPG တင်ပါ (ဘောင်ချာတွင် ပုံနှိပ်ပေးသည်)',
            '<strong>လက်မှတ်:</strong> ခွင့်ပြုချက်ရှိ လက်မှတ်ရေးထိုးသူ၏ လက်မှတ် + ကုမ္ပဏီတံဆိပ်',
            '<strong>ဘဏ်အကောင့်များ:</strong> အများအပြား ထည့်နိုင်သည် (KBank, SCB, BBL, စသည်)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>"+ Create New" ကို နှိပ်ပါ → အမျိုးအစား = Biller ကို ရွေးပါ</li>
            <li>ကုမ္ပဏီအချက်အလက် + အခွန် ID + လိပ်စာ ဖြည့်ပါ</li>
            <li>လိုဂို + လက်မှတ် + တံဆိပ် တင်ပါ</li>
            <li>ဘဏ်အကောင့်များ ထည့်ပါ (အကောင့်များစွာ ထောက်ပံ့သည်)</li>
            <li>"Save Profile" ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Profile တွင် ဘဏ်အကောင့် ထည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/03-bank-accounts',
        'alt' => 'ဘဏ် brand badge ပါသော ဘဏ်အကောင့် စာရင်း',
        'caption' => 'Bank Accounts — profile ပေါ်တွင် အကောင့် ထည့်/ပြင်/ဖျက်ရန်',
        'callouts' => [
            '<strong>ဘဏ်:</strong> dropdown မှ ရွေးပါ (KBank/SCB/BBL/Krungsri/TTB, စသည်)',
            '<strong>အကောင့်နံပါတ်:</strong> အကောင့်နံပါတ်',
            '<strong>အကောင့်အမည်:</strong> အကောင့်ပိုင်ရှင်၏ အမည်',
            '<strong>Brand badge:</strong> ဘဏ်၏ လိုဂို ငွေလက်ခံဖြေများတွင် အလိုအလျောက် ပေါ်လာသည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Biller Profile တစ်ခုကို ဖွင့်ပါ → "Bank Accounts" tab</li>
            <li>"+ Add Account" ကို နှိပ်ပါ</li>
            <li>ဘဏ်ရွေးပြီး အကောင့်နံပါတ် + အမည် ဖြည့်ပါ</li>
            <li>Save ကို နှိပ်ပါ</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>အကြံပြုချက်:</strong> ဘောင်ချာထုတ်သောအခါ "ငွေပေးချေမှု နည်းလမ်း = လွှဲပြောင်း" ရွေးပါက ဤ profile မှ ဘဏ်အကောင့် ရွေးနိုင်ပြီး PDF တွင် အလိုအလျောက် ပုံနှိပ်ပေးသည်
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Customer Profiles (ပုံမှန် ဖောက်သည်များ)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/04-customer-profiles',
        'alt' => 'Customer Profiles စာရင်း',
        'caption' => 'Customer Profiles — မကြာခဏ ဘောင်ချာထုတ်ပေးသော ပုံမှန်ဖောက်သည်များ',
        'callouts' => [
            '<strong>ဖောက်သည်အမည် + အခွန် ID:</strong> ဘောင်ချာတွင် ပုံနှိပ်မည့် အချက်အလက်',
            '<strong>လိပ်စာ:</strong> စာရွက်စာတမ်း ပို့ရန် လိပ်စာ',
            '<strong>Quick fill:</strong> ဘောင်ချာထုတ်သောအခါ → profile ရွေးပါ → အချက်အလက်အားလုံး ချက်ချင်း ဖြည့်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>"+ Create New" ကို နှိပ်ပါ → အမျိုးအစား = Customer ကို ရွေးပါ</li>
            <li>ဖောက်သည်၏ အမည် + အခွန် ID + လိပ်စာ ဖြည့်ပါ</li>
            <li>Save ကို နှိပ်ပါ</li>
            <li>နောက်တစ်ကြိမ် ဘောင်ချာထုတ်သောအခါ → ဤ profile ရွေးပါ → ဒေတာ အလိုအလျောက် ဖြည့်သည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: ဘောင်ချာထုတ်သောအခါ ဘဏ်အကောင့် ဘာကြောင့် မမြင်ရသနည်း?</dt>
        <dd>ဖြေ: Financial Profile (Biller Profile) ပေါ်တွင် ဦးစွာ ဘဏ်အကောင့် ဖန်တီးရမည် — ထို့နောက် Finance → Tax Invoice တွင် ရရှိမည်</dd>

        <dt>မေး: ဘောင်ချာဟောင်းများတွင် အသုံးပြုနေသော profile ကို ဖျက်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: <strong>မဖျက်သင့်ပါ</strong> — ဘောင်ချာဟောင်းများ ၎င်းတို့၏ ကိုးကားချက် ဆုံးရှုံးသွားမည်၊ archive လုပ်ခြင်းကို အစား အသုံးပြုပါ</dd>

        <dt>မေး: Biller profile များစွာ ရှိနိုင်ပါသလား?</dt>
        <dd>ဖြေ: ရှိနိုင်ပါသည် — ဥပမာ ကုမ္ပဏီအမည်ခြားများဖြင့် ဘောင်ချာထုတ်ပါက (ဥပမာ "ABC Co., Ltd." နှင့် "ABC Service")</dd>

        <dt>မေး: လက်မှတ် + တံဆိပ်ကို ဘယ်နေရာတွင် ထည့်ရမည်နည်း?</dt>
        <dd>ဖြေ: Biller Profile → "Signature/Stamp" tab တွင် → PNG ဖိုင်အဖြစ် တင်ပါ (နောက်ခံ ပွင့်လင်းသော ဖိုင် အကြံပြုသည်)</dd>
    </dl>
</section>
