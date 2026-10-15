{{-- User Manual: Notifications (Chinese) --}}

<h4><i class="bi bi-bell-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"通知(Notifications)"</strong> 菜单保存系统自动生成的各类提醒 ——
    例如护照即将到期、签证即将到期、工作即将到期，以及来自雇主的新消息
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入?</h4>
<p>拥有 <code>view-notifications</code> 权限的任何人</p>

<h4><i class="bi bi-list-check me-2"></i>使用步骤</h4>

<h5>1. 查看通知</h5>
<div class="manual-step">
    点击导航栏上的铃铛图标，或打开 Notifications 菜单 —— 会按最新到最旧顺序显示 · "卡片"视图显示与雇员菜单相同的固定大小卡片(按屏幕宽度排满每行，序号在左上角，勾选框在右上角，国旗/国籍在照片旁)—— 卡片颜色仍表示紧急程度(红 ≤ 30 天，黄 ≤ 60 天，灰 = 已过期) · 到期日、剩余天数和按钮位于卡片底部 · "表格"视图不变 · <strong>按月份筛选</strong>：选择月份和年份(如 2026 年 12 月)→ 每个标签显示该标签证件在该月到期的<strong>所有人</strong>，包括尚未到提醒时间的人(系统只在设定的提前天数内生成提醒，例如签证 30 天)—— 这些人带有"尚未到提醒时间"标签，只有编辑/定位按钮(到提醒时间后才能续期或取消)
</div>

<h5>2. 标记为已读</h5>
<div class="manual-step">
    点击某条通知 → 系统会自动将其标记为已读
</div>

<h5>3. 取消/删除通知</h5>
<div class="manual-step">
    点击某条通知上的 X 图标 —— 仅拥有 <code>cancel-notifications</code> 权限的用户可操作
</div>

<h5>4. 延长通知提醒</h5>
<div class="manual-step">
    部分通知可以延长(例如推迟签证到期提醒的时间) —— 点击"延长"按钮
</div>

<h5>5. 编辑雇员资料</h5>
<div class="manual-step">
    点击雇员卡片或表格行上的 <i class="bi bi-pencil-fill"></i>（编辑员工）—— 编辑表单会在同一页面以窗口打开，保存后通知列表会刷新显示最新资料。（仅在"雇员"菜单启用时显示；若该菜单设有密码，会先要求输入密码。）
</div>

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>搜索框:</strong> 输入雇员或雇主姓名的一部分,系统会立即显示匹配的建议(无需输入完整姓名);点击建议即可立即筛选列表 —— 只在当前打开的标签页内搜索
    <br><strong>两个搜索框</strong> —— 第一个框可输入雇员(姓名/护照/各类编号)、雇主或项目负责人，然后从列表选择：选雇员 = 只显示该人，选雇主 = 只显示该雇主，选负责人 = 该负责人名下的所有雇主(输入后按 Enter = 按输入文字搜索) · 第一个框有内容后第二个框才可用，用于在第一个框的结果中进一步缩小范围，可输入同样类型的内容
</div>

<div class="manual-tip">
    <strong>Web Push:</strong> 在浏览器中开启通知权限，即可实时接收提醒
</div>

<div class="manual-tip">
    <strong>到期扫描(Expiry Scanner):</strong> 系统每天早上会自动扫描到期日期(CheckExpiries 定时任务) —— 自动生成新的通知
</div>
<div class="manual-tip">
    <strong>发送到 LINE 聊天：</strong>将卡片上的 <i class="bi bi-grid-3x2-gap-fill"></i> 按钮拖入聊天，或点击以复制文本 / 卡片图片 / 分享 —— 详见雇员菜单手册第 11 节
</div>
