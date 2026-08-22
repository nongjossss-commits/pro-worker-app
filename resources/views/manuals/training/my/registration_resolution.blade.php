{{-- Training Edition: Registration Resolution (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-card-checklist"></i> {{ __('Registration Resolution') }} — {{ __('Manage cabinet resolutions for worker registration') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Registration Resolution"</strong> မီနူးသည် လုပ်သားအသစ် မှတ်ပုံတင်ခြင်းနှင့် ပတ်သက်သော
        <strong>ကက်ဘိနက် ဆုံးဖြတ်ချက်များ</strong>ကို စီမံသည်၊
        ဥပမာ စက်တင်ဘာ ၁၆ ရက် ကက်ဘိနက်ဆုံးဖြတ်ချက်၊ COVID ကာလ အထူးဆုံးဖြတ်ချက် — စနစ်သည် <strong>ဆုံးဖြတ်ချက်များစွာကို တစ်ပြိုင်နက်</strong> tab များအဖြစ် ထောက်ပံ့သည်
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
    <h2 class="slide-title">ဆုံးဖြတ်ချက် Tab ရွေးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/01-tab-bar',
        'alt' => 'ဆုံးဖြတ်ချက် tab bar နှင့် ဖန်တီးရန်ခလုတ်',
        'caption' => 'Resolution Tab Bar — tab တစ်ခုစီသည် ကက်ဘိနက်ဆုံးဖြတ်ချက် အဆင့်တစ်ခု',
        'callouts' => [
            '<strong>Tab Bar:</strong> tab တစ်ခုစီသည် ဆုံးဖြတ်ချက်အဆင့် ၁ ခု (ဥပမာ "Sep 16 \'24 Cabinet Resolution")',
            '<strong>+ Add Tab:</strong> ဆုံးဖြတ်ချက်အသစ် ဖန်တီးရန် (Super Admin သာ)',
            '<strong>⚙️ Edit Tab:</strong> ဆုံးဖြတ်ချက်ကို အမည်ပြောင်း / ဖျက်ရန်',
            '<strong>⭐ Default:</strong> ပထမဆုံး ဝင်ရောက်သောအခါ ပြသသော default tab',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Registration Resolution</strong></li>
            <li>လိုချင်သော ဆုံးဖြတ်ချက်၏ tab ကို နှိပ်ပါ</li>
            <li>စာမျက်နှာသည် ထိုဆုံးဖြတ်ချက်၏ ဒေတာကို ပြသရန် refresh ဖြစ်သည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ဝန်ထမ်း တိုးတက်မှု အရောင်စနစ်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/02-color-legend',
        'alt' => 'တိုးတက်မှု အရောင် ၅ ခု ပြသသော Legend',
        'caption' => 'Color Legend — အရောင် ၅ ခုသည် ဝန်ထမ်းတစ်ဦးစီ၏ တိုးတက်မှုကို ပြသည်',
        'callouts' => [
            '<strong>⚪ မသက်တမ်းတိုးသေးပါ:</strong> မစတင်သေးပါ',
            '<strong>🟣 ဗီဇာ အသစ်ပြောင်းပြီး:</strong> ဗီဇာ သက်တမ်းတိုးပြီး၊ WP ကျန်နေဆဲ',
            '<strong>🟡 အလုပ်လုပ်ခွင့်ပြုချက် အသစ်ပြောင်းပြီး:</strong> WP သက်တမ်းတိုးပြီး၊ ဗီဇာ ကျန်နေဆဲ',
            '<strong>🔵 နှစ်ခုစလုံး သက်တမ်းတိုးပြီး:</strong> လုံးဝ သက်တမ်းတိုးပြီး၊ ပိတ်ရန် အသင့်',
            '<strong>🟢 အပြီးသတ်ပြီး:</strong> ပိတ်သိမ်းပြီး',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>အရောင် အလိုအလျောက် ပြောင်းလဲသည်:</strong> ဝန်ထမ်း၏ သက်တမ်းကုန်ဆုံးရက်ကို Auto Settings ပစ်မှတ်နှင့် ကိုက်ညီအောင် အပ်ဒိတ်လုပ်တိုင်း → အရောင် ချက်ချင်း ပြောင်းလဲသည်
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Auto Settings ချိန်ညှိရန် (tab တစ်ခုချင်း)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/03-auto-settings',
        'alt' => 'tab အမည်နှင့် ဗီဇာ/WP/mou ကွက်လပ်များ ပြသသော Auto Settings popup',
        'caption' => 'Auto Settings — ဆုံးဖြတ်ချက် tab တစ်ခုချင်း သီးခြား ချိန်ညှိထားသည်',
        'callouts' => [
            '<strong>Popup ခေါင်းစဉ်:</strong> tab အမည်ကို ပြသသည်၊ ဤ tab အတွက်သာ သက်ရောက်ကြောင်း ရှင်းလင်းစေသည်',
            '<strong>Auto WP Expiry:</strong> ပစ်မှတ် အလုပ်လုပ်ခွင့်ပြုချက် သက်တမ်းကုန်ရက်',
            '<strong>Auto Visa Expiry:</strong> ပစ်မှတ် ဗီဇာ သက်တမ်းကုန်ရက်',
            '<strong>Auto MOU Group:</strong> ပစ်မှတ် MOU အမျိုးအစား',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>လိုချင်သော ဆုံးဖြတ်ချက် tab ကို ဖွင့်ပါ → <strong>"Auto Settings"</strong> ကို နှိပ်ပါ</li>
            <li>WP + ဗီဇာ သက်တမ်းကုန်ရက် + MOU Group ဖြည့်ပါ</li>
            <li>Save ကို နှိပ်ပါ → <strong>ဤ tab တစ်ခုတည်းသာ</strong> သက်ရောက်သည်</li>
            <li>ကိုက်ညီသော ရက်စွဲရှိသော ဝန်ထမ်းများ → ချက်ချင်း မီနူးထဲသို့ အလိုအလျောက် ဆွဲယူသည်</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>ထည့်ရန်သာ:</strong> မီနူးထဲရှိပြီးသား ဝန်ထမ်းများကို ရက်စွဲပြောင်းလဲသောအခါ <strong>ဘယ်တော့မှ ထုတ်ပေးမည်မဟုတ်ပါ</strong> — Complete/Cancel နှိပ်မှသာ ၎င်းတို့ကို ထုတ်ပေးသည်
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">အဆင့်များ ခြစ်ရန် + တိုးတက်မှု ခြေရာခံရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/04-progress',
        'alt' => 'ဝန်ထမ်းများနှင့် အဆင့်များ ပါဝင်သော အလုပ်ရှင်ကတ်',
        'caption' => 'Employer Card — အဆင့်တစ်ခုစီအတွက် checkbox ပါသော ဝန်ထမ်းများ',
        'callouts' => [
            '<strong>အလုပ်ရှင်ကတ်:</strong> ထိုအလုပ်ရှင်၏ ဝန်ထမ်းများအားလုံးကို စုစည်းသည်',
            '<strong>Checkbox အဆင့်များ:</strong> အဆင့်တစ်ခု ပြီးပြီဟု မှတ်တမ်းတင်ရန် ခြစ်ပါ',
            '<strong>နောက်ဆုံးကတ် အပေါ်ဆုံးသို့ ရွေ့သည်:</strong> တစ်ခုခု ခြစ်ပြီး refresh လုပ်ပါ → ထိုကတ် အပေါ်ဆုံးသို့ ရွေ့သွားသည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အလုပ်ရှင်ကတ်ကို ဖွင့်ပါ → ဝန်ထမ်းစာရင်း ကြည့်ပါ</li>
            <li>ပြီးစီးသော အဆင့်တစ်ခုစီအတွက် checkbox ကို ခြစ်ပါ</li>
            <li>စနစ်သည် အချိန်တံဆိပ်ကို အလိုအလျောက် မှတ်တမ်းတင်သည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: Registration Resolution နှင့် Renewal Resolution ဘယ်လိုကွာခြားသနည်း?</dt>
        <dd>ဖြေ: Registration = စနစ်ထဲသို့ ပထမဆုံးအကြိမ် ဝင်ရောက်သော ဝန်ထမ်းအသစ်များ၊ Renewal = သက်တမ်းကုန်ဆုံးခါနီး ရှိပြီးသား ဝန်ထမ်းများ</dd>

        <dt>မေး: tab တစ်ခု၏ Auto Settings သည် အခြား tab နှင့် ထပ်နေပါသလား?</dt>
        <dd>ဖြေ: မထပ်ပါ — tab တစ်ခုစီတွင် ၎င်း၏ Auto Settings ကိုယ်ပိုင် (tab တစ်ခုချင်း key) ရှိသည်</dd>

        <dt>မေး: ဝန်ထမ်းတစ်ဦး ဘာကြောင့် မီနူးမှ ပျောက်သွားသနည်း?</dt>
        <dd>ဖြေ: စနစ်သည် မည်သူကိုမှ <strong>အလိုအလျောက် ဖယ်ရှားမည်မဟုတ်ပါ</strong> — "Complete"/"Cancel" ကို ကိုယ်တိုင်နှိပ်ခြင်း၊ သို့မဟုတ် ဝန်ထမ်းအလုပ်ရှင်ပြောင်းခြင်းမှသာ ၎င်းတို့ကို ဖယ်ရှားသည်</dd>
    </dl>
</section>
