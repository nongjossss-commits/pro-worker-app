{{-- Training Edition: Employer Ticket (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-send-fill"></i> {{ __('Employer Ticket') }} — {{ __('For employers: send a request to the office') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Employer Ticket"</strong> မီနူးသည် <strong>Employer အခန်းကဏ္ဍ</strong>အတွက်
        ရုံးထံသို့ တောင်းဆိုချက်များ တိုက်ရိုက်ပို့ရန် ဖြစ်သည် — ဥပမာ- "ဗီဇာသက်တမ်းတိုးရန် တောင်းဆိုသည်"၊ "ဝန်ထမ်းအလုပ်ထုတ်ရန် တောင်းဆိုသည်" — အီးမေးလ်/Line အစား။
        ရုံးက ဤတောင်းဆိုချက်များကို <strong>တောင်းဆိုချက် စာပုံး</strong> မီနူးတွင် လက်ခံရရှိသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">တောင်းဆိုချက်အသစ် ဖန်တီးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employer_ticket/01-new-ticket',
        'alt' => 'တောင်းဆိုချက်အသစ် ဖန်တီးသောဖောင်',
        'caption' => 'တောင်းဆိုချက်အသစ် ဖောင် — အမျိုးအစား + အသေးစိတ် ရွေးပြီး စာရွက်စာတမ်း ပူးတွဲပါ',
        'callouts' => [
            '<strong>အမျိုးအစား:</strong> ဗီဇာ / အလုပ်လုပ်ခွင့်ပြုချက် / နိုင်ငံကူးလက်မှတ် / အခြား',
            '<strong>ဆက်စပ်ဝန်ထမ်း:</strong> မိမိ၏ ဝန်ထမ်းများထဲမှ ရွေးပါ',
            '<strong>အသေးစိတ်:</strong> လိုအပ်သည်ကို ဖော်ပြပါ',
            '<strong>ဖိုင် ပူးတွဲရန်:</strong> PDF / ပုံ (ရွေးချယ်နိုင်)',
            '<strong>ဦးစားပေးအဆင့်:</strong> Normal / High / Urgent',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar (Employer) → <strong>Employer Ticket</strong> သို့မဟုတ် "+ New Ticket"</li>
            <li>အမျိုးအစား + ဝန်ထမ်း ရွေးပါ</li>
            <li>အသေးစိတ် ဖြည့်ပြီး စာရွက်စာတမ်း ပူးတွဲပါ</li>
            <li>Submit ကို နှိပ်ပါ → ရုံးက ချက်ချင်း အကြောင်းကြားခံရပါမည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">အခြေအနေ ခြေရာခံရန် + ပြန်ကြားရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employer_ticket/02-status-chat',
        'alt' => 'တောင်းဆိုချက်စာရင်း + စကားပြောဆိုမှု',
        'caption' => 'My Tickets — အခြေအနေ ခြေရာခံပြီး ရုံးနှင့် စကားပြောရန်',
        'callouts' => [
            '<strong>အခြေအနေ:</strong> Open / In Progress / Resolved',
            '<strong>စကားပြောဆိုမှု:</strong> ရုံးနှင့် စကားပြောရန်',
            '<strong>အကြောင်းကြားချက်:</strong> ရုံးက ပြန်ကြားသောအခါ ပေါ်လာသည်',
            '<strong>ဖြေရှင်းပြီးအဖြစ် အမှတ်အသားပြုရန်:</strong> ကျေနပ်ပါက တောင်းဆိုချက်ကို ပိတ်ပါ',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: တောင်းဆိုချက် မည်မျှ တင်နိုင်သနည်း?</dt>
        <dd>ဖြေ: အကန့်အသတ်မရှိပါ — တောင်းဆိုချက်တစ်ခုစီသည် သီးခြားကိစ္စ ဖြစ်သည်</dd>

        <dt>မေး: အခြားကုမ္ပဏီများ၏ တောင်းဆိုချက်များကို ကြည့်ရှုနိုင်ပါသလား?</dt>
        <dd>ဖြေ: မရနိုင်ပါ — မိမိ၏ ကုမ္ပဏီကိုသာ ကြည့်ရှုနိုင်သည်</dd>

        <dt>မေး: ရုံးက ကျွန်ုပ်၏ တောင်းဆိုချက်ကို ပိတ်လိုက်သည်၊ သို့သော် မပြီးစီးသေးပါ?</dt>
        <dd>ဖြေ: တောင်းဆိုချက်အသစ်ဖွင့်ပြီး ဟောင်းသောတောင်းဆိုချက် နံပါတ်ကို ကိုးကားပါ</dd>
    </dl>
</section>
