{{-- Training Edition: Finance (Add-on Module) (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-cash-coin"></i> {{ __('Finance') }} — {{ __('办公室的会计 + 税务 + 操作日志系统') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"财务(Finance)"</strong> 菜单是为需要内建会计系统的办公室提供的<strong>附加模块</strong>。
        包含账簿(Ledger)、税务发票(Tax Invoices)、预扣税(WHT)、
        ภ.พ.30 / ภ.ง.ด.3/53、银行对账(Bank Reconciliation)、月度打包(Monthly Bundle)以及操作日志(Audit Log)
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff(取决于 manage-finance 权限)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开 Finance 菜单</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/01-main-dashboard',
        'alt' => 'Finance 主页面，含摘要卡片 + 子菜单',
        'caption' => 'Finance 主页 —— 摘要卡片 + 子菜单链接',
        'callouts' => [
            '<strong>摘要卡片:</strong> 本月总额、收入、支出、增值税、预扣税',
            '<strong>子菜单:</strong> Ledger / Tax Invoices / WHT / Reports / Bank / Audit Log',
            '<strong>月度打包:</strong> 一键生成月末结账文件 ZIP 的按钮',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>Finance</strong></li>
            <li>查看摘要卡片以了解整体状况</li>
            <li>根据所需操作选择子菜单</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">记录账簿(Ledger)条目</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/02-ledger-entry',
        'alt' => '收入-支出记录表单',
        'caption' => '账簿条目 —— 收入 / 支出，含增值税 + 预扣税',
        'callouts' => [
            '<strong>类型:</strong> 收入 / 支出',
            '<strong>日期:</strong> 记录日期(默认 = 今天)',
            '<strong>往来方:</strong> 客户或供应商',
            '<strong>增值税:</strong> 7%(默认) —— 未税或含税',
            '<strong>预扣税:</strong> 3%(一般服务) / 5%(财产租赁)',
            '<strong>单据图片:</strong> 可附上单据照片',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Finance → Ledger → "+ 记录项目"</li>
            <li>选择类型(收入/支出)</li>
            <li>填写：日期、往来方、金额、增值税</li>
            <li>如有，附上单据 → 点击"保存"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">创建税务发票(Tax Invoice)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/03-tax-invoice',
        'alt' => '税务发票创建表单',
        'caption' => '税务发票表单 —— 选择档案 + 填写客户 + 指定付款方式',
        'callouts' => [
            '<strong>开票方档案:</strong> 我们公司(来自 Financial Profiles)',
            '<strong>客户信息:</strong> 名称 + 纳税人识别号 + 地址',
            '<strong>增值税 7%:</strong> 泰国默认税率，四舍五入至 2 位小数',
            '<strong>付款方式:</strong> 现金 / 转账 / PromptPay',
            '<strong>银行账户:</strong> 如选择"转账" → 选择银行账户',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Tax Invoices → "+ 新建"</li>
            <li>选择 <strong>Biller Profile</strong></li>
            <li>填写客户信息 + 金额 + 增值税</li>
            <li>勾选付款方式</li>
            <li>点击"保存并开具" —— 系统会锁定编号 + 生成 PDF</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>注意:</strong> 已开具(Issued)的发票无法修改 —— 必须作废后重新开具
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">每月税务报表(ภ.พ.30 / ภ.ง.ด.3/53)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/04-tax-reports',
        'alt' => 'Tax Reports 页面 —— 选择月份 + 下载',
        'caption' => '税务报表 —— 每月汇总用于报税',
        'callouts' => [
            '<strong>选择月份:</strong> 月份下拉菜单',
            '<strong>ภ.พ.30:</strong> 当月增值税(收入 - 支出增值税)',
            '<strong>ภ.ง.ด.3:</strong> 个人预扣税',
            '<strong>ภ.ง.ด.53:</strong> 法人预扣税',
            '<strong>导出 Excel:</strong> 用于报税',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Finance → Tax Reports</li>
            <li>选择所需月份</li>
            <li>点击下载各项报表</li>
            <li>使用下载的文件进行报税</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">月度打包 + 银行对账 + 操作日志</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/05-monthly-bundle',
        'alt' => '月度打包 + 银行对账 + 操作日志',
        'caption' => '完整的月末结账功能组合',
        'callouts' => [
            '<strong>月度打包:</strong> 包含全月文件的 ZIP(收入 + 支出 + 发票 + 预扣税)',
            '<strong>银行对账:</strong> 上传对账单 → 系统自动进行匹配',
            '<strong>操作日志:</strong> 所有财务数据修改的历史记录 —— 谁修改了、修改了什么、何时修改',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>小贴士:</strong> 月末时 → 生成月度打包 → 银行对账 → 检查操作日志 = 一套流程完成月末结账
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 税务发票号码是连续的吗?</dt>
        <dd>A: 是连续的 —— 系统会在同一税务年度内接续上一张发票编号，不会中断</dd>

        <dt>Q: 可以删除开错的发票吗?</dt>
        <dd>A: 可以<strong>作废(Void)</strong>，但无法真正删除 —— 发票号码仍会保留在系统中以维持顺序</dd>

        <dt>Q: 预扣税 3% 和 5% 有什么区别?</dt>
        <dd>A: 3% = 一般服务费 / 5% = 财产租赁费、个人劳务费</dd>

        <dt>Q: 看不到 Finance 菜单?</dt>
        <dd>A: 需要拥有 manage-finance 或更高角色 + 订阅套餐包含 Finance 模块</dd>
    </dl>
</section>
