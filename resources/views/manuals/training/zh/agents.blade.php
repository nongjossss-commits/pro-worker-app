{{-- Training Edition: Agents (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Agents') }} — {{ __('为办公室介绍客户或雇员的中介') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"劳务中介(Agents)"</strong> 菜单保存<strong>中介/经纪人</strong>信息，
        即为办公室介绍客户或雇员的人 —— 用于追踪中介费与佣金
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开 Agents 菜单 + 新增中介</h2>

    @include('manuals.training._screenshot', [
        'src' => 'agents/01-list-add',
        'alt' => '中介列表 + 新增按钮',
        'caption' => '中介列表 + 新增弹窗',
        'callouts' => [
            '<strong>中介名称:</strong> 个人或中介公司名称',
            '<strong>联系方式:</strong> 电话 / 邮箱 / Line',
            '<strong>佣金:</strong> 中介费百分比',
            '<strong>备注:</strong> 添加特定信息',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>Agents</strong></li>
            <li>点击"+ 新增中介"</li>
            <li>填写信息 → 点击 Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: Agent 与 Importer 有什么区别?</dt>
        <dd>A: Agent = 介绍客户的中介(泰国经纪人)，Importer = 劳务进口公司(签名/印章用于文件)</dd>

        <dt>Q: 可以将 Agent 关联到 Employer 吗?</dt>
        <dd>A: 可以 —— Employer 中有"介绍中介"字段可选择 Agent</dd>
    </dl>
</section>
