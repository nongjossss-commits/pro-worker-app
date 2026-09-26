{{-- User Manual: Finance (Chinese) --}}

<h4><i class="bi bi-cash-coin me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"财务(Finance)"</strong> 菜单是办公室的会计/财务核心中枢 ——
    包括账簿(Ledger)、税务发票(Tax Invoice)、预扣税(WHT)开具、
    税务报表(ภ.พ.30、ภ.ง.ด.3/53)、银行对账(Bank Reconciliation)以及操作日志(Audit Log)
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> —— 拥有完整权限</li>
    <li><span class="manual-role">Staff</span> —— 部分权限(取决于 <code>manage-finance</code> 权限)</li>
    <li><span class="manual-role">Caretaker</span> <span class="manual-role">Employer</span> —— 无权限</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>财务菜单各部分</h4>
<ol>
    <li><strong>账簿(Ledger)</strong> —— 记录所有收入-支出，按日期分类</li>
    <li><strong>税务发票(Tax Invoices)</strong> —— 创建/查看/打印税务发票 + 付款方式</li>
    <li><strong>预扣税(WHT)</strong> —— 记录收到的 3%/5% 预扣税 + 开具文件</li>
    <li><strong>税务报表(Tax Reports)</strong> —— ภ.พ.30 + ภ.ง.ด.3/53 —— 每月汇总用于报税</li>
    <li><strong>银行对账(Bank Reconciliation)</strong> —— 对账银行账户余额与系统记录</li>
    <li><strong>操作日志(Audit Log)</strong> —— 查看所有财务数据的修改历史</li>
    <li><strong>月度打包(Monthly Bundle)</strong> —— 下载月末结账文件的 ZIP 压缩包</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>常见操作步骤</h4>

<h5>1. 记录新的收入</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>进入账簿(Ledger) → 点击"+ 记录项目"</li>
        <li>选择"收入"</li>
        <li>填写日期、客户、金额、增值税类型</li>
        <li>如有，附上单据图片</li>
        <li>点击"保存"</li>
    </ol>
</div>

<h5>2. 创建税务发票</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>进入税务发票(Tax Invoices) → 点击"+ 新建"</li>
        <li>选择<strong>开票方档案</strong>(我们公司)</li>
        <li>填写客户名称 + 纳税人识别号 + 地址</li>
        <li>填写金额 + 增值税率(通常为 7%)</li>
        <li>勾选付款方式(现金、转账、PromptPay)</li>
        <li>如选择"转账" —— 从档案中选择银行账户</li>
        <li>点击"保存并开具" —— 系统会锁定发票号码 + 生成 PDF</li>
    </ol>
</div>

<h5>3. 生成每月税务报表</h5>
<div class="manual-step">
    进入税务报表(Tax Reports) → 选择月份 → 下载 ภ.พ.30 或 ภ.ง.ด.3/53
</div>

<h5>4. 银行对账</h5>
<div class="manual-step">
    进入银行对账(Bank Reconciliation) → 上传银行对账单 → 系统会自动与系统记录进行匹配
</div>

<h5>5. 月末结账(Monthly Bundle)</h5>
<div class="manual-step">
    进入月度打包(Monthly Bundle) → 选择月份 → 点击"生成" → 下载包含当月所有文件的 ZIP 压缩包
</div>

<h5>6. 总览与统计卡片(点击即可筛选)</h5>
<div class="manual-step">
    4 张卡片:<strong>今日 / 本月收入</strong> = 当日/当月实际收到的钱,按每笔收款日期计算(上月部分付款、本月付清的账单会正确分到两个月,不重复计算);<strong>待处理金额</strong> = 未付或部分付款账单的未结金额;<strong>逾期金额</strong> = 标记为逾期账单的未结金额。<strong>点击卡片</strong>只显示构成该数字的账单(再点一次取消)。收入卡片会在"已付"栏下列出每笔收款 —— 粗体 = 本期,淡色 = 其他期间;筛选汇总显示的"本期收款"与卡片数字一致。报价单不计入。
</div>

<h5>7. 账单来源 —— 来源标记与筛选</h5>
<div class="manual-step">
    雇主/项目名称下方显示来源标记,如 <code>Workflow › MOU 续签</code>、<code>续签决议 › 标签页 X</code>、<code>手动账单</code>。来源标签页已删除时标记为<strong>红色</strong>("标签页已删除" + 日期)—— 账单仍然有效,可继续收款或开具贷项通知单。<strong>来源</strong>筛选:全部 / 来自使用中的标签页 / 来自已删除的标签页。有来自已删除标签页的未付账单时会显示红色提示条。
</div>

<h5>8. 按工作菜单分的标签页</h5>
<div class="manual-step">
    <strong>Workflow 与 Pre-Production</strong>、<strong>注册决议</strong>、<strong>续签决议</strong> 标签页显示每个雇主的总额 / 已开账单 / 未结;<strong>管理财务</strong>可设置价格、开账单和收款。<strong>手动账单</strong>与<strong>报价单</strong>是独立标签页 —— 报价单记录不会与真实账单混在一起。
</div>

<h5>9. 收款(Payment History)</h5>
<div class="manual-step">
    打开账单 → 填写<strong>金额</strong>、<strong>收款日期</strong>、<strong>收款账户</strong>并附上凭证。可分多期收款,状态自动变为"部分付款"/"已付"(包括贷项通知单)。未选账户时系统会先确认,因为这笔款<strong>不会</strong>计入任何银行余额。选择了账户的收款会按收款日期记入账簿(Ledger)。
</div>

<h5>10. 贷项通知单(Credit Note)</h5>
<div class="manual-step">
    用于减少已开出账单的金额(折扣或放弃剩余款项):<strong>贷项通知单</strong> → <strong>新建</strong> → 选择账单、金额和原因(保存为草稿)→ <strong>开具</strong>锁定单号并立即减少未结金额。开错 → <strong>作废</strong>;草稿可删除。贷项通知单会包含在月度汇总中。
</div>

<h5>11. 手动创建账单</h5>
<div class="manual-step">
    先选择<strong>单据类型</strong>:<strong>报价单</strong>(雇主可不选)或<strong>发票</strong>(必须选雇主)。可加入<strong>草稿员工</strong>(填写姓名/国籍/护照/照片,不创建真实员工资料 —— 仅限手动账单)。报价单可选择显示<strong>总额</strong>或只显示单价。所有单据都会附上系统设定的增值税/预扣税说明。
</div>

<h5>12. 月度报告</h5>
<div class="manual-step">
    <strong>月度报告</strong> → 选择月份/开票方 → 下载 ZIP(CSV + 凭证 + 预扣税文件)。收入<strong>每笔收款一行</strong>,按实际收款日期 —— 跨多个月付款的账单会出现在各月报告中。账单的预扣税只在最后一期收款行显示一次。不包括报价单。
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>增值税 7%:</strong> 泰国的默认税率，四舍五入保留 2 位小数
</div>

<div class="manual-tip">
    <strong>预扣税 3% vs 5%:</strong> 3% = 一般服务费，5% = 财产/人员租赁费
</div>

<div class="manual-warn">
    <strong>已开具(Issued)的税务发票无法修改:</strong> 依法禁止修改 —— 必须作废后重新开具新发票
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>
<dl>
    <dt>Q: 找不到选择银行账户的选项?</dt>
    <dd>A: 必须先在财务档案中创建账户 —— 前往 Financial Profiles → 选择档案 → 添加账户</dd>

    <dt>Q: 税务发票号码是连续的吗?</dt>
    <dd>A: 是连续的 —— 系统始终会在同一税务年度内接续上一张发票编号，不会中断</dd>

    <dt>Q: 可以删除开错的税务发票吗?</dt>
    <dd>A: 可以<strong>作废(Void)</strong>，但无法真正删除 —— 发票号码仍会保留在系统中以维持顺序</dd>
</dl>
