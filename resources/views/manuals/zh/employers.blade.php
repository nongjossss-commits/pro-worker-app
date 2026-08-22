{{--
    User Manual: Employers (Chinese)
    Audience: New office staff who may have attended training but forgotten steps.
    Tone: Friendly, step-by-step, plain Chinese.
--}}

<h4><i class="bi bi-building me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"雇主"</strong> 菜单是通过我们公司雇用外籍劳工的公司或个人的数据库。
    用于保存基本信息，例如公司名称、纳税人识别号、地址、电话号码、各种证件文件，
    并与在该雇主处工作的<strong>雇员</strong>相关联。
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> —— 拥有全部权限</li>
    <li><span class="manual-role">Staff</span> —— 可查看 / 新增 / 编辑</li>
    <li><span class="manual-role">Caretaker</span> —— 可查看 / 新增 / 编辑(不可删除)</li>
    <li><span class="manual-role">Employer</span> —— 只能看到自己的数据</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>筛选栏</strong> 位于顶部 —— 按状态筛选、是否有雇员、按名称/纳税人识别号搜索</li>
    <li><strong>雇主卡片</strong> —— 每张卡片显示 1 家公司：名称、雇员人数、状态</li>
    <li>右上角的 <strong>"+ 新增雇主"</strong> 按钮 —— 用于创建新雇主</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>常见操作步骤</h4>

<h5>1. 新增雇主</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击右上角的 <strong>"+ 新增雇主"</strong> 按钮</li>
        <li>填写基本信息 —— 公司名称(泰文/英文)、13 位纳税人识别号、地址</li>
        <li>如有需要，选择 <strong>"业务负责人"</strong>(负责跟进该客户的员工)</li>
        <li>如有，上传相关证件文件(增值税登记证复印件、商业登记证等)</li>
        <li>点击 <strong>"保存"</strong> —— 系统会立即创建该雇主</li>
    </ol>
</div>

<h5>2. 搜索雇主</h5>
<div class="manual-step">
    在最上方的搜索框中输入名称或纳税人识别号 —— 列表会自动筛选
</div>

<h5>3. 编辑雇主信息</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击要编辑的雇主卡片</li>
        <li>点击 <i class="bi bi-pencil"></i> <strong>"编辑"</strong> 图标</li>
        <li>修改所需信息，然后点击 <strong>"保存"</strong></li>
    </ol>
</div>

<h5>4. 查看该雇主的雇员</h5>
<div class="manual-step">
    点击雇主卡片 → 即可看到在该雇主处工作的所有雇员名单，以及每个人的状态
</div>

<h5>5. 删除雇主</h5>
<div class="manual-step">
    点击卡片上的 <i class="bi bi-trash text-danger"></i> 图标 —— 系统会先要求确认
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i> <strong>警告:</strong>
        如果该雇主名下仍有雇员，系统将<strong>不允许删除</strong>，必须先转移或终止所有雇员
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>须知事项 / 使用小贴士</h4>

<div class="manual-tip">
    <strong>泰文与英文:</strong> 两种语言的名称都要填写完整 ——
    英文名称将用于打印官方文件(MOU、劳动合同)
</div>

<div class="manual-tip">
    <strong>纳税人识别号:</strong> 必须是 13 位数字，填写错误时系统会提示
</div>

<div class="manual-tip">
    <strong>多个地址:</strong> 一个雇主可以有多个地址 —— 注册地址和工作场所地址
</div>

<div class="manual-warn">
    <strong>注意:</strong> 删除雇主之前，请确认没有正在进行中的 <strong>Production</strong> 或 <strong>Workflow</strong> 工作 ——
    删除会导致该工作的状态出错
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>

<dl>
    <dt>Q: 为什么无法新增雇主?</dt>
    <dd>A: 请检查是否已填写所有带星号(*)的必填栏位，尤其是名称和纳税人识别号</dd>

    <dt>Q: 雇员从雇主卡片中消失了?</dt>
    <dd>A: 请检查状态筛选器 —— 该雇员可能处于"已离职"或"合同到期"状态，请尝试选择"全部"</dd>

    <dt>Q: 删错雇主了，还能找回来吗?</dt>
    <dd>A: 可以 —— 前往<strong>"回收站"</strong>(Central Trash)菜单并点击恢复(仅限 Admin/Super Admin)</dd>
</dl>
