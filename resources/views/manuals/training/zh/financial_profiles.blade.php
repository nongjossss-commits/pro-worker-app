{{-- Training Edition: Financial Profiles (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-vcard-fill"></i> {{ __('Financial Profiles') }} — {{ __('开票方与客户的主数据模板') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"财务档案(Financial Profiles)"</strong> 菜单用于保存
        Biller(开票方 = 我们公司) + Customer(常用客户)的<strong>主数据</strong>。
        每次开具发票/收据时，系统都会让您从这些档案中选择 —— 无需每次重新输入。
        包含<strong>银行账户</strong>、<strong>标志</strong>和<strong>签名</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff(manage-finance)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开 Financial Profiles 菜单</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/01-list',
        'alt' => '按 Biller / Customer 类型分类的档案列表',
        'caption' => '财务档案列表 —— 两种类型：Biller + Customer',
        'callouts' => [
            '<strong>Biller 档案:</strong> 我们公司(如以不同名义开票，可能有多个档案)',
            '<strong>Customer 档案:</strong> 经常开票的常用客户',
            '<strong>+ 新建:</strong> 新增档案',
            '<strong>Edit / Delete:</strong> 管理按钮',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → Finance → <strong>财务档案(Financial Profiles)</strong></li>
            <li>选择 Biller 或 Customer 类型</li>
            <li>查看现有档案列表</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">创建 Biller 档案(开票方)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/02-biller-builder',
        'alt' => '含完整字段的 Biller Builder 页面',
        'caption' => 'Biller 档案构建器 —— 开票方信息',
        'callouts' => [
            '<strong>公司名称 + 纳税人识别号:</strong> 最重要的部分',
            '<strong>地址:</strong> 注册地址',
            '<strong>标志(Logo):</strong> 上传 PNG/JPG(印在发票上)',
            '<strong>签名:</strong> 授权签署人的签名 + 公司印章',
            '<strong>银行账户:</strong> 可添加多个(KBank、SCB、BBL 等)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击"+ 新建" → 选择类型 = Biller</li>
            <li>填写公司信息 + 纳税人识别号 + 地址</li>
            <li>上传标志 + 签名 + 印章</li>
            <li>添加银行账户(支持多个账户)</li>
            <li>点击"Save Profile"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">为档案添加银行账户</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/03-bank-accounts',
        'alt' => '含品牌标志的银行账户列表',
        'caption' => '银行账户 —— 在档案中新增/编辑/删除账户',
        'callouts' => [
            '<strong>银行:</strong> 从下拉菜单选择(KBank/SCB/BBL/Krungsri/TTB 等)',
            '<strong>账号:</strong> 银行账号',
            '<strong>账户名称:</strong> 账户持有人姓名',
            '<strong>品牌标志:</strong> 银行标志会自动显示在收据上',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>打开 Biller Profile → "Bank Accounts" 标签</li>
            <li>点击"+ 添加账户"</li>
            <li>选择银行 + 填写账号 + 账户名称</li>
            <li>点击 Save</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>小贴士:</strong> 开具发票时选择"付款方式 = 转账" → 系统会让您从此档案中选择银行账户，并自动打印在 PDF 上
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Customer 档案(常用客户)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/04-customer-profiles',
        'alt' => 'Customer Profiles 列表',
        'caption' => 'Customer 档案 —— 经常开票的常用客户',
        'callouts' => [
            '<strong>客户名称 + 纳税人识别号:</strong> 将打印在发票上的信息',
            '<strong>地址:</strong> 文件寄送地址',
            '<strong>快速填充:</strong> 开票时 → 选择档案 → 信息立即自动填入',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击"+ 新建" → 选择类型 = Customer</li>
            <li>填写客户名称 + 纳税人识别号 + 地址</li>
            <li>点击 Save</li>
            <li>下次开票时 → 选择此档案 → 信息会自动填入</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 为什么开票时看不到银行账户?</dt>
        <dd>A: 必须先在财务档案(Biller Profile)中创建账户 —— 之后才能在 Finance → Tax Invoice 中选择</dd>

        <dt>Q: 可以删除已用于旧发票的档案吗?</dt>
        <dd>A: <strong>不建议</strong> —— 旧发票会找不到对应档案，请改用归档</dd>

        <dt>Q: 可以拥有多个 Biller 档案吗?</dt>
        <dd>A: 可以 —— 例如以不同公司名义开票时(如"ABC 有限公司"和"ABC Service")</dd>

        <dt>Q: 签名 + 印章在哪里设置?</dt>
        <dd>A: 在 Biller Profile → "签名/印章"标签 → 上传 PNG 文件(建议使用透明背景)</dd>
    </dl>
</section>
