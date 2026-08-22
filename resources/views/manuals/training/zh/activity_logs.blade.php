{{-- Training Edition: Activity Logs (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-clock-history"></i> {{ __('Activity Logs') }} — {{ __('系统中所有变更的审计记录') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"操作日志(Activity Logs)"</strong> 菜单记录系统中<strong>每一次数据变更</strong> ——
        谁修改了、修改了什么、何时修改 —— 用于<strong>审计与事后核查</strong>，
        以保证透明并防止舞弊
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">查看历史记录 + 筛选</h2>

    @include('manuals.training._screenshot', [
        'src' => 'activity_logs/01-list-filter',
        'alt' => '操作日志列表 + 筛选栏',
        'caption' => '操作日志 —— 可排序表格 + 筛选器',
        'callouts' => [
            '<strong>按用户筛选:</strong> 选择特定用户',
            '<strong>按日期筛选:</strong> 指定日期范围',
            '<strong>按类型筛选:</strong> create / update / delete',
            '<strong>按实体筛选:</strong> Employee / Employer 等',
            '<strong>详情:</strong> 查看变更前后的数据',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>操作日志</strong></li>
            <li>使用筛选器进行搜索</li>
            <li>点击某一行查看变更前后的详细信息</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">用于审计与调查</h2>

    <div class="slide-instructions">
        <strong>常见使用场景:</strong>
        <ol>
            <li>雇员记录不见了 —— 查看是谁删除的、何时删除的</li>
            <li>护照数据被更改 —— 查看是谁修改的</li>
            <li>发票号码被作废 —— 查看操作人及原因</li>
            <li>每月审核员工的工作记录</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>小贴士:</strong> 操作日志无法删除 —— 即使 Super Admin 也无法修改(不可变)
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 历史记录保留多久?</dt>
        <dd>A: 不会自动删除 —— 永久保留用于审计(如有需要，Super Admin 可清理旧记录)</dd>

        <dt>Q: Staff/Caretaker 可以查看自己的日志吗?</dt>
        <dd>A: 不可以 —— 仅 Super Admin/Admin 可以查看操作日志</dd>
    </dl>
</section>
