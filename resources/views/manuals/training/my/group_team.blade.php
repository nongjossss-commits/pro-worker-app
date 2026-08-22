{{-- Training Edition: Group & Team (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Group & Team') }} — {{ __('Organize employees into smaller teams') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Group & Team"</strong> မီနူးကို ဝန်ထမ်းများကို အဖွဲ့သေးများအဖြစ် <strong>စုစည်းရန်</strong> အသုံးပြုသည်၊
        ဥပမာ- "A စက်ရုံ မနက်ဝေဒ်ရှစ်လ်"၊ "အိမ်တွင်းအလုပ် အဖွဲ့" — တစ်ခုတည်းအဖြစ် စီမံနိုင်ရန်။
        အမျိုးအစား ၂ မျိုး ခွဲထားသည်: <strong>Affiliated</strong> (အလုပ်ရှင်နှင့် ချည်နှောင်ထား) + <strong>Independent</strong> (မချည်နှောင်ထား)
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (၎င်းစီမံသော အုပ်စုများကိုသာ)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">အုပ်စုအမျိုးအစား ရွေးရန် — Affiliated vs Independent</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/01-type-selection',
        'alt' => 'ကတ် ၂ ခု ပါသော အုပ်စုအမျိုးအစား ရွေးချယ်ရေးစာမျက်နှာ',
        'caption' => 'Group Type Selection — Affiliated သို့မဟုတ် Independent ရွေးပါ',
        'callouts' => [
            '<strong>Affiliated:</strong> အလုပ်ရှင် ၁ ဦးနှင့် ချည်နှောင်ထား — အုပ်စုထဲရှိ ဝန်ထမ်းများသည် ထိုအလုပ်ရှင်၏ ဝန်ထမ်းဖြစ်ရမည်',
            '<strong>Independent:</strong> အလုပ်ရှင်နှင့် မချည်နှောင်ထား — အလုပ်ရှင်မည်သူ့ဝန်ထမ်းမဆို ထည့်နိုင်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Group & Team</strong></li>
            <li>Affiliated သို့မဟုတ် Independent ရွေးပါ</li>
            <li>Affiliated ဖြစ်ပါက → အလုပ်ရှင်ကို အရင်ရွေးပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">အုပ်စု ဖန်တီးရန် + အဖွဲ့ဝင်ထည့်ရန် + အဖွဲ့ခွဲများ</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/02-manage',
        'alt' => 'အဖွဲ့ခွဲ accordion ပါသော Manage Groups စာမျက်နှာ',
        'caption' => 'Manage Groups — အုပ်စုတစ်ခုစီ၏ accordion + ၎င်း၏ အဖွဲ့ခွဲများ',
        'callouts' => [
            '<strong>+ Create New Group:</strong> အမည်ပေးပါ → အတည်ပြုပါ',
            '<strong>+ Add Member:</strong> ဝန်ထမ်း ရှာပါ → ခြစ်ပါ → အတည်ပြုပါ',
            '<strong>+ Create Sub-Team:</strong> အုပ်စုကို အဖွဲ့ခွဲများအဖြစ် ခွဲရန်',
            '<strong>Drag & Drop:</strong> ဝန်ထမ်းများကို အဖွဲ့များအကြား ဆွဲယူရန်',
            '<strong>Highlight pulse:</strong> နောက်ဆုံးထည့်သော အဖွဲ့သည် လိမ္မော်ရောင်ဖြင့် တဖျပ်ဖျပ် ပြသည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>"+ Create New Group" ကို နှိပ်ပါ → အမည်ပေးပါ</li>
            <li>"+ Add Member" ကို နှိပ်ပါ → ရှာပါ → ခြစ်ပါ → အတည်ပြုပါ</li>
            <li>အုပ်စုကို နောက်ထပ် ခွဲလိုပါက "+ Create Sub-Team" ကို နှိပ်ပါ</li>
            <li>ဝန်ထမ်းများကို အဖွဲ့များအကြား ဆွဲယူနိုင်သည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">အလုပ်ဖန်တီးသောအခါ အုပ်စုကို အသုံးပြုရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/03-use-in-workflow',
        'alt' => 'Production / Workflow item ဖန်တီးသောအခါ Group Name အသုံးပြုခြင်း',
        'caption' => 'Group Name — Production/Workflow item သတ်မှတ်သောအခါ အသုံးပြုသည်',
        'callouts' => [
            '<strong>"Group Name" ကွက်လပ်:</strong> အလုပ်ဖန်တီးသော ဖောင်တိုင်းတွင် ရှိသည်',
            '<strong>Auto-link:</strong> စနစ်သည် အုပ်စု၏ ဝန်ထမ်းများကို အတူတကွ ဆွဲယူပေးသည်',
            '<strong>တစ်ခုတည်းအဖြစ် စီမံရန်:</strong> အစုလိုက် ငွေတောင်းခံခြင်း + အစုလိုက် PDF ဖန်တီးခြင်း',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Production / Workflow ရှိ အလုပ်ဖန်တီးရေးဖောင်ကို ဖွင့်ပါ</li>
            <li>ဖန်တီးထားသော အုပ်စုနှင့် ကိုက်ညီသော Group Name ကို သတ်မှတ်ပါ</li>
            <li>စနစ်သည် ၎င်းတို့ကို အလိုအလျောက် ချိတ်ဆက်ပေးပါလိမ့်မည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: ဝန်ထမ်းတစ်ဦးသည် အုပ်စု မည်မျှ ပါဝင်နိုင်သနည်း?</dt>
        <dd>ဖြေ: တစ်ချိန်တည်းတွင် များစွာ ပါဝင်နိုင်သည် — ဥပမာ- Employer A ၏ Affiliated + Independent "25/3 အင်တာဗျူး" တစ်ပြိုင်နက်</dd>

        <dt>မေး: ဝန်ထမ်းတစ်ဦး အလုပ်ရှင် ပြောင်းသွားသည် — မူလ Affiliated အုပ်စု ဘာဖြစ်သွားမည်နည်း?</dt>
        <dd>ဖြေ: အလုပ်ရှင်နှင့် ချည်နှောင်ထားသောကြောင့် မူလ Affiliated အုပ်စုမှ <strong>အလိုအလျောက် ဖယ်ရှားခံရသည်</strong> — Independent အုပ်စုများကို မထိခိုက်ပါ</dd>

        <dt>မေး: အုပ်စုနှစ်ခု အမည်တူနိုင်ပါသလား?</dt>
        <dd>ဖြေ: အလုပ်ရှင်တစ်ဦးတည်းအောက်တွင် မတူနိုင်ပါ — စနစ်က သတိပေးပါလိမ့်မည်</dd>
    </dl>
</section>
