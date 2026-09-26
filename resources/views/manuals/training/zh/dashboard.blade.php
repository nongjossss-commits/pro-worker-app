{{-- Training Edition: Dashboard (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }} — {{ __('系统整体概览与摘要') }}
    </h3>
    <p class="training-intro-desc">
        <strong>Dashboard(仪表板)</strong>是用户登录后看到的第一个页面 —— 显示<strong>摘要信息</strong>：
        雇员/雇主数量、待处理工作、最新通知，以及前往常用菜单的<strong>快捷链接</strong>
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
    <div class="slide-number">STEP 0</div>
    <h2 class="slide-title">登录 + 安全</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/03-login',
        'alt' => '登录页面 —— 颜色和标志跟随系统品牌设置',
        'caption' => '登录页面 —— 颜色和标志跟随系统品牌设置',
    ])

    <div class="slide-instructions">
        <ol>
            <li>关闭浏览器 / 应用 → 下次需重新登录</li>
            <li>程序页面全部关闭超过 5 分钟 → 自动登出</li>
            <li>刷新或新标签页 → 照常使用</li>
            <li>"记住我"记住邮箱;"忘记密码?"发送重设链接(60 分钟有效)</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开 Dashboard + 查看总览</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/01-overview',
        'alt' => '包含摘要卡片 + 快捷链接的 Dashboard 页面',
        'caption' => 'Dashboard —— 摘要卡片 + 快捷链接 + 最近活动',
        'callouts' => [
            '<strong>摘要卡片:</strong> 雇主/雇员/待处理工作数量',
            '<strong>到期提醒:</strong> 即将到期的雇员(60/30/7 天)',
            '<strong>快捷链接:</strong> 前往常用菜单',
            '<strong>最近通知:</strong> 最新 5 条',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>登录 → 自动进入 Dashboard</li>
            <li>查看顶部的摘要卡片</li>
            <li>点击卡片或快捷链接前往所需菜单</li>
            <li>语言按钮后的按钮 = <strong>浅色 / 深色 / 跟随设备</strong>模式 —— 立即切换无需刷新,按设备分别记住</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">不同角色看到的 Dashboard 不同</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/02-role-variants',
        'alt' => 'Admin / Caretaker / Employer 各自看到的 Dashboard',
        'caption' => '按角色显示的 Dashboard —— 可见数据不同',
        'callouts' => [
            '<strong>Admin/Staff:</strong> 可查看全系统所有数据',
            '<strong>Caretaker:</strong> 只能看到自己负责的雇主+雇员',
            '<strong>Employer:</strong> 只能看到自己的雇员',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 摘要卡片上的数字对不上?</dt>
        <dd>A: 缓存每 60 秒更新一次 —— 请刷新或稍等片刻</dd>

        <dt>Q: 为什么 Caretaker 看到的数据比较少?</dt>
        <dd>A: Caretaker 只能看到通过 employer_caretaker 关联表分配给自己的雇主</dd>
    </dl>
</section>
