{{-- Training Edition: Workflow — slide-friendly with annotated screenshots (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-diagram-3-fill"></i> {{ __('Workflow') }} — {{ __('The hub for jobs currently in progress') }}
    </h3>
    <p class="training-intro-desc">
        ဤမီနူးသည် လုပ်ငန်းစဉ် အဆင့်ဆင့်ကို ဖြတ်သန်းနေသော <strong>အလုပ်တိုင်း၏ စင်တာ</strong> ဖြစ်သည် —
        ဥပမာ- အလုပ်သမားရေးရာဌာနတွင် တင်ပြခြင်း၊ နိုင်ငံကူးလက်မှတ် ဆောင်ရွက်ခြင်း၊ ဗီဇာလျှောက်ထားခြင်း၊ အလုပ်လုပ်ခွင့်ပြုချက် ထုတ်ပေးခြင်း။
        အသုံးပြုသူများသည် ဝန်ထမ်းတစ်ဦးစီ၏ <strong>အဆင့်များကို ခြစ်</strong>နိုင်ပြီး စနစ်က ၎င်းတို့အတွက် တိုးတက်မှုကို ခြေရာခံပေးသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (ကြည့်ရှုရုံသာ)</span>
    </div>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Workflow သို့ ဝင်ရောက်ပြီး တဘ် ရွေးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/01-main-view',
        'alt' => 'Work Type အမျိုးမျိုး၏ တဘ်များ ပြသော Workflow ပင်မစာမျက်နှာ',
        'caption' => 'Workflow ပင်မစာမျက်နှာ — အပေါ်ဘားတွင် Work Type တစ်ခုစီအတွက် တဘ် ရှိသည်',
        'callouts' => [
            '<strong>Tab Bar:</strong> အလုပ်အမျိုးအစား ရွေးရန် (Notify In / Visa Renewal / Imported MOU / Notify Out)',
            '<strong>+ Add Employee ခလုတ်:</strong> အလုပ်တစ်ခုသို့ ဝန်ထမ်း ထည့်ရန်',
            '<strong>စစ်ထုတ်ခြင်း:</strong> operator, အခြေအနေဖြင့် စစ်ထုတ်ခြင်း၊ အမည်ဖြင့် ရှာဖွေခြင်း',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li><strong>Sidebar → Workflow</strong> ကို နှိပ်ပါ</li>
            <li>လုပ်ဆောင်နေသော Work Type ၏ <strong>Tab</strong> ကို ရွေးပါ</li>
            <li>အလုပ်ရှင်တစ်ဦးစီ၏ ကတ်သည် ၎င်း၏ ဝန်ထမ်းစာရင်းနှင့်အတူ ပေါ်လာသည်</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>အကြံပြုချက်:</strong> <strong>လတ်တလော လှုပ်ရှားမှု</strong> ရှိသော ကတ်သည် refresh လုပ်တိုင်း အပေါ်ဆုံးသို့ ရွှေ့သွားသည်
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ဝန်ထမ်း၏ အဆင့်ကို ခြစ်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/02-tick-step',
        'alt' => 'အဆင့်တစ်ခုစီအတွက် ခြစ်ဘောက်စ် ပါသော ဝန်ထမ်းကတ်',
        'caption' => 'အဆင့်တစ်ခုစီအတွက် ခြစ်ဘောက်စ် ပါသော ဝန်ထမ်းကတ်',
        'callouts' => [
            '<strong>ခြစ်ဘောက်စ်:</strong> ထိုအဆင့် ပြီးစီးကြောင်း မှတ်တမ်းတင်ရန် ခြစ်ပါ',
            '<strong>အဆင့်အမည်:</strong> အဆင့်၏ အမည် (ဥပမာ- "လျှောက်ထားစာ တင်ရန်"၊ "အခကြေးငွေ ပေးချေရန်")',
            '<strong>ပြင်းအားချိန်တိုင်း:</strong> အလုံးစုံ တိုးတက်မှု ရာခိုင်နှုန်း',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>ပြီးစီးသော အဆင့်၏ <strong>ခြစ်ဘောက်စ်</strong> ကို နှိပ်ပါ</li>
            <li>စနစ်သည် <strong>အချိန်တံဆိပ် + ပြုလုပ်သူကို</strong> အလိုအလျောက် မှတ်တမ်းတင်သည်</li>
            <li>တိုးတက်မှု ပြင်းအားချိန်သည် ချက်ချင်း update ဖြစ်သည်</li>
            <li>အဆင့်အားလုံး ပြီးစီးသောအခါ → အလုပ်ပိတ်ရန် <strong>Finish</strong> ကို နှိပ်ပါ</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>သတိပြုရန်:</strong> မှားယွင်းစွာ ခြစ်မိပါက ထပ်နှိပ်၍ ပြန်ဖျက်နိုင်သည်၊ သို့သော် Activity Log သည် ဘယ်လိုပဲဖြစ်ဖြစ် ပြောင်းလဲမှုကို မှတ်တမ်းတင်ပါလိမ့်မည်
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Workflow အလုပ်ထဲသို့ ဝန်ထမ်း ထည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/03-add-employee-modal',
        'alt' => 'Workflow ထဲသို့ ဝန်ထမ်း ထည့်ရေး modal',
        'caption' => 'Add Employee Modal — အလုပ်အမျိုးအစား + အလုပ်ရှင် + ဝန်ထမ်းများ ရွေးပါ',
        'callouts' => [
            '<strong>ရှာဖွေနိုင်သော အလုပ်ရှင် dropdown:</strong> အမည်/ကုဒ် ရိုက်ထည့်၍ ရှာနိုင်သည်',
            '<strong>ဝန်ထမ်းစာရင်း:</strong> ထိုအလုပ်ရှင်၏ ဝန်ထမ်းများ',
            '<strong>Bulk select:</strong> လူများစွာကို တစ်ပြိုင်နက် ရွေးရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>တဘ်၏ အပေါ်ပိုင်းရှိ <strong>"+ Add Employee"</strong> ကို နှိပ်ပါ</li>
            <li><strong>အလုပ်ရှင်</strong> ကို ရွေးပါ (ရိုက်ထည့်ရှာနိုင်သည်)</li>
            <li><strong>ဝန်ထမ်းများ</strong> ကို ရွေးပါ (အများအပြား ရွေးနိုင်သည်)</li>
            <li><strong>"Add"</strong> ကို နှိပ်ပါ — ဝန်ထမ်းများသည် အလုပ်ရှင်ကတ်ပေါ်တွင် ချက်ချင်း ပေါ်လာပါမည်</li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">"Notify Out" တဘ်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/04-notify-out',
        'alt' => 'Notify Out တဘ်ရှိ ဝန်ထမ်းကတ်တွင် ရက်စွဲ + အကြောင်းရင်း ကွက်လပ်များ',
        'caption' => 'Notify Out တဘ် — အလုပ်ထုတ်အကြောင်းကြားရက်နှင့် အကြောင်းရင်း ဖြည့်ရန် အဝါရောင်ဘား',
        'callouts' => [
            '<strong>အလုပ်ထုတ်အကြောင်းကြားရက် (လိုအပ်သည်):</strong> ရက်စွဲရွေးစနစ်၊ Finish မနှိပ်မီ လိုအပ်သည်',
            '<strong>အကြောင်းရင်း:</strong> အလုပ်ထုတ်ပြီး / ထုတ်ပယ်ခံရသည် / စာချုပ်ကုန်ဆုံးပြီး / အလုပ်ရှင်ပြောင်းလဲမှု / အခြား',
            '<strong>အရောင်ပါ badge:</strong> အဝါရောင် = ဖြည့်ရန် လိုအပ်သည်၊ အစိမ်းရောင် = ပြီးစီးရန် အသင့်ဖြစ်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li><strong>"Notify Out"</strong> တဘ်ကို ဖွင့်ပါ</li>
            <li>ဝန်ထမ်း ထည့်ပါ (စနစ်တစ်ခုလုံး ရှာဖွေနိုင်သည် — ကမ္ဘာလုံးဆိုင်ရာ ရှာဖွေမှု)</li>
            <li>အဝါရောင်ဘားတွင် <strong>အလုပ်ထုတ်အကြောင်းကြားရက်</strong> + <strong>အကြောင်းရင်း</strong> ဖြည့်ပါ</li>
            <li><strong>Finish</strong> ကို နှိပ်ပါ — စနစ်သည် ဝန်ထမ်း၏ အခြေအနေကို "resigned" အဖြစ် အလိုအလျောက် update လုပ်ပါလိမ့်မည်</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>အကြံပြုချက်:</strong> ဝန်ထမ်းသည် အလုပ်ရှင် လွှဲပြောင်းနေခြင်း ဖြစ်ပါက (တကယ့်အလုပ်ထုတ်ခြင်း မဟုတ်ပါက) → notify_out မှတ်တမ်းကို အလိုအလျောက် ပယ်ဖျက်ပါလိမ့်မည်
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">တင်သွင်းထားသော MOU — Demand Card ဖန်တီးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/05-mou-import',
        'alt' => 'subtype အရောင် badge ပါသော တင်သွင်းထားသော MOU ကတ်',
        'caption' => 'MOU Import ကတ် — subtype (Return/New/Pending) ကို အရောင်နှင့် badge ဖြင့် ပြသသည်',
        'callouts' => [
            '<strong>ဘောင်အရောင်:</strong> 🟢 Return | 🔵 New from Origin | 🟠 Pending',
            '<strong>Badge:</strong> နောက်မှ အမျိုးအစား ပြောင်းရန် နှိပ်ပါ',
            '<strong>ရှာဖွေနိုင်သော အလုပ်ရှင်:</strong> scroll လှိမ့်မည့်အစား ရိုက်ထည့်ရှာနိုင်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li><strong>"တင်သွင်းထားသော MOU"</strong> တဘ်ကို ဖွင့်ပါ → <strong>"Create Job"</strong> ကို နှိပ်ပါ</li>
            <li>အလုပ်ရှင် ရွေးပါ (ရိုက်ထည့်ရှာနိုင်သည်) + အမျိုးအစား သတ်မှတ်ပါ:
                <ul>
                    <li>🟢 <strong>Return</strong> — ဝန်ထမ်း ထိုင်းနိုင်ငံတွင် ရောက်ရှိပြီးဖြစ်သည်</li>
                    <li>🔵 <strong>New from Origin</strong> — မူရင်းနိုင်ငံမှ လူသစ်</li>
                    <li>🟠 <strong>မသေချာသေးပါ</strong> — နောက်မှ ဆုံးဖြတ်ရန်</li>
                </ul>
            </li>
            <li>လူမျိုး + အမျိုးသား/အမျိုးသမီး အရေအတွက် ဖြည့်ပါ</li>
            <li><strong>Create Demand Card</strong> ကို နှိပ်ပါ</li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: ကျွန်ုပ်၏ ကတ် ဘာကြောင့် အပေါ်ဆုံးသို့ မရွှေ့သွားသနည်း?</dt>
        <dd>ဖြေ: စနစ်သည် <strong>refresh</strong> လုပ်သောအခါ သို့မဟုတ် အခြားမီနူးမှ ပြန်လာသောအခါတွင်သာ ကတ်များကို ပြန်စီစဉ်သည် — UI သည် လုပ်ငန်းလုပ်နေစဉ် ခုန်ပေါက်ခြင်း မပြုပါ (အနှောင့်အယှက် ဖြစ်စေခြင်း ရှောင်ရှားရန်)</dd>

        <dt>မေး: ဝန်ထမ်းတစ်ဦး Notify Out တဘ်မှ ပျောက်သွားသည်?</dt>
        <dd>ဖြေ: အလုပ်ရှင်အသစ်သို့ လွှဲပြောင်းသောအခါ အလိုအလျောက် ပယ်ဖျက်ခံရသည် — notify_out ဆိုသည်မှာ "အလုပ်ရှင်ဟောင်းမှ ထွက်ခွာခြင်း" ဖြစ်၍ နောက်ထပ် မသက်ဆိုင်တော့ပါ</dd>

        <dt>မေး: Caretaker သည် ကတ်အချို့ကို မြင်ရပြီး အချို့ကို မမြင်ရပါ?</dt>
        <dd>ဖြေ: Caretaker သည် ၎င်းအား သတ်မှတ်ထားသော အလုပ်ရှင်များကိုသာ မြင်ရသည်</dd>
    </dl>
</section>
