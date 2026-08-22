{{-- Training Edition: Pre-Production (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-clipboard-data-fill"></i> {{ __('P Production (Pre-Production)') }} — {{ __('进入 Workflow 前的文件准备中心') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"Pre-Production"</strong> 菜单用于在送入 Workflow 之前，<strong>准备文件</strong>和客户数据。
        适用于已在 Sales 成交的新客户 → 准备 Pre-Prod → 送入 Workflow 继续处理
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker(仅可查看)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">进入 Pre-Production 页面</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/01-main-view',
        'alt' => 'Pre-Production 主页面，显示雇主工作卡片',
        'caption' => 'Pre-Production 主视图 —— 每张卡片代表一个雇主的工作',
        'callouts' => [
            '<strong>顶部统计摘要卡片:</strong> 即将到期 / 进行中 / 待审核',
            '<strong>筛选:</strong> 雇主 / 业务负责人 / 工作类型(MOU/签证)',
            '<strong>工作卡片:</strong> 销售人员照片 + 雇主名称 + 雇员人数 + 状态',
            '<strong>最新卡片浮动至顶部:</strong> 有最新动态时(勾选步骤/修改数据)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>Pre-Production</strong></li>
            <li>查看顶部的统计摘要卡片</li>
            <li>使用筛选器按雇主或负责人缩小范围</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">打开工作 + 逐一编辑雇员信息</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/02-edit-job',
        'alt' => 'Edit Job 页面，含多个标签：雇员、文件、财务',
        'caption' => 'Edit Job 页面 —— 标签：雇员 / 文件 / 财务 / 进度',
        'callouts' => [
            '<strong>标签栏:</strong> 在 Employee / Document / Financial / Timeline 之间切换',
            '<strong>雇员卡片:</strong> 每位雇员都有编辑 + 查看文件按钮',
            '<strong>文件扫描:</strong> 直接用相机拍照上传至系统',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击雇主工作卡片 → 进入 Edit Job 页面</li>
            <li>选择 <strong>"雇员"</strong> 标签</li>
            <li>点击编辑 ✏️ 按钮编辑各雇员信息</li>
            <li>通过 Upload 或文件扫描上传文件</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">添加自定义字段(Custom Field)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/03-custom-fields',
        'alt' => '为特殊工作添加自定义字段的弹窗',
        'caption' => '自定义字段 —— 添加该工作专属的字段',
        'callouts' => [
            '<strong>"Fields" 按钮:</strong> 位于 MOU 卡片上',
            '<strong>新增字段:</strong> 例如"体检证明编号"、"预约日期"',
            '<strong>指定类型:</strong> text / number / date / dropdown',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击工作卡片上的 <strong>"Fields"</strong> 按钮</li>
            <li>点击"+ 新增字段"</li>
            <li>命名字段 + 选择类型 → 保存</li>
            <li>新字段会出现在各雇员的 Custom Fields 标签中</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">财务标签(Financial Tab)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/04-financial-tab',
        'alt' => '含定价分期卡片的财务标签',
        'caption' => '财务标签 —— 创建定价分期 + 分期付款 + 开票',
        'callouts' => [
            '<strong>+ 新增标签:</strong> 每份工作可创建多个财务标签(例如"服务费"、"转雇主分期")',
            '<strong>定价分期(Pricing Tiers):</strong> 按分期设定人头价格 + 人数 + 备注',
            '<strong>备注弹窗:</strong> 点击备注 → 弹出大窗口，附 500 字符计数器',
            '<strong>铅笔 / 垃圾桶按钮:</strong> 编辑/删除分期(删除时会要求确认)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开 Edit Job → 点击 <strong>"Financial"</strong> 标签或"财务"按钮</li>
            <li>点击 <strong>"+ 新增标签"</strong> → 命名(不可为空/不可重复)</li>
            <li>选择"按人头(Per-head)"模式 → 新增定价分期</li>
            <li>点击<strong>备注框</strong> → 弹出窗口供输入</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>该备注也会显示在发票/收据上</strong> —— 可用于向客户说明该费用的具体内容
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">将工作送入 Workflow</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/05-send-to-workflow',
        'alt' => 'Send to Workflow + Bulk Send 按钮',
        'caption' => 'Send to Workflow —— 可逐个发送，也可一次性整批发送',
        'callouts' => [
            '<strong>Send to Workflow:</strong> 将工作送入 Workflow 流程',
            '<strong>Bulk Send:</strong> 一键发送整份 MOU 批次',
            '<strong>权限:</strong> 仅限 approve-production(Admin/Super Admin)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>确认文件 + 数据已准备就绪</li>
            <li>点击 <strong>"Send to Workflow"</strong>(单个)或 <strong>"整批发送"</strong>(Bulk)</li>
            <li>工作会移至 <strong>Workflow</strong> 菜单</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>注意:</strong> 已送入 Workflow 的工作无法在 Pre-Prod 中编辑 —— 需在 Workflow 中修改
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 为什么看不到"Send to Workflow"按钮?</dt>
        <dd>A: 请检查您的角色 —— 需拥有 <code>approve-production</code> 权限(Admin/Super Admin)</dd>

        <dt>Q: 雇员在 Pre-Prod 期间离职了?</dt>
        <dd>A: 将该雇员从 Pre-Prod 工作中移除，如所有人都已离职则可取消整份工作</dd>

        <dt>Q: 一名雇员可以同时出现在多个 Pre-Prod 工作中吗?</dt>
        <dd>A: 可以，只要 Work Type 不同(例如同时进行 MOU + 签证续签) —— 不能重复相同的 Work Type</dd>
    </dl>
</section>
