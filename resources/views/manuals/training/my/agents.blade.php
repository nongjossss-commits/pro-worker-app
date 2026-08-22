{{-- Training Edition: Agents (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Agents') }} — {{ __('Brokers who bring customers or employees to the office') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Agents"</strong> မီနူးသည် ရုံးထံသို့ ဖောက်သည် သို့မဟုတ် ဝန်ထမ်းများ ဆွဲယူပေးသော <strong>အကြားလှုပ်ရှားသူ/အေးဂျင့်များ</strong>၏ ဒေတာကို သိမ်းဆည်းသည် —
        အကြားလှုပ်ရှားသူ ခ + ကော်မရှင် ခြေရာခံရန်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Agents မီနူးကို ဖွင့်ပြီး အေးဂျင့် ထည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'agents/01-list-add',
        'alt' => 'အေးဂျင့်စာရင်း + ထည့်ရန်ခလုတ်',
        'caption' => 'Agents List + Add Modal',
        'callouts' => [
            '<strong>အေးဂျင့်အမည်:</strong> ပုဂ္ဂိုလ် သို့မဟုတ် အကြားလှုပ်ရှားရေးကုမ္ပဏီ၏ အမည်',
            '<strong>ဆက်သွယ်ရေး:</strong> ဖုန်း / အီးမေးလ် / Line',
            '<strong>ကော်မရှင်:</strong> အကြားလှုပ်ရှားသူ ခ ရာခိုင်နှုန်း',
            '<strong>မှတ်ချက်များ:</strong> သီးခြားအသေးစိတ် ထည့်ရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Agents</strong></li>
            <li>"+ Add Agent" ကို နှိပ်ပါ</li>
            <li>အသေးစိတ် ဖြည့်ပါ → Save ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: Agent နှင့် Importer ဘယ်လိုကွာခြားသနည်း?</dt>
        <dd>ဖြေ: Agent = ဖောက်သည်များ ဆွဲယူပေးသော အကြားလှုပ်ရှားသူ (ထိုင်း broker)၊ Importer = လုပ်သားတင်သွင်းသော ကုမ္ပဏီ (စာရွက်စာတမ်းများတွင် လက်မှတ်/တံဆိပ် အသုံးပြုသည်)</dd>

        <dt>မေး: Agent ကို Employer နှင့် ချိတ်ဆက်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: ချိတ်ဆက်နိုင်ပါသည် — Employer တွင် Agent ရွေးရန် "ညွှန်းပို့သူ Agent" ကွက်လပ် ရှိသည်</dd>
    </dl>
</section>
