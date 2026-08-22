{{-- Training Edition: Activity Logs (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-clock-history"></i> {{ __('Activity Logs') }} — {{ __('An audit trail of every change in the system') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"လုပ်ဆောင်ချက် မှတ်တမ်းများ"</strong> မီနူးသည် စနစ်ရှိ <strong>ဒေတာ ပြောင်းလဲမှု တိုင်း</strong>ကို မှတ်တမ်းတင်သည် —
        မည်သူက မည်သည့်အရာကို မည်သည့်အချိန်တွင် ပြောင်းလဲသည် — <strong>စစ်ဆေးရေး + နောက်ကြောင်းပြန် ပြန်လည်သုံးသပ်ရေး</strong>အတွက်၊
        ပွင့်လင်းမြင်သာမှု + လိမ်လည်မှု ကာကွယ်ရေးအတွက် ဖြစ်သည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">မှတ်တမ်း ကြည့်ရှုရန် + စစ်ထုတ်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'activity_logs/01-list-filter',
        'alt' => 'လုပ်ဆောင်ချက်မှတ်တမ်း စာရင်း + စစ်ထုတ်ရေးဘား',
        'caption' => 'လုပ်ဆောင်ချက် မှတ်တမ်းများ — စီစဉ်နိုင်သော ဇယား + စစ်ထုတ်ချက်များ',
        'callouts' => [
            '<strong>အသုံးပြုသူဖြင့် စစ်ထုတ်ခြင်း:</strong> သီးခြား အသုံးပြုသူကို ရွေးပါ',
            '<strong>ရက်စွဲဖြင့် စစ်ထုတ်ခြင်း:</strong> ရက်စွဲအပိုင်းအခြား သတ်မှတ်ပါ',
            '<strong>အမျိုးအစားဖြင့် စစ်ထုတ်ခြင်း:</strong> ဖန်တီး / ပြင်ဆင် / ဖျက်',
            '<strong>Entity ဖြင့် စစ်ထုတ်ခြင်း:</strong> Employee / Employer စသည်',
            '<strong>အသေးစိတ်:</strong> ပြောင်းလဲမှု မတိုင်မီ/နောက် ဒေတာများ ကြည့်ရှုနိုင်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Activity Logs</strong></li>
            <li>ရှာဖွေရန် စစ်ထုတ်ချက်များကို အသုံးပြုပါ</li>
            <li>မတိုင်မီ/နောက် အသေးစိတ်ကို ကြည့်ရန် လိုင်းကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">စစ်ဆေးရေး + စုံစမ်းစစ်ဆေးရေးအတွက် အသုံးပြုခြင်း</h2>

    <div class="slide-instructions">
        <strong>အသုံးများသော အခြေအနေများ:</strong>
        <ol>
            <li>ဝန်ထမ်းမှတ်တမ်း ပျောက်နေသည် — မည်သူက မည်သည့်အချိန်တွင် ဖျက်ခဲ့သည်ကို စစ်ဆေးပါ</li>
            <li>နိုင်ငံကူးလက်မှတ် ဒေတာ ပြောင်းလဲသည် — မည်သူက ပြင်ဆင်ခဲ့သည်ကို စစ်ဆေးပါ</li>
            <li>ဘေဂျင်နံပါတ်တစ်ခု ပယ်ဖျက်ခံရသည် — မည်သူက၊ ဘာကြောင့်ကို စစ်ဆေးပါ</li>
            <li>ဝန်ထမ်းတစ်ဦး၏ လစဉ်လုပ်ငန်းကို ပြန်လည်သုံးသပ်ခြင်း</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>အကြံပြုချက်:</strong> Activity Log ကို ဖျက်၍မရပါ — Super Admin ပင်လျှင် ပြင်ဆင်၍ မရပါ (ပြောင်းလဲမှု မလုပ်နိုင်ပါ)
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: မှတ်တမ်းသည် နောက်ကြောင်းပြန် မည်မျှ ကြာအောင် ရှိသနည်း?</dt>
        <dd>ဖြေ: အလိုအလျောက် ဖျက်ခြင်း မရှိပါ — စစ်ဆေးရေးအတွက် အမြဲတမ်း ထိန်းသိမ်းထားသည် (လိုအပ်ပါက Super Admin သည် ဟောင်းနွမ်းသောမှတ်တမ်းများကို ရှင်းနိုင်သည်)</dd>

        <dt>မေး: Staff/Caretaker သည် ၎င်းတို့၏ ကိုယ်ပိုင် မှတ်တမ်းများကို ကြည့်ရှုနိုင်ပါသလား?</dt>
        <dd>ဖြေ: မရနိုင်ပါ — Super Admin/Admin သာ Activity Log ကို ကြည့်ရှုနိုင်သည်</dd>
    </dl>
</section>
