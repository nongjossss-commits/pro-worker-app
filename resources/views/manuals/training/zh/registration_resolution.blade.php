{{-- Training Edition: Registration Resolution (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-card-checklist"></i> {{ __('Registration Resolution') }} — {{ __('管理用于劳工登记的内阁决议') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"登记决议(Registration Resolution)"</strong> 菜单用于管理关于新外籍劳工登记的<strong>内阁决议</strong>，
        例如 9 月 16 日内阁决议、疫情期间的特殊决议 —— 系统支持以标签形式<strong>同时管理多项决议</strong>
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
    <h2 class="slide-title">选择决议标签(Resolution Tab)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/01-tab-bar',
        'alt' => '决议标签栏，含新建按钮',
        'caption' => '决议标签栏 —— 每个标签代表一轮内阁决议',
        'callouts' => [
            '<strong>标签栏:</strong> 每个标签代表 1 轮决议(例如"9月16日内阁决议")',
            '<strong>+ Add Tab:</strong> 新建决议(仅限 Super Admin)',
            '<strong>⚙️ Edit Tab:</strong> 重命名 / 删除决议',
            '<strong>⭐ Default:</strong> 首次进入时显示的默认标签',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>登记决议</strong></li>
            <li>点击所需决议的标签</li>
            <li>页面会刷新并显示该决议的数据</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">雇员进度颜色系统</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/02-color-legend',
        'alt' => '显示 5 种进度颜色的图例',
        'caption' => '颜色图例 —— 5 种颜色显示各雇员的进度',
        'callouts' => [
            '<strong>⚪ Not renewed yet:</strong> 尚未开始',
            '<strong>🟣 已更新签证:</strong> 签证已续签，等待工作许可证',
            '<strong>🟡 已更新工作许可证:</strong> 工作许可证已续签，等待签证',
            '<strong>🔵 Both renewed:</strong> 全部续签完成，可关闭',
            '<strong>🟢 Finalized:</strong> 已完成关闭',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>颜色自动更新:</strong> 只要雇员的到期日更新到与 Auto Settings 目标一致 → 颜色会立即改变
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">设置 Auto Settings(按标签)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/03-auto-settings',
        'alt' => 'Auto Settings 弹窗，显示标签名称及 visa/wp/mou 字段',
        'caption' => 'Auto Settings —— 每个决议标签分别设置',
        'callouts' => [
            '<strong>弹窗标题:</strong> 直接显示标签名称，表明仅适用于该标签',
            '<strong>Auto WP Expiry:</strong> 目标工作许可证到期日',
            '<strong>Auto Visa Expiry:</strong> 目标签证到期日',
            '<strong>Auto MOU Group:</strong> 目标 MOU 类型',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开所需的决议标签 → 点击 <strong>"Auto Settings"</strong></li>
            <li>填写 WP + 签证到期日 + MOU Group</li>
            <li>点击 Save → 仅适用于<strong>此标签</strong></li>
            <li>日期匹配的雇员 → 会立即自动拉入菜单</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>仅新增(Add-only):</strong> 已在菜单中的雇员，在日期变更时<strong>不会被移除</strong> —— 只有点击 Complete/Cancel 才会将其移出
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">勾选步骤 + 追踪进度</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/04-progress',
        'alt' => '含雇员与步骤的雇主卡片',
        'caption' => '雇主卡片 —— 雇员及各步骤的勾选框',
        'callouts' => [
            '<strong>雇主卡片:</strong> 汇集该雇主的所有雇员',
            '<strong>步骤勾选框:</strong> 勾选表示该步骤已完成',
            '<strong>最新卡片浮动至顶部:</strong> 勾选后刷新 → 该卡片会移至最上方',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开雇主卡片 → 查看雇员名单</li>
            <li>勾选各已完成步骤的勾选框</li>
            <li>系统会自动记录时间戳</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 登记决议与续签决议有什么区别?</dt>
        <dd>A: 登记 = 新雇员首次进入系统，续签 = 现有雇员即将到期</dd>

        <dt>Q: 一个标签的 Auto Settings 会与其他标签重叠吗?</dt>
        <dd>A: 不会 —— 每个标签都有独立的 Auto Settings(per-tab keys)</dd>

        <dt>Q: 为什么雇员从菜单中消失了?</dt>
        <dd>A: 系统<strong>不会自动移除</strong>任何人 —— 只有手动点击"完成"/"取消"，或该雇员转换雇主，才会将其移除</dd>
    </dl>
</section>
