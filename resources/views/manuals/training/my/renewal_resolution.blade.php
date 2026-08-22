{{-- Training Edition: Renewal Resolution (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-arrow-clockwise"></i> {{ __('Renewal Resolution') }} — {{ __('Manage cabinet resolutions for worker renewal') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Renewal Resolution"</strong> မီနူးသည် သက်တမ်းကုန်ဆုံးခါနီး ဝန်ထမ်းများကို <strong>သက်တမ်းတိုးရန်</strong> ကက်ဘိနက် ဆုံးဖြတ်ချက်များကို စီမံသည်။
        Registration Resolution နှင့် တူညီသော ယန္တရားကို အသုံးပြုသည် — သို့သော် အလုပ်လုပ်ခွင့်ပြုချက် သို့မဟုတ် ဗီဇာ သက်တမ်းကုန်ဆုံးခါနီး ရှိပြီးသား ဝန်ထမ်းများကို အာရုံစိုက်သည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (ကြည့်ရှုရုံသာ)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">မီနူးကို ဖွင့်ပြီး ဆုံးဖြတ်ချက် tab ရွေးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/01-tab-bar',
        'alt' => 'Renewal Resolution ပင်မစာမျက်နှာ + tab bar',
        'caption' => 'Renewal Resolution — tab တစ်ခုစီသည် သက်တမ်းတိုးမှု အဆင့်တစ်ခု',
        'callouts' => [
            '<strong>Tab Bar:</strong> ဥပမာ "2025 Renewal Round 1"',
            '<strong>Stats ကတ်များ:</strong> စုစုပေါင်းဝန်ထမ်း / ပြီးစီး / ကျန်ရှိနေ',
            '<strong>Filter pills:</strong> တိုးတက်မှုအပေါ် အခြေခံသော အရောင် ၅ ခု',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Renewal Resolution</strong></li>
            <li>လိုချင်သော ဆုံးဖြတ်ချက်၏ tab ကို နှိပ်ပါ</li>
            <li>အပေါ်ပိုင်းရှိ အကျဉ်းချုပ်ကတ်များတွင် ခြုံငုံသုံးသပ်ချက် ကြည့်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Filter pills — တိုးတက်မှုအလိုက် စစ်ထုတ်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/02-filter-pills',
        'alt' => 'အရောင် ၅ ခု ပါသော filter pills',
        'caption' => 'Filter pills — တစ်ပြိုင်နက် များစွာ ရွေးနိုင်သည်',
        'callouts' => [
            '<strong>⚪ မသက်တမ်းတိုးသေးပါ:</strong> မစတင်သေးသော ဝန်ထမ်းများ',
            '<strong>🟣 ဗီဇာ အသစ်ပြောင်းပြီး:</strong> ဗီဇာ သက်တမ်းတိုးပြီး၊ WP ကျန်နေ',
            '<strong>🟡 အလုပ်လုပ်ခွင့်ပြုချက် အသစ်ပြောင်းပြီး:</strong> WP သက်တမ်းတိုးပြီး၊ ဗီဇာ ကျန်နေ',
            '<strong>🔵 လုံးဝ သက်တမ်းတိုးပြီး – အပြီးသတ်ရန် အသင့်:</strong> နှစ်ခုစလုံး ပြီးပြီ၊ ပိတ်ရန် အသင့်',
            '<strong>🟢 အပြီးသတ်ပြီး:</strong> ပိတ်သိမ်းပြီး',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>စစ်ထုတ်လိုသော အခြေအနေ pill ကို နှိပ်ပါ</li>
            <li>တစ်ပြိုင်နက် များစွာ ရွေးနိုင်သည် (on/off toggle)</li>
            <li>pill အတွင်းရှိ နံပါတ်သည် ထို filter နှင့် ကိုက်ညီသော အရေအတွက်ကို ပြသသည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Auto Settings ချိန်ညှိရန် (tab တစ်ခုချင်း)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/03-auto-settings',
        'alt' => 'သက်တမ်းတိုးမှု tab အတွက် Auto Settings popup',
        'caption' => 'Auto Settings — tab တစ်ခုချင်း ပစ်မှတ် သက်တမ်းကုန်ရက်များ သတ်မှတ်ရန်',
        'callouts' => [
            '<strong>Popup ခေါင်းစဉ်တွင် tab အမည် ပြသသည်:</strong> အခြား tab များနှင့် ရှုပ်ထွေးမှု မဖြစ်စေရန်',
            '<strong>Auto WP/Visa Expiry:</strong> ဤ tab အတွက် အသုံးပြုမည့် သက်တမ်းကုန်ရက်',
            '<strong>Auto MOU Group:</strong> ဤ tab အတွက် MOU အမျိုးအစား',
            '<strong>Save Settings:</strong> ဤ tab တစ်ခုတည်းသာ သက်ရောက်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>tab တစ်ခုကို ဖွင့်ပါ → <strong>"Auto Settings"</strong> ကို နှိပ်ပါ</li>
            <li>ပစ်မှတ် သက်တမ်းကုန်ရက်များ ဖြည့်ပါ</li>
            <li>Save ကို နှိပ်ပါ</li>
            <li>ကိုက်ညီသော သက်တမ်းကုန်ရက်ရှိသော ဝန်ထမ်းများ → tab ထဲသို့ အလိုအလျောက် ဆွဲယူသည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">တိုးတက်မှု ခြေရာခံ + အဆင့်များ ခြစ်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/04-progress-tracking',
        'alt' => 'တိုးတက်မှု အဆင့်များ ပါဝင်သော အလုပ်ရှင်ကတ်',
        'caption' => 'Employer card — ဝန်ထမ်းတစ်ဦးစီတွင် ခြစ်ရမည့် အဆင့်များ ရှိသည်',
        'callouts' => [
            '<strong>ဝန်ထမ်းကတ်:</strong> တိုးတက်မှုအပေါ် အခြေခံ၍ အရောင် ပြောင်းလဲသည် (အရောင် ၅ ခု)',
            '<strong>အဆင့် checkbox များ:</strong> အဆင့်တစ်ခုစီ ပြီးသည့်အခါ ခြစ်ပါ',
            '<strong>ကတ် အပေါ်ဆုံးသို့ ရွေ့သည်:</strong> တစ်ခုခု ခြစ်/ဒေတာပြင်တိုင်း → refresh လုပ်ပါက နောက်ဆုံးကတ် အပေါ်ဆုံးတွင် ရှိသည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အလုပ်ရှင်ကတ်ကို ဖွင့်ပါ</li>
            <li>အဆင့်တစ်ခုစီအတွက် checkbox ကို ခြစ်ပါ</li>
            <li>ဝန်ထမ်းကတ်၏ အရောင်သည် တိုးတက်မှုအပေါ် အခြေခံ၍ ပြောင်းလဲသည်</li>
            <li>လုံးဝ သက်တမ်းတိုးပြီးသည့်အခါ → အစိမ်းရောင်ပြောင်းသည် → အလုပ်ကို ပိတ်ရန် <strong>"Finish"</strong> ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">အကျဉ်းချုပ် Stat ကတ်များ + ခြုံငုံသုံးသပ်ချက်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/05-stats-cards',
        'alt' => 'စာမျက်နှာ အပေါ်ပိုင်းရှိ အကျဉ်းချုပ် stat ကတ်များ',
        'caption' => 'Summary Stat Cards — ဤ tab ၏ ခြုံငုံသုံးသပ်ချက်',
        'callouts' => [
            '<strong>စုစုပေါင်း ဝန်ထမ်း:</strong> ဤဆုံးဖြတ်ချက်တွင် စုစုပေါင်း',
            '<strong>စုစုပေါင်း ပယ်ဖျက်:</strong> ပယ်ဖျက်ခံရသူများ',
            '<strong>ဒေတာဘေ့စ်တွင် မှတ်တမ်းတင်ပြီး:</strong> ပြီးစီးပြီး',
            '<strong>ဇီဝအချက်အလက် စုဆောင်းပြီး:</strong> ဇီဝအချက်အလက် စုဆောင်းပြီးသား',
            '<strong>စုစုပေါင်း အလုပ်ရှင်:</strong> ဤ tab ရှိ အလုပ်ရှင် အရေအတွက်',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>Stat ကတ်ကို နှိပ်ပါ:</strong> = ထိုအမျိုးအစားသို့ ချက်ချင်း စစ်ထုတ်သည် (ဥပမာ "Finished" ကို နှိပ်ပါက → ပြီးစီးသူများကိုသာ စစ်ထုတ်သည်)
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: ဝန်ထမ်းတစ်ဦး သက်တမ်းကုန်သွားပြီ၊ သက်တမ်းတိုးနိုင်ပါသေးသလား?</dt>
        <dd>ဖြေ: ဆုံးဖြတ်ချက်၏ အခြေအနေများပေါ် မူတည်သည် — အချို့ ဆုံးဖြတ်ချက်များသည် နောက်ပြန်ရေတွက်၍ သက်တမ်းတိုးခွင့် ပြုသည်၊ ဦးစွာ ဝန်ကြီးဌာန စည်းမျဉ်းကို စစ်ဆေးပါ</dd>

        <dt>မေး: ဤဝန်ထမ်းကို ဘာကြောင့် သက်တမ်းတိုး၍ မရသနည်း?</dt>
        <dd>ဖြေ: ဝန်ထမ်း၏ အခြေအနေသည် "Active" ဟုတ်/မဟုတ် စစ်ဆေးပါ (ရပ်စဲ/စာချုပ်ကုန်ဆုံးခြင်း မဟုတ်ပါ)</dd>

        <dt>မေး: သက်တမ်းကုန်ရက် အပ်ဒိတ်လုပ်ပြီးနောက် ဝန်ထမ်းတစ်ဦး ပျောက်သွားသည်?</dt>
        <dd>ဖြေ: ဤသို့ မဖြစ်သင့်ပါ — စနစ်သည် "ထည့်ရန်သာ" ဖြစ်ပြီး မည်သူကိုမှ အလိုအလျောက် မဖယ်ရှားပါ (ယခင်က bug ရှိခဲ့သော်လည်း ပြင်ဆင်ပြီးဖြစ်သည်)</dd>
    </dl>
</section>
