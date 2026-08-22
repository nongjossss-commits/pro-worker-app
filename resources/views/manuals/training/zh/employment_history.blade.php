{{-- Training Edition: Employment History (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-badge"></i> {{ __('Employment History') }} — {{ __('所有雇员，包括已离职/合同到期的') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"雇用历史(Employment History)"</strong> 菜单显示曾经存在于系统中的<strong>所有雇员</strong>，
        无论是在职、已离职、合同到期，还是已转移至其他雇主。
        用于查看历史记录、查找旧雇员，以及将已离职雇员<strong>转移</strong>到新雇主
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker(仅限自己负责的)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">查询历史雇员</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/01-search-filter',
        'alt' => '雇用历史页面 + 筛选栏',
        'caption' => '雇用历史 —— 显示所有雇员，包括非在职人员',
        'callouts' => [
            '<strong>搜索:</strong> 输入姓名 / 护照号码',
            '<strong>按国籍筛选:</strong> 缅甸 / 老挝 / 柬埔寨 / 越南',
            '<strong>按 MOU 类型筛选:</strong> 可选择任意组别',
            '<strong>按护照筛选:</strong> CI / PJ / TD / 国际护照',
            '<strong>按粉卡筛选:</strong> 有 / 无',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>雇用历史</strong></li>
            <li>输入搜索内容或使用顶部筛选器</li>
            <li>点击"筛选" —— 结果同时包含在职与非在职雇员</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">批量将旧雇员转移至新雇主</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/02-bulk-transfer',
        'alt' => '批量操作栏 + 转移雇主弹窗',
        'caption' => '批量转移 —— 将多名雇员转移至新雇主',
        'callouts' => [
            '<strong>勾选框:</strong> 选择多名雇员',
            '<strong>批量操作栏:</strong> 浮现于底部',
            '<strong>转移雇主:</strong> 选择目标雇主',
            '<strong>影响:</strong> 这些雇员的 notify_out 记录会自动取消',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>勾选要转移的雇员</li>
            <li>批量操作栏 → "Actions" → <strong>"转移雇主"</strong></li>
            <li>选择目标雇主 → 确认</li>
            <li>系统会执行转移 + 自动取消这些雇员的 notify_out 记录</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">导出 + 批量 PDF</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/03-export-pdf',
        'alt' => '导出 CSV + 批量 PDF 按钮',
        'caption' => '导出 + PDF —— 使用批量操作',
        'callouts' => [
            '<strong>导出 CSV:</strong> 立即下载(依据当前筛选条件)',
            '<strong>Advanced Export:</strong> 自行选择要导出的列',
            '<strong>Automated PDF:</strong> 从模板批量生成多人的 PDF',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>筛选所需数据</li>
            <li>点击右上角的"Export CSV" —— 立即下载</li>
            <li>或使用批量操作 → "Advanced Export" / "Automated PDF"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 与"雇员"菜单有什么区别?</dt>
        <dd>A: 雇员 = 仅在职人员，雇用历史 = 所有人，包括已离职/合同到期/已发出离职通知的</dd>

        <dt>Q: 回收站中的雇员会显示在这里吗?</dt>
        <dd>A: 不会 —— 请前往"回收站"(Central Trash)查看，可以从那里恢复</dd>
    </dl>
</section>
