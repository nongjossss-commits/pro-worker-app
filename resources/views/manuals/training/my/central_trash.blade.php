{{-- Training Edition: Central Trash (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-trash-fill"></i> {{ __('Central Trash') }} — {{ __('Restore deleted data') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Central Trash"</strong> မီနူးသည် စနစ်တစ်ခုလုံးမှ <strong>ဖျက်လိုက်သော ဒေတာ</strong>များကို စုစည်းသည်
        (ဝန်ထမ်း / အလုပ်ရှင် / Production / စသည်) — သတ်မှတ်ကာလအတွင်း <strong>ပြန်လည်ရယူနိုင်သည်</strong>၊
        သို့မဟုတ် မလိုအပ်ပါက အပြီးတိုင် ဖျက်နိုင်သည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">ဖျက်လိုက်သော အရာများ စာရင်း ကြည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'central_trash/01-list',
        'alt' => 'အမျိုးအစားအလိုက် ခွဲထားသော trash items စာရင်း',
        'caption' => 'Central Trash — entity အမျိုးအစားအလိုက် tab များ ခွဲထားသည်',
        'callouts' => [
            '<strong>Tab များ:</strong> Employees / Employers / Production / စသည်',
            '<strong>ဖျက်ထားသည့်ရက်:</strong> ဖျက်ခဲ့သည့် ရက်စွဲ',
            '<strong>ဖျက်သူ:</strong> မည်သူက ဖျက်ခဲ့သနည်း',
            '<strong>ကျန်ရှိရက်:</strong> အလိုအလျောက် ဖျက်ရန်အထိ ကျန်ရှိသောရက်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Central Trash</strong></li>
            <li>ဖျက်လိုက်သော ဒေတာအမျိုးအစားအတွက် tab ရွေးပါ</li>
            <li>ပြန်လည်ရယူလိုသော item ကို ရှာပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ပြန်လည်ရယူရန် သို့မဟုတ် အပြီးတိုင် ဖျက်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'central_trash/02-restore-delete',
        'alt' => 'အတည်ပြုချက်ပါသော Restore + Delete Forever ခလုတ်များ',
        'caption' => 'Restore / Delete Forever — နှစ်ခုစလုံး အတည်ပြုချက် လိုအပ်သည်',
        'callouts' => [
            '<strong>♻️ Restore:</strong> ဒေတာကို ပုံမှန် အသုံးပြုမှုသို့ ပြန်ခေါ်ဆောင်သည်',
            '<strong>🗑️ Delete Forever:</strong> အပြီးတိုင် ဖျက်သည် — ပြန်လည်ရယူ၍ မရပါ!',
            '<strong>အတည်ပြုချက် dialog:</strong> လုပ်ဆောင်ချက် နှစ်ခုစလုံး အတည်ပြုချက် လိုအပ်သည်',
            '<strong>Bulk action:</strong> item များစွာကို တစ်ပြိုင်နက် ရွေးနိုင်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>ပြန်လည်ရယူလိုသော item ကို ရှာပါ → <strong>♻️ Restore</strong> ကို နှိပ်ပါ</li>
            <li>သို့မဟုတ် အပြီးတိုင် ဖျက်ရန် <strong>🗑️ Delete Forever</strong> ကို နှိပ်ပါ</li>
            <li>dialog တွင် အတည်ပြုပါ</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>သတိပြုပါ:</strong> Delete Forever = အပြီးတိုင် ဖျက်ခြင်း၊ ပြန်လည်ရယူ၍ မရပါ — နှိပ်မီ သေချာပါစေ
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: trash သည် item များကို မည်မျှကြာ သိမ်းထားသနည်း?</dt>
        <dd>ဖြေ: default အားဖြင့် ရက် ၃၀ → ထို့နောက် အလိုအလျောက် ဖျက်သည် (Super Admin က ချိန်ညှိနိုင်သည်)</dd>

        <dt>မေး: ဖျက်လိုက်သော ဝန်ထမ်းကို ပြန်လည်ရယူပါက ၎င်း၏ အလုပ်ရှင်ချိတ်ဆက်မှု ယခင်အတိုင်း ရှိပါသလား?</dt>
        <dd>ဖြေ: ရှိပါသည် — ဆက်နွယ်မှု + စာရွက်စာတမ်း + လုပ်ဆောင်ချက် မှတ်တမ်း အားလုံး မပျက်စီးဘဲ ကျန်ရှိသည်</dd>

        <dt>မေး: Staff သည် ဒေတာ ဖျက်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: ခွင့်ပြုချက်ပေါ် မူတည်သည် — အချို့ လုပ်ဆောင်ချက်များသည် Admin သို့မဟုတ် ပိုမြင့်သော role လိုအပ်သည်</dd>
    </dl>
</section>
