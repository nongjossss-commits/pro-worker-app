{{-- User Manual: User Management (Chinese) --}}

<h4><i class="bi bi-person-fill-gear me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"用户管理(User Management)"</strong> 菜单用于创建/编辑/删除
    系统的<strong>用户账户</strong>，并为每个人分配<strong>角色(Role)</strong>，
    同时可以在页面底部查看每个角色的<strong>权限(Permissions)</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> —— 可以进入</li>
    <li>(Super Admin 可以看到所有 super-admin 用户；Admin 可以看到除 super-admin 以外的所有人)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>搜索栏</strong> + 按角色筛选</li>
    <li><strong>角色标签栏</strong> —— Super Admin、Admin、Caretaker、Staff、Employer</li>
    <li><strong>用户表格</strong> —— 姓名、邮箱、状态、操作</li>
    <li>页面底部的<strong>"角色与权限"区块</strong>(仅拥有 manage-roles 权限者可见) —— 查看所有角色及其关联的权限</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 创建新用户</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击"+ 新增用户"</li>
        <li>填写姓名、邮箱、密码</li>
        <li>选择角色(例如 Staff、Caretaker)</li>
        <li>点击"保存" —— 该用户即可登录</li>
    </ol>
</div>

<h5>2. 更改用户角色</h5>
<div class="manual-step">
    点击用户姓名 → 选择新角色 → 保存
</div>

<h5>3. 启用/停用账户(状态)</h5>
<div class="manual-step">
    点击用户行上的状态开关 —— Active(启用)= 可登录，Inactive(停用)= 无法登录
</div>

<h5>4. 重置密码</h5>
<div class="manual-step">
    点击姓名 → 点击"更改密码" → 输入新密码 → 保存
</div>

<h5>5. 查看各角色的权限</h5>
<div class="manual-step">
    滚动至页面底部 —— "角色与权限"区块会显示所有角色及其关联的权限
</div>

<h5>6. 按用户设置的权限</h5>
<div class="manual-step">
    用户编辑页列出可逐人授予或撤销的权限:<strong>更新进度步骤</strong>(勾选步骤;Admin/Staff 默认有,Caretaker 没有)·<strong>编辑员工</strong>(员工资料、预约、登录信息 —— 与勾选步骤分开)·<strong>管理工作流程</strong>(创建工作、设置步骤和通知)·<strong>管理财务</strong>(价格、账单、收款)·<strong>移动附件</strong>(超级管理员可授予特定 Admin/Staff)。
</div>

<h5>7. 雇主账号与 Pro Walker Labour 账号</h5>
<div class="manual-step">
    <strong>Employer</strong> 账号只能看到自己公司的数据,不能进入工作菜单(Pre-Production / Workflow / 决议)。Pro Walker Labour 账号(会计 / 股东 / 组长 / 组员)只能由超级管理员创建,且只能访问 Labour 模块。
</div>

<h5>8. 登录与安全</h5>
<div class="manual-step">
    关闭浏览器或应用后必须重新登录;程序的页面全部关闭超过 5 分钟会自动登出。"记住我"只在该设备上记住邮箱(密码请交由浏览器保存)。连续多次输错密码会暂时禁止登录。
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>Pro-Worker 中的 5 种角色:</strong>
    <ul class="mb-0 mt-1">
        <li><span class="manual-role">Super Admin</span> —— 拥有全部权限</li>
        <li><span class="manual-role">Admin</span> —— 拥有除 Super Admin Settings 外的全部权限</li>
        <li><span class="manual-role">Staff</span> —— 处理日常工作(雇员、雇主、财务)</li>
        <li><span class="manual-role">Caretaker</span> —— 负责管理雇员(不可删除)</li>
        <li><span class="manual-role">Employer</span> —— 登录查看自身数据的客户</li>
    </ul>
</div>

<div class="manual-warn">
    <strong>注意:</strong> 请勿将 <strong>Admin</strong> 角色授予非管理层人员 —— Admin 拥有删除和编辑所有内容的权限
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 删错用户了，还能找回来吗?</dt>
    <dd>A: 前往 Central Trash → 点击恢复(仅限 Super Admin)</dd>

    <dt>Q: 可以更改某个角色的权限吗?</dt>
    <dd>A: 无法通过界面操作 —— 需要通过命令行(Tinker)或修改 Seeder 完成</dd>

    <dt>Q: 为什么我的 Staff 用户无法进入某些菜单?</dt>
    <dd>A: 请查看"角色与权限"区块 —— 确认 Staff 拥有哪些权限，如有缺失，请联系 Super Admin 添加</dd>
</dl>
