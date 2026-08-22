{{-- Training Edition: Employer Ticket (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-send-fill"></i> {{ __('Employer Ticket') }} — {{ __('供雇主使用：向办公室提交请求') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"提交请求(Employer Ticket)"</strong> 菜单供 <strong>Employer 角色</strong>使用，
        可直接向办公室提交请求 —— 例如"申请续签签证"、"申请办理雇员离职" —— 无需通过邮件/Line。
        办公室将在<strong>工单收件箱</strong>菜单中接收
    </p>
    <div class="training-role-row">
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">创建新工单</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employer_ticket/01-new-ticket',
        'alt' => '新建工单表单',
        'caption' => '新建工单表单 —— 选择类型 + 填写详情 + 附上文件',
        'callouts' => [
            '<strong>类型:</strong> Visa / Work Permit / Passport / 其他',
            '<strong>相关雇员:</strong> 从自己的雇员中选择',
            '<strong>详情:</strong> 说明具体需求',
            '<strong>附上文件:</strong> PDF / 图片(可选)',
            '<strong>优先级:</strong> Normal / High / Urgent',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏(Employer) → <strong>提交请求</strong> 或 "+ New Ticket"</li>
            <li>选择类型 + 雇员</li>
            <li>填写详情 + 附上文件</li>
            <li>点击提交 → 办公室会立即收到通知</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">追踪状态 + 回复</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employer_ticket/02-status-chat',
        'alt' => '工单列表 + 对话记录',
        'caption' => '我的工单 —— 追踪状态 + 与办公室对话',
        'callouts' => [
            '<strong>状态:</strong> Open / In Progress / Resolved',
            '<strong>对话记录:</strong> 与办公室沟通',
            '<strong>通知:</strong> 办公室回复时会弹出提醒',
            '<strong>标记为已解决:</strong> 满意后关闭工单',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 可以提交多少个工单?</dt>
        <dd>A: 不限数量 —— 每个工单为独立事项</dd>

        <dt>Q: 能看到其他公司的工单吗?</dt>
        <dd>A: 不能 —— 只能看到自己公司的工单</dd>

        <dt>Q: 办公室关闭了我的工单，但事情还没处理完?</dt>
        <dd>A: 请开一个新工单，并注明原工单编号</dd>
    </dl>
</section>
