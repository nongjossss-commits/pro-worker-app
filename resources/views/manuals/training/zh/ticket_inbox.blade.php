{{-- Training Edition: Ticket Inbox (Chinese) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-inbox-fill"></i> {{ __('Ticket Inbox') }} — {{ __('接收并处理雇主提交的请求') }}
    </h3>
    <p class="training-intro-desc">
        <strong>"工单收件箱(Ticket Inbox)"</strong> 菜单用于接收雇主提交的<strong>工单</strong> ——
        例如"申请为该雇员续签签证"、"申请更换护照" —— Admin/Staff 负责接收 + 指派 + 追踪
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (manage-tickets)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">接收新工单 + 指派</h2>

    @include('manuals.training._screenshot', [
        'src' => 'ticket_inbox/01-list-assign',
        'alt' => '工单列表 + 指派下拉菜单',
        'caption' => '工单收件箱 —— 按状态列出',
        'callouts' => [
            '<strong>状态:</strong> Open / In Progress / Resolved / Closed',
            '<strong>指派给:</strong> 负责此工单的员工',
            '<strong>优先级:</strong> Normal / High / Urgent',
            '<strong>类型:</strong> visa / wp / passport / 其他',
            '<strong>最后更新:</strong> 最近一次回复的时间',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>侧边栏 → <strong>工单收件箱</strong></li>
            <li>点击工单以打开查看</li>
            <li>点击"Assign to..." → 选择负责的员工</li>
            <li>根据处理进度更新状态</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">回复 + 附上文件</h2>

    @include('manuals.training._screenshot', [
        'src' => 'ticket_inbox/02-chat',
        'alt' => '工单回复页面 + 对话记录',
        'caption' => '工单详情 —— 对话记录 + 附件',
        'callouts' => [
            '<strong>消息记录:</strong> 办公室与雇主之间的对话',
            '<strong>附上文件:</strong> 上传 PDF/图片',
            '<strong>关联雇员:</strong> 将某位雇员关联到该工单',
            '<strong>标记为已解决:</strong> 处理完成后关闭工单',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">常见问题</h2>

    <dl class="slide-faq">
        <dt>Q: 回复时会通知客户吗?</dt>
        <dd>A: 只要有回复，系统会自动发送通知 + 邮件</dd>

        <dt>Q: 可以重新指派工单吗?</dt>
        <dd>A: 可以 —— Admin 可以随时更改负责的员工</dd>
    </dl>
</section>
