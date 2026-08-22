{{-- Training Edition: Group & Team (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Group & Team') }} — {{ __('将雇员组织为若干小团队') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"分组与团队(Group & Team)"</strong> 菜单用于将<strong>雇员分组</strong>为若干小团队，
        例如"A 厂早班团队"、"家政团队" —— 以便作为一个整体进行管理。
        分为 2 种类型：<strong>Affiliated</strong>(隶属雇主) + <strong>Independent</strong>(独立)
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker(仅限自己负责的分组)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">选择分组类型 —— Affiliated vs Independent</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/01-type-selection',
        'alt' => '分组类型选择页面，含 2 张卡片',
        'caption' => '分组类型选择 —— 选择 Affiliated 或 Independent',
        'callouts' => [
            '<strong>Affiliated:</strong> 绑定 1 个雇主 —— 组内雇员必须属于该雇主',
            '<strong>Independent:</strong> 不绑定雇主 —— 可加入任何雇主的雇员',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>分组与团队</strong></li>
            <li>选择 Affiliated 或 Independent</li>
            <li>如为 Affiliated → 先选择雇主</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">创建分组 + 新增成员 + 子团队</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/02-manage',
        'alt' => 'Manage Groups 页面，含子团队手风琴列表',
        'caption' => 'Manage Groups —— 每个分组的手风琴列表 + 子团队',
        'callouts' => [
            '<strong>+ 新建分组:</strong> 命名 → 确认',
            '<strong>+ 新增成员:</strong> 搜索雇员 → 勾选 → 确认',
            '<strong>+ 新建子团队:</strong> 在分组内划分子团队',
            '<strong>拖放:</strong> 在团队之间拖动雇员',
            '<strong>高亮闪烁:</strong> 最近新增的团队会以橙色闪烁提示',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击"+ 新建分组" → 命名</li>
            <li>点击"+ 新增成员" → 搜索 → 勾选 → 确认</li>
            <li>如需进一步细分，点击"+ 新建子团队"</li>
            <li>可在团队之间拖动雇员</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">在创建工作时使用分组</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/03-use-in-workflow',
        'alt' => '在创建 Production / Workflow 项目时使用 Group Name',
        'caption' => 'Group Name —— 用于指定 Production/Workflow 项目',
        'callouts' => [
            '<strong>"Group Name" 字段:</strong> 出现在每个工作创建表单中',
            '<strong>自动关联:</strong> 系统会一并带入该分组的雇员',
            '<strong>作为一个整体管理:</strong> 批量开票 + 批量生成 PDF',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开 Production / Workflow 中的工作创建表单</li>
            <li>填写与已创建分组一致的 Group Name</li>
            <li>系统会自动关联</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 一名雇员最多可以属于几个分组?</dt>
        <dd>A: 可以同时属于多个 —— 例如同时属于雇主 A 的 Affiliated 分组 + Independent 的"3/25 面试"分组</dd>

        <dt>Q: 雇员转换雇主后，原来的 Affiliated 分组会怎样?</dt>
        <dd>A: 会被<strong>自动移除</strong>原 Affiliated 分组，因为该分组与雇主绑定 —— Independent 分组不受影响</dd>

        <dt>Q: 可以创建同名分组吗?</dt>
        <dd>A: 在同一雇主下不可以 —— 系统会提示警告</dd>
    </dl>
</section>
