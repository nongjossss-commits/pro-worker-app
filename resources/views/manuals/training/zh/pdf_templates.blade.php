{{-- Training Edition: PDF Templates (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-file-earmark-pdf-fill"></i> {{ __('PDF Templates') }} — {{ __('创建带自动填充字段的文件模板') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"PDF Templates"</strong> 菜单用于通过<strong>拖放</strong>字段，
        为各类文件(劳动合同、证明文件、政府表格)创建 <strong>PDF 模板</strong>。
        生成正式文件时，系统会自动填入数据
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">创建新模板 —— 2 种方式</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/01-create-mode',
        'alt' => '模板创建页面，含 2 个单选选项',
        'caption' => '创建模板 —— 上传新 PDF，或克隆现有模板',
        'callouts' => [
            '<strong>📤 Upload new PDF:</strong> 从电脑中的空白 PDF 开始',
            '<strong>📋 Copy from existing:</strong> 克隆现有模板 + 稍作调整',
            '<strong>可搜索下拉菜单:</strong> 搜索要克隆的模板',
            '<strong>字段数量:</strong> 显示将复制的字段数量',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>PDF Templates</strong> → "+ Create New Template"</li>
            <li>选择模式：上传新 PDF，或克隆现有模板</li>
            <li>命名 + 选择类型(Global / Employer)</li>
            <li>点击"Upload & Go to Builder"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">在 Builder 中拖放字段</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/02-builder-drag',
        'alt' => 'PDF Builder —— 将字段拖到 PDF 上',
        'caption' => '模板构建器 —— 将字段拖到 PDF 上',
        'callouts' => [
            '<strong>字段面板(左侧):</strong> 可放置的字段(雇主名称 / 护照 / 签名)',
            '<strong>PDF 预览(中间):</strong> 将字段拖到所需位置',
            '<strong>属性(右侧):</strong> 调整大小 / 字体 / 对齐方式',
            '<strong>保存:</strong> 保存字段映射 → 即可用于雇员',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">智能 Quick Print —— 空白打印 + 填入数据</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/03-quick-print',
        'alt' => '显示字段分析的 Quick Print 弹窗',
        'caption' => 'Quick Print —— 打印前先分析字段',
        'callouts' => [
            '<strong>选择模板:</strong> 系统会立即分析其字段',
            '<strong>字段分析:</strong> 雇员/雇主/Delegate/Importer/证人字段各有多少个',
            '<strong>如含有雇员字段:</strong> 会被阻止 + 建议先选择雇员',
            '<strong>如不含:</strong> 选择目标雇主/Delegate/Importer 即可直接打印',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 克隆模板后，删除原始模板会有影响吗?</dt>
        <dd>A: 不会 —— 系统会完整复制文件 + 字段映射，两者完全独立</dd>

        <dt>Q: 上传的 PDF 是扫描件(图片)?</dt>
        <dd>A: 仍可用作背景 —— 将字段拖放到空白区域即可</dd>

        <dt>Q: 泰文字体怎么处理?</dt>
        <dd>A: 系统使用 THSarabunNew + CP874 编码 —— 完整支持泰文</dd>
    </dl>
</section>
