{{-- User Manual: Incomplete Data (Chinese) --}}

<h4><i class="bi bi-exclamation-diamond-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"数据不完整(Incomplete Data)"</strong> 菜单汇集了系统中<strong>数据尚不完整</strong>的
    <strong>雇主/雇员</strong>，例如缺少纳税人识别号、缺少照片、缺少护照信息，
    以便办公室能够看到并补充完整
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> —— 可以进入(需拥有 <code>manage-tickets</code> 权限)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>搜索框</strong> —— 输入雇员或雇主姓名的一部分,系统会立即显示匹配的建议;点击建议即可立即筛选列表</li>
    <li><strong>两个搜索框</strong> —— 第一个框可输入雇员(姓名/护照/各类编号)、雇主或项目负责人，然后从列表选择：选雇员 = 只显示该人，选雇主 = 只显示该雇主，选负责人 = 该负责人名下的所有雇主(输入后按 Enter = 按输入文字搜索) · 第一个框有内容后第二个框才可用，用于在第一个框的结果中进一步缩小范围，可输入同样类型的内容</li>
    <li><strong>类型标签</strong> —— Employers / Employees</li>
    <li><strong>表格</strong> —— 显示数据缺失的记录，并注明具体缺少什么</li>
    <li><strong>编辑按钮</strong> —— 点击前往补全数据</li>
    <li><strong>雇员卡片</strong> —— 与雇员菜单相同的卡片(撑满整行)(按钮相同)，黄色"缺少 N 项"标签显示仍为空的必填项数量</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>补全缺失数据</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>选择类型(Employers/Employees)</li>
        <li>点击表格中的记录 —— 系统会显示缺少的内容(例如"缺少：纳税人识别号、照片")</li>
        <li>点击"编辑" → 系统会带你前往该记录的编辑页面</li>
        <li>补全数据 → 保存</li>
        <li>返回后，该记录将<strong>从"数据不完整"列表中消失</strong></li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>每周检查一次:</strong> 该菜单有助于保持系统数据完整 —— 避免因数据缺失而中断日常工作
</div>

<div class="manual-tip">
    <strong>创建税务发票前:</strong> 请先检查此菜单 —— 如果雇主没有纳税人识别号，将无法开具发票
</div>
