{{-- Training Edition: Employers (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-building-fill"></i> {{ __('Employers') }} — {{ __('雇用外籍劳工的客户公司主数据') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"雇主(Employers)"</strong> 菜单保存<strong>雇主</strong>(客户公司)的数据，即雇用外籍劳工的公司。
        这里的数据会用于雇员、生成文件、税务发票、各类合同 —— 是系统的核心基础
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker(仅限自己负责的)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开菜单 + 查看雇主列表</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/01-list',
        'alt' => '雇主列表，含筛选栏 + 序号',
        'caption' => '雇主列表 —— 以卡片 + 表格两种视图显示',
        'callouts' => [
            '<strong>+ 新增雇主:</strong> 创建新雇主',
            '<strong>筛选:</strong> 搜索、按省份筛选、按 JobOwner 筛选',
            '<strong>序号:</strong> 卡片右上角的编号(CSS counter)',
            '<strong>卡片 / 表格切换:</strong> 切换视图',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>雇主</strong></li>
            <li>筛选或搜索所需雇主</li>
            <li>点击卡片进入编辑页面</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">新增雇主</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/02-create-form',
        'alt' => '新增雇主表单',
        'caption' => '新增雇主表单 —— 填写基本信息 + 纳税人识别号',
        'callouts' => [
            '<strong>公司名称(泰文/英文):</strong> 两种语言均需填写',
            '<strong>纳税人识别号:</strong> 13 位数字',
            '<strong>地址:</strong> 可添加多个地址(注册地址 / 文件寄送地址)',
            '<strong>JobOwner:</strong> 实际负责该客户的人员(例如 Kung)',
            '<strong>Caretakers:</strong> 指派负责管理此雇主的 Caretaker 用户',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击 <strong>"+ 新增雇主"</strong></li>
            <li>填写公司信息 + 纳税人识别号 + 地址</li>
            <li>选择 JobOwner(实际负责人)</li>
            <li>点击 Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">编辑雇主信息 + 新增授权代表</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/03-edit-detail',
        'alt' => '雇主编辑页面 —— 信息、地址、签名、授权代表标签',
        'caption' => '编辑雇主 —— 多个标签：信息 / 地址 / 签名 / 授权代表',
        'callouts' => [
            '<strong>基本信息标签:</strong> 名称 + 纳税人识别号 + 联系方式',
            '<strong>地址标签:</strong> 可添加多个地址',
            '<strong>签名/印章标签:</strong> 上传签名 + 公司印章',
            '<strong>授权代表标签:</strong> 添加代表雇主签署文件的 Delegate',
            '<strong>其他文件标签:</strong> 3 个插槽(默认名称由 Super Admin 设置)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击雇主卡片 → 铅笔 ✏️ 按钮</li>
            <li>选择要编辑的标签</li>
            <li>点击 Save 保存</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">预览 + 快捷操作</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/04-preview-modal',
        'alt' => '雇主预览弹窗',
        'caption' => '预览弹窗 —— 无需打开编辑页面即可快速查看信息',
        'callouts' => [
            '<strong>预览 🔍 按钮:</strong> 以只读方式查看数据',
            '<strong>统计:</strong> 在职 + 离职雇员数量，并按国籍分类',
            '<strong>在职雇员名单:</strong> 前 10 位，可翻页',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>雇主卡片 → 放大镜 🔍 按钮</li>
            <li>查看数据 + 雇员人数</li>
            <li>点击"查看全部"前往该雇主的雇员列表</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 可以删除仍有雇员的雇主吗?</dt>
        <dd>A: 可以删除 —— 但雇员会变成孤立数据，建议改用归档，或先将雇员转移到其他雇主</dd>

        <dt>Q: JobOwner 与 Caretakers 有什么区别?</dt>
        <dd>A: JobOwner = 实际负责该客户的人员(例如 Kung 负责多家公司)，Caretaker = 系统角色，用于让某用户能看到相关数据</dd>

        <dt>Q: Caretaker 能看到哪些雇主?</dt>
        <dd>A: 只能看到在该雇主的 Caretakers 标签中被指派的雇主</dd>
    </dl>
</section>
