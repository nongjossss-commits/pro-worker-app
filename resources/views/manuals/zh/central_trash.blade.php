{{-- User Manual: Central Trash (Chinese) --}}

<h4><i class="bi bi-trash-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"回收站(Central Trash)"</strong> 菜单集中保存系统中<strong>所有被删除的数据</strong>，
    包括雇主、雇员、授权代表、地址等，全部集中<strong>在一处</strong>，
    以便轻松进行<strong>恢复(Restore)</strong>或<strong>永久删除(Force Delete)</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> —— 可以进入</li>
    <li>需拥有 <code>view-trash</code> 权限，并根据数据类型拥有 <code>restore-*</code> 或 <code>force-delete-*</code> 权限</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>标签栏</strong> —— 按数据类型区分(Employers、Employees、Delegates 等)</li>
    <li><strong>表格</strong> —— 显示已删除的项目 + 删除日期 + 删除人</li>
    <li>每行都有<strong>恢复按钮和永久删除按钮</strong></li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 恢复已删除的项目</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>选择数据类型标签(例如 Employees)</li>
        <li>找到要恢复的记录</li>
        <li>点击 <i class="bi bi-arrow-counterclockwise"></i> "恢复" 按钮</li>
        <li>确认 —— 该记录将返回原来的菜单</li>
    </ol>
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        恢复雇员(Employee)可能会超出系统配额 —— 若超出配额，则无法恢复，需先增加配额
    </div>
</div>

<h5>2. 永久删除(切勿随意操作！)</h5>
<div class="manual-warn">
    点击 <i class="bi bi-x-circle-fill text-danger"></i> "永久删除" 按钮 → 确认两次 → 记录将<strong>永久消失</strong>，无法恢复
    <br><br>
    仅限以下情况使用：
    <ul class="mb-0">
        <li>误输入的重复数据</li>
        <li>忘记删除的测试数据</li>
        <li>需要真正清理的旧系统遗留数据</li>
    </ul>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>软删除(Soft Delete)与永久删除(Force Delete)的区别:</strong> 软删除 = 移入回收站(可恢复)，永久删除 = 彻底删除(无法恢复)
</div>

<div class="manual-tip">
    <strong>恢复后:</strong> 请检查恢复的记录是否存在数据冲突(例如已恢复的雇主，其雇员仍在回收站中)
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 已永久删除的数据还能找回吗?</dt>
    <dd>A: <strong>无法找回</strong> —— 需要联系服务器管理员查看数据库备份</dd>

    <dt>Q: 回收站会保留多久?</dt>
    <dd>A: 没有期限 —— 系统不会自动删除，如需清理请手动删除</dd>

    <dt>Q: 删除了雇主，雇员会去哪里?</dt>
    <dd>A: 雇员数据仍然保留 —— 只是与该雇主的关联会消失，需要重新指定雇主</dd>
</dl>
