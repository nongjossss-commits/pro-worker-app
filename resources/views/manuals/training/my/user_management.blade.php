{{-- Training Edition: User Management (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('User Management') }} — {{ __('Create / edit users + assign roles + permissions') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"User Management"</strong> မီနူးကို ရုံးဝန်ထမ်း + ဖောက်သည် (employer role) များအတွက်
        <strong>အသုံးပြုသူ အကောင့်များ</strong> ဖန်တီး/ပြင်ဆင်ရန် + <strong>role များ</strong> သတ်မှတ်ရန် + <strong>ခွင့်ပြုချက်များ</strong> ချိန်ညှိရန် အသုံးပြုသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin (ကန့်သတ်ထားသည်)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">အသုံးပြုသူ အသစ် ထည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/01-create-user',
        'alt' => 'အသုံးပြုသူအသစ် ဖန်တီးရေးဖောင် + role ရွေးစရာ',
        'caption' => 'New User Form — ဒေတာဖြည့်ပြီး role ရွေးရန်',
        'callouts' => [
            '<strong>အမည် + အီးမေးလ်:</strong> ဝင်ရောက်ရန် အသုံးပြုသည်',
            '<strong>စကားဝှက်:</strong> default တစ်ခု၊ သို့မဟုတ် ကိုယ်ပိုင် သတ်မှတ်နိုင်သည်',
            '<strong>Role:</strong> super-admin / admin / staff / caretaker / employer',
            '<strong>ချိတ်ဆက်ထားသော Employer:</strong> role = employer အတွက်သာ (မည်သည့်အလုပ်ရှင်နှင့် ချိတ်ဆက်ထားသနည်း)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>User Management</strong></li>
            <li>"+ Add User" ကို နှိပ်ပါ</li>
            <li>အမည် + အီးမေးလ် + စကားဝှက် ဖြည့်ပါ</li>
            <li>Role + ချိတ်ဆက်ထားသော Employer (employer ဖြစ်ပါက) ရွေးပါ</li>
            <li>Save ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">အဓိက Role ၅ ခု + ခွင့်ပြုချက်များ</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/02-roles-permissions',
        'alt' => 'Role စာရင်း + ခွင့်ပြုချက် matrix',
        'caption' => 'Roles & Permissions — role ၅ ခု + ခွင့်ပြုချက် ၂၇ ခုကျော်',
        'callouts' => [
            '<strong>super-admin:</strong> ဝင်ရောက်ခွင့် အပြည့်အစုံ (root)',
            '<strong>admin:</strong> ဒေတာအားလုံးကို စီမံသည်၊ သို့သော် ခွင့်ပြုချက်/role ပြင်ဆင်ခွင့် မရှိပါ',
            '<strong>staff:</strong> နေ့စဉ် လုပ်ငန်း — ဒေတာ ရိုက်သွင်း/ပြင်ဆင်ခြင်း',
            '<strong>caretaker:</strong> ၎င်းအား သတ်မှတ်ထားသော အလုပ်ရှင်များကိုသာ မြင်ရသည်',
            '<strong>employer:</strong> ဖောက်သည် — ၎င်း၏ ဒေတာကိုသာ မြင်ရသည်',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Employer တစ်ဦးအတွက် Caretaker သတ်မှတ်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/03-caretaker-assign',
        'alt' => 'Employer Edit စာမျက်နှာ → Caretakers tab',
        'caption' => 'Caretaker Assignment — အလုပ်ရှင်တစ်ဦးစီအတွက် caretaker အသုံးပြုသူများ ရွေးရန်',
        'callouts' => [
            '<strong>Caretakers tab:</strong> Employer Edit စာမျက်နှာတွင်',
            '<strong>Multi-select:</strong> အလုပ်ရှင်တစ်ဦးတွင် caretaker များစွာ ရှိနိုင်သည်',
            '<strong>Caretaker သည် ၎င်းအား သတ်မှတ်ထားသည်ကိုသာ မြင်ရသည်:</strong> ဒေတာ ယိုစိမ့်မှု ကာကွယ်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Employers မီနူး → အလုပ်ရှင်တစ်ဦး ရွေးပါ → Caretakers tab</li>
            <li>caretaker အသုံးပြုသူများ ရွေးပါ (multi-select)</li>
            <li>Save ကို နှိပ်ပါ</li>
            <li>ထို Caretaker များသည် ယခုအခါ ၎င်းတို့၏ sidebar တွင် ဤအလုပ်ရှင်ကို မြင်ရမည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: Role အသစ် ထည့်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: Spatie Permission package မှတစ်ဆင့် ဖြစ်နိုင်သည် — Super Admin သာ</dd>

        <dt>မေး: အသုံးပြုသူ၏ စကားဝှက် ပြန်လည်သတ်မှတ်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: ရနိုင်ပါသည် — Edit user → စကားဝှက်အသစ် ရိုက်ပါ → Save</dd>

        <dt>မေး: အသုံးပြုသူတစ်ဦးကို ဖျက်ပါက ၎င်းဖန်တီးထားသော ဒေတာ ဘာဖြစ်သွားမည်နည်း?</dt>
        <dd>ဖြေ: ဒေတာ ကျန်ရှိနေသည် — အသုံးပြုသူ အကောင့်ကိုသာ ဖျက်သည် (audit log တွင် ဆက်လက် မြင်ရသည်)</dd>
    </dl>
</section>
