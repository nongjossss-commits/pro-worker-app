{{-- Training Edition: Pre-Production (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-clipboard-data-fill"></i> {{ __('P Production (Pre-Production)') }} — {{ __('The document-prep hub before entering Workflow') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Pre-Production"</strong> မီနူးသည် Workflow သို့ ပို့မီ <strong>စာရွက်စာတမ်းများ</strong>နှင့် ဖောက်သည်ဒေတာကို ပြင်ဆင်သည့်နေရာ ဖြစ်သည်။
        Sales တွင် ရောင်းချမှု ပြီးစီးသော ဖောက်သည်အသစ်များအတွက် အသုံးပြုသည် → Pre-Prod ပြင်ဆင်ရန် → ဆက်လက်ဆောင်ရွက်ရန် Workflow သို့ ပို့ရန်
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
    <h2 class="slide-title">Pre-Production စာမျက်နှာသို့ ဝင်ရောက်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/01-main-view',
        'alt' => 'အလုပ်ရှင် အလုပ်ကတ်များ ပြသော Pre-Production ပင်မစာမျက်နှာ',
        'caption' => 'Pre-Production ပင်မမြင်ကွင်း — ကတ်တစ်ခုစီသည် အလုပ်ရှင်တစ်ဦး၏ အလုပ်ဖြစ်သည်',
        'callouts' => [
            '<strong>အပေါ်ပိုင်းရှိ အနှစ်ချုပ်ကတ်များ:</strong> သတ်မှတ်ရက်နီးလာ / ဆောင်ရွက်နေဆဲ / စစ်ဆေးရန် စောင့်ဆိုင်းနေသည်',
            '<strong>စစ်ထုတ်ခြင်း:</strong> အလုပ်ရှင် / အလုပ်ပိုင်ရှင် / အလုပ်အမျိုးအစား (MOU/ဗီဇာ)',
            '<strong>အလုပ်ကတ်:</strong> အရောင်းဝန်ထမ်း ဓာတ်ပုံ + အလုပ်ရှင်အမည် + ဝန်ထမ်းအရေအတွက် + အခြေအနေ',
            '<strong>နောက်ဆုံး ကတ်သည် အပေါ်ဆုံးသို့ ရွှေ့သွားသည်:</strong> လတ်တလော လှုပ်ရှားမှု ရှိတိုင်း (အဆင့်ခြစ်ခြင်း/ဒေတာပြင်ဆင်ခြင်း)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Pre-Production</strong></li>
            <li>အပေါ်ပိုင်းရှိ အနှစ်ချုပ်ကတ်များကို စစ်ဆေးပါ</li>
            <li>အလုပ်ရှင် သို့မဟုတ် ပိုင်ရှင်ဖြင့် ကျဉ်းမြောင်းစေရန် စစ်ထုတ်ချက်များကို အသုံးပြုပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">အလုပ်ကို ဖွင့်ပြီး ဝန်ထမ်းတစ်ဦးချင်း ပြင်ဆင်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/02-edit-job',
        'alt' => 'တဘ်များစွာ ပါသော Edit Job စာမျက်နှာ: ဝန်ထမ်း၊ စာရွက်စာတမ်း၊ ငွေကြေး',
        'caption' => 'Edit Job စာမျက်နှာ — တဘ်များ: Employees / Documents / Finance / Timeline',
        'callouts' => [
            '<strong>Tab Bar:</strong> Employee / Document / Financial / Timeline ကြား ပြောင်းရန်',
            '<strong>Employee Card:</strong> ဝန်ထမ်းတစ်ဦးစီတွင် ပြင်ဆင်ရန် + စာရွက်စာတမ်းကြည့်ရန် ခလုတ်များ ရှိသည်',
            '<strong>Document Scanner:</strong> ကင်မရာမှ ဓာတ်ပုံကို တိုက်ရိုက် စနစ်ထဲသို့ ရိုက်ယူရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အလုပ်ရှင်၏ အလုပ်ကတ်ကို နှိပ်ပါ → Edit Job စာမျက်နှာကို ဖွင့်ပေးသည်</li>
            <li><strong>"Employees"</strong> တဘ်ကို ရွေးပါ</li>
            <li>ဝန်ထမ်းတစ်ဦးစီ၏ ဒေတာကို ပြင်ဆင်ရန် ✏️ ခလုတ်ကို နှိပ်ပါ</li>
            <li>Upload သို့မဟုတ် Document Scanner မှတစ်ဆင့် စာရွက်စာတမ်းများ တင်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">ကွက်လပ်ထပ်တိုး ထည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/03-custom-fields',
        'alt' => 'အထူးအလုပ်တစ်ခုအတွက် ကွက်လပ်ထပ်တိုး ထည့်ရေး modal',
        'caption' => 'Custom Fields — ဤအလုပ်နှင့် သီးသန့်သော ကွက်လပ်များ ထည့်ရန်',
        'callouts' => [
            '<strong>"Fields" ခလုတ်:</strong> MOU ကတ်ပေါ်တွင်',
            '<strong>ကွက်လပ်အသစ် ထည့်ရန်:</strong> ဥပမာ- "ဆေးလက်မှတ်နံပါတ်"၊ "ချိန်းဆိုမှုရက်"',
            '<strong>အမျိုးအစား သတ်မှတ်ရန်:</strong> text / number / date / dropdown',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အလုပ်ကတ်ပေါ်ရှိ <strong>"Fields"</strong> ခလုတ်ကို နှိပ်ပါ</li>
            <li>"+ Add New Field" ကို နှိပ်ပါ</li>
            <li>ကွက်လပ်ကို အမည်ပေးပြီး အမျိုးအစား ရွေးပါ → Save</li>
            <li>ကွက်လပ်အသစ်သည် ဝန်ထမ်းတစ်ဦးစီ၏ Custom Fields တဘ်တွင် ပေါ်လာပါမည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">ငွေကြေးတဘ်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/04-financial-tab',
        'alt' => 'စျေးနှုန်းအဆင့်ကတ်များ ပါသော ငွေကြေးတဘ်',
        'caption' => 'Financial Tab — စျေးနှုန်းအဆင့်များ + အရစ်ကျများ + ငွေတောင်းခံခြင်း ဖန်တီးရန်',
        'callouts' => [
            '<strong>+ Add Tab:</strong> အလုပ်တစ်ခုလျှင် ငွေကြေးတဘ်များစွာ ဖန်တီးရန် (ဥပမာ- "ဝန်ဆောင်မှုကြေး"၊ "အလုပ်ရှင်ပြောင်းလဲမှု အရစ်ကျ")',
            '<strong>Pricing Tiers:</strong> တစ်ဦးချင်း စျေးနှုန်းကို အဆင့်လိုက် + လူဦးရေ + မှတ်ချက် သတ်မှတ်ရန်',
            '<strong>မှတ်ချက် popup:</strong> မှတ်ချက်ကို နှိပ်ပါ → စာလုံး 500 ရေတွက်ကိရိယာ ပါသော popup ကြီး',
            '<strong>ခဲတံ / အမှိုက်ပုံး ခလုတ်များ:</strong> အဆင့်ကို ပြင်ဆင်/ဖျက်ရန် (ဖျက်ရန် အတည်ပြုချက် တောင်းသည်)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Edit Job ကို ဖွင့်ပါ → <strong>"Financial"</strong> တဘ် သို့မဟုတ် "Finance" ခလုတ်ကို နှိပ်ပါ</li>
            <li><strong>"+ Add Tab"</strong> ကို နှိပ်ပါ → အမည်ပေးပါ (အလွတ်/ထပ်နေခြင်း မရှိရပါ)</li>
            <li>"Per-head" စနစ် ရွေးပါ → Pricing Tier ထည့်ပါ</li>
            <li><strong>မှတ်ချက်ဘောက်စ်</strong> ကို နှိပ်ပါ → ရိုက်ထည့်ရန် popup ပွင့်လာသည်</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>ဤမှတ်ချက်သည် ငွေတောင်းခံလွှာ/ငွေလက်ခံလွှာများပေါ်တွင်လည်း ပေါ်လာသည်</strong> — ဖောက်သည်ကို ဤအခကြေးငွေသည် မည်သည့်အတွက်ဖြစ်ကြောင်း ရှင်းပြရန် အသုံးပြုပါ
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">အလုပ်ကို Workflow သို့ ပို့ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/05-send-to-workflow',
        'alt' => 'Send to Workflow + Bulk Send ခလုတ်များ',
        'caption' => 'Send to Workflow — တစ်ဦးချင်း ပို့ရန် သို့မဟုတ် အားလုံးကို တစ်ပြိုင်နက် ပို့ရန်',
        'callouts' => [
            '<strong>Send to Workflow:</strong> အလုပ်ကို Workflow လုပ်ငန်းစဉ်ထဲသို့ ပို့သည်',
            '<strong>Bulk Send:</strong> MOU အသုတ်တစ်ခုလုံးကို တစ်ချက်နှိပ်ရုံဖြင့် ပို့ရန်',
            '<strong>ခွင့်ပြုချက်:</strong> approve-production သာ (Admin/Super Admin)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>စာရွက်စာတမ်း + ဒေတာ အသင့်ဖြစ်ကြောင်း သေချာပါစေ</li>
            <li><strong>"Send to Workflow"</strong> (တစ်ဦးချင်း) သို့မဟုတ် <strong>"Send Whole Batch"</strong> (Bulk) ကို နှိပ်ပါ</li>
            <li>အလုပ်သည် <strong>Workflow</strong> မီနူးထဲသို့ ရွှေ့သွားပါမည်</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>သတိပြုရန်:</strong> Workflow သို့ ပို့ပြီးသော အလုပ်ကို Pre-Prod တွင် ထပ်မံပြင်ဆင်၍ မရတော့ပါ — Workflow တွင်သာ ပြင်ဆင်ရမည်
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: "Send to Workflow" ခလုတ်ကို ဘာကြောင့် မမြင်ရသနည်း?</dt>
        <dd>ဖြေ: သင့်အခန်းကဏ္ဍကို စစ်ဆေးပါ — <code>approve-production</code> ခွင့်ပြုချက် လိုအပ်သည် (Admin/Super Admin)</dd>

        <dt>မေး: Pre-Prod ကာလအတွင်း ဝန်ထမ်းတစ်ဦး အလုပ်ထွက်သွားသည်?</dt>
        <dd>ဖြေ: ထိုဝန်ထမ်းကို Pre-Prod အလုပ်မှ ဖယ်ရှားပါ၊ အားလုံး အလုပ်ထွက်သွားပါက အလုပ်တစ်ခုလုံးကို Cancel လုပ်ပါ</dd>

        <dt>မေး: ဝန်ထမ်းတစ်ဦးသည် Pre-Prod အလုပ် များစွာတွင် ပါဝင်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: Work Type မတူပါက ပါဝင်နိုင်ပါသည် (ဥပမာ- MOU + ဗီဇာသက်တမ်းတိုးကို တစ်ပြိုင်နက်) — Work Type တူညီပါက ထပ်ခွင့်မရှိပါ</dd>
    </dl>
</section>
