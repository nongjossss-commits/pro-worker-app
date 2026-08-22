{{--
    User Manual: Employees (Chinese)
--}}

<h4><i class="bi bi-people-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"雇员"</strong> 菜单是本公司管理的外籍劳工(缅甸/老挝/柬埔寨)数据库。
    用于保存基本信息(姓名、国籍、护照号码、签证、工作许可证、MOU 合同)，
    并与雇员所在的<strong>雇主</strong>相关联。
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> —— 拥有全部权限，包括离职/恢复/永久删除</li>
    <li><span class="manual-role">Staff</span> —— 可查看 / 新增 / 编辑，不可永久删除</li>
    <li><span class="manual-role">Caretaker</span> —— 可查看 / 新增 / 编辑 + 办理雇员离职</li>
    <li><span class="manual-role">Employer</span> —— 只能看到自己的雇员</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>筛选栏</strong> 位于顶部 —— 按国籍、签证状态、雇主筛选，可按姓名/护照号码搜索</li>
    <li><strong>状态标签</strong> —— 在职 / 离职 / 合同到期 / 回收站</li>
    <li><strong>雇员卡片</strong> —— 每张卡片显示照片、姓名、国籍、签证状态、雇主</li>
    <li>右上角的 <strong>"+ 新增雇员"</strong> 和 <strong>"从 Excel 导入"</strong> 按钮</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>常见操作步骤</h4>

<h5>1. 逐个新增雇员</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击右上角的 <strong>"+ 新增雇员"</strong></li>
        <li>填写基本信息 —— 姓名(泰文/英文)、性别、国籍、出生日期</li>
        <li>填写<strong>护照</strong>号码 + 到期日</li>
        <li>选择该雇员所在的<strong>雇主</strong></li>
        <li>如有，上传照片和证件文件(护照复印件、签证、工作许可证)</li>
        <li>点击 <strong>"保存"</strong></li>
    </ol>
</div>

<h5>2. 批量导入雇员(Excel 批量导入)</h5>
<div class="manual-step">
    适合雇主一次性引入多名雇员的情况
    <ol class="mb-0 mt-2">
        <li>点击 <strong>"从 Excel 导入"</strong> 按钮</li>
        <li>先下载 <strong>"模板"</strong> 文件 —— 示例文件中有系统所需的列标题</li>
        <li>按照列标题在 Excel 中填写雇员信息(每人一行)</li>
        <li>将文件上传回系统</li>
        <li>确认前先查看<strong>预览</strong> —— 系统会显示将要新增的记录，并对错误数据提出警告</li>
        <li>点击 <strong>"确认"</strong> —— 系统会一次性全部新增</li>
    </ol>
</div>

<h5>3. 离职 / 合同到期(Terminate)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开要办理离职的雇员卡片</li>
        <li>点击 <strong>"离职 / 合同到期"</strong> 按钮</li>
        <li>选择原因 + 离职日期</li>
        <li>点击 <strong>"确认"</strong> —— 状态将变更为"离职"，不再计入使用配额</li>
    </ol>
    <div class="manual-tip mt-2 mb-0">
        <i class="bi bi-info-circle-fill"></i> <strong>小贴士:</strong>
        离职的雇员并未消失 —— 仍会保留在"离职"标签下，如重新雇用可以恢复
    </div>
</div>

<h5>4. 恢复离职雇员(Reinstate)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击顶部的<strong>"离职"</strong>状态标签</li>
        <li>找到要恢复的雇员</li>
        <li>点击 <strong>"恢复在职"</strong></li>
    </ol>
</div>

<h5>5. 永久删除雇员(Force Delete)</h5>
<div class="manual-warn">
    <strong>仅限 Admin/Super Admin</strong> —— 用于处理误录入或重复的雇员，删除后<strong>无法恢复</strong>
    <br>
    步骤：前往"回收站"标签 → 点击永久删除图标 → 确认两次
</div>

<h4><i class="bi bi-lightbulb me-2"></i>须知事项 / 使用小贴士</h4>

<div class="manual-tip">
    <strong>雇员最大配额:</strong> Super Admin 可以限制在职雇员的数量
    (在 Super Admin Settings → 配额设置 中设置) —— 如果配额已满，将无法新增雇员
</div>

<div class="manual-tip">
    <strong>雇员 vs 授权代表(Delegate):</strong> 部分角色左侧菜单中的"员工信息"
    实际指的是<strong>雇主的授权代表(Delegate)</strong>，并非外籍劳工雇员 —— 这是两个不同的菜单
</div>

<div class="manual-tip">
    <strong>签证/护照即将到期:</strong> 系统会在<strong>通知(Notifications)</strong>中
    自动提醒即将到期的项目 —— 请每天早上检查
</div>

<div class="manual-warn">
    <strong>永久删除雇员前:</strong> 请确认没有 Production / Workflow 工作或税务发票
    仍关联到该雇员
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>

<dl>
    <dt>Q: 无法新增雇员，提示"超出配额"?</dt>
    <dd>A: 系统配额已满 —— 请联系 Super Admin 增加配额，或将不再使用的旧雇员办理离职</dd>

    <dt>Q: 为什么 Excel 导入会出现错误?</dt>
    <dd>A: 请检查 Excel 文件中的列标题是否与下载的模板完全一致 —— 不要更改列名</dd>

    <dt>Q: 雇员转到了新的雇主?</dt>
    <dd>A: 编辑该雇员 → 将"雇主"更改为新雇主 → 保存</dd>

    <dt>Q: 只能看到一家公司的雇员?</dt>
    <dd>A: 如果您以"Employer"角色登录，只能看到自己的雇员 —— 需要 Admin/Staff 或更高角色才能查看所有人</dd>
</dl>
