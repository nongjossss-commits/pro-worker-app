{{-- User Manual: P Production (Chinese) --}}

<h4><i class="bi bi-clipboard-data-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"P Production"</strong> 菜单是办公室所有正在处理工作的中枢 ——
    从在 Sales 菜单成交的新客户 → 进入文件准备阶段(Pre-Production)
    → 再转交给 Workflow 继续处理
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> —— 拥有完整权限</li>
    <li><span class="manual-role">Caretaker</span> —— 仅可查看，部分内容无法编辑</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li>顶部的<strong>统计摘要卡片</strong> —— 即将到期、进行中、待审核的工作数量</li>
    <li><strong>筛选栏</strong> —— 按雇主、业务负责人、工作类型(MOU/签证)筛选</li>
    <li><strong>工作卡片</strong> —— 每张卡片显示销售人员照片 + 雇主名称 + 雇员人数 + 各步骤状态</li>
    <li><strong>"Send to Workflow" 按钮</strong> —— 将工作送入 Workflow 流程(仅限拥有 approve-production 权限的角色)</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 打开工作查看详情</h5>
<div class="manual-step">
    点击工作卡片 → 进入 Edit Job 页面，内含多个标签：雇员、文件、财务、进度
</div>

<h5>2. 逐一编辑雇员信息</h5>
<div class="manual-step">
    在"雇员"标签中 —— 每张雇员卡片都有编辑、查看文件、上传照片按钮。
    可使用<strong>文件扫描(Document Scanner)</strong>功能直接从相机拍照上传至系统
</div>

<h5>3. 添加自定义字段(Custom Field)</h5>
<div class="manual-step">
    某些工作需要额外的特殊信息 —— 点击卡片上的"Fields"按钮 → 根据需要新增字段
    (例如"体检证明编号"、"入职培训日期")
</div>

<h5>4. 将工作送入 Workflow</h5>
<div class="manual-step">
    文件准备就绪后 → 点击 <strong>"Send to Workflow"</strong> 按钮
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        仅限拥有 <code>approve-production</code> 权限的角色(Admin/Super Admin)
    </div>
</div>

<h5>5. 整批一次性发送(Bulk Send)</h5>
<div class="manual-step">
    如同一份 MOU 下有多名雇员 —— 点击 MOU 卡片上的"整批发送"按钮，一次性全部送出
</div>

<h5>6. 工作单中的财务标签(Financial Tab)</h5>
<div class="manual-step">
    打开 Edit Job → 点击 <strong>"Financial"</strong> 标签，或点击雇主卡片上的"财务"按钮
    <ul class="mb-0">
        <li>可在同一份工作单中<strong>创建多个财务标签</strong>(例如"缅甸 MOU 服务费"、"转雇主分期")
            —— 点击 <strong>"+ 新增标签"</strong> → 命名标签(必填 —— 不可为空 / 不可重复)</li>
        <li>点击 <i class="bi bi-pencil-square"></i> 图标，或<strong>双击标签名称</strong>，即可重命名</li>
        <li>点击 <i class="bi bi-trash"></i> 图标删除标签(会出现确认提示 + 显示影响范围)</li>
    </ul>
</div>

<h5>7. 设置按人头计价 + 分期备注</h5>
<div class="manual-step">
    在财务标签中选择 <strong>"按人头(Per-head)"</strong> 模式：
    <ul class="mb-0">
        <li>新增价格分期(Tier) —— 每个分期包含价格 + 人数 + <strong>备注</strong></li>
        <li>点击<strong>备注框</strong>或 <i class="bi bi-pencil-square"></i> 图标，打开大窗口进行编辑(附 500 字符计数器 + Ctrl+Enter 保存)</li>
        <li>该备注也会显示在按此分期开具的发票/收据上</li>
        <li>点击 <i class="bi bi-trash"></i> 删除分期 —— 会出现确认提示，若有雇员已分配至该分期会另行警告</li>
    </ul>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>快速查看整体状态:</strong> 卡片颜色表示紧急程度 —— 黄色 = 即将到期，红色 = 已逾期
</div>

<div class="manual-tip">
    <strong>Pre-Production 与 Workflow 的区别:</strong> 本菜单中的工作属于 Pre-Production(文件准备阶段)，
    点击 Send to Workflow 后，工作会移至"Workflow"菜单
</div>

<div class="manual-warn">
    <strong>注意:</strong> 已送入 Workflow 的工作，无法再在 Production 中编辑 —— 需前往 Workflow 中修改
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 为什么看不到"Send to Workflow"按钮?</dt>
    <dd>A: 您的角色没有 approve-production 权限 —— 请联系 Admin 添加权限</dd>

    <dt>Q: 点击 Send to Workflow 后，工作去了哪里?</dt>
    <dd>A: 已移至"Workflow"菜单 —— 按雇主名称筛选即可找到</dd>

    <dt>Q: 雇员在 Production 处理期间离职了?</dt>
    <dd>A: 打开该雇员卡片 → 点击"离职" —— 该工作会自动从 Production 中移除</dd>
</dl>
