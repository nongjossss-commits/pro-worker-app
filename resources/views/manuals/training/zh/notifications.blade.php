{{-- Training Edition: Notifications (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-bell-fill"></i> {{ __('Notifications') }} — {{ __('汇集所有类型通知的中心') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"通知(Notifications)"</strong> 菜单汇集系统中<strong>所有通知</strong> ——
        例如即将到期的雇员、已批准的报价单、客户新提交的工单。
        支持 <strong>Web Push</strong>(浏览器弹窗通知) + <strong>应用内铃铛图标</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker</span>
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">查看通知 + 标记为已读</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/01-list',
        'alt' => '通知列表 + 未读/全部筛选',
        'caption' => '通知列表 —— 区分未读/已读',
        'callouts' => [
            '<strong>铃铛图标:</strong> 位于导航栏右上角 —— 带未读数量徽章',
            '<strong>筛选:</strong> 未读 / 全部 / 按类型',
            '<strong>点击通知:</strong> 直接打开相关内容',
            '<strong>全部标为已读:</strong> 清除徽章计数',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">通知类型</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/02-types',
        'alt' => '各类通知示例',
        'caption' => '通知类型 —— 颜色与图标各不相同',
        'callouts' => [
            '<strong>🔴 到期提醒:</strong> 即将到期的雇员(护照/签证/工作许可证)',
            '<strong>🔵 工单:</strong> 客户提交了新请求',
            '<strong>🟢 已批准:</strong> 报价单 / 合同已批准',
            '<strong>🟡 Workflow:</strong> 工作进入新步骤',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">开启 Web Push 通知</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/03-web-push',
        'alt' => '浏览器请求 web push 权限的弹窗',
        'caption' => 'Web Push —— 即使关闭浏览器也能收到提醒',
        'callouts' => [
            '<strong>权限弹窗:</strong> 首次登录时出现',
            '<strong>"允许":</strong> 通过浏览器接收通知',
            '<strong>后台运行:</strong> 关闭标签页后仍可正常工作',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>登录 → 浏览器会询问权限</li>
            <li>点击 <strong>"允许"</strong></li>
            <li>有新通知时 → 浏览器会立即弹出提醒</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 看不到 web push 弹窗?</dt>
        <dd>A: 浏览器设置 → 网站权限 → 通知 → 手动允许</dd>

        <dt>Q: 谁会收到通知?</dt>
        <dd>A: 取决于角色 —— Admin 可看到全部，Caretaker/Employer 只能看到自己的</dd>
    </dl>
</section>
