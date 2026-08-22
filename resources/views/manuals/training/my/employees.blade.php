{{-- Training Edition: Employees — slide-friendly with annotated screenshots (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Employees') }} — {{ __('Manage all migrant worker data') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Employees"</strong> မီနူးကို ဝန်ထမ်းတိုင်း၏ ဒေတာကို <strong>ထည့်ရန် / ပြင်ဆင်ရန် / ကြည့်ရှုရန်</strong> အသုံးပြုသည် —
        ကိုယ်ရေးအချက်အလက်၊ နိုင်ငံကူးလက်မှတ်၊ ဗီဇာ၊ အလုပ်လုပ်ခွင့်ပြုချက်၊ ဓာတ်ပုံ၊ ပူးတွဲဖိုင်များ။
        အလုပ်အမျိုးအစားတိုင်း (Production၊ Workflow၊ Registration Resolution၊ Renewal Resolution) ၏ အစပိုင်း ဖြစ်သည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (ကိုယ်ပိုင်ဝန်ထမ်းများကိုသာ)</span>
    </div>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">ဝန်ထမ်းစာရင်းစာမျက်နှာကို ဖွင့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/01-list-view',
        'alt' => 'စစ်ထုတ်ရေးဘား ပါသော ဝန်ထမ်းစာရင်းစာမျက်နှာ (ကတ်မြင်ကွင်း)',
        'caption' => 'ဝန်ထမ်းစာရင်း စာမျက်နှာ — ကတ်မြင်ကွင်းနှင့် ဇယားမြင်ကွင်းကြား ပြောင်းနိုင်သည်',
        'callouts' => [
            '<strong>စစ်ထုတ်ရေးဘား:</strong> ရှာဖွေခြင်း၊ လူမျိုးဖြင့် စစ်ထုတ်ခြင်း၊ MOU အုပ်စု၊ နိုင်ငံကူးလက်မှတ်',
            '<strong>+ Add Employee:</strong> ဝန်ထမ်းအသစ် ဖန်တီးရန်',
            '<strong>ကတ်/ဇယား မြင်ကွင်း:</strong> လိုအပ်သလို ပြောင်းလဲပါ',
            '<strong>Bulk Action:</strong> လူများစွာကို ခြစ်ပြီး Export လုပ်ရန်၊ အလုပ်ရှင်လွှဲပြောင်းရန်၊ PDF ဖန်တီးရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li><strong>Sidebar → Employees</strong> ကို နှိပ်ပါ</li>
            <li>မြင်ကွင်းအမျိုးအစား ရွေးပါ (<strong>ကတ်</strong> သို့မဟုတ် <strong>ဇယား</strong>)</li>
            <li>လိုချင်သော ဝန်ထမ်းကို ရှာရန် အပေါ်ရှိ စစ်ထုတ်ချက်များကို အသုံးပြုပါ</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>အကြံပြုချက်:</strong> "Employment History" မီနူးသည် အလုပ်ထုတ်ပြီးသူများ အပါအဝင် လူတိုင်းကို ပြသသည် — ဤမီနူးနှင့် ကွဲပြားပြီး ဤမီနူးသည် အသုံးပြုနေဆဲကိုသာ ပြသသည်
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ဝန်ထမ်းအသစ် ထည့်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/02-add-employee',
        'alt' => 'ဝန်ထမ်းအသစ် ဖန်တီးသောဖောင်',
        'caption' => 'ဝန်ထမ်းအသစ် ဖောင် — ဒေတာအမျိုးအစားတိုင်းအတွက် တဘ်များစွာ',
        'callouts' => [
            '<strong>အလုပ်ရှင် ရွေးရန်:</strong> ဝန်ထမ်းသည် အလုပ်ရှင်တစ်ဦးနှင့် အမြဲ ချိတ်ဆက်ထားရမည်',
            '<strong>လိုအပ်သောကွက်လပ်များ:</strong> အမည်၊ လူမျိုး၊ နိုင်ငံကူးလက်မှတ်',
            '<strong>တဘ်များ:</strong> General Info → Passport/Visa → Documents → Photo',
            '<strong>Document Scanner:</strong> ကင်မရာမှ ဓာတ်ပုံကို တိုက်ရိုက် စနစ်ထဲသို့ ရိုက်ယူရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>(ညာဘက်အပေါ်ထောင့်) <strong>"+ Add Employee"</strong> ကို နှိပ်ပါ</li>
            <li><strong>အလုပ်ရှင်</strong> ကို ရွေးပါ (ရိုက်ထည့်ရှာနိုင်သည်)</li>
            <li><strong>အမည် + လူမျိုး + နိုင်ငံကူးလက်မှတ်</strong> ဖြည့်ပါ (လိုအပ်သည်)</li>
            <li>တဘ်တစ်ခုစီတွင် အချက်အလက်ထပ်တိုး ဖြည့်ပါ (ရွေးချယ်နိုင်သည် — နောက်မှ ပြင်ဆင်နိုင်သည်)</li>
            <li><strong>"Save"</strong> ကို နှိပ်ပါ</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>သတိပြုရန်:</strong> Employee Cap — subscription tier အပေါ်မူတည်၍ စနစ်သည် ဝန်ထမ်းစုစုပေါင်း အရေအတွက်ကို ကန့်သတ်သည်
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">ဝန်ထမ်းဒေတာ ပြင်ဆင်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/03-edit-employee',
        'alt' => 'ဒေတာနှင့် စာရွက်စာတမ်းတဘ်များ ပါသော ဝန်ထမ်း ပြင်ဆင်ရေးစာမျက်နှာ',
        'caption' => 'ဝန်ထမ်း ပြင်ဆင်ရေးစာမျက်နှာ — Personal၊ Documents၊ History တဘ်များ',
        'callouts' => [
            '<strong>Personal:</strong> အမည်၊ လိပ်စာ၊ လူမျိုး၊ မွေးသက္ကရာဇ်',
            '<strong>Documents:</strong> နိုင်ငံကူးလက်မှတ်၊ ဗီဇာ၊ အလုပ်လုပ်ခွင့်ပြုချက် + PDF/ပုံ တင်ရန်',
            '<strong>Other Documents:</strong> ထပ်တိုးစာရွက်စာတမ်းအတွက် နေရာ ၁၀ ခု (Super Admin တွင် မူလအမည်များ သတ်မှတ်ထားသည်)',
            '<strong>History တဘ်:</strong> ပြောင်းလဲမှု မှတ်တမ်း + activity log ကြည့်ရှုရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li><strong>ဝန်ထမ်းကတ်</strong> သို့မဟုတ် ခဲတံ ✏️ ခလုတ်ကို နှိပ်ပါ</li>
            <li>တဘ်တစ်ခုစီတွင် ကိုယ်ပိုင် ကွက်လပ်များ ရှိသည်</li>
            <li><strong>Upload</strong> ခလုတ် သို့မဟုတ် <strong>Document Scanner</strong> မှတစ်ဆင့် ဖိုင်များ တင်ပါ</li>
            <li><strong>"Save"</strong> ကို နှိပ်ပါ — စနစ်သည် ပြောင်းလဲမှုကို Activity Log တွင် မှတ်တမ်းတင်ပါလိမ့်မည်</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>အကြံပြုချက်:</strong> ဝန်ထမ်းဒေတာ ပြင်ဆင်ပြီးနောက် ၎င်း၏ အလုပ်ကတ်သည် Workflow/Production တွင် အပေါ်ဆုံးသို့ ရွှေ့သွားသည်
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">ဝန်ထမ်း Preview ခလုတ်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/04-preview-popup',
        'alt' => 'ဝန်ထမ်းဒေတာကို ဖတ်ရုံသာ ပြသော popup',
        'caption' => 'Preview Popup — ပြင်ဆင်ရေးစာမျက်နှာ မဖွင့်ဘဲ ဝန်ထမ်းဒေတာကို လျင်မြန်စွာ ကြည့်ရန်',
        'callouts' => [
            '<strong>Preview 🔍 ခလုတ်:</strong> ဝန်ထမ်းကတ်တိုင်း၊ စာမျက်နှာတိုင်းတွင်',
            '<strong>Read-only:</strong> ကြည့်ရုံသာ၊ ပြင်ဆင်၍မရပါ',
            '<strong>ပါဝင်သည်များ:</strong> Personal၊ နိုင်ငံကူးလက်မှတ်၊ ဗီဇာ၊ စာရွက်စာတမ်း၊ ဓာတ်ပုံ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>ဝန်ထမ်းကတ်ပေါ်ရှိ <strong>မှန်ဘီလူး 🔍</strong> အိုင်ကွန်ကို ရှာပါ</li>
            <li>နှိပ်ပါ → ဒေတာအားလုံးပြသော modal ပေါ်လာသည်</li>
            <li>ပြန်သွားရန် modal ကို ပိတ်ပါ သို့မဟုတ် ပြင်ပနေရာကို နှိပ်ပါ</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Caretaker:</strong> Preview သည် ၎င်းစီမံသော အလုပ်ရှင်များ၏ ဝန်ထမ်းများအတွက်သာ အလုပ်လုပ်သည်
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Bulk Actions — လူများစွာကို တစ်ပြိုင်နက် ကိုင်တွယ်ရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/05-bulk-actions',
        'alt' => 'လူများစွာ ခြစ်ထားချိန် ပေါ်လာသော ပေါ်မျောနေသော bulk action ဘား',
        'caption' => 'Bulk Action Bar — ဝန်ထမ်းများစွာ ခြစ်ချိန် ပေါ်လာသည်',
        'callouts' => [
            '<strong>ခြစ်ရန်:</strong> ကတ်တိုင်း၏ ဘယ်ဘက်အပေါ်ထောင့်တွင် ခြစ်ဘောက်စ် ရှိသည်',
            '<strong>Action menu:</strong> Export၊ အလုပ်ရှင်လွှဲပြောင်းရန်၊ PDF ဖန်တီးရန်၊ Production သို့ ပို့ရန်',
            '<strong>ရေတွက်ကိန်း:</strong> ရွေးထားသည့် အရေအတွက်ကို ပြသည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>လိုချင်သော ဝန်ထမ်းများ၏ <strong>ခြစ်ဘောက်စ်</strong> ကို ခြစ်ပါ (အများအပြား ရွေးနိုင်သည်)</li>
            <li>Bulk Action Bar သည် အောက်ခြေတွင် ပေါ်လာသည်</li>
            <li>dropdown မှ လုပ်ဆောင်ချက် ရွေးပါ:
                <ul>
                    <li><strong>Export CSV / Advanced Export</strong></li>
                    <li><strong>Transfer Employer</strong> (Bulk Transfer)</li>
                    <li><strong>Automated PDF</strong> (နမူနာမှ PDF ဖန်တီးရန်)</li>
                    <li><strong>Send to Production</strong></li>
                </ul>
            </li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: ဝန်ထမ်းအသစ် ထည့်၍မရပါ — အမှားပြသည်?</dt>
        <dd>ဖြေ: Employee Cap ကို စစ်ဆေးပါ — subscription အပေါ်မူတည်၍ စနစ်သည် အရေအတွက်ကို ကန့်သတ်သည်၊ တိုးမြှင့်ရန် လိုအပ်ပါက Super Admin ကို ဆက်သွယ်ပါ</dd>

        <dt>မေး: ဝန်ထမ်းတစ်ဦး စာရင်းမှ ပျောက်နေသည်?</dt>
        <dd>ဖြေ: "Employment History" မီနူးကို စစ်ဆေးပါ — အလုပ်ထုတ်အကြောင်းကြားခံရသည် သို့မဟုတ် စာချုပ်ကုန်ဆုံးသည် သို့မဟုတ် "Central Trash" သို့ ဖျက်ခံရသည် ဖြစ်နိုင်သည်</dd>

        <dt>မေး: Caretaker သည် မျှော်မှန်းထားသည်ထက် ဝန်ထမ်း နည်းနည်းသာ မြင်ရသည်?</dt>
        <dd>ဖြေ: Caretaker သည် ၎င်းစီမံသော အလုပ်ရှင်များ၏ ဝန်ထမ်းများကိုသာ မြင်ရသည်</dd>

        <dt>မေး: Preview ခလုတ် အလုပ်မလုပ်ပါ — Error 500?</dt>
        <dd>ဖြေ: ယခင်က bug တစ်ခု ရှိခဲ့ဖူးသည် — ယခုအခါ ပြင်ဆင်ပြီးဖြစ်ပြီး Caretaker သည် ပုံမှန် Preview လုပ်နိုင်ပါပြီ (၎င်းစီမံသော ဝန်ထမ်းများအတွက်သာ)</dd>
    </dl>
</section>
