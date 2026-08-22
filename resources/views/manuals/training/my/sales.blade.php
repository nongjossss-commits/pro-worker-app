{{-- Training Edition: Sales (Myanmar) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-cart-fill"></i> {{ __('Sales') }} — {{ __('The pipeline from Lead to closed sale') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Sales"</strong> မီနူးသည် ဖောက်သည်အသစ် (Lead) မှ
        → ရောင်းချမှု ပြီးစီးခြင်း → Production သို့ လွှဲပြောင်းခြင်းအထိ <strong>ရောင်းချမှု လုပ်ငန်းစဉ်</strong>ကို စီမံသည်။
        အခြေအနေ ပြောင်းလဲရန် ဆွဲယူနိုင်သော <strong>Kanban board</strong> ကို အသုံးပြုသည်
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Sales မီနူးကို ဖွင့်ရန် — Kanban Board</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/01-kanban-board',
        'alt' => 'ရောင်းချမှု အဆင့်များ၏ ကော်လံများ ပြသော Kanban board',
        'caption' => 'Sales Kanban — ကော်လံတစ်ခုစီသည် အဆင့်တစ်ခု (New / Contacted / Quoted / Closed)',
        'callouts' => [
            '<strong>ကော်လံများ:</strong> lead ဖြတ်သန်းသော အဆင့်အမျိုးမျိုး',
            '<strong>ကတ်များ:</strong> ဖောက်သည်တစ်ဦးစီ၊ အကျဉ်းချုပ်နှင့်တကွ',
            '<strong>Drag & Drop:</strong> ကတ်ကို အခြားကော်လံသို့ ဆွဲယူပါ = အဆင့် ပြောင်းလဲမှု',
            '<strong>+ New Lead:</strong> ဖောက်သည်အသစ် ထည့်ရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Sales</strong></li>
            <li>Kanban board ကို ကြည့်ပါ — ကော်လံတစ်ခုစီသည် ဖောက်သည်၏ အဆင့် ဖြစ်သည်</li>
            <li>အပေါ်ပိုင်းရှိ Owner ဖြင့် စစ်ထုတ်ခြင်း သို့မဟုတ် ရှာဖွေခြင်း</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Lead အသစ် ဖန်တီးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/02-new-lead',
        'alt' => 'Lead အသစ် ဖန်တီးသောဖောင်',
        'caption' => 'New Lead Form — ဖောက်သည်အချက်အလက်နှင့် ဆက်သွယ်ရေးလမ်းကြောင်း',
        'callouts' => [
            '<strong>ဖောက်သည်အချက်အလက်:</strong> အမည်၊ ကုမ္ပဏီ၊ ဆက်သွယ်ရေး',
            '<strong>ရင်းမြစ်:</strong> မည်သည့်လမ်းကြောင်းမှ ရောက်လာသနည်း (ညွှန်းပို့ချက် / FB / website)',
            '<strong>Owner:</strong> မည်သည့်အရောင်းဝန်ထမ်း တာဝန်ယူသနည်း',
            '<strong>ကနဦးအဆင့်:</strong> ပုံမှန်အားဖြင့် "New" သို့မဟုတ် "Contacted" မှ စတင်သည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li><strong>"+ New Lead"</strong> ကို နှိပ်ပါ</li>
            <li>ဖောက်သည်၏ အချက်အလက် + အရောင်းဝန်ထမ်း ဖြည့်ပါ</li>
            <li>Save ကို နှိပ်ပါ → ကတ်သည် Kanban board တွင် ပေါ်လာသည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">ဝန်ထမ်း ထည့်ရန် + ဈေးနှုန်းပြသလွှာ ဖန်တီးရန်</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/03-quotation-modal',
        'alt' => 'ဈေးနှုန်းပြသလွှာ ဖန်တီးရေး modal + ဝန်ထမ်းစီမံခန့်ခွဲမှု',
        'caption' => 'Quotation Modal — ဝန်ထမ်းထည့်ရန် + စျေးနှုန်းသတ်မှတ်ရန် + PDF ဖန်တီးရန်',
        'callouts' => [
            '<strong>Manage Employees:</strong> ယာယီဝန်ထမ်း ထည့်ရန် (ယခုအချိန်တွင် အမှန်ဝန်ထမ်း ဖြစ်စရာမလိုပါ)',
            '<strong>Pricing Tiers:</strong> Production ကဲ့သို့ တစ်ဦးချင်း စျေးနှုန်း သတ်မှတ်ရန်',
            '<strong>Generate PDF:</strong> ဖောက်သည်ထံ ပို့ရန် PDF ဈေးနှုန်းပြသလွှာ ထုတ်ပေးရန်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Lead ကတ်ကို ဖွင့်ပါ → <strong>"Manage Employees"</strong> ကို နှိပ်ပါ</li>
            <li>ယာယီဝန်ထမ်း ထည့်ပါ (စနစ်သည် ၎င်းတို့ကို Temp အဖြစ် ဦးစွာ ဖန်တီးပေးသည်)</li>
            <li>ငွေကြေးတဘ်ကို ဖွင့်ပါ → စျေးနှုန်း သတ်မှတ်ပါ</li>
            <li><strong>"Quotation"</strong> ကို နှိပ်ပါ → PDF ဈေးနှုန်းပြသလွှာ ထုတ်ပေးသည်</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">ရောင်းချမှု ပြီးစီးရန် → Production သို့ ပို့ရန် (Transition)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/04-transition-to-production',
        'alt' => 'Lead → Production ပြောင်းလဲရေး modal',
        'caption' => 'Transition Modal — Lead ကို Production Order အဖြစ် ပြောင်းလဲရန်',
        'callouts' => [
            '<strong>Work Type ရွေးပါ:</strong> Production က ကိုင်တွယ်မည့် အလုပ်',
            '<strong>ပြောင်းလဲမှု အတည်ပြုပါ:</strong> စနစ်သည် Employer + Employees + Production Order ကို အလိုအလျောက် ဖန်တီးပေးသည်',
            '<strong>ယာယီဝန်ထမ်း → အမှန်ဝန်ထမ်း:</strong> ဤအချိန်တွင် ယာယီဝန်ထမ်းများသည် အမှန်ဖြစ်သွားသည်',
            '<strong>Lead အလိုအလျောက် သိမ်းဆည်း:</strong> ရောင်းချမှု ပြီးစီးသောကြောင့် မူလ Lead ကို archive လုပ်ပါလိမ့်မည်',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>ဖောက်သည် ဝယ်ယူရန် သဘောတူသည် → ကတ်ကို <strong>"Closed Won"</strong> သို့ ဆွဲယူပါ</li>
            <li><strong>"Transition to Production"</strong> ကို နှိပ်ပါ</li>
            <li>Work Type ရွေးပါ → အတည်ပြုပါ</li>
            <li>စနစ်သည် Employer/Employees/Production Order ကို တစ်ပြိုင်နက် ဖန်တီးပေးသည် → Pre-Prod မီနူးတွင် ချက်ချင်း ပေါ်လာသည်</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>အကြံပြုချက်:</strong> Lead အောက်တွင် ထည့်ထားသော ယာယီဝန်ထမ်းများသည် → ပြောင်းလဲစဉ်တွင် အမှန်ဝန်ထမ်းအဖြစ် အလိုအလျောက် ပြောင်းလဲသွားသည်
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Sales ၏ မြင်နိုင်စွမ်းနှင့် ခွင့်ပြုချက်များ</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/05-visibility-permissions',
        'alt' => 'Super Admin ရှိ Sales မီနူး မြင်နိုင်စွမ်း ဆက်တင်များ',
        'caption' => 'Sales Menu Visibility — Super Admin က ဖွင့်/ပိတ်နိုင်သည်',
        'callouts' => [
            '<strong>မူလ မြင်နိုင်စွမ်း:</strong> Super Admin Settings တွင် Sales ကို ဖွင့်/ပိတ်နိုင်သည်',
            '<strong>အခန်းကဏ္ဍအလိုက်:</strong> Caretaker/Employer တွင် Sales မီနူး မမြင်ရပါ',
            '<strong>Owner-scoped:</strong> Staff သည် ၎င်းပိုင်ဆိုင်သော lead ကိုသာ မြင်ရသည် (ထိုသို့ သတ်မှတ်ထားပါက)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Super Admin Settings → Menu Visibility</li>
            <li>Sales မီနူးကို ဖွင့်/ပိတ်ပါ</li>
            <li>ဝင်ရောက်ခွင့်ရှိသော အခန်းကဏ္ဍများ သတ်မှတ်ပါ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">မေးလေ့ရှိသော မေးခွန်းများ</h2>

    <dl class="slide-faq">
        <dt>မေး: "Closed Lost" lead ကို ဖျက်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: ဖျက်နိုင်ပါသည် — သို့သော် ရောင်းချမှု မှတ်တမ်းနှင့် နောင် analytics အတွက် archive လုပ်ရန် အကြံပြုပါသည်</dd>

        <dt>မေး: lead တစ်ခု နောက်မှ ပြန်လာပါက — အသစ်ဖန်တီးရမလား၊ ဟောင်းကို ပြန်သုံးရမလား?</dt>
        <dd>ဖြေ: မူလ Lead ကို ဖွင့်ပြီး ၎င်း၏ အဆင့်ကို New သို့မဟုတ် Contacted သို့ ပြန်ပြောင်းပါ</dd>

        <dt>မေး: Lead ပေါ်ရှိ ယာယီဝန်ထမ်းများသည် အမှန်ဝန်ထမ်းများနှင့် ဘယ်လိုကွာခြားသနည်း?</dt>
        <dd>ဖြေ: Temp = employees table တွင် မှတ်တမ်း မရှိသေးပါ (JSON အဖြစ် သိမ်းထားသည်)၊ Real = စနစ်၏ အမှန် မှတ်တမ်းအဖြစ် ဖန်တီးထားသည် — ပြောင်းလဲစဉ်တွင် ဤအရာ အလိုအလျောက် ဖြစ်ပေါ်သည်</dd>

        <dt>မေး: ထုတ်ပေးပြီးသား ဈေးနှုန်းပြသလွှာကို ပြင်ဆင်နိုင်ပါသလား?</dt>
        <dd>ဖြေ: ပြင်ဆင်နိုင်ပါသည် — သို့သော် နောက်တစ်ကြိမ် ထုတ်သောအခါ ဘေဂျင်နံပါတ်/ဗားရှင်းအသစ် ပြသပါလိမ့်မည်</dd>
    </dl>
</section>
