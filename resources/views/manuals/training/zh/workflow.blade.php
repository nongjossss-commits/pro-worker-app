{{-- Training Edition: Workflow — slide-friendly with annotated screenshots (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-diagram-3-fill"></i> {{ __('Workflow') }} — {{ __('正在处理中工作的枢纽') }}
    </h3>
    <p class="training-intro-desc">
        本菜单是<strong>所有正在按流程推进的工作</strong>的枢纽 ——
        例如向劳工厅申报文件、办理护照、申请签证、核发工作许可证。
        用户可以为每位雇员<strong>勾选完成的步骤</strong>，系统会自动追踪进度
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker(仅可查看)</span>
    </div>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">进入 Workflow + 选择标签</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/01-main-view',
        'alt' => 'Workflow 主页面，显示各工作类型的标签',
        'caption' => 'Workflow 主页面 —— 顶部栏为各 Work Type 的标签',
        'callouts' => [
            '<strong>标签栏:</strong> 选择工作类型(Notify In / 签证续签 / 引进 MOU / Notify Out)',
            '<strong>+ Add Employee 按钮:</strong> 将雇员加入工作',
            '<strong>筛选:</strong> 按 Operator、状态筛选，按姓名搜索',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击<strong>侧边栏 → Workflow</strong></li>
            <li>选择要处理的 Work Type <strong>标签</strong></li>
            <li>各雇主的卡片会连同其雇员名单一起显示</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>小贴士:</strong> 有<strong>最新动态</strong>的卡片，每次刷新时都会浮动至最上方
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">勾选雇员的步骤</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/02-tick-step',
        'alt' => '雇员卡片，含各步骤的勾选框',
        'caption' => '雇员卡片，含各步骤的勾选框',
        'callouts' => [
            '<strong>勾选框:</strong> 勾选表示该步骤已完成',
            '<strong>步骤名称:</strong> 步骤名称(例如"提交申请"、"缴纳费用")',
            '<strong>进度条:</strong> 整体进度百分比',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击已完成步骤的<strong>勾选框</strong></li>
            <li>系统会自动记录<strong>时间戳 + 操作人</strong></li>
            <li>进度条会立即更新</li>
            <li>所有步骤完成后 → 点击 <strong>Finish</strong> 关闭该工作</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>注意:</strong> 勾错可再次点击取消，但无论如何操作日志都会记录此次变更
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">将雇员加入 Workflow 工作</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/03-add-employee-modal',
        'alt' => '将雇员加入 Workflow 的弹窗',
        'caption' => '新增雇员弹窗 —— 选择工作类型 + 雇主 + 雇员',
        'callouts' => [
            '<strong>可搜索雇主下拉菜单:</strong> 输入名称/代码即可搜索',
            '<strong>雇员列表:</strong> 该雇主名下的雇员',
            '<strong>批量选择:</strong> 一次选择多人',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击标签顶部的 <strong>"+ Add Employee"</strong></li>
            <li>选择<strong>雇主</strong>(可输入搜索)</li>
            <li>选择<strong>雇员</strong>(支持多选)</li>
            <li>点击 <strong>"Add"</strong> —— 雇员会立即出现在该雇主的卡片上</li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">"离职通知(Notify Out)"标签</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/04-notify-out',
        'alt' => 'Notify Out 标签中的雇员卡片，含日期 + 原因字段',
        'caption' => 'Notify Out 标签 —— 黄色提示条用于填写离职通知日期与原因',
        'callouts' => [
            '<strong>离职通知日期(必填):</strong> 日期选择器，点击 Finish 前必须填写',
            '<strong>原因:</strong> 离职 / 解雇 / 合同到期 / 转换雇主 / 其他',
            '<strong>彩色标签:</strong> 黄色 = 需填写，绿色 = 可以完成',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开 <strong>"离职通知"</strong> 标签</li>
            <li>新增雇员(可搜索系统中任何人 —— 全局搜索)</li>
            <li>在黄色提示条中填写<strong>离职通知日期</strong> + <strong>原因</strong></li>
            <li>点击 <strong>Finish</strong> —— 系统会自动将雇员状态更新为"resigned"</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>小贴士:</strong> 如果雇员是转换雇主(并非真正离职) → notify_out 会自动取消
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">引进 MOU —— 创建需求卡(Demand Card)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/05-mou-import',
        'alt' => '引进 MOU 卡片，含子类型彩色标签',
        'caption' => 'MOU 引进卡片 —— 用颜色与标签显示子类型(Return/New/Pending)',
        'callouts' => [
            '<strong>边框颜色:</strong> 🟢 Return | 🔵 New from Origin | 🟠 Pending',
            '<strong>标签:</strong> 点击可事后更改类型',
            '<strong>可搜索雇主:</strong> 输入搜索代替滚动查找',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开 <strong>"引进 MOU"</strong> 标签 → 点击 <strong>"Create Job"</strong></li>
            <li>选择雇主(可输入搜索) + 指定类型:
                <ul>
                    <li>🟢 <strong>Return</strong> —— 雇员已在泰国</li>
                    <li>🔵 <strong>New from Origin</strong> —— 来自输出国的新人员</li>
                    <li>🟠 <strong>尚不确定</strong> —— 稍后再决定</li>
                </ul>
            </li>
            <li>填写国籍 + 男女人数</li>
            <li>点击 <strong>Create Demand Card</strong></li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 为什么我的卡片没有浮动到最上方?</dt>
        <dd>A: 系统只在<strong>刷新</strong>或从其他菜单返回时才会重新排序 —— 处理过程中界面不会跳动(避免干扰操作)</dd>

        <dt>Q: 雇员从 Notify Out 标签中消失了?</dt>
        <dd>A: 转换雇主时会自动取消 —— notify_out 表示"脱离旧雇主"，转换后已不再适用</dd>

        <dt>Q: Caretaker 用户为什么有些卡片能看到、有些看不到?</dt>
        <dd>A: Caretaker 只能看到分配给自己的雇主</dd>
    </dl>
</section>
