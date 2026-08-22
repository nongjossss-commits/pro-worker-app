{{-- Training Edition: Importers (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-box-seam-fill"></i> {{ __('Importers') }} — {{ __('从国外引进 MOU 劳工的公司') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"劳务进口代理公司(Importers)"</strong> 菜单保存负责从国外引进劳工的
        <strong>劳务进口公司</strong>(MOU Importer)信息 —— 附有用于 MOU 文件的<strong>签名 + 印章</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开菜单 + 查看 Importer 列表</h2>

    @include('manuals.training._screenshot', [
        'src' => 'importers/01-list',
        'alt' => 'Importers 列表',
        'caption' => 'Importers 列表',
        'callouts' => [
            '<strong>公司名称(泰文/英文):</strong> 依商业登记信息',
            '<strong>登记编号:</strong> Importer Registration Number',
            '<strong>地址:</strong> 注册地址',
            '<strong>签名 1/2 + 印章:</strong> 用于自动生成的 PDF',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">新增 + 编辑 Importer 信息</h2>

    @include('manuals.training._screenshot', [
        'src' => 'importers/02-form',
        'alt' => 'Importer 表单',
        'caption' => 'Importer 表单 —— 信息 + 2 个签名位置',
        'callouts' => [
            '<strong>基本信息:</strong> 名称 + 纳税人识别号 + 地址',
            '<strong>签名 1:</strong> 主要授权签署人',
            '<strong>签名 2:</strong> 次要授权签署人(可选)',
            '<strong>印章:</strong> 公司印章',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击"+ 新增 Importer"</li>
            <li>填写信息 + 上传签名 1(主要)</li>
            <li>上传印章 + 签名 2(可选)</li>
            <li>点击 Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: Importer 在哪里使用?</dt>
        <dd>A: 用于含有 Importer 字段的 PDF Templates(MOU 引进文件) —— 生成时系统会自动带入数据 + 签名</dd>

        <dt>Q: Importer 与 Agent 有什么区别?</dt>
        <dd>A: Importer = 劳务进口公司(在 MOU/文件中扮演角色)，Agent = 介绍客户的中介(收取中介费)</dd>

        <dt>Q: 为什么需要 2 个签名位置?</dt>
        <dd>A: 部分文件需要 2 位董事共同签署 —— "签名 2"字段就是为此情况设计的(可选择是否填写)</dd>
    </dl>
</section>
