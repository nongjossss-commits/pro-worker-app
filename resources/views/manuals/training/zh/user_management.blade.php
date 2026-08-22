{{-- Training Edition: User Management (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('User Management') }} — {{ __('创建/编辑用户 + 分配角色 + 权限') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"用户管理(User Management)"</strong> 菜单用于创建/编辑办公室员工
        以及客户(employer 角色)的<strong>用户账户</strong>，并分配<strong>角色(role)</strong>、设置<strong>权限(permissions)</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin(有限权限)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">新增用户</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/01-create-user',
        'alt' => '新建用户表单 + 角色选择器',
        'caption' => '新建用户表单 —— 填写信息 + 选择角色',
        'callouts' => [
            '<strong>姓名 + 邮箱:</strong> 用于登录',
            '<strong>密码:</strong> 默认密码或自行设置',
            '<strong>角色:</strong> super-admin / admin / staff / caretaker / employer',
            '<strong>关联雇主:</strong> 仅角色为 employer 时需要(关联到哪个雇主)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>用户管理</strong></li>
            <li>点击"+ 新增用户"</li>
            <li>填写姓名 + 邮箱 + 密码</li>
            <li>选择角色 + 关联雇主(如为 employer)</li>
            <li>点击 Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">5 种主要角色 + 权限</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/02-roles-permissions',
        'alt' => '角色列表 + 权限矩阵',
        'caption' => '角色与权限 —— 5 种角色 + 27+ 项权限',
        'callouts' => [
            '<strong>super-admin:</strong> 拥有全部权限(root)',
            '<strong>admin:</strong> 管理所有数据，但不能修改权限/角色',
            '<strong>staff:</strong> 日常工作 —— 录入/编辑数据',
            '<strong>caretaker:</strong> 只能看到分配给自己的雇主',
            '<strong>employer:</strong> 客户 —— 只能看到自己的数据',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">为雇主指派 Caretaker</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/03-caretaker-assign',
        'alt' => 'Employer Edit 页面 → Caretakers 标签',
        'caption' => 'Caretaker 指派 —— 为每个雇主选择 caretaker 用户',
        'callouts' => [
            '<strong>Caretakers 标签:</strong> 位于 Employer Edit 页面',
            '<strong>多选:</strong> 一个雇主可以有多个 caretaker',
            '<strong>Caretaker 只能看到被指派的部分:</strong> 防止数据泄露',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Employers 菜单 → 选择雇主 → Caretakers 标签</li>
            <li>选择 caretaker 用户(多选)</li>
            <li>点击 Save</li>
            <li>这些 Caretaker 将会在自己的侧边栏中看到该雇主</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 可以新增角色吗?</dt>
        <dd>A: 可以通过 Spatie Permission 套件实现 —— 仅限 Super Admin</dd>

        <dt>Q: 可以为用户重置密码吗?</dt>
        <dd>A: 可以 —— 编辑用户 → 输入新密码 → Save</dd>

        <dt>Q: 删除用户后，该用户创建的数据会怎样?</dt>
        <dd>A: 数据仍会保留 —— 只有用户账户被删除(操作日志中仍可查看)</dd>
    </dl>
</section>
