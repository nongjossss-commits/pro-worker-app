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
    点击导航栏上的铃铛图标，或打开 Notifications 菜单 —— 会按最新到最旧顺序显示
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

<h4><i class="bi bi-lightbulb me-2"></i>使用小贴士</h4>

<div class="manual-tip">
    <strong>Web Push:</strong> 在浏览器中开启通知权限，即可实时接收提醒
</div>

<div class="manual-tip">
    <strong>到期扫描(Expiry Scanner):</strong> 系统每天早上会自动扫描到期日期(CheckExpiries 定时任务) —— 自动生成新的通知
</div>
