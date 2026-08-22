{{-- User Manual: Workflow (Chinese) --}}

<h4><i class="bi bi-diagram-3-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"Workflow"</strong> 菜单是办公室各类流程工作的中枢 ——
    例如向劳工厅申报文件、办理护照、申请签证、核发工作许可证等。
    每项工作都会按照预设的"步骤(Steps)"依次推进
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> —— 可以进入</li>
    <li><span class="manual-role">Caretaker</span> —— 仅可查看，不可编辑</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>步骤栏</strong> 位于顶部 —— 显示工作需经过的各个步骤 + 每个步骤中的工作数量</li>
    <li><strong>筛选栏</strong> —— 按步骤、雇主、工作类型筛选</li>
    <li><strong>工作卡片</strong> —— 显示当前所选步骤中的所有工作</li>
    <li><strong>"Auto-apply MOU" 按钮</strong> —— 用于系统自动处理的 MOU 续签工作</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 查看各步骤中的工作</h5>
<div class="manual-step">
    点击步骤栏中的某个 Step → 仅显示处于该步骤中的工作
</div>

<h5>2. 将工作移至下一步骤</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开该工作卡片</li>
        <li>点击 <strong>"继续 / Next Step"</strong> 按钮</li>
        <li>填写新步骤所需的信息(例如收据编号、日期)</li>
        <li>点击"确认" —— 工作将移至新步骤</li>
    </ol>
</div>

<h5>3. 退回上一步骤</h5>
<div class="manual-step">
    如有错误，可使用 <strong>"Send Back"</strong> 按钮将工作退回上一步骤，
    或使用 <strong>"Send Back to Pre-Production"</strong> 将其退回 Production 菜单
</div>

<h5>4. 设置附加字段(Custom Fields)</h5>
<div class="manual-step">
    点击 MOU 卡片上的"Fields"按钮 → 根据步骤需要添加额外信息
    (例如"体检证明编号"、"面试预约日期")
</div>

<h5>5. 自动续签 MOU(Auto-apply)</h5>
<div class="manual-step">
    系统每 24 小时会<strong>自动处理</strong> MOU 续签工作。
    Admin 可在 Super Admin Settings → Workflow 区块中进行设置
</div>

<h5>6. 引进 MOU —— 创建需求卡(Demand Card)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开 <strong>"引进 MOU"</strong> 标签 → 点击 <strong>"Create Job"</strong> 按钮</li>
        <li>选择 Work Type = 引进 MOU</li>
        <li><strong>选择雇主</strong> —— 可输入搜索(泰文名/英文名/代码)，无需滚动查找</li>
        <li><strong>选择引进 MOU 类型</strong>：
            <ul>
                <li><span class="badge bg-success">Return</span> = 雇员已在泰国 → 可立即记录雇员数据</li>
                <li><span class="badge bg-primary">New from Origin</span> = 来自输出国的新人员 → 尚无雇员数据，等待 Demand → 名单</li>
                <li>如尚不确定 → 可留空，系统会显示为 <span class="badge bg-warning text-dark">Pending Classification</span></li>
            </ul>
        </li>
        <li>填写国籍以及需要引进的男女人数</li>
        <li>点击"Create Demand Card"</li>
    </ol>
</div>

<h5>7. 事后更改引进 MOU 类型</h5>
<div class="manual-step">
    在 Workflow "引进 MOU" 标签页面 → 点击卡片上的<strong>彩色标签(Return/New/Pending)</strong> → 选择新类型 → 点击 Save
</div>

<h5>8. "离职通知(Notify Out)"标签 —— 关闭前需填写日期和原因</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开 <strong>"离职通知(Notify Out)"</strong> 标签 → 点击 <strong>"+ Add Employee"</strong></li>
        <li>可搜索<strong>系统中的任何雇员</strong>(全局搜索)—— 不受雇主范围限制</li>
        <li>正在进行<strong>续签 / 转换雇主</strong>工作的雇员，仍可加入 notify_out(两者可并行处理)</li>
        <li>已在 notify_out 中的同一雇员 —— <strong>无法重复加入</strong>，直到该流程完成为止</li>
        <li>系统会按雇员<strong>当前所属雇主自动分组</strong>(1 个雇主 = 1 个 order)</li>
    </ol>
</div>

<div class="manual-step">
    <strong>在 notify_out 标签中点击"完成"前:</strong>
    <ol class="mb-0">
        <li>雇员卡片下方会出现<strong>黄色提示条</strong></li>
        <li>填写<strong>离职通知日期</strong>(日期选择器 —— 必填)</li>
        <li>选择<strong>原因</strong>(下拉菜单：离职 / 解雇 / 合同到期 / 转换雇主 / 潜逃回国 / 死亡) —— 或自行输入</li>
        <li>系统会在每次修改时自动保存 → 标签会变为绿色的<strong>"可以完成"</strong></li>
        <li>点击"Finish" → 系统会立即<strong>自动更新 employee.terminated_at + termination_reason + status='resigned'</strong></li>
        <li><strong>如尚未填写离职通知日期</strong> → 无法点击 Finish，系统会提示"请先填写离职通知日期"</li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>使用多重筛选:</strong> 可同时选择多个 Step 以查看整体概况
</div>

<div class="manual-tip">
    <strong>业务负责人:</strong> 使用"业务负责人"筛选器，只查看自己负责的工作
</div>

<div class="manual-warn">
    <strong>注意:</strong> 移动步骤会影响发送给客户的通知 —— 点击前请务必确认
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 某项工作不见了 —— 在 Workflow 中找不到?</dt>
    <dd>A: 请检查筛选条件 —— 可能处于未选中的 Step 中，请尝试选择"全部"或按雇主名称筛选</dd>

    <dt>Q: 可以新增 Step 吗?</dt>
    <dd>A: 可以 —— 请联系 Admin 通过 Step 设置菜单新增</dd>

    <dt>Q: 可以删除正在使用中的 Step 吗?</dt>
    <dd>A: 不可以 —— 其中的工作会被卡住，需先将其移至其他 Step</dd>

    <dt>Q: 某雇员的 notify_out 记录自己消失了?</dt>
    <dd>A: 当雇员转换雇主时，系统会<strong>自动取消</strong>其待处理的 notify_out 记录 —— 因为 notify_out 表示"脱离旧雇主"，转移后已不再适用。可在"操作日志"菜单中查看历史记录，如有需要，也可在新雇主下重新创建 notify_out</dd>

    <dt>Q: 可以从雇员菜单手动办理 notify_out 吗?</dt>
    <dd>A: 可以 —— 雇员菜单 → "离职通知"按钮 → 填写日期 + 原因(不强制经过 Workflow，如需快速处理可直接在此操作)</dd>
</dl>
