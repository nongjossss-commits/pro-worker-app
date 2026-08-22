{{-- User Manual: PDF Templates (Chinese) --}}

<h4><i class="bi bi-file-earmark-pdf-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"PDF 模板(PDF Templates)"</strong> 菜单用于为各类文件创建 <strong>PDF 模板</strong>，
    例如劳动合同、工作证明等。
    通过将数据字段<strong>拖放(drag and drop)</strong>到已上传的 PDF 模板上，
    系统会在生成正式文件时自动填入数据
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> —— 拥有完整权限</li>
    <li><span class="manual-role">Staff</span> —— 可查看 + 使用模板(<code>view-pdf-templates</code> 权限)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>模板列表</strong> —— 显示所有已创建的模板</li>
    <li><strong>"+ 新建模板"</strong> 按钮</li>
    <li><strong>编辑器页面</strong>(打开模板时) —— PDF 预览 + 可拖动的字段列表</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 创建新模板 —— 有 2 种方式</h5>
<div class="manual-step">
    <strong>方式一：上传新的 PDF</strong>
    <ol class="mb-2">
        <li>点击"+ 新建模板"</li>
        <li>选择 <strong>"Upload new PDF"</strong></li>
        <li>为模板命名(例如"缅甸 MOU 劳动合同")</li>
        <li>上传原始 PDF 文件(例如带空白栏位的空白合同)</li>
        <li>点击"Upload &amp; Go to Builder" → 进入编辑器页面</li>
    </ol>
    <strong>方式二：从现有模板复制(Clone)</strong>
    <ol class="mb-0">
        <li>点击"+ 新建模板"</li>
        <li>选择 <strong>"Copy from existing template"</strong></li>
        <li>从列表中选择原始模板(可搜索)</li>
        <li>设置新名称(例如加上"(Copy)"或"v2") + 选择类型/雇主</li>
        <li>点击"Clone &amp; Go to Builder" → 系统会复制 PDF 文件及所有字段位置 → 只需调整所需部分后保存</li>
    </ol>
</div>

<h5>2. 拖放字段</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>在编辑器页面中 —— 字段列表位于左侧(雇主名称、纳税人识别号等)</li>
        <li>将所需字段拖放到 PDF 预览图上的目标位置</li>
        <li>可调整每个字段的大小/字体</li>
        <li>点击"保存"</li>
    </ol>
</div>

<h5>3. 使用模板生成文件</h5>
<div class="manual-step">
    在雇员/雇主菜单中 —— 点击"生成文件" → 选择模板 → 系统会自动填入数据
</div>

<h5>4. 使用 Quick Print 快速打印(适用于无需雇员数据的模板)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击 PDF Templates 页面顶部的 <strong>"打印文件"</strong>(绿色)按钮</li>
        <li>选择模板 —— 系统会<strong>分析字段</strong>并显示需要哪些人的数据：
            <ul>
                <li><span class="badge bg-warning text-dark">雇员</span> = 必须先选择雇员 —— 系统会提示，且无法空白打印</li>
                <li><span class="badge bg-primary">雇主</span> / <span class="badge bg-info">授权代表</span> / <span class="badge bg-success">Importer</span> = 从下拉菜单中选择对象即可打印</li>
            </ul>
        </li>
        <li>如果模板含有雇员字段 → 点击 <strong>"前往选择雇员"</strong> → 在雇员管理页面选择所需人员 → 点击"生成自动 PDF"</li>
        <li>如果没有雇员字段 → 选择目标雇主/授权代表/Importer(视模板使用而定) → 点击 <strong>"下载 PDF"</strong> 或 <strong>"打印 / 预览"</strong></li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>泰文字体:</strong> 系统使用 THSarabunNew + CP874 编码 —— 完整支持泰文
</div>

<div class="manual-tip">
    <strong>签名 + 印章:</strong> 可以从财务档案(Financial Profile)插入签名/印章，也可以采用程序方式绘制(直接画在 PDF 上)
</div>

<div class="manual-tip">
    <strong>Clone 可节省时间，适用于:</strong> 需要制作使用相同表格但更改部分字段的模板 —— 例如沿用相同合同，只更改公司名称或个别条款
</div>

<div class="manual-warn">
    <strong>Quick Print 与雇员字段:</strong> 如果模板中含有雇员数据字段(雇员姓名、护照等)，<strong>将无法空白打印</strong> —— 必须先选择雇员，否则空白打印出来这些字段将是空的，无法实际使用
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 上传的 PDF 文字是扫描图片(Scanned)?</dt>
    <dd>A: 系统仍可将其作为背景使用 —— 只需自行将字段拖放到空白位置上即可</dd>

    <dt>Q: 字段位置放错了?</dt>
    <dd>A: 打开模板 → 将字段拖到正确位置 → 保存 —— 系统会立即应用新位置</dd>

    <dt>Q: Clone 模板后删除原始模板，会影响克隆出来的模板吗?</dt>
    <dd>A: 不会影响 —— 系统会将 PDF 文件完整复制到新的路径，两个模板完全相互独立</dd>

    <dt>Q: 点击"打印文件"后为什么"下载"按钮是禁用的?</dt>
    <dd>A: 因为所选模板含有雇员数据字段 —— Quick Print 不支持此类模板，请改为在"雇员管理"页面选择雇员后使用"生成自动 PDF"</dd>
</dl>
