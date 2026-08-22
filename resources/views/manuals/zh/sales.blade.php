{{--
    User Manual: Sales (Read and Sale) (Chinese)
--}}

<h4><i class="bi bi-megaphone-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"销售与报价(Read and Sale)"</strong> 菜单用于管理<strong>尚未</strong>成为正式雇主的客户。
    用于记录客户咨询(Lead)、创建报价单(Quotation)，成交后
    系统会自动创建<strong>雇主 + 雇员 + Production 工作</strong>。
</p>
<p>
    这是整个工作流程的起点 —— 从销售接洽新客户 → 成交 → 进入日常管理。
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> —— 可以进入</li>
    <li><span class="manual-role">Caretaker</span> <span class="manual-role">Employer</span> —— 不可进入</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<p>本页面采用 <strong>看板(Kanban Board)</strong> 形式 —— 按客户状态分列：</p>
<ol>
    <li><strong>新客户(Lead)</strong> —— 刚建立联系，尚未沟通</li>
    <li><strong>洽谈中(In Progress)</strong> —— 正在协商条件</li>
    <li><strong>已报价(Quoted)</strong> —— 已向客户报价，等待回复</li>
    <li><strong>成交(Won)</strong> —— 达成协议！即将进入正式系统</li>
    <li><strong>已流失(Lost)</strong> —— 客户未采纳</li>
</ol>
<p>直接拖动卡片跨列即可改变状态</p>

<h4><i class="bi bi-list-check me-2"></i>常见操作步骤</h4>

<h5>1. 新增客户(Lead)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击页面上方的 <strong>"+ 新增 Lead"</strong></li>
        <li>填写基本信息 —— 公司/个人名称、电话号码、来源(如电话、转介绍、Facebook)</li>
        <li>选择<strong>业务类型</strong>(MOU、签证、其他)</li>
        <li>点击 <strong>"保存"</strong> —— Lead 会出现在"新客户"列中</li>
    </ol>
</div>

<h5>2. 创建报价单(Quotation)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开该 Lead 的卡片</li>
        <li>点击 <strong>"创建报价单"</strong> 按钮</li>
        <li>填写产品/服务项目 + 价格 + 数量</li>
        <li>选择 <strong>"财务档案"(Financial Profile)</strong>(开票方)—— 将显示在单据上的公司档案</li>
        <li>点击 <strong>"预览"</strong> 查看 PDF 示例</li>
        <li>点击 <strong>"发送"</strong> —— Lead 会自动移到"已报价"列</li>
    </ol>
</div>

<h5>3. 成交 → 进入正式系统</h5>
<div class="manual-step">
    当客户同意成交后：
    <ol class="mb-0 mt-2">
        <li>打开"已报价"列中的卡片</li>
        <li>点击 <strong>"成交 / 转为正式客户"</strong> 按钮</li>
        <li>系统将要求填写<strong>完整的雇主信息</strong>(纳税人识别号、注册地址等)</li>
        <li>填写要管理的<strong>雇员名单</strong>(可逐个填写，也可通过 Excel 导入)</li>
        <li>点击 <strong>"确认创建"</strong> —— 系统会同时创建：
            <ul>
                <li>"雇主"菜单中的新雇主</li>
                <li>"雇员"菜单中的所有雇员</li>
                <li>"P Production"菜单中的新 Production 工作</li>
            </ul>
        </li>
    </ol>
</div>

<h5>4. 取消 Lead</h5>
<div class="manual-step">
    将卡片拖到 <strong>"已流失(Lost)"</strong> 列，或点击卡片上的"取消"按钮，
    并注明原因(如"价格太高"、"无法联系")
    <div class="manual-tip mt-2 mb-0">
        <i class="bi bi-info-circle-fill"></i> <strong>小贴士:</strong>
        已取消的 Lead 仍会保留在历史记录中 —— 可以回顾原因和相关统计数据
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>须知事项 / 使用小贴士</h4>

<div class="manual-tip">
    <strong>为什么要先经过 Lead 再进入正式系统?</strong>
    因为通常 70-80% 的 Lead 最终不会成交 —— 如果直接创建为雇主，系统中会充满无用数据。
    将 Lead/销售流程分离出来，是为了让正式雇主名单保持干净、可靠
</div>

<div class="manual-tip">
    <strong>报价单 ≠ 税务发票:</strong>
    报价单(Quotation)不涉及税务，仅用于告知价格。
    税务发票(Tax Invoice)则在客户付款后于 Finance 菜单中开具
</div>

<div class="manual-tip">
    <strong>拖动卡片更改状态:</strong> 无需打开卡片 —— 直接用鼠标跨列拖动即可，
    系统会自动保存
</div>

<div class="manual-warn">
    <strong>成交后无法修改:</strong>
    一旦点击"成交"且系统已创建雇主/雇员后，
    原 Lead 将被"锁定"，无法再编辑 —— 需修改时须直接前往雇主/雇员记录中修改
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>

<dl>
    <dt>Q: 为什么看不到这个菜单?</dt>
    <dd>A: 此菜单仅 <strong>Super Admin / Admin / Staff</strong> 角色可见 —— Caretaker 和 Employer 没有查看权限</dd>

    <dt>Q: 已发送的报价单可以修改吗?</dt>
    <dd>A: 可以，只要客户尚未成交 —— 成交后将被锁定</dd>

    <dt>Q: 老客户回来了，需要重新建立 Lead 吗?</dt>
    <dd>A: 不需要 —— 如果对方已经是系统中的雇主，直接在 Production 菜单中新增 Production 工作即可</dd>

    <dt>Q: 谁是"Lead 所有者"?</dt>
    <dd>A: 创建该 Lead 的人将成为<strong>负责的销售人员</strong>，用于日后计算佣金</dd>
</dl>
