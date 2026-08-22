{{-- Training Edition: Notifications (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-bell-fill"></i> {{ __('Notifications') }} — {{ __('The hub for every kind of alert') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"အကြောင်းကြားချက်များ"</strong> မီနူးသည် စနစ်ရှိ <strong>သတိပေးချက်တိုင်း</strong>ကို စုစည်းသည် —
        ဥပမာ- သက်တမ်းကုန်ဆုံးခါနီး ဝန်ထမ်း၊ အတည်ပြုပြီး ဈေးနှုန်းပြသလွှာ၊ ဖောက်သည် တောင်းဆိုချက်အသစ်။
        <strong>Web Push</strong> (browser popup notification) + <strong>အက်ပ်ထဲ ခေါင်းလောင်းအိုင်ကွန်</strong> ကို ပံ့ပိုးသည်
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
    <h2 class="slide-title">အကြောင်းကြားချက်များ ကြည့်ရှုရန် + ဖတ်ပြီးအဖြစ် အမှတ်အသားပြုရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/01-list',
        'alt' => 'အကြောင်းကြားချက်စာရင်း + ဖတ်ရသေး/အားလုံး စစ်ထုတ်ခြင်း',
        'caption' => 'အကြောင်းကြားချက်စာရင်း — ဖတ်ရသေး/ဖတ်ပြီး ခွဲထားသည်',
        'callouts' => [
            '<strong>ခေါင်းလောင်း အိုင်ကွန်:</strong> navbar ညာဘက်အပေါ်ထောင့် — ဖတ်ရသေးအရေအတွက် badge ပြသည်',
            '<strong>စစ်ထုတ်ခြင်း:</strong> ဖတ်ရသေး / အားလုံး / အမျိုးအစားအလိုက်',
            '<strong>အကြောင်းကြားချက်ကို နှိပ်ခြင်း:</strong> ဆက်စပ်အရာကို တိုက်ရိုက် ဖွင့်ပေးသည်',
            '<strong>အားလုံးကို ဖတ်ပြီးအဖြစ် အမှတ်အသားပြုရန်:</strong> badge ရေတွက်ကိန်းကို ရှင်းလင်းသည်',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">အကြောင်းကြားချက် အမျိုးအစားများ</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/02-types',
        'alt' => 'အကြောင်းကြားချက် အမျိုးအစားအမျိုးမျိုး၏ နမူနာများ',
        'caption' => 'အကြောင်းကြားချက် အမျိုးအစားများ — မတူညီသော အရောင်နှင့် အိုင်ကွန်များ',
        'callouts' => [
            '<strong>🔴 သက်တမ်းကုန်ဆုံးမှု သတိပေးချက်:</strong> သက်တမ်းကုန်ဆုံးခါနီး ဝန်ထမ်းများ (နိုင်ငံကူးလက်မှတ်/ဗီဇာ/WP)',
            '<strong>🔵 တောင်းဆိုချက်:</strong> ဖောက်သည်က တောင်းဆိုချက်အသစ် ပို့ခဲ့သည်',
            '<strong>🟢 အတည်ပြုပြီး:</strong> ဈေးနှုန်းပြသလွှာ / စာချုပ် အတည်ပြုပြီး',
            '<strong>🟡 Workflow:</strong> အလုပ်တစ်ခု အဆင့်အသစ်သို့ ဝင်ရောက်သည်',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Web Push Notification ဖွင့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/03-web-push',
        'alt' => 'web push ခွင့်ပြုချက် တောင်းသော browser popup',
        'caption' => 'Web Push — browser ပိတ်ထားလျှင်ပင် သတိပေးချက်များ လက်ခံရရှိသည်',
        'callouts' => [
            '<strong>ခွင့်ပြုချက် popup:</strong> ပထမဆုံး အကောင့်ဝင်သည့်အခါ ပေါ်လာသည်',
            '<strong>"Allow":</strong> browser မှတစ်ဆင့် အကြောင်းကြားချက်များ လက်ခံရန်',
            '<strong>နောက်ခံ:</strong> tab ပိတ်ထားလျှင်ပင် ဆက်လက်အလုပ်လုပ်နေမည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>အကောင့်ဝင်ပါ → browser က ခွင့်ပြုချက် တောင်းပါမည်</li>
            <li><strong>"Allow"</strong> ကို နှိပ်ပါ</li>
            <li>အကြောင်းကြားချက် ရောက်ရှိလာသောအခါ → browser တွင် ချက်ချင်း ပေါ်လာပါမည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: web push popup မတွေ့ရပါ?</dt>
        <dd>ဖြေ: Browser ဆက်တင်များ → site ခွင့်ပြုချက်များ → notification → ကိုယ်တိုင် ခွင့်ပြုပါ</dd>

        <dt>မေး: မည်သူတို့ အကြောင်းကြားချက် လက်ခံရရှိသနည်း?</dt>
        <dd>ဖြေ: အခန်းကဏ္ဍအပေါ် မူတည်သည် — Admin သည် အားလုံးကို မြင်ရပြီး Caretaker/Employer သည် ၎င်းတို့၏ ကိုယ်ပိုင်ကိုသာ မြင်ရသည်</dd>
    </dl>
</section>
