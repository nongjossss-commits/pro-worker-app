{{-- User Manual: Registration Resolution (Chinese) --}}

<h4><i class="bi bi-file-earmark-text-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"登记决议(Registration Resolution)"</strong> 菜单用于管理
    政府定期发布的外籍劳工<strong>新一轮登记内阁决议</strong>。
    系统会保存表格、时间安排以及加入该决议的雇员
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> —— 拥有完整权限</li>
    <li><span class="manual-role">Caretaker</span> —— 仅可查看</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>决议标签栏(Tabs)</strong> —— 每个标签代表 1 项内阁决议(例如 2023 年 11 月决议、2024 年 3 月决议)</li>
    <li><strong>雇主卡片</strong> —— 显示在该决议中拥有雇员的雇主</li>
    <li><strong>状态筛选</strong> —— 按步骤筛选(待处理、进行中、已完成)</li>
    <li><strong>进度筛选</strong> —— 按 visa-only / both / renewal 筛选</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 选择所需的决议</h5>
<div class="manual-step">
    点击该决议的标签 → 显示该决议中的所有雇主 + 雇员
</div>

<h5>2. 将雇员加入决议</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开雇主卡片</li>
        <li>点击"加入雇员到决议"</li>
        <li>选择要加入本决议的雇员</li>
        <li>点击"确认"</li>
    </ol>
</div>

<h5>3. 追踪各雇员的状态</h5>
<div class="manual-step">
    雇员卡片会显示：
    <ul class="mb-0">
        <li>浅蓝色 = visa only(仅办理签证)</li>
        <li>深蓝色 = both(同时办理签证 + 工作许可证)</li>
        <li>实线边框 = 目前已完成的最高步骤</li>
    </ul>
</div>

<h5>4. 使用多重条件筛选</h5>
<div class="manual-step">
    按住 Ctrl/Cmd 可同时选择多个状态 —— 一次性按多个进度条件筛选
</div>

<h5>5. Auto Settings —— 按标签单独设置(Per-tab)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开所需的决议标签 → 点击 <strong>"Auto Settings"</strong> 按钮</li>
        <li>弹窗标题会显示<strong>标签名称</strong>，并提示该设置仅适用于此标签</li>
        <li>填写 Auto WP/Visa Expiry + MOU Group → 点击 Save</li>
        <li>每个标签都有各自独立的 Auto Settings，互不重叠</li>
    </ol>
</div>

<h5>6. 自动拉取雇员进入菜单(Add-only)</h5>
<div class="manual-step">
    工作许可证或签证到期日与 Auto Settings 相符的雇员，会<strong>立即被自动拉入菜单</strong>
    <br>
    已在菜单中的雇员，在日期更新时<strong>不会被移除</strong> —— 只会根据进度更改颜色(none / visa_only / work_permit_only / both)
    <br>
    只有手动点击完成 / 取消，才会将雇员从菜单中移除
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>进度标签颜色:</strong> 系统使用 4 个颜色等级，便于快速查看每位雇员所处的阶段
</div>

<div class="manual-tip">
    <strong>状态筛选只显示匹配项:</strong> 如筛选"Visa only"，将只看到有雇员仅办理签证的雇主
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 可以新增决议吗?</dt>
    <dd>A: 可以 —— Super Admin 可通过 Resolution Tabs Settings 菜单新增</dd>

    <dt>Q: 一名雇员可以同时属于多个决议吗?</dt>
    <dd>A: 可以 —— 一名雇员可加入多个决议，并会显示在其加入的每个决议的卡片中</dd>

    <dt>Q: 为什么雇主卡片不见了?</dt>
    <dd>A: 如果该雇主在当前筛选范围内没有雇员，卡片就不会显示</dd>
</dl>
