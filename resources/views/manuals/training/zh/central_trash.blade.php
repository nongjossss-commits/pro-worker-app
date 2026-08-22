{{-- Training Edition: Central Trash (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-trash-fill"></i> {{ __('Central Trash') }} — {{ __('恢复已删除的数据') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"回收站(Central Trash)"</strong> 菜单集中保存整个系统中<strong>已删除的数据</strong>
        (雇员 / 雇主 / Production 等) —— 在规定期限内<strong>可以恢复</strong>，
        如不再需要也可永久删除
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">查看已删除项目列表</h2>

    @include('manuals.training._screenshot', [
        'src' => 'central_trash/01-list',
        'alt' => '按类型分类的回收站项目列表',
        'caption' => '回收站 —— 按实体类型分标签',
        'callouts' => [
            '<strong>标签:</strong> Employees / Employers / Production 等',
            '<strong>删除时间:</strong> 删除日期',
            '<strong>删除人:</strong> 由谁删除',
            '<strong>剩余天数:</strong> 距离自动清除的倒计时',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>回收站</strong></li>
            <li>选择被删除数据的类型标签</li>
            <li>查找要恢复的项目</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">恢复(Restore)或永久删除(Delete Forever)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'central_trash/02-restore-delete',
        'alt' => 'Restore + Delete Forever 按钮，含确认提示',
        'caption' => 'Restore / Delete Forever —— 两者均需确认',
        'callouts' => [
            '<strong>♻️ Restore:</strong> 将数据恢复至正常使用状态',
            '<strong>🗑️ Delete Forever:</strong> 永久删除 —— 无法恢复！',
            '<strong>确认对话框:</strong> 两种操作都需要确认',
            '<strong>批量操作:</strong> 可同时选择多个项目',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>找到要恢复的项目 → 点击 <strong>♻️ Restore</strong></li>
            <li>或点击 <strong>🗑️ Delete Forever</strong> 永久删除</li>
            <li>在确认对话框中确认</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>注意:</strong> Delete Forever = 永久删除，无法恢复 —— 点击前请务必确认
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 回收站会保留多久?</dt>
        <dd>A: 默认 30 天 → 之后自动清除(Super Admin 可调整此设置)</dd>

        <dt>Q: 恢复已删除的雇员后，其雇主关联还和之前一样吗?</dt>
        <dd>A: 是的 —— 所有关联关系 + 文件 + 操作日志都会保持不变</dd>

        <dt>Q: Staff 可以删除数据吗?</dt>
        <dd>A: 取决于权限 —— 部分操作需要 Admin 或更高权限</dd>
    </dl>
</section>
