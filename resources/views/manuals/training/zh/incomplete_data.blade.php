{{-- Training Edition: Incomplete Data (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ __('Incomplete Data') }} — {{ __('用于查找数据不完整雇员的工具') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"数据不完整(Incomplete Data)"</strong> 菜单是用于查找<strong>数据不完整雇员</strong>的工具 ——
        例如缺少护照、缺少工作许可证到期日、缺少照片、缺少地址 ——
        以便团队在正式使用前补充完整
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开菜单 + 查看数据缺失的雇员列表</h2>

    @include('manuals.training._screenshot', [
        'src' => 'incomplete_data/01-list',
        'alt' => '数据不完整的雇员列表',
        'caption' => '数据不完整列表 —— 显示缺少哪些字段',
        'callouts' => [
            '<strong>雇员:</strong> 姓名 + 雇主',
            '<strong>缺失字段:</strong> 红色徽章标示缺少的字段',
            '<strong>编辑 ✏️ 按钮:</strong> 点击直接进入该雇员的编辑页面',
            '<strong>筛选:</strong> 按缺失字段类型选择',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>数据不完整</strong></li>
            <li>查看哪些雇员数据缺失，以及缺少哪些字段</li>
            <li>点击"编辑" → 进入雇员页面 → 填写数据</li>
            <li>回来再次检查 —— 数据完整后该条目会自动消失</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 哪些字段算是"数据不完整"?</dt>
        <dd>A: 系统生成文件所需的关键字段 —— 护照、国籍、employer_id、出生日期等</dd>

        <dt>Q: 已经填写完整了，为什么雇员还在列表中?</dt>
        <dd>A: 有 60 秒的缓存 —— 请等待自动刷新或手动刷新</dd>
    </dl>
</section>
