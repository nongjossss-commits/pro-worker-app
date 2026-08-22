{{-- User Manual: Employment History (Chinese) --}}

<h4><i class="bi bi-person-badge me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"雇用历史(Employment History)"</strong> 菜单汇集了曾经在系统中出现过的<strong>所有雇员</strong>，
    无论是<em>目前在职</em>、<em>已离职(Resigned)</em>、<em>合同已到期</em>，还是<em>已转至其他雇主</em>。
    用于<strong>查看历史记录</strong>、<strong>查找旧雇员</strong>，以及将已离职雇员<strong>批量转移</strong>到新雇主
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> —— 拥有完整权限</li>
    <li><span class="manual-role">Caretaker</span> —— 只能看到自己所负责雇主的雇员</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>筛选栏</strong> 位于顶部 —— 搜索，按国籍/MOU 类型/粉卡/护照类型筛选</li>
    <li><strong>右上角</strong> —— 导出 CSV 按钮、切换视图(卡片/表格)、每页显示数量选择</li>
    <li><strong>雇员列表</strong> —— 显示所有人，包括在职和非在职(含离职/合同到期)</li>
    <li><strong>批量操作栏</strong>(悬浮于底部) —— 勾选多名雇员后 → 转移雇主 / 导出 / 生成 PDF</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 查询历史雇员</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>在"搜索..."框中输入姓名 / 护照号码</li>
        <li>如需要，选择其他筛选条件(国籍 / MOU / 护照)</li>
        <li>点击"筛选" —— 结果同时包含在职与非在职雇员</li>
        <li>点击"清除"以重置筛选条件</li>
    </ol>
</div>

<h5>2. 批量将旧雇员转移至新雇主</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>勾选要转移的雇员(可多选)</li>
        <li>底部会弹出批量操作栏 → 点击"操作(Actions)" → <strong>"转移雇主"</strong></li>
        <li>选择目标雇主 → 确认</li>
        <li>系统会立即将所选雇员全部转移到新雇主</li>
    </ol>
</div>

<h5>3. 导出数据为 CSV / Excel</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>如只需特定数据，请先进行筛选</li>
        <li>点击右上角的"导出 CSV" —— 文件会立即下载</li>
        <li>或使用批量操作栏中的"Advanced Export" —— 可自行选择要导出的列</li>
    </ol>
</div>

<h5>4. 批量自动生成 PDF</h5>
<div class="manual-step">
    勾选多名雇员 → 批量操作栏 → "Automated PDF" → 选择模板 → 系统会为所有人一次性生成 PDF
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>与"雇员"菜单的区别:</strong> 普通的雇员菜单只显示在职雇员，
    而本菜单会显示<strong>所有人</strong>，包括已离职/合同到期的雇员 —— 需要查找旧人员或回顾历史记录时使用
</div>

<div class="manual-tip">
    <strong>表格视图 vs 卡片视图:</strong> 表格适合同时比较多人的数据，
    卡片适合逐一查看某个人的详细信息(含照片和标签)
</div>

<div class="manual-warn">
    <strong>转移雇主:</strong> 被转移雇员的 <strong>employer_id 会发生变化</strong>，
    系统会立即<strong>自动取消</strong>该雇员尚未处理的"离职通知(notify_out)"记录(如有)
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 为什么看到的雇员数量比预期少?</dt>
    <dd>A: 请检查该用户的权限 —— 如果是 Caretaker 角色，只能看到自己所负责雇主的雇员</dd>

    <dt>Q: 转移雇主后，雇员立即从 notify_out 菜单中消失了?</dt>
    <dd>A: 这是正常的 —— 当 employer_id 变更时，系统会自动取消该雇员待处理的 notify_out 记录，因为 notify_out 是用于"脱离旧雇主"，转移后已不再适用</dd>

    <dt>Q: 已删除(回收站中)的雇员会显示在此菜单中吗?</dt>
    <dd>A: 不会 —— 请前往"回收站"(Central Trash)菜单查看，可以从那里恢复</dd>

    <dt>Q: CSV 导出包含哪些列?</dt>
    <dd>A: 基础列 —— 使用"Advanced Export"可自行选择列(包括 MOU、到期日、状态等)</dd>
</dl>
