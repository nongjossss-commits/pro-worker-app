{{-- Training Edition: Sales (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-cart-fill"></i> {{ __('Sales') }} — {{ __('从 Lead 到成交的整个流程') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Sales"</strong> 菜单用于管理从新客户(Lead)
        → 成交 → 转交 Production 的<strong>整个销售流程</strong>。
        采用 <strong>看板(Kanban board)</strong>，可通过拖放方便地更改状态
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开 Sales 菜单 —— 看板(Kanban Board)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/01-kanban-board',
        'alt' => '显示销售阶段各列的看板',
        'caption' => 'Sales 看板 —— 每列代表一个阶段(New / Contacted / Quoted / Closed)',
        'callouts' => [
            '<strong>列:</strong> lead 所经历的各个阶段',
            '<strong>卡片:</strong> 每位客户，附简要信息',
            '<strong>拖放:</strong> 将卡片拖到其他列 = 更改阶段',
            '<strong>+ New Lead:</strong> 新增客户',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>Sales</strong></li>
            <li>查看看板 —— 每列代表客户所处的阶段</li>
            <li>使用顶部的 Owner 筛选器或搜索</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">创建新 Lead</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/02-new-lead',
        'alt' => '新建 Lead 表单',
        'caption' => '新建 Lead 表单 —— 客户信息与联系渠道',
        'callouts' => [
            '<strong>客户信息:</strong> 姓名、公司、联系方式',
            '<strong>来源:</strong> 来自哪个渠道(转介绍 / FB / 官网)',
            '<strong>Owner:</strong> 负责的销售人员',
            '<strong>初始阶段:</strong> 通常从"New"或"Contacted"开始',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击 <strong>"+ New Lead"</strong></li>
            <li>填写客户信息 + 销售负责人</li>
            <li>点击 Save → 卡片会出现在看板中</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">新增雇员 + 创建报价单</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/03-quotation-modal',
        'alt' => '创建报价单的弹窗 + 雇员管理',
        'caption' => '报价单弹窗 —— 新增雇员 + 设定价格 + 生成 PDF',
        'callouts' => [
            '<strong>Manage Employees:</strong> 新增临时雇员(暂不需要是正式雇员)',
            '<strong>Pricing Tiers:</strong> 设定按人头计价，与 Production 中相同',
            '<strong>Generate PDF:</strong> 生成 PDF 报价单发送给客户',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开该 Lead 的卡片 → 点击 <strong>"Manage Employees"</strong></li>
            <li>新增临时雇员(系统会先将其创建为 Temp)</li>
            <li>打开财务标签 → 设定价格</li>
            <li>点击 <strong>"Quotation"</strong> → 生成 PDF 报价单</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">成交 → 送入 Production(Transition)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/04-transition-to-production',
        'alt' => 'Lead → Production 转换弹窗',
        'caption' => '转换弹窗 —— 将 Lead 转换为 Production Order',
        'callouts' => [
            '<strong>选择 Work Type:</strong> Production 将要处理的工作类型',
            '<strong>确认转换:</strong> 系统会自动创建 Employer + Employees + Production Order',
            '<strong>临时雇员 → 正式雇员:</strong> 此时临时雇员会转为正式雇员',
            '<strong>自动归档 Lead:</strong> 由于已成交，原 Lead 会被自动归档',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>客户同意购买 → 将卡片拖至 <strong>"Closed Won"</strong></li>
            <li>点击 <strong>"Transition to Production"</strong></li>
            <li>选择 Work Type → 确认</li>
            <li>系统会一次性创建 Employer/Employees/Production Order → 立即出现在 Pre-Prod 菜单中</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>小贴士:</strong> 在 Lead 中新增的临时雇员 → 转换时会自动转为正式雇员
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Sales 菜单的可见性与权限</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/05-visibility-permissions',
        'alt' => 'Super Admin 中的 Sales 菜单可见性设置',
        'caption' => 'Sales 菜单可见性 —— Super Admin 可开启/关闭',
        'callouts' => [
            '<strong>默认可见性:</strong> 可在 Super Admin Settings 中开启/关闭 Sales 菜单',
            '<strong>按角色设置:</strong> Caretaker/Employer 看不到 Sales 菜单',
            '<strong>按 Owner 限定范围:</strong> Staff 只能看到自己负责的 lead(如有此设置)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Super Admin Settings → Menu Visibility</li>
            <li>可开启/关闭 Sales 菜单</li>
            <li>设置可访问的角色</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: "Closed Lost"的 Lead 可以删除吗?</dt>
        <dd>A: 可以删除 —— 但建议改用归档，以便追踪销售历史和后续分析</dd>

        <dt>Q: 如果 lead 之后又回来了 —— 是新建，还是恢复原来的?</dt>
        <dd>A: 打开原来的 Lead，将阶段改回 New 或 Contacted 即可</dd>

        <dt>Q: Lead 中的临时雇员与正式雇员有什么区别?</dt>
        <dd>A: Temp = 尚未在 employees 表中建立正式记录(以 JSON 形式保存)，Real = 已在系统中建立正式记录 —— 转换时会自动完成转化</dd>

        <dt>Q: 已开具的报价单可以修改吗?</dt>
        <dd>A: 可以 —— 但下次生成时会显示新的单号/版本</dd>
    </dl>
</section>
