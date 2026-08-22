{{-- Training Edition: Ticket Inbox (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-inbox-fill"></i> {{ __('Ticket Inbox') }} — {{ __('Receive and manage requests from employers') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"တောင်းဆိုချက် စာပုံး"</strong> မီနူးသည် အလုပ်ရှင်များ တင်သွင်းသော <strong>တောင်းဆိုချက်များ</strong> ရောက်ရှိသောနေရာ ဖြစ်သည် —
        ဥပမာ- "ဤဝန်ထမ်း၏ ဗီဇာ သက်တမ်းတိုးရန် တောင်းဆိုသည်"၊ "နိုင်ငံကူးလက်မှတ် ပြောင်းလဲရန် တောင်းဆိုသည်" — Admin/Staff က လက်ခံ + တာဝန်ပေး + ခြေရာခံသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (manage-tickets)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">တောင်းဆိုချက်အသစ် လက်ခံရန် + တာဝန်ပေးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'ticket_inbox/01-list-assign',
        'alt' => 'တောင်းဆိုချက်စာရင်း + တာဝန်ပေးရေး dropdown',
        'caption' => 'တောင်းဆိုချက် စာပုံး — အခြေအနေအလိုက် စာရင်း',
        'callouts' => [
            '<strong>အခြေအနေ:</strong> Open / In Progress / Resolved / Closed',
            '<strong>တာဝန်ပေးထားသူ:</strong> ဤတောင်းဆိုချက်ကို တာဝန်ယူသော ဝန်ထမ်း',
            '<strong>ဦးစားပေးအဆင့်:</strong> Normal / High / Urgent',
            '<strong>အမျိုးအစား:</strong> visa / wp / passport / အခြား',
            '<strong>နောက်ဆုံး update:</strong> နောက်ဆုံး ပြန်ကြားချက် အချိန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>တောင်းဆိုချက် စာပုံး</strong></li>
            <li>တောင်းဆိုချက်ကို ဖွင့်ရန် နှိပ်ပါ</li>
            <li>"Assign to..." ကို နှိပ်ပါ → တာဝန်ယူမည့် ဝန်ထမ်းကို ရွေးပါ</li>
            <li>လုပ်ငန်း တိုးတက်မှုအလိုက် အခြေအနေကို update လုပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ပြန်ကြားရန် + စာရွက်စာတမ်း ပူးတွဲရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'ticket_inbox/02-chat',
        'alt' => 'တောင်းဆိုချက် ပြန်ကြားရေးစာမျက်နှာ + စကားပြောဆိုမှု',
        'caption' => 'တောင်းဆိုချက် အသေးစိတ် — စကားပြောဆိုမှု + ပူးတွဲဖိုင်များ',
        'callouts' => [
            '<strong>မက်ဆေ့ချ် စကားဝိုင်း:</strong> ရုံး ↔ အလုပ်ရှင် အကြား မက်ဆေ့ချ်များ',
            '<strong>စာရွက်စာတမ်း ပူးတွဲရန်:</strong> PDF/ပုံများ တင်ပါ',
            '<strong>ဝန်ထမ်း ပူးတွဲရန်:</strong> ဝန်ထမ်းတစ်ဦးကို တောင်းဆိုချက်နှင့် ချိတ်ဆက်ပါ',
            '<strong>ဖြေရှင်းပြီးအဖြစ် အမှတ်အသားပြုရန်:</strong> ပြီးစီးပါက တောင်းဆိုချက်ကို ပိတ်ပါ',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: ကျွန်ုပ် ပြန်ကြားသောအခါ ဖောက်သည် အကြောင်းကြားခံရပါသလား?</dt>
        <dd>ဖြေ: ပြန်ကြားချက် ရှိတိုင်း စနစ်သည် အကြောင်းကြားချက် + အီးမေးလ်ကို အလိုအလျောက် ပို့ပေးသည်</dd>

        <dt>မေး: တောင်းဆိုချက်ကို ပြန်လည်တာဝန်ပေးနိုင်ပါသလား?</dt>
        <dd>ဖြေ: ပြုလုပ်နိုင်ပါသည် — Admin သည် တာဝန်ယူသော ဝန်ထမ်းကို အချိန်မရွေး ပြောင်းနိုင်သည်</dd>
    </dl>
</section>
