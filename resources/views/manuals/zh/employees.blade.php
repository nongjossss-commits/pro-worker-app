{{--
    User Manual: Employees (Chinese)
--}}

<h4><i class="bi bi-people-fill me-2"></i>这个菜单是做什么的?</h4>
<p>
    <strong>"雇员"</strong> 菜单是本公司管理的外籍劳工(缅甸/老挝/柬埔寨)数据库。
    用于保存基本信息(姓名、国籍、护照号码、签证、工作许可证、MOU 合同)，
    并与雇员所在的<strong>雇主</strong>相关联。
</p>

<h4><i class="bi bi-person-check me-2"></i>谁可以进入这个菜单?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> —— 拥有全部权限，包括离职/恢复/永久删除</li>
    <li><span class="manual-role">Staff</span> —— 可查看 / 新增 / 编辑，不可永久删除</li>
    <li><span class="manual-role">Caretaker</span> —— 可查看 / 新增 / 编辑 + 办理雇员离职</li>
    <li><span class="manual-role">Employer</span> —— 只能看到自己的雇员</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>页面外观</h4>
<ol>
    <li><strong>筛选栏</strong> 位于顶部 —— 按国籍、签证状态、雇主筛选，可按姓名/护照号码搜索 · <strong>国籍框</strong> 包含全世界所有国籍(4 种语言)—— 输入部分名称即可缩小选项(泰文/英文/缅文/中文均可，如"越"、"japan")，或点击 ▾ 箭头滚动选择完整列表 · 点击空白框会先显示本浏览器"最近选择"的 4 项，然后是主要国籍(缅甸、老挝、柬埔寨、越南) · <strong>到期日框</strong>(筛选栏末尾)筛选工作许可<em>或</em>签证在所选日期到期的雇员</li>
    <li><strong>搜索框</strong> —— 输入雇员或雇主姓名的一部分,系统会立即显示匹配的建议(无需输入完整姓名);点击建议即可立即筛选列表 · <strong>点击某个雇员建议只显示该雇员</strong>，即使其他雇主下有同名的人 · 不选择直接按 Enter 会显示所有与输入文字匹配的人 · 搜索结果也包括已离职/已取消登记的雇员(与建议列表一致)</li>
    <li><strong>两个搜索框</strong> —— 第一个框可输入雇员(姓名/护照/各类编号)、雇主或项目负责人，然后从列表选择：选雇员 = 只显示该人，选雇主 = 只显示该雇主，选负责人 = 该负责人名下的所有雇主(输入后按 Enter = 按输入文字搜索) · 第一个框有内容后第二个框才可用，用于在第一个框的结果中进一步缩小范围，可输入同样类型的内容</li>
    <li><strong>状态标签</strong> —— 在职 / 离职 / 合同到期 / 回收站</li>
    <li><strong>按地址筛选</strong> —— 按雇主地址选择 府 → 县(区) → 乡(分区)(仅列出当前列表中已有的地址) · 更换府后会清除原来的县/乡 · 搜索或使用其他筛选时地址筛选保持有效 · 切换到英文等非泰语界面时，如已记录英文名称则地名显示为英文 · 雇主、资料不完整、通知、Workflow、Pre-Production、注册/续期决议、组/团队菜单中的筛选相同</li>
    <li><strong>雇员卡片</strong> —— 以网格显示，每张卡片大小固定，按屏幕宽度自动排满每行(屏幕越大或缩小页面每行越多，放大则越少，手机每行 1 张)；长文字：雇主名称以 … 省略但放大镜按钮保留，详细信息最多显示 2 行(悬停可看全文) · 每张显示照片(国旗靠卡片左边缘，国籍在照片右侧，按所选语言显示)、姓名(🔍 预览按钮紧跟在姓名后)、雇主、最多 6 行详细信息(默认：护照号码、护照到期日、工作许可类型、90天报到 —— Super Admin 可在 Super Admin 设置 →「雇员卡片信息」标签中更改，最多 6 项并可排序)、进行中工作的状态框(可显示 3 个状态标签，过长文字以 … 省略，悬停可看全文，卡片不会变大)，按钮在底部 · 勾选框在右上角 · 可用右侧按钮选择每页 25 / 50 / 100 名 —— 系统会在该浏览器记住最后的选择(刷新或重新打开后不变，每个菜单分别记忆，首次为 100) · 离职雇员、雇主页面的雇员标签、组与团队也采用相同布局(Workflow / 决议菜单保持原卡片)</li>
    <li>右上角的 <strong>"+ 新增雇员"</strong> 和 <strong>"从 Excel 导入"</strong> 按钮</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>常见操作步骤</h4>

<h5>1. 逐个新增雇员</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击右上角的 <strong>"+ 新增雇员"</strong></li>
        <li>填写基本信息 —— 姓名(泰文/英文)、性别、国籍(可输入搜索或从完整列表选择 —— 与筛选框相同)、出生日期</li>
        <li>填写<strong>护照</strong>号码 + 到期日</li>
        <li>选择该雇员所在的<strong>雇主</strong></li>
        <li>如有，上传照片和证件文件(护照复印件、签证、工作许可证)</li>
        <li>点击 <strong>"保存"</strong></li>
    </ol>
</div>

<div class="manual-tip">
    💡 <strong>新增 / 编辑 / 预览雇员页面已分为标签页</strong> —— 无需向下滚动，直接点击所需标签：
    <strong>个人信息</strong>(照片、姓名、出生日期、国籍、电话 + 登录信息) · <strong>护照、签证与工作许可</strong>(护照 + 缅甸/柬埔寨雇员的护照类型、签证 · 工作许可依次为：许可类型 → 工作许可编号 → 身份编号 → 粉卡号 → 签发/到期日、90天报告、RA 编号、职位、工作性质) ·
    <strong>雇佣与保险</strong>(入职日期/工龄、申请编号、税号、工人编码、部门、银行 · 保险) · <strong>附件</strong>(数字 = 已附文件数)
    · <strong>输入框按内容类型设定宽度</strong>(年龄/身高窄、日期短、姓名/工作性质长)，并按屏幕宽度排列 —— 大屏或缩小页面每行更多，放大则更少
    <ul class="mb-0 mt-2">
        <li>顶部为<strong>资料头</strong> —— 照片、姓名、雇主、出生日期(年龄)、国籍、护照号，以及护照 / 签证 / 工作许可 / 90天报告的到期标签：
            <span class="text-danger">红 = 已过期</span> · <span class="text-warning">黄 = 即将到期(30天；90天报告15天)</span> · <span class="text-success">绿 = 有效</span> · 灰 = 未填写</li>
        <li><strong>保存</strong>按钮在每个标签页都固定在屏幕底部 —— 一次保存所有标签页</li>
        <li>如有漏填或填错的字段，系统会自动打开该标签页并显示<strong>红点</strong> · 仍缺少的字段(黄色图标)会在标签上计数</li>
        <li>在手机上可左右滑动标签栏</li>
        <li><strong>附件</strong>标签页分为<strong>主要文件</strong>(1–8)和<strong>其他文件</strong>(9–18)，并显示"已附 x/y" —— 每个位置是一张小卡片：绿色边 + "已附" = 已有文件(👁 查看 · PDF · ✏️ 编辑) · <strong>选择文件 / 更换文件</strong>和 📷 扫描/拍照 · 选好新文件后卡片变蓝并显示文件名(点击保存后上传) · <strong>其他文件 9–18</strong>：位置标题即文件名称(默认 = Super Admin 在设置 → Attachment Files Settings 中设定的名称)，点击名称旁的 ✏️ 可仅为该雇员重命名(Enter = 确定，Esc = 取消)—— 高级下载/导出/编辑使用 Super Admin 设定的统一名称</li>
        <li><strong>预览</strong>(🔍 按钮)：资料头和标签栏固定不动，窗口大小不变 —— 切换标签时只有下方内容滑入(内容较长时在框内滚动) · 雇主预览也一样 · 雇员预览窗口更宽、几乎占满屏幕高度，字段按窗口宽度依次排满每行(年龄/身高等短字段占位少，姓名/医院等长字段占位多；手机为 2 列) · 附件标签以小方框并排显示 —— 有文件的为绿色并带「查看」和「PDF」按钮，无文件的为淡色并显示「无文件」；名称过长时最多显示 2 行(悬停可看全名)</li>
        <li>预览右上角有 <strong>"编辑"</strong> 按钮(仅限有编辑权限的用户) —— 点击进入编辑页面，保存或取消后回到原页面并重新打开预览，显示最新数据</li>
    </ul>
</div>

<h5>2. 批量导入雇员(Excel 批量导入)</h5>
<div class="manual-step">
    适合雇主一次性引入多名雇员的情况
    <ol class="mb-0 mt-2">
        <li>点击 <strong>"从 Excel 导入"</strong> 按钮</li>
        <li>先下载 <strong>"模板"</strong> 文件 —— 示例文件中有系统所需的列标题</li>
        <li>按照列标题在 Excel 中填写雇员信息(每人一行)</li>
        <li>将文件上传回系统</li>
        <li>确认前先查看<strong>预览</strong> —— 系统会显示将要新增的记录，并对错误数据提出警告</li>
        <li>点击 <strong>"确认"</strong> —— 系统会一次性全部新增</li>
    </ol>
</div>

<h5>3. 离职 / 合同到期(Terminate)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>打开要办理离职的雇员卡片</li>
        <li>点击 <strong>"离职 / 合同到期"</strong> 按钮</li>
        <li>选择原因 + 离职日期</li>
        <li>点击 <strong>"确认"</strong> —— 状态将变更为"离职"，不再计入使用配额</li>
    </ol>
    <div class="manual-tip mt-2 mb-0">
        <i class="bi bi-info-circle-fill"></i> <strong>小贴士:</strong>
        离职的雇员并未消失 —— 仍会保留在"离职"标签下，如重新雇用可以恢复
    </div>
</div>

<h5>4. 恢复离职雇员(Reinstate)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>点击顶部的<strong>"离职"</strong>状态标签</li>
        <li>找到要恢复的雇员</li>
        <li>点击 <strong>"恢复在职"</strong></li>
    </ol>
</div>

<h5>5. 永久删除雇员(Force Delete)</h5>
<div class="manual-warn">
    <strong>仅限 Admin/Super Admin</strong> —— 用于处理误录入或重复的雇员，删除后<strong>无法恢复</strong>
    <br>
    步骤：前往"回收站"标签 → 点击永久删除图标 → 确认两次
</div>

<h5>6. 员工重复检查</h5>
<div class="manual-step">
    所有新建员工的途径(本菜单、Workflow / Pre-Production、注册/续签决议、Excel 导入、销售转移)都会比对 护照 / 工作证 / 粉卡 / 身份证号 / RA 编号,重复时保存前提示(仅提示,不阻止)。Excel 导入和销售转移需勾选"仍然继续"。
</div>

<h5>7. 搜索可找到所有状态</h5>
<div class="manual-step">
    默认列表只显示在职员工,但搜索时也会找到已通知离职 / 已取消注册的员工,并显示 <strong>Terminated</strong> / <strong>Registration Cancelled</strong> 标记 —— 便于核对重复提示。
</div>

<h5>8. 批量移动附件(超级管理员)</h5>
<div class="manual-step">
    勾选员工 → <strong>移动附件</strong> → 选择来源/目标栏位及方式:交换 / 移动并删除来源 / 合并到 B(合并两个 PDF)。只作用于所选员工;超级管理员可将此权限授予特定 Admin/Staff。
</div>

<h5>9. 工作证类型</h5>
<div class="manual-step">
    "工作证类型"列表由超级管理员在设置中管理(新增 / 重命名 / 排序)。重命名会自动更新现有员工;仍在使用的类型不能删除;"其他"可随时手动填写。
</div>

<h5>10. 编辑员工照片(裁剪窗口)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li><strong>旋转 / 调整角度</strong> — 旋转后空出的角会填充为所选背景色(默认白色),不再是灰色/黑色</li>
        <li><strong>Auto Face Center Crop</strong> — 按证件照样式裁剪并使人脸居中;所有浏览器均可使用(首次使用可能需要几秒钟)</li>
        <li><strong>White BG / Light Blue BG</strong> — 系统自动去除背景并填充颜色</li>
        <li>如果抠错了(例如手臂或肩膀不见了),点击 <strong>Refine / Edit Mask</strong>:
            <ul>
                <li><strong>原图虚影</strong> — 在后面淡淡显示原图,可以看到被裁掉的部分</li>
                <li><strong>恢复</strong> — 在虚影上涂抹,把误删的部分找回 · <strong>Erase</strong> — 擦除多余部分</li>
                <li><strong>智能边缘</strong>(默认开启)— 把笔刷中心放在要擦除/恢复的区域,笔刷边缘可以越过,系统会自动停在轮廓线</li>
                <li><strong>魔术棒</strong> — 单击即可擦除一整片相近颜色的区域</li>
                <li>鼠标滚轮 = 缩放 · 按住空格键或右键拖动 = 移动画面 · <kbd>[</kbd> <kbd>]</kbd> = 笔刷大小 · <kbd>Ctrl</kbd>+<kbd>Z</kbd> = 撤销</li>
            </ul>
        </li>
        <li>点击 <strong>Apply Changes</strong> — 照片立即使用所选背景色(无需再次点击背景按钮)</li>
    </ol>
</div>

<h5>11. 将雇员 / 雇主资料发送到聊天（LINE 等）</h5>
<div class="manual-step">
    雇员和雇主卡片上有 <i class="bi bi-grid-3x2-gap-fill"></i> 按钮（卡片右下角）——在雇员、雇主、通知、Pre-Production、登记/续期决议、Workflow、预约日历、资料不完整及工单中均可使用。
    <ol class="mb-0 mt-2">
        <li>将按钮<strong>拖入</strong> LINE 聊天（电脑版）或其他应用 —— 收到的是易读的文本：姓名、国籍、护照、到期日……（拖到系统内的聊天仍然是原来的卡片）</li>
        <li><strong>点击</strong>按钮打开菜单：
            <ul>
                <li><strong>复制为文本</strong> —— 然后在聊天中按 <kbd>Ctrl</kbd>+<kbd>V</kbd></li>
                <li><strong>复制为卡片图片</strong> —— 含照片和资料的品牌色卡片，在聊天中按 <kbd>Ctrl</kbd>+<kbd>V</kbd></li>
                <li><strong>保存卡片图片</strong> —— 保存为 PNG 文件</li>
                <li><strong>分享…</strong>（手机/平板）—— 打开设备的分享菜单，选择 LINE</li>
            </ul>
        </li>
        <li>从通知菜单发送时，文本会包含原因，例如 "⚠️ 签证到期：28/01/2026（已逾期 242 天）"</li>
        <li><strong>管理员</strong>可选择允许发送的栏位 —— 点击 <i class="bi bi-grid-3x2-gap-fill"></i> → <strong>分享设置</strong>（姓名始终包含）</li>
        <li><strong>随菜单的资料：</strong>申请编号、预约、团队和备注取自所发送的卡片 —— Workflow / Pre-Production 用该工作的，登记 / 续期用该标签的，从雇员菜单发送时用雇员记录 · 已勾选但尚无资料的栏位显示 "-"</li>
        <li>每次分享都会记录在<strong>操作日志</strong>中：谁以何种方式发送了谁的资料</li>
    </ol>
</div>
<div class="manual-warn">
    资料一旦进入 LINE 或其他应用，系统便无法再控制（个人资料 —— PDPA）。只发送给需要的人。
</div>

<h4><i class="bi bi-lightbulb me-2"></i>须知事项 / 使用小贴士</h4>

<div class="manual-tip">
    <strong>雇员最大配额:</strong> Super Admin 可以限制在职雇员的数量
    (在 Super Admin Settings → 配额设置 中设置) —— 如果配额已满，将无法新增雇员
</div>

<div class="manual-tip">
    <strong>雇员 vs 授权代表(Delegate):</strong> 部分角色左侧菜单中的"员工信息"
    实际指的是<strong>雇主的授权代表(Delegate)</strong>，并非外籍劳工雇员 —— 这是两个不同的菜单
</div>

<div class="manual-tip">
    <strong>签证/护照即将到期:</strong> 系统会在<strong>通知(Notifications)</strong>中
    自动提醒即将到期的项目 —— 请每天早上检查
</div>
<div class="manual-tip">
    <strong>高级下载（合并为一个 PDF / 每人一个 PDF）：</strong>来自扫描仪或手机应用的 PDF（较新的 PDF 格式）会在合并前自动转换 · 若仍无法合并，下载中心会提示，而"每人一个 PDF"会将原始文件放入 ZIP —— ZIP 方式始终包含所有原始文件
</div>
<div class="manual-tip">
    <strong>下载大量雇员：</strong>点击开始后，文件在后台生成 —— 下载中心显示进度（如 "Processing... 120/500"），完成后自动开始下载（请保持下载中心打开）· 若已关闭，可在 24 小时内回到列表点击 Download · 超出服务器内存的"合并为一个 PDF"会被拆分为 ZIP 中的多个 PDF（顺序不变），而不会失败
</div>

<div class="manual-warn">
    <strong>永久删除雇员前:</strong> 请确认没有 Production / Workflow 工作或税务发票
    仍关联到该雇员
</div>

<h4><i class="bi bi-question-circle me-2"></i>常见问题</h4>

<dl>
    <dt>Q: 无法新增雇员，提示"超出配额"?</dt>
    <dd>A: 系统配额已满 —— 请联系 Super Admin 增加配额，或将不再使用的旧雇员办理离职</dd>

    <dt>Q: 为什么 Excel 导入会出现错误?</dt>
    <dd>A: 请检查 Excel 文件中的列标题是否与下载的模板完全一致 —— 不要更改列名</dd>

    <dt>Q: 雇员转到了新的雇主?</dt>
    <dd>A: 编辑该雇员 → 将"雇主"更改为新雇主 → 保存</dd>

    <dt>Q: 只能看到一家公司的雇员?</dt>
    <dd>A: 如果您以"Employer"角色登录，只能看到自己的雇员 —— 需要 Admin/Staff 或更高角色才能查看所有人</dd>
</dl>
