{{-- Training Edition: Incomplete Data (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ __('Incomplete Data') }} — {{ __('A tool for finding employees with missing data') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"အချက်အလက် မပြည့်စုံ"</strong> မီနူးသည် <strong>ဒေတာ ချို့တဲ့နေသော ဝန်ထမ်းများ</strong>ကို ရှာဖွေသည့် ကိရိယာ ဖြစ်သည် —
        ဥပမာ- နိုင်ငံကူးလက်မှတ် မရှိ၊ အလုပ်လုပ်ခွင့်ပြုချက် သက်တမ်းကုန်ဆုံးရက် မရှိ၊ ဓာတ်ပုံ မရှိ၊ လိပ်စာ မရှိ —
        ဒေတာကို အမှန်တကယ် အသုံးမပြုမီ အဖွဲ့က ပြင်ဆင်နိုင်ရန်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">မီနူးကို ဖွင့်ပြီး ဒေတာချို့တဲ့နေသော ဝန်ထမ်းများ ကြည့်ရှုရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'incomplete_data/01-list',
        'alt' => 'ဒေတာမပြည့်စုံသော ဝန်ထမ်းများ၏ စာရင်း',
        'caption' => 'အချက်အလက်မပြည့်စုံ စာရင်း — မည်သည့်ကွက်လပ်များ ချို့တဲ့နေသည်ကို ပြသသည်',
        'callouts' => [
            '<strong>ဝန်ထမ်း:</strong> အမည် + အလုပ်ရှင်',
            '<strong>ချို့တဲ့နေသော ကွက်လပ်များ:</strong> အနီရောင် badge က မည်သည့်ကွက်လပ် ချို့တဲ့သည်ကို ပြသသည်',
            '<strong>ပြင်ဆင်ရန် ✏️ ခလုတ်:</strong> ထိုဝန်ထမ်း၏ ပြင်ဆင်ရေးစာမျက်နှာသို့ တိုက်ရိုက် သွားရန် နှိပ်ပါ',
            '<strong>စစ်ထုတ်ခြင်း:</strong> ချို့တဲ့နေသော ကွက်လပ်အမျိုးအစားဖြင့် ရွေးချယ်ပါ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>အချက်အလက် မပြည့်စုံ</strong></li>
            <li>မည်သည့် ဝန်ထမ်းများတွင် ဒေတာချို့တဲ့ကာ မည်သည့်ကွက်လပ်များ ချို့တဲ့သည်ကို ကြည့်ပါ</li>
            <li>ပြင်ဆင်ရန်ကို နှိပ်ပါ → ဝန်ထမ်း၏ စာမျက်နှာသို့ သွားပါ → ဒေတာကို ဖြည့်ပါ</li>
            <li>ပြန်လာပြီး ထပ်စစ်ဆေးပါ — ဒေတာ ပြည့်စုံသွားပါက မှတ်တမ်းသည် ပျောက်ဆုံးသွားပါလိမ့်မည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: မည်သည့်ကွက်လပ်များကို "အချက်အလက် မပြည့်စုံ" ဟု သတ်မှတ်သနည်း?</dt>
        <dd>ဖြေ: စနစ်က စာရွက်စာတမ်းများ ဖန်တီးရန် လိုအပ်သော အရေးကြီးကွက်လပ်များ — နိုင်ငံကူးလက်မှတ်၊ လူမျိုး၊ employer_id၊ မွေးသက္ကရာဇ် စသည်</dd>

        <dt>မေး: ဖြည့်ပြီးသားဖြစ်သော်လည်း ဝန်ထမ်းသည် ဘာကြောင့် ဆက်ပေါ်နေသနည်း?</dt>
        <dd>ဖြေ: 60 စက္ကန့် cache ရှိသည် — ပြန်လည် update ဖြစ်အောင် စောင့်ပါ သို့မဟုတ် ကိုယ်တိုင် refresh လုပ်ပါ</dd>
    </dl>
</section>
