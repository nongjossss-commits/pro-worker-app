{{-- User Manual: Financial Profiles (Chinese) --}}

<h4><i class="bi bi-person-vcard me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"财务档案(Financial Profiles)"</strong> 菜单用于保存
    显示在财务文件(如报价单、税务发票、收据)上的
    <strong>开票方(Biller)</strong> 和 <strong>客户(Customer)</strong> 信息
</p>
<p>
    一个办公室可能拥有多个开票方档案(例如"曼谷办公室"、"清迈办公室")，
    每个档案都有各自的标志、签名、印章和<strong>银行账户</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> —— 可以进入</li>
    <li><span class="manual-role">Staff</span> —— 可以进入(取决于 <code>manage-finance</code> 权限)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>档案列表</strong>(左侧) —— 显示所有档案，按 Biller / Customer 类型区分</li>
    <li><strong>编辑表单</strong>(右侧) —— 编辑所选档案的信息</li>
    <li><strong>"银行账户"面板</strong> —— 保存档案后出现 —— 可为该档案新增/编辑/删除账户</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 创建开票方档案(Biller)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击"+ 新增档案"</li>
        <li>选择类型"Biller(开票方)"</li>
        <li>填写公司名称、纳税人识别号、地址、电话、邮箱</li>
        <li>上传<strong>标志、签名、印章</strong></li>
        <li>在 PDF 预览图上拖动以放置<strong>签名/印章的位置</strong></li>
        <li>点击"保存"</li>
    </ol>
</div>

<h5>2. 为档案添加银行账户</h5>
<div class="manual-step">
    保存档案后 —— "银行账户"面板会出现：
    <ol class="mb-0 mt-2">
        <li>点击"+ 添加银行"</li>
        <li>选择类型：<strong>泰国银行 / PromptPay / 自定义</strong></li>
        <li>如选择"泰国银行" —— 从列表中选择银行(17 家银行，附彩色标志)</li>
        <li>填写账户名称 + 账号 + 分行</li>
        <li>点击"保存"</li>
    </ol>
</div>

<h5>3. 编辑档案</h5>
<div class="manual-step">
    点击左侧列表中的档案 → 在右侧表单中修改 → 点击"保存"
</div>

<h5>4. 删除档案</h5>
<div class="manual-step">
    点击档案上的垃圾桶图标 —— 系统会要求确认
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        如果该档案已被用于已开具的税务发票 —— <strong>请勿删除</strong>，否则会导致旧的 PDF 找不到对应档案
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>Biller 与 Customer 档案的区别:</strong> Biller = 我们自己(开票方)，Customer = 常用客户
    (用于在税务发票中自动填入信息，无需重新输入)
</div>

<div class="manual-tip">
    <strong>多个 Biller 档案:</strong> 适合拥有多个分支机构/多个法人实体的办公室
</div>

<div class="manual-tip">
    <strong>银行标志:</strong> 系统会自动在 PDF 上加上品牌色和缩写字母
    (例如 KBANK = 绿色 K，SCB = 紫色 S)
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 无法添加银行账户?</dt>
    <dd>A: 必须先<strong>保存档案</strong> —— 银行账户面板只会在保存后才会出现</dd>

    <dt>Q: 签名没有显示在 PDF 上?</dt>
    <dd>A: 请检查是否已上传签名并在预览图上放置了位置 —— 如果没有放置，系统就不知道该放在哪里</dd>

    <dt>Q: 选择银行后下拉菜单没有收起?</dt>
    <dd>A: 选定后系统会立即将其收起为一个标签 —— 点击"更改"即可重新选择</dd>
</dl>
