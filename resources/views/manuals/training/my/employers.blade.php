{{-- Training Edition: Employers (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-building-fill"></i> {{ __('Employers') }} — {{ __('The master record of client companies hiring migrant workers') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Employers"</strong> မီနူးသည် ရွှေ့ပြောင်းအလုပ်သမား ငှားရမ်းသော <strong>အလုပ်ရှင်များ</strong> (ဖောက်သည် ကုမ္ပဏီများ) ၏ ဒေတာကို သိမ်းဆည်းသည်။
        ဤဒေတာကို ဝန်ထမ်း၊ စာရွက်စာတမ်း ဖန်တီးခြင်း၊ အခွန်ဘေဂျင်များ၊ စာချုပ်များတွင် အသုံးပြုသည် — စနစ်၏ အခြေခံအုတ်မြစ် ဖြစ်သည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (ကိုယ်ပိုင်ကိုသာ)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">မီနူးကို ဖွင့်ပြီး အလုပ်ရှင်စာရင်း ကြည့်ရှုရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/01-list',
        'alt' => 'စစ်ထုတ်ရေးဘား + အစဉ်နံပါတ်များ ပါသော အလုပ်ရှင်စာရင်း',
        'caption' => 'အလုပ်ရှင်စာရင်း — ကတ် + ဇယား မြင်ကွင်းဖြင့် ပြသသည်',
        'callouts' => [
            '<strong>+ Add Employer:</strong> အလုပ်ရှင်အသစ် ဖန်တီးရန်',
            '<strong>စစ်ထုတ်ခြင်း:</strong> ရှာဖွေခြင်း၊ တိုင်းဒေသကြီးဖြင့် စစ်ထုတ်ခြင်း၊ JobOwner ဖြင့် စစ်ထုတ်ခြင်း',
            '<strong>အစဉ်နံပါတ်:</strong> ကတ်၏ ညာဘက်အပေါ်ထောင့်ရှိ နံပါတ် (CSS counter)',
            '<strong>ကတ် / ဇယား ပြောင်းရန်:</strong> မြင်ကွင်း ပြောင်းလဲရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Employers</strong></li>
            <li>လိုချင်သော အလုပ်ရှင်ကို စစ်ထုတ် သို့မဟုတ် ရှာဖွေပါ</li>
            <li>ကတ်ကို နှိပ်၍ ပြင်ဆင်ရေးစာမျက်နှာသို့ သွားပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">အလုပ်ရှင်အသစ် ထည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/02-create-form',
        'alt' => 'အလုပ်ရှင်အသစ် ဖန်တီးသောဖောင်',
        'caption' => 'အလုပ်ရှင်အသစ် ဖောင် — အခြေခံအချက်အလက် + အခွန် ID ဖြည့်ပါ',
        'callouts' => [
            '<strong>ကုမ္ပဏီအမည် (ထိုင်း/အင်္ဂလိပ်):</strong> ဘာသာစကားနှစ်မျိုးလုံး',
            '<strong>အခွန် ID:</strong> ဂဏန်း ၁၃ လုံး',
            '<strong>လိပ်စာ:</strong> လိပ်စာ များစွာ ပံ့ပိုးသည် (မှတ်ပုံတင် / စာပို့လိပ်စာ)',
            '<strong>JobOwner:</strong> အမှန်တကယ် ဖောက်သည်စီမံသူ (ဥပမာ- Kung)',
            '<strong>Caretakers:</strong> ဤအလုပ်ရှင်ကို စီမံသော Caretaker အသုံးပြုသူများကို သတ်မှတ်ရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li><strong>"+ Add Employer"</strong> ကို နှိပ်ပါ</li>
            <li>ကုမ္ပဏီအသေးစိတ် + အခွန် ID + လိပ်စာ ဖြည့်ပါ</li>
            <li>JobOwner (အမှန်တကယ် စီမံသူ) ရွေးပါ</li>
            <li>Save ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">အလုပ်ရှင်ဒေတာ ပြင်ဆင်ရန် + ကိုယ်စားလှယ် ထည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/03-edit-detail',
        'alt' => 'အလုပ်ရှင် ပြင်ဆင်ရေးစာမျက်နှာ — အသေးစိတ်၊ လိပ်စာ၊ လက်မှတ်၊ ကိုယ်စားလှယ် တဘ်များ',
        'caption' => 'Edit Employer — တဘ်များစွာ: အသေးစိတ် / လိပ်စာများ / လက်မှတ် / ကိုယ်စားလှယ်များ',
        'callouts' => [
            '<strong>General Info တဘ်:</strong> အမည် + အခွန် ID + ဆက်သွယ်ရေးအချက်အလက်',
            '<strong>Addresses တဘ်:</strong> လိပ်စာများစွာ ထည့်ရန်',
            '<strong>Signature/Stamp တဘ်:</strong> လက်မှတ် + ကုမ္ပဏီတံဆိပ် တင်ရန်',
            '<strong>Delegates တဘ်:</strong> အလုပ်ရှင်ကိုယ်စား လက်မှတ်ရေးထိုးမည့် ကိုယ်စားလှယ်များ ထည့်ရန်',
            '<strong>Other Documents တဘ်:</strong> နေရာ ၃ ခု (Super Admin သတ်မှတ်ထားသော မူလအမည်များ)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အလုပ်ရှင်ကတ်ကို နှိပ်ပါ → ခဲတံ ✏️ ခလုတ်</li>
            <li>ပြင်ဆင်လိုသော တဘ်ကို ရွေးပါ</li>
            <li>သိမ်းဆည်းရန် Save ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">အစမ်းကြည့်ခြင်း + အမြန်လုပ်ဆောင်ချက်များ</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/04-preview-modal',
        'alt' => 'အလုပ်ရှင် အစမ်းကြည့်ချက် popup',
        'caption' => 'Preview Popup — ပြင်ဆင်ရေးစာမျက်နှာ မဖွင့်ဘဲ အသေးစိတ်အားလုံးကို လျင်မြန်စွာ ကြည့်ရန်',
        'callouts' => [
            '<strong>Preview 🔍 ခလုတ်:</strong> ဖတ်ရုံသာ ဒေတာ ကြည့်ရန်',
            '<strong>စာရင်းအင်း:</strong> အသုံးပြုနေဆဲ + အလုပ်ထုတ်ပြီးသော ဝန်ထမ်းအရေအတွက်၊ လူမျိုးအလိုက် ခွဲခြားထားသည်',
            '<strong>အသုံးပြုနေဆဲ ဝန်ထမ်းစာရင်း:</strong> ပထမ ၁၀ ဦး၊ စာမျက်နှာခွဲထားသည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အလုပ်ရှင်ကတ် → မှန်ဘီလူး 🔍 ခလုတ်</li>
            <li>ဒေတာ + ဝန်ထမ်းအရေအတွက် ကြည့်ပါ</li>
            <li>ဤအလုပ်ရှင်၏ ဝန်ထမ်းစာရင်းသို့ သွားရန် "View All" ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: ဝန်ထမ်းရှိသေးသော အလုပ်ရှင်ကို ဖျက်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: ဖျက်နိုင်ပါသည် — သို့သော် ဝန်ထမ်းများသည် မိဘမဲ့ ဖြစ်သွားနိုင်သည်၊ archive ကို အသုံးပြုပါ သို့မဟုတ် ဝန်ထမ်းများကို အခြားအလုပ်ရှင်သို့ အရင်လွှဲပြောင်းပါ</dd>

        <dt>မေး: JobOwner နှင့် Caretakers ဘယ်လိုကွာခြားသနည်း?</dt>
        <dd>ဖြေ: JobOwner = အမှန်တကယ် ဖောက်သည်စီမံသူ (ဥပမာ- Kung သည် ကုမ္ပဏီများစွာ စီမံသည်)၊ Caretaker = အသုံးပြုသူတစ်ဦးကို ဒေတာမြင်နိုင်ခွင့်ပေးရန် သတ်မှတ်သော စနစ်အခန်းကဏ္ဍ</dd>

        <dt>မေး: Caretaker သည် မည်သည့်အလုပ်ရှင်များကို မြင်ရသနည်း?</dt>
        <dd>ဖြေ: ထိုအလုပ်ရှင်၏ Caretakers တဘ်တွင် သတ်မှတ်ထားသော အလုပ်ရှင်များကိုသာ</dd>
    </dl>
</section>
