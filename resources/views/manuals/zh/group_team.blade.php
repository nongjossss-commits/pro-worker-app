{{-- User Manual: Group & Team (Chinese) --}}

<h4><i class="bi bi-people-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"分组与团队(Group &amp; Team)"</strong> 菜单用于将<strong>雇员分组</strong>为若干小团队，
    以便作为一个整体进行管理，例如 <em>"A 厂早班团队"</em>、<em>"家政团队"</em>、<em>"建筑团队"</em>。
    用于<strong>批量创建 Production / Workflow 工作</strong>、<strong>批量开票</strong>，以及<strong>让数据保持有序</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> —— 拥有完整权限</li>
    <li><span class="manual-role">Caretaker</span> —— 只能管理自己负责雇主的分组</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<p>进入菜单后，会看到 <strong>2 个选项</strong>：</p>
<ol>
    <li><strong>Affiliated with Employer(隶属雇主的分组)</strong> —— 与特定雇主绑定的分组，组内雇员必须属于该雇主</li>
    <li><strong>Independent / No Employer(独立分组)</strong> —— 不绑定任何雇主的分组，可加入任何雇主的雇员</li>
</ol>
<p>两种类型都有 <strong>Manage(管理)</strong> 页面，显示<strong>每个分组的手风琴列表</strong> + 组内的<strong>子团队</strong></p>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 创建新分组</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>进入"分组与团队" → 选择分组类型(Affiliated / Independent)</li>
        <li>如为 Affiliated → 选择雇主</li>
        <li>点击"<strong>+ 新建分组</strong>"</li>
        <li>为分组命名(例如"A 厂早班团队") → 确认</li>
    </ol>
</div>

<h5>2. 将雇员加入分组</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>展开分组手风琴 → 点击"+ 新增成员"</li>
        <li>输入姓名 / 护照号码进行搜索 → 从列表中选择雇员 → 确认</li>
        <li>Affiliated：只显示该雇主的雇员</li>
        <li>Independent：显示系统中所有雇员</li>
    </ol>
</div>

<h5>3. 在分组内划分子团队</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开分组 → 点击"<strong>+ 新建子团队</strong>"</li>
        <li>为团队命名(例如"A1 团队"、"A2 团队")</li>
        <li>将雇员拖入团队(拖放)，或在团队中点击"新增"</li>
    </ol>
</div>

<h5>4. 在创建工作时使用分组</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开 Pre-Prod / Workflow / Production 菜单</li>
        <li>点击"+ 新增工作"时 → 指定已创建的 Group Name</li>
        <li>系统会将该分组的雇员一并带入 —— 可作为一个整体统一管理</li>
    </ol>
</div>

<h5>5. 移动 / 删除 / 重命名分组</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>展开分组手风琴 → 点击 <i class="bi bi-pencil-square"></i> 图标进行重命名</li>
        <li>点击 <i class="bi bi-trash"></i> 图标删除分组(雇员会从分组中移除，但不会从系统中删除)</li>
        <li>可在团队之间或将雇员移出分组时使用拖放操作</li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>选择合适的分组类型:</strong>
    <ul class="mb-0">
        <li><strong>Affiliated</strong> —— 适合同一雇主的雇员，例如"ABC 工厂全体雇员"</li>
        <li><strong>Independent</strong> —— 适合跨雇主的雇员，例如从多家工厂集中的"需在同一天面试的雇员"</li>
    </ul>
</div>

<div class="manual-tip">
    <strong>工作中的 Group Name:</strong> 创建 Production Order 或 Workflow 项目时，填写与此处分组名称一致的 Group Name，系统会自动关联
</div>

<div class="manual-warn">
    <strong>删除分组:</strong> 删除分组仅表示将雇员从该分组中移除，并不会从系统中删除雇员。
    但<strong>删除子团队</strong>时，团队内的雇员会自动回到上级分组中(不会丢失)
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 一名雇员最多可以属于几个分组?</dt>
    <dd>A: 可以<strong>同时属于多个分组</strong> —— 例如同时属于"A 厂雇员"(Affiliated)和"3/25 面试团队"(Independent)</dd>

    <dt>Q: 雇员转换雇主后，原来的 Affiliated 分组会怎样?</dt>
    <dd>A: 该雇员会被<strong>自动从原 Affiliated 分组中移除</strong>，因为 Affiliated 分组与雇主绑定 —— Independent 分组不受影响</dd>

    <dt>Q: 可以创建同名分组吗?</dt>
    <dd>A: 在同一雇主下不可以 —— 系统会提示"分组名称重复"，需要更改名称</dd>

    <dt>Q: 无法在团队之间拖动雇员?</dt>
    <dd>A: 必须属于同一分组才可拖动 —— 不同分组间的团队请改用"新增成员" → 选择的方式</dd>
</dl>
