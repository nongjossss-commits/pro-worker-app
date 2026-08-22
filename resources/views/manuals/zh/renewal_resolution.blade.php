{{-- User Manual: Renewal Resolution (Chinese) --}}

<h4><i class="bi bi-arrow-clockwise me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"续签决议(Renewal Resolution)"</strong> 菜单用于管理关于
    <strong>续签</strong>已到期外籍劳工证件的<strong>内阁决议</strong>，
    例如续签工作许可证(Work Permit)、续签签证(Visa)、续签 MOU
</p>
<p>
    与<strong>登记决议(Registration Resolution)</strong>类似 —— 但侧重于为已在系统中的雇员续签，而非新登记
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> —— 可以进入</li>
    <li><span class="manual-role">Caretaker</span> —— 仅可查看</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>续签决议标签栏</strong> —— 每个标签代表一轮续签决议(例如"2024 年续签第 1 轮")</li>
    <li><strong>雇主 + 雇员卡片</strong> —— 与登记决议使用相同机制</li>
    <li><strong>进度筛选</strong> —— visa-only、work-permit-only、both</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 选择续签决议</h5>
<div class="manual-step">
    点击所需的续签决议标签
</div>

<h5>2. 将雇员加入决议</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开雇主卡片</li>
        <li>点击"加入雇员到决议"</li>
        <li>选择<strong>即将到期</strong>的雇员(系统会自动高亮显示)</li>
        <li>点击"确认"</li>
    </ol>
</div>

<h5>3. 查看整体进度</h5>
<div class="manual-step">
    顶部摘要卡片显示：该决议中的雇员总数、已完成数、剩余数
</div>

<h5>4. 自动应用(Auto-apply)</h5>
<div class="manual-step">
    <strong>Workflow MOU 自动应用</strong>系统会与本决议协同工作 ——
    在 Workflow 中完成的续签工作，每 24 小时会自动应用回本决议
</div>

<h5>5. Auto Settings —— 按标签单独设置(Per-tab)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开所需的决议标签 → 点击右上角的 <strong>"Auto Settings"</strong> 按钮</li>
        <li>弹窗标题会显示<strong>标签名称</strong>(例如"31/03/2026")，并提示该设置仅适用于此标签</li>
        <li>填写：
            <ul>
                <li><strong>Auto Work Permit Expiry Date</strong> —— 目标工作许可证到期日</li>
                <li><strong>Auto Visa Expiry Date</strong> —— 目标签证到期日</li>
                <li><strong>Auto MOU Group</strong> —— 目标 MOU 组别</li>
            </ul>
        </li>
        <li>点击 Save → 仅适用于<strong>此标签</strong>，不影响其他标签</li>
        <li>每个标签都有各自独立的 Auto Settings —— 31/03/2026 标签中的雇员会根据该标签的设置来评估颜色/进度，与其他标签无关</li>
    </ol>
</div>

<h5>6. 雇员进度颜色系统</h5>
<div class="manual-step">
    每张卡片上的雇员会根据 Auto Settings 显示相应的进度颜色：
    <ul class="mb-0">
        <li>⚪ <strong>none</strong> = 尚未续签</li>
        <li>🟦 <strong>visa_only</strong> = 已续签签证(等待工作许可证)</li>
        <li>🟧 <strong>work_permit_only</strong> = 已续签工作许可证(等待签证)</li>
        <li>🟩 <strong>both</strong> = 全部续签完成，可关闭</li>
        <li>✅ <strong>completed</strong> = 已完成关闭</li>
    </ul>
</div>

<h5>7. 自动拉取雇员进入菜单</h5>
<div class="manual-step">
    工作许可证或签证到期日与某标签的 Auto Settings 相符的雇员，会在日期更新时<strong>立即被自动拉入该标签</strong>
    <br>
    <strong>"仅新增(add-only)"机制:</strong> 已在菜单中的雇员，在日期更新时<strong>不会被移除</strong> —— 只会根据进度更改颜色(只有手动点击完成/取消才会将其移出菜单)
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>建议在到期前 60 天开始续签:</strong> 系统会在 Notifications 菜单和 Incomplete Data 菜单中
    高亮显示即将到期的雇员
</div>

<div class="manual-tip">
    <strong>登记决议 vs 续签决议:</strong> 登记 = 新雇员进入系统，续签 = 现有雇员即将到期
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 雇员已经过期了，还能续签吗?</dt>
    <dd>A: 取决于该决议的条件 —— 部分决议允许追溯续签，请先查阅相关部级法规</dd>

    <dt>Q: 为什么无法为该雇员续签?</dt>
    <dd>A: 请检查该雇员状态是否为"在职"(而非离职/合同到期)</dd>
</dl>
