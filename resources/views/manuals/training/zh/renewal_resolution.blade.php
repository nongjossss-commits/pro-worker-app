{{-- Training Edition: Renewal Resolution (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-arrow-clockwise"></i> {{ __('Renewal Resolution') }} — {{ __('管理用于劳工续签的内阁决议') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"续签决议(Renewal Resolution)"</strong> 菜单用于管理<strong>为即将到期的雇员办理续签</strong>的内阁决议。
        使用与登记决议相同的机制 —— 但侧重于工作许可证或签证即将到期的现有雇员
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
    <h2 class="slide-title">打开菜单 + 选择决议标签</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/01-tab-bar',
        'alt' => '续签决议主页面 + 标签栏',
        'caption' => '续签决议 —— 每个标签代表一轮续签',
        'callouts' => [
            '<strong>标签栏:</strong> 例如"2025 年续签第 1 轮"',
            '<strong>统计卡片:</strong> 总雇员数 / 已完成 / 待处理',
            '<strong>筛选标签(pills):</strong> 5 种颜色对应不同进度',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>续签决议</strong></li>
            <li>点击所需续签决议的标签</li>
            <li>查看顶部摘要卡片的整体情况</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">筛选标签(Filter pills) —— 按进度筛选</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/02-filter-pills',
        'alt' => '5 种颜色的筛选标签',
        'caption' => '筛选标签 —— 可同时选择多个',
        'callouts' => [
            '<strong>⚪ 尚未续签:</strong> 尚未开始续签的雇员',
            '<strong>🟣 已更新签证:</strong> 签证已续签，工作许可证待处理',
            '<strong>🟡 已更新工作许可证:</strong> 工作许可证已续签，签证待处理',
            '<strong>🔵 全部续签完成 —— 可以完成:</strong> 两项均已完成，可关闭',
            '<strong>🟢 已完成:</strong> 已关闭',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击要筛选的状态标签</li>
            <li>可同时点击多个(开/关切换)</li>
            <li>标签内的数字表示符合该筛选条件的数量</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">设置 Auto Settings(按标签)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/03-auto-settings',
        'alt' => '续签标签的 Auto Settings 弹窗',
        'caption' => 'Auto Settings —— 每个标签分别设定目标到期日',
        'callouts' => [
            '<strong>弹窗标题显示标签名称:</strong> 避免与其他标签混淆',
            '<strong>Auto WP/Visa Expiry:</strong> 适用于此标签的到期日',
            '<strong>Auto MOU Group:</strong> 此标签对应的 MOU 类型',
            '<strong>Save Settings:</strong> 仅适用于此标签',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开标签 → 点击 <strong>"Auto Settings"</strong></li>
            <li>填写目标到期日</li>
            <li>点击 Save</li>
            <li>到期日匹配的雇员 → 会自动拉入该标签</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">追踪进度 + 勾选步骤</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/04-progress-tracking',
        'alt' => '含进度步骤的雇主卡片',
        'caption' => '雇主卡片 —— 每位雇员都有可勾选的步骤',
        'callouts' => [
            '<strong>雇员卡片:</strong> 颜色会根据进度变化(5 种颜色)',
            '<strong>步骤勾选框:</strong> 按步骤勾选',
            '<strong>卡片浮动至顶部:</strong> 每次勾选/修改数据后 → 刷新后最新的卡片会排在最上面',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开雇主卡片</li>
            <li>勾选各步骤的勾选框</li>
            <li>雇员卡片的颜色会根据进度变化</li>
            <li>全部续签完成后 → 变为绿色 → 点击 <strong>"完成"</strong> 关闭该工作</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">统计摘要卡片 + 整体概览</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/05-stats-cards',
        'alt' => '页面顶部的统计摘要卡片',
        'caption' => '统计摘要卡片 —— 显示该标签的整体情况',
        'callouts' => [
            '<strong>雇员总数:</strong> 该决议中的总人数',
            '<strong>已取消总数:</strong> 被取消的数量',
            '<strong>已记录至数据库:</strong> 已完成的数量',
            '<strong>Biometrics Collected:</strong> 已采集生物识别信息的数量',
            '<strong>雇主总数:</strong> 该标签中的雇主数量',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>点击统计卡片:</strong> = 立即筛选至该类别(例如点击"已完成" → 只筛选出已完成的项目)
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 雇员已经过期了，还能续签吗?</dt>
        <dd>A: 取决于该决议的条件 —— 部分决议允许追溯续签，请先查阅相关部级法规</dd>

        <dt>Q: 为什么无法为该雇员续签?</dt>
        <dd>A: 请检查该雇员状态是否为"在职"(而非离职/合同到期)</dd>

        <dt>Q: 更新到期日后雇员被移出了菜单?</dt>
        <dd>A: 正常情况下不应发生 —— 系统采用"仅新增(add-only)"机制，不会自动移除任何人(此前的一个 bug 已在早期提交中修复)</dd>
    </dl>
</section>
