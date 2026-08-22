{{-- Training Edition: Employees — slide-friendly with annotated screenshots (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Employees') }} — {{ __('管理所有外籍劳工数据') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"雇员(Employees)"</strong> 菜单用于<strong>新增 / 编辑 / 查看</strong>每位雇员的数据 ——
        个人信息、护照、签证、工作许可证、照片、附件文件。
        这是所有类型工作(Production、Workflow、登记决议、续签决议)的起点
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker(仅限自己负责的雇员)</span>
    </div>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">打开雇员列表页面</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/01-list-view',
        'alt' => '雇员列表页面(卡片视图)含筛选栏',
        'caption' => '雇员列表页面 —— 可在卡片视图与表格视图之间切换',
        'callouts' => [
            '<strong>筛选栏:</strong> 搜索、按国籍筛选、MOU 组、护照',
            '<strong>+ 新增雇员:</strong> 创建新雇员',
            '<strong>卡片/表格视图:</strong> 按需切换',
            '<strong>批量操作:</strong> 勾选多人以导出、转移雇主、生成 PDF',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击<strong>侧边栏 → 雇员</strong></li>
            <li>选择视图类型(<strong>卡片</strong> 或 <strong>表格</strong>)</li>
            <li>使用顶部筛选器查找所需雇员</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>小贴士:</strong> "雇用历史"菜单会显示所有人，包括已离职雇员 —— 与本菜单不同，本菜单只显示在职雇员
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">新增雇员</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/02-add-employee',
        'alt' => '新增雇员表单页面',
        'caption' => '新增雇员表单 —— 每个数据类别对应一个标签',
        'callouts' => [
            '<strong>选择雇主:</strong> 雇员必须始终关联一个雇主',
            '<strong>必填字段:</strong> 姓名、国籍、护照',
            '<strong>标签:</strong> 基本信息 → 护照/签证 → 文件 → 照片',
            '<strong>文件扫描:</strong> 可直接用相机拍照上传至系统',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击右上角的 <strong>"+ 新增雇员"</strong></li>
            <li>选择<strong>雇主</strong>(可输入搜索)</li>
            <li>填写<strong>姓名 + 国籍 + 护照</strong>(必填)</li>
            <li>在各标签中填写更多信息(非必填 —— 可稍后再补充)</li>
            <li>点击 <strong>"保存"</strong></li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>注意:</strong> Employee Cap —— 系统会根据订阅套餐限制雇员总数
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">编辑雇员信息</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/03-edit-employee',
        'alt' => '雇员编辑页面，含信息与文件标签',
        'caption' => '雇员编辑页面 —— Personal、Documents、History 标签',
        'callouts' => [
            '<strong>Personal:</strong> 姓名、地址、国籍、出生日期',
            '<strong>Documents:</strong> 护照、签证、工作许可证 + 上传 PDF/图片',
            '<strong>Other Documents:</strong> 10 个插槽用于附加文件(默认名称在 Super Admin 中设置)',
            '<strong>History 标签:</strong> 查看修改历史 + 操作日志',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>点击<strong>雇员卡片</strong>或铅笔 ✏️ 按钮</li>
            <li>各个标签中都有相应字段可填写</li>
            <li>通过 <strong>Upload</strong> 按钮或<strong>文件扫描</strong>上传文件</li>
            <li>点击 <strong>"保存"</strong> —— 系统会将变更记录到操作日志中</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>小贴士:</strong> 编辑雇员信息后 → 该雇员所属工作的卡片会在 Workflow/Production 中排到最上面
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">雇员预览按钮</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/04-preview-popup',
        'alt' => '以只读方式显示雇员数据的预览弹窗',
        'caption' => '预览弹窗 —— 无需打开编辑页面即可快速查看雇员数据',
        'callouts' => [
            '<strong>预览 🔍 按钮:</strong> 出现在每个页面的雇员卡片上',
            '<strong>只读:</strong> 只能查看，不能编辑',
            '<strong>涵盖内容:</strong> Personal、护照、签证、文件、照片',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>在雇员卡片上寻找<strong>放大镜 🔍</strong> 图标</li>
            <li>点击 → 弹出窗口显示全部信息</li>
            <li>关闭弹窗或点击空白区域返回</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Caretaker:</strong> 只能预览自己所负责雇主的雇员
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">批量操作 —— 同时处理多人</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/05-bulk-actions',
        'alt' => '勾选多人后浮现的批量操作栏',
        'caption' => '批量操作栏 —— 勾选多名雇员后自动浮现',
        'callouts' => [
            '<strong>勾选框:</strong> 每张卡片左上角都有勾选框',
            '<strong>操作菜单:</strong> 导出、转移雇主、生成 PDF、送入 Production',
            '<strong>计数器:</strong> 显示已选择的人数',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>勾选所需雇员的<strong>勾选框</strong>(可多选)</li>
            <li>批量操作栏会浮现在底部</li>
            <li>从下拉菜单中选择操作:
                <ul>
                    <li><strong>Export CSV / Advanced Export</strong></li>
                    <li><strong>转移雇主</strong>(Bulk Transfer)</li>
                    <li><strong>Automated PDF</strong>(从模板批量生成 PDF)</li>
                    <li><strong>Send to Production</strong></li>
                </ul>
            </li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 无法新增雇员 —— 出现错误?</dt>
        <dd>A: 请检查 Employee Cap —— 系统会根据订阅套餐限制数量，如需增加请联系 Super Admin</dd>

        <dt>Q: 雇员从列表中消失了?</dt>
        <dd>A: 请前往"雇用历史"菜单查看 —— 可能已办理离职/合同到期，或已被删除至"回收站"</dd>

        <dt>Q: Caretaker 看到的雇员比预期少?</dt>
        <dd>A: Caretaker 只能看到自己所负责雇主的雇员</dd>

        <dt>Q: 预览按钮无法使用 —— 出现 Error 500?</dt>
        <dd>A: 之前曾有此问题 —— 现已修复，Caretaker 现在可以正常预览(仅限自己负责的雇员)</dd>
    </dl>
</section>
