{{-- Training Edition: Delegates (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-vcard"></i> {{ __('Delegates') }} — {{ __('获授权代表雇主签署文件的人员') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"授权代表(Delegates)"</strong> 菜单保存<strong>获授权</strong>代表雇主在各类文件
        (如授权书、申请表)上签署的人员信息 —— 与雇主一样，具备完整的签名 + 印章 + 地址信息
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开 Delegates 菜单</h2>

    @include('manuals.training._screenshot', [
        'src' => 'delegates/01-list',
        'alt' => '授权代表列表 + 筛选',
        'caption' => '授权代表列表',
        'callouts' => [
            '<strong>姓名(泰文/英文) + 职位:</strong> 获授权的签署人',
            '<strong>关联雇主:</strong> 关联到哪些雇主(可选)',
            '<strong>+ 新增授权代表:</strong> 创建新的',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">新增 + 编辑授权代表信息</h2>

    @include('manuals.training._screenshot', [
        'src' => 'delegates/02-form',
        'alt' => '授权代表创建/编辑表单',
        'caption' => '授权代表表单 —— 信息与签名',
        'callouts' => [
            '<strong>个人信息:</strong> 姓名 + 职位 + 纳税人识别号 + 地址',
            '<strong>签名:</strong> 上传 PNG(透明背景)',
            '<strong>授权书:</strong> 可附上参考用 PDF',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击"+ 新增授权代表"</li>
            <li>填写信息 + 上传签名</li>
            <li>点击 Save</li>
            <li>生成 PDF 时可使用：指定 Delegate 字段 —— 系统会自动带入姓名/签名</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 与 employer 角色侧边栏中的"员工信息"有何区别?</dt>
        <dd>A: 在 employer 角色的侧边栏中，"员工信息" = Delegates(公司的授权签署人)，而"雇员"菜单 = 实际的外籍劳工</dd>

        <dt>Q: 一位 Delegate 最多可以代表几个雇主签署?</dt>
        <dd>A: 可以代表多个 —— 生成 PDF 时可自由选择所需的 Delegate</dd>
    </dl>
</section>
