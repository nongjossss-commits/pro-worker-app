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
    <li><span class="manual-role">Caretaker</span> —— 可处理日常工作(员工资料、预约),但不能修改工作流程结构或财务数据;获得"更新进度步骤"权限后才能勾选步骤</li>
    <li><span class="manual-role">Employer</span>(客户账号)—— 无法进入此菜单</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>决议标签栏(Tabs)</strong> —— 每个标签代表 1 项内阁决议(例如 2023 年 11 月决议、2024 年 3 月决议)</li>
    <li><strong>搜索框</strong> —— 输入雇主或雇员姓名的一部分,系统会立即显示匹配的建议(无需输入完整姓名);点击建议即可立即筛选列表 · 可按姓名（泰文/英文）、护照号、工作许可号、身份证号、RA 号、粉卡号和<strong>申请号</strong>搜索 —— 包括 Workflow / Pre-Production 工作卡上以及注册/续期决议卡上的申请号 · 输入时弹出的建议与按「搜索」使用相同规则</li>
    <li><strong>两个搜索框</strong> —— 第一个框可输入雇员(姓名/护照/各类编号)、雇主或项目负责人，然后从列表选择：选雇员 = 只显示该人，选雇主 = 只显示该雇主，选负责人 = 该负责人名下的所有雇主(输入后按 Enter = 按输入文字搜索) · 第一个框有内容后第二个框才可用，用于在第一个框的结果中进一步缩小范围，可输入同样类型的内容 · 在雇主卡片中，搜索词匹配雇主/负责人时显示其全部雇员，匹配雇员时只显示匹配的雇员</li>
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

<h5>7. 选择已完成的员工与三种全选方式</h5>
<div class="manual-step">
    已完成员工的复选框在 24 小时撤销期内隐藏,过后员工移入历史 —— 点击 <strong>已保存到数据库</strong> 卡片可列出并选择所有已完成员工。对同时有已完成和未完成员工的雇主点击全选时,系统会询问:<strong>全部 / 仅已完成 / 仅未完成</strong>(用于导出、批量编辑、下载)。
</div>

<h5>8. 分组</h5>
<div class="manual-step">
    点击员工卡片上的 <strong>分组</strong> → 选择或新建分组(如"第 1 批")。分组按决议标签页和雇主分开;重命名/删除即时生效,无需刷新。
    <br><strong>已完成员工保留原分组:</strong> 重命名 / 删除分组或更改分组只影响尚未完成的员工 —— 已完成的员工留在原分组，并可在历史中按原分组名称搜索。
    <br><strong>历史(雇主卡片上的时钟按钮):</strong> 员工完成后在列表中保留 24 小时，然后移入该雇主的 <strong>工作历史</strong> 弹窗。可按月份查看(默认最新月份)或按日期范围;可搜索员工(姓名 / 护照 / 申请号 / RA 号)和分组 —— 输入搜索时会在所有月份中查找。在系统开始记录日期之前完成的员工位于"无日期(旧记录)"。如需选择已完成员工进行导出 / 下载，请点击 <strong>已保存到数据库</strong> 卡片，其中仍列出所有已完成员工。
</div>

<h5>9. 预约与申请编号按标签页分开</h5>
<div class="manual-step">
    预约日期/地点和申请编号<strong>按决议标签页</strong>保存 —— 在一个标签页设置不会出现在其他标签页或菜单。
</div>

<h5>10. 标签页提示标记开关(超级管理员)</h5>
<div class="manual-step">
    位于标签页编辑按钮旁 —— 用于提前准备但尚未启用的标签页:员工照常处理,但卡片上的"属于决议组…"标记和通知标记会隐藏,避免与当前轮次混淆。
</div>

<h5>11. 批量移动附件(超级管理员)</h5>
<div class="manual-step">
    勾选员工 → <strong>移动附件</strong> → 交换 / 移动并删除来源 / <strong>合并到 B</strong>(合并两个 PDF)。只作用于所选员工;可授予特定 Admin/Staff。
</div>

<h5>12. 删除已开账单的标签页</h5>
<div class="manual-step">
    删除标签页会先隐藏 7 天(可恢复),之后永久删除。<strong>已开账单</strong>的工作不会被删除 —— 账单和收款记录仍保留,可在财务菜单继续收款或开具贷项通知单(红色"标签页已删除"标记)。
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>进度标签颜色:</strong> 系统使用 4 个颜色等级，便于快速查看每位雇员所处的阶段
</div>

<div class="manual-tip">
    <strong>状态筛选只显示匹配项:</strong> 如筛选"Visa only"，将只看到有雇员仅办理签证的雇主
</div>
<div class="manual-tip">
    <strong>发送到 LINE 聊天：</strong>将卡片上的 <i class="bi bi-grid-3x2-gap-fill"></i> 按钮拖入聊天，或点击以复制文本 / 卡片图片 / 分享 —— 详见雇员菜单手册第 11 节
</div>
<div class="manual-tip">
    <strong>经办人（卡片右下角的 <i class="bi bi-person-badge-fill"></i> 按钮）：</strong>选择系统用户，或选择"外部人员（自行输入姓名）"并输入姓名，或选择"无"以清除——在登记、续期（所有标签）、Pre-Production 和 Workflow 中操作相同 · 自行输入的姓名显示"(外部)" · 选择经办人不会改变卡片状态
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
