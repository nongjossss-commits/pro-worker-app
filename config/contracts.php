<?php

/**
 * Content for the Super Admin "Trial Contract" and "Service Contract" printable
 * documents (resources/views/super-admin/contract-trial.blade.php and
 * contract-service.blade.php). Each document is available in 4 languages;
 * the SA picks up to 2 at generation time and both render stacked per section.
 *
 * Placeholder tokens (replaced by the blade view, not by Laravel's __()):
 *   {{PROVIDER_NAME}}, {{CUSTOMER_NAME}}   - intro paragraph
 *   {{TRIAL_START}}, {{TRIAL_END}}         - trial clause 2
 *   {{TEST_URL_SENTENCE}}                  - trial clause 2 (built from test_url_sentence below)
 *   {{TEST_URL}}                           - inside test_url_sentence
 *   {{SERVICE_START}}, {{SERVICE_END}}, {{SERVICE_YEARS}} - service clause 3
 *
 * The Thai ('th') content below is copied verbatim from the original
 * hardcoded blade text — not reworded — so Thai-only output is unchanged.
 */

return [

    'trial' => [

        'th' => [
            'doc_title' => 'สัญญาทดลองใช้บริการระบบ',
            'doc_id_label' => 'เลขที่สัญญา:',
            'made_at_label' => 'ทำที่',
            'date_label' => 'วันที่',
            'intro' => 'สัญญาฉบับนี้จัดทำขึ้นระหว่าง <strong>{{PROVIDER_NAME}}</strong> ซึ่งต่อไปนี้จะเรียกว่า <em>"ผู้ให้บริการ"</em> ฝ่ายหนึ่ง กับ <strong>{{CUSTOMER_NAME}}</strong> ซึ่งต่อไปนี้จะเรียกว่า <em>"ผู้รับบริการ"</em> อีกฝ่ายหนึ่ง โดยทั้งสองฝ่ายตกลงกันมีข้อความดังต่อไปนี้',
            'provider_box_title' => 'ผู้ให้บริการ',
            'customer_box_title' => 'ผู้รับบริการ',
            'party_labels' => [
                'company_name' => 'ชื่อบริษัท:',
                'address' => 'ที่อยู่:',
                'tax_id' => 'เลขผู้เสียภาษี:',
                'phone_email' => 'โทรศัพท์ / อีเมล:',
            ],
            'clause_prefix' => 'ข้อ :n.',
            'test_url_sentence' => 'โดยผู้รับบริการสามารถเข้าถึงระบบทดสอบได้ที่ {{TEST_URL}}',
            'clauses' => [
                [
                    'type' => 'clause',
                    'title' => 'ขอบเขตและวัตถุประสงค์',
                    'body' => 'ผู้ให้บริการตกลงเปิดให้ผู้รับบริการ <strong>ทดลองใช้งานระบบบริหารจัดการแรงงาน (Pro-Worker)</strong> บนสภาพแวดล้อมทดสอบ (Test Server) เพื่อวัตถุประสงค์ในการประเมินฟีเจอร์ ทดสอบความเสถียร และพิจารณาความเหมาะสมของระบบกับการใช้งานจริงในองค์กรของผู้รับบริการเท่านั้น การทดลองใช้นี้<strong>ไม่ใช่</strong>การให้บริการเชิงพาณิชย์',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ระยะเวลาทดลองใช้',
                    'body' => 'สัญญานี้มีผลตั้งแต่วันที่ <strong>{{TRIAL_START}}</strong> ถึงวันที่ <strong>{{TRIAL_END}}</strong> {{TEST_URL_SENTENCE}} เมื่อครบกำหนดระยะเวลาทดลอง ผู้ให้บริการขอสงวนสิทธิ์ในการระงับการเข้าถึงโดยมิต้องแจ้งให้ทราบล่วงหน้า',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ค่าบริการ',
                    'body' => 'การทดลองใช้ตามสัญญานี้ <strong>ไม่มีค่าบริการ (Free of Charge)</strong> ผู้รับบริการไม่ต้องชำระเงินใดๆ แก่ผู้ให้บริการในช่วงระยะเวลาทดลอง หากภายหลังผู้รับบริการประสงค์จะใช้งานเชิงพาณิชย์ ทั้งสองฝ่ายจะจัดทำ "สัญญาเช่าใช้บริการระบบ" แยกต่างหาก',
                ],
                [
                    'type' => 'warning',
                    'title' => 'การปฏิเสธความรับผิดและไม่รับประกัน (Disclaimer &amp; No Warranty)',
                    'body' => 'ผู้ให้บริการขอแจ้งให้ผู้รับบริการรับทราบและตกลงโดยชัดแจ้งว่า:
                        <ol class="mb-0 mt-2">
                            <li><strong>ไม่รับประกันข้อมูลใดๆ ทั้งสิ้น</strong> — ข้อมูลที่ผู้รับบริการบันทึกในระบบทดสอบ อาจสูญหาย เสียหาย หรือถูกลบโดยผู้ให้บริการได้ทุกเมื่อ โดยไม่ต้องแจ้งให้ทราบล่วงหน้า</li>
                            <li><strong>ไม่รับประกันความพร้อมใช้งานของระบบ (No SLA)</strong> — Server ทดสอบอาจมีความล่าช้า (Latency) ความเร็วในการตอบสนองที่แตกต่างจาก Production Server หรือหยุดทำงานเพื่อบำรุงรักษาได้ทุกเมื่อ</li>
                            <li><strong>ไม่รับผิดในความเสียหาย</strong> ไม่ว่าทางตรงหรือทางอ้อม ที่อาจเกิดจากการใช้งานระบบทดสอบ รวมถึงความสูญเสียทางธุรกิจ ข้อมูลสูญหาย หรือเหตุอื่นใด</li>
                            <li>ผู้รับบริการตกลง <strong>ไม่ใช้ข้อมูลจริง (Real Data)</strong> ของบุคคลธรรมดาหรือลูกค้าจริงในระบบทดสอบ ควรใช้ข้อมูลตัวอย่างหรือข้อมูลทดสอบเท่านั้น</li>
                        </ol>',
                ],
                [
                    'type' => 'exclusion',
                    'title' => 'ขอบเขตที่ไม่รวมในการให้บริการ — ฟีเจอร์การเงิน (Finance Feature Exclusion)',
                    'body' => 'ผู้รับบริการรับทราบและตกลงโดยชัดแจ้งว่า <strong>ฟีเจอร์การเงิน (Finance Module)</strong> ซึ่งรวมถึงแต่ไม่จำกัดเพียง: ระบบใบเสนอราคา, ใบแจ้งหนี้, ใบกำกับภาษี, ใบเสร็จ, การคำนวณภาษีมูลค่าเพิ่ม (VAT), ภาษีหัก ณ ที่จ่าย (WHT), ภ.พ.30, ภ.ง.ด.3/53, รายงานภาษี, การกระทบยอดบัญชีธนาคาร, สมุดบัญชี (Ledger), และรายงานการเงินทั้งหมด <strong>ไม่รวมอยู่ในขอบเขตของสัญญาทดลองใช้ฉบับนี้</strong> และจะ <strong>ไม่เปิดให้ใช้งาน</strong> ในระยะทดลอง เนื่องจากฟีเจอร์ดังกล่าวเป็นโมดูลที่มีความละเอียดอ่อน ต้องทำงานควบคู่กับเรื่องภาษี ระบบบัญชี และข้อบังคับทางกฎหมาย จึงจัดเป็นบริการแยกต่างหาก หากผู้รับบริการประสงค์จะใช้งานฟีเจอร์การเงิน จะต้องทำสัญญาเพิ่มเติม (Add-on Agreement) ในภายหลัง',
                ],
                [
                    'type' => 'clause',
                    'title' => 'การรักษาความลับ',
                    'body' => 'ทั้งสองฝ่ายตกลงที่จะรักษาความลับเกี่ยวกับข้อมูลทางธุรกิจ ระบบ ฟีเจอร์ และเทคโนโลยีที่ได้รับทราบจากการทดลองใช้ ผู้รับบริการจะไม่เปิดเผย ทำสำเนา หรือใช้ประโยชน์เชิงพาณิชย์จากข้อมูลของผู้ให้บริการ และจะไม่กระทำการใดอันเป็นการละเมิดทรัพย์สินทางปัญญาของผู้ให้บริการ',
                ],
                [
                    'type' => 'clause',
                    'title' => 'การสิ้นสุดสัญญา',
                    'body' => 'สัญญานี้สิ้นสุดลงโดยอัตโนมัติเมื่อครบกำหนดระยะเวลาตามข้อ 2. หรือเมื่อฝ่ายใดฝ่ายหนึ่งบอกเลิกสัญญาโดยแจ้งเป็นลายลักษณ์อักษรล่วงหน้า 3 วันทำการ เมื่อสัญญาสิ้นสุด ผู้ให้บริการมีสิทธิลบข้อมูลทั้งหมดในระบบทดสอบโดยไม่ต้องเก็บสำรอง',
                ],
            ],
            'closing_paragraph' => 'สัญญานี้ทำขึ้นเป็นสองฉบับ มีข้อความถูกต้องตรงกัน คู่สัญญาทั้งสองฝ่ายได้อ่านและเข้าใจข้อความโดยตลอดแล้ว จึงได้ลงลายมือชื่อไว้ต่อหน้าพยานเป็นสำคัญ',
            'signature_labels' => [
                'provider_role' => 'ผู้ให้บริการ',
                'customer_role' => 'ผู้รับบริการ',
                'witness_1' => 'พยาน 1',
                'witness_2' => 'พยาน 2',
                'position_placeholder' => 'ตำแหน่ง',
            ],
        ],

        'en' => [
            'doc_title' => 'Trial Service Agreement',
            'doc_id_label' => 'Contract No.:',
            'made_at_label' => 'Made at',
            'date_label' => 'Date',
            'intro' => 'This Agreement is made between <strong>{{PROVIDER_NAME}}</strong>, hereinafter referred to as the <em>"Provider"</em>, of the one part, and <strong>{{CUSTOMER_NAME}}</strong>, hereinafter referred to as the <em>"Customer"</em>, of the other part, whereby both parties agree to the following terms',
            'provider_box_title' => 'Provider',
            'customer_box_title' => 'Customer',
            'party_labels' => [
                'company_name' => 'Company Name:',
                'address' => 'Address:',
                'tax_id' => 'Tax ID:',
                'phone_email' => 'Phone / Email:',
            ],
            'clause_prefix' => 'Clause :n.',
            'test_url_sentence' => 'The Customer may access the test system at {{TEST_URL}}',
            'clauses' => [
                [
                    'type' => 'clause',
                    'title' => 'Scope and Purpose',
                    'body' => 'The Provider agrees to grant the Customer a <strong>trial of the Pro-Worker labor management system</strong> on a Test Server environment, solely for the purpose of evaluating features, testing stability, and assessing the system\'s suitability for real use within the Customer\'s organization. This trial is <strong>not</strong> a commercial service.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Trial Period',
                    'body' => 'This Agreement is effective from <strong>{{TRIAL_START}}</strong> to <strong>{{TRIAL_END}}</strong>. {{TEST_URL_SENTENCE}} Upon expiry of the trial period, the Provider reserves the right to suspend access without prior notice.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Fees',
                    'body' => 'The trial under this Agreement is <strong>free of charge</strong>. The Customer is not required to pay any fee to the Provider during the trial period. Should the Customer later wish to proceed with commercial use, both parties will execute a separate "Service Contract".',
                ],
                [
                    'type' => 'warning',
                    'title' => 'Disclaimer &amp; No Warranty',
                    'body' => 'The Provider notifies the Customer, who expressly agrees, that:
                        <ol class="mb-0 mt-2">
                            <li><strong>No data warranty whatsoever</strong> — data the Customer enters into the test system may be lost, damaged, or deleted by the Provider at any time without prior notice.</li>
                            <li><strong>No availability warranty (No SLA)</strong> — the test server may experience latency, response times different from the Production server, or downtime for maintenance at any time.</li>
                            <li><strong>No liability for damages</strong>, whether direct or indirect, arising from use of the test system, including business loss, data loss, or any other cause.</li>
                            <li>The Customer agrees <strong>not to use real data</strong> of actual individuals or real customers in the test system, and should use sample or test data only.</li>
                        </ol>',
                ],
                [
                    'type' => 'exclusion',
                    'title' => 'Finance Feature Exclusion',
                    'body' => 'The Customer acknowledges and expressly agrees that the <strong>Finance Module</strong>, including but not limited to: quotations, invoices, tax invoices, receipts, Value Added Tax (VAT) calculation, Withholding Tax (WHT), ภ.พ.30, ภ.ง.ด.3/53, tax reports, bank reconciliation, the Ledger, and all financial reports, is <strong>not included in the scope of this trial Agreement</strong> and <strong>will not be enabled</strong> during the trial period, as this module is sensitive and must operate in conjunction with tax, accounting, and legal requirements, and is therefore offered as a separate service. Should the Customer wish to use the Finance feature, an additional Add-on Agreement must be executed at a later date.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Confidentiality',
                    'body' => 'Both parties agree to keep confidential any business information, systems, features, and technology disclosed as a result of the trial. The Customer shall not disclose, copy, or commercially exploit the Provider\'s information, and shall not infringe upon the Provider\'s intellectual property.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Termination',
                    'body' => 'This Agreement terminates automatically upon expiry of the period stated in Clause 2, or when either party terminates it by written notice given 3 business days in advance. Upon termination, the Provider is entitled to delete all data in the test system without retaining a backup.',
                ],
            ],
            'closing_paragraph' => 'This Agreement is made in two counterparts of identical content. Both parties have read and fully understood the terms and have accordingly signed in the presence of witnesses.',
            'signature_labels' => [
                'provider_role' => 'Provider',
                'customer_role' => 'Customer',
                'witness_1' => 'Witness 1',
                'witness_2' => 'Witness 2',
                'position_placeholder' => 'Position',
            ],
        ],

        'zh' => [
            'doc_title' => '试用服务协议',
            'doc_id_label' => '合同编号:',
            'made_at_label' => '签订地点',
            'date_label' => '日期',
            'intro' => '本协议由 <strong>{{PROVIDER_NAME}}</strong>(以下简称"服务提供者")与 <strong>{{CUSTOMER_NAME}}</strong>(以下简称"客户")共同签订,双方同意以下条款',
            'provider_box_title' => '服务提供者',
            'customer_box_title' => '客户',
            'party_labels' => [
                'company_name' => '公司名称:',
                'address' => '地址:',
                'tax_id' => '纳税人识别号:',
                'phone_email' => '电话 / 邮箱:',
            ],
            'clause_prefix' => '第:n条',
            'test_url_sentence' => '客户可通过以下地址访问测试系统:{{TEST_URL}}',
            'clauses' => [
                [
                    'type' => 'clause',
                    'title' => '范围与目的',
                    'body' => '服务提供者同意向客户开放 <strong>Pro-Worker 劳务管理系统的试用</strong>,试用环境为测试服务器(Test Server),仅供客户评估系统功能、测试稳定性,并判断该系统是否适合其组织的实际使用。此试用<strong>不属于</strong>商业性服务。',
                ],
                [
                    'type' => 'clause',
                    'title' => '试用期限',
                    'body' => '本协议自 <strong>{{TRIAL_START}}</strong> 起至 <strong>{{TRIAL_END}}</strong> 止生效。{{TEST_URL_SENTENCE}} 试用期届满后,服务提供者保留随时中止访问权限的权利,无需另行通知。',
                ],
                [
                    'type' => 'clause',
                    'title' => '服务费用',
                    'body' => '本协议项下的试用<strong>完全免费</strong>,客户在试用期内无需向服务提供者支付任何费用。若客户日后希望进行商业性使用,双方应另行签订"系统租用服务协议"。',
                ],
                [
                    'type' => 'warning',
                    'title' => '免责声明与不保证条款(Disclaimer &amp; No Warranty)',
                    'body' => '服务提供者特此告知客户,客户明确同意以下事项:
                        <ol class="mb-0 mt-2">
                            <li><strong>不对任何数据作出保证</strong> — 客户在测试系统中录入的数据,服务提供者可随时予以丢失、损坏或删除,无需事先通知。</li>
                            <li><strong>不保证系统可用性(无 SLA)</strong> — 测试服务器可能出现延迟、响应速度与生产服务器不同,或因维护而随时停机。</li>
                            <li>服务提供者<strong>不承担任何直接或间接损害赔偿责任</strong>,包括但不限于因使用测试系统而产生的业务损失、数据丢失或其他任何原因。</li>
                            <li>客户同意<strong>不在测试系统中使用真实数据(Real Data)</strong>,包括真实自然人或真实客户的信息,应仅使用示例或测试数据。</li>
                        </ol>',
                ],
                [
                    'type' => 'exclusion',
                    'title' => '服务范围排除条款 — 财务功能(Finance Feature Exclusion)',
                    'body' => '客户确认并明确同意,<strong>财务功能模块(Finance Module)</strong>,包括但不限于:报价单系统、发票、税务发票、收据、增值税(VAT)计算、预扣税(WHT)、ภ.พ.30、ภ.ง.ด.3/53、税务报表、银行对账、账簿(Ledger)以及全部财务报表,<strong>均不包含在本试用协议的服务范围之内</strong>,且在试用期间<strong>不予开放使用</strong>。由于该模块具有较高敏感性,须与税务、会计制度及法律规定配合运作,因此作为独立服务提供。若客户日后希望使用财务功能,须另行签订附加服务协议(Add-on Agreement)。',
                ],
                [
                    'type' => 'clause',
                    'title' => '保密条款',
                    'body' => '双方同意对因本次试用而获知的业务信息、系统、功能及技术予以保密。客户不得披露、复制或将服务提供者的信息用于商业用途,亦不得侵犯服务提供者的知识产权。',
                ],
                [
                    'type' => 'clause',
                    'title' => '协议终止',
                    'body' => '本协议在第2条所载期限届满时自动终止,或经任一方提前3个工作日书面通知终止。协议终止后,服务提供者有权删除测试系统中的全部数据,且无需保留备份。',
                ],
            ],
            'closing_paragraph' => '本协议一式两份,双方各执一份,内容完全一致。双方已阅读并充分理解本协议条款,特此在见证人见证下签字确认。',
            'signature_labels' => [
                'provider_role' => '服务提供者',
                'customer_role' => '客户',
                'witness_1' => '见证人 1',
                'witness_2' => '见证人 2',
                'position_placeholder' => '职位',
            ],
        ],

        'my' => [
            'doc_title' => 'စနစ်စမ်းသပ်အသုံးပြုမှု သဘောတူညီချက်',
            'doc_id_label' => 'သဘောတူညီချက်အမှတ်:',
            'made_at_label' => 'ချုပ်ဆိုသည့်နေရာ',
            'date_label' => 'ရက်စွဲ',
            'intro' => 'ဤသဘောတူညီချက်ကို <strong>{{PROVIDER_NAME}}</strong> (ယခုမှစ၍ <em>"ဝန်ဆောင်မှုပေးသူ"</em> ဟု ခေါ်ဆိုမည်) တစ်ဖက်နှင့် <strong>{{CUSTOMER_NAME}}</strong> (ယခုမှစ၍ <em>"ဝန်ဆောင်မှုလက်ခံသူ"</em> ဟု ခေါ်ဆိုမည်) တစ်ဖက်တို့အကြား ချုပ်ဆိုပြီး၊ နှစ်ဖက်စလုံးသည် အောက်ပါစည်းကမ်းချက်များကို သဘောတူညီကြသည်',
            'provider_box_title' => 'ဝန်ဆောင်မှုပေးသူ',
            'customer_box_title' => 'ဝန်ဆောင်မှုလက်ခံသူ',
            'party_labels' => [
                'company_name' => 'ကုမ္ပဏီအမည်:',
                'address' => 'လိပ်စာ:',
                'tax_id' => 'အခွန်ထမ်းအမှတ်:',
                'phone_email' => 'ဖုန်း / အီးမေးလ်:',
            ],
            'clause_prefix' => 'အပိုဒ် :n။',
            'test_url_sentence' => 'ဝန်ဆောင်မှုလက်ခံသူသည် စမ်းသပ်စနစ်ကို {{TEST_URL}} တွင် ဝင်ရောက်နိုင်သည်',
            'clauses' => [
                [
                    'type' => 'clause',
                    'title' => 'နယ်ပယ်နှင့် ရည်ရွယ်ချက်',
                    'body' => 'ဝန်ဆောင်မှုပေးသူသည် ဝန်ဆောင်မှုလက်ခံသူအား <strong>Pro-Worker လုပ်သားစီမံခန့်ခွဲမှုစနစ်ကို စမ်းသပ်အသုံးပြုခွင့်</strong>ကို စမ်းသပ်ဆာဗာ (Test Server) ပတ်ဝန်းကျင်ပေါ်တွင် ဖွင့်ပေးရန် သဘောတူသည်၊ ရည်ရွယ်ချက်မှာ ဝန်ဆောင်မှုလက်ခံသူ၏ အဖွဲ့အစည်းအတွင်း စနစ်၏ လုပ်ဆောင်ချက်များကို အကဲဖြတ်ရန်၊ တည်ငြိမ်မှုကို စမ်းသပ်ရန်နှင့် လက်တွေ့အသုံးပြုမှုနှင့် ကိုက်ညီမှုရှိမရှိ စဉ်းစားရန်အတွက်သာ ဖြစ်သည်။ ဤစမ်းသပ်အသုံးပြုမှုသည် ကူးသန်းရောင်းဝယ်ရေးဆိုင်ရာ ဝန်ဆောင်မှု <strong>မဟုတ်ပါ</strong>',
                ],
                [
                    'type' => 'clause',
                    'title' => 'စမ်းသပ်ကာလ',
                    'body' => 'ဤသဘောတူညီချက်သည် <strong>{{TRIAL_START}}</strong> မှ <strong>{{TRIAL_END}}</strong> အထိ သက်ရောက်မှုရှိသည်။ {{TEST_URL_SENTENCE}} စမ်းသပ်ကာလ ကုန်ဆုံးသောအခါ ဝန်ဆောင်မှုပေးသူသည် ကြိုတင်အသိပေးခြင်းမပြုဘဲ ဝင်ရောက်ခွင့်ကို ရပ်ဆိုင်းပိုင်ခွင့် ရှိသည်',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ဝန်ဆောင်ခ',
                    'body' => 'ဤသဘောတူညီချက်အရ စမ်းသပ်အသုံးပြုမှုသည် <strong>အခမဲ့ (Free of Charge)</strong> ဖြစ်သည်၊ စမ်းသပ်ကာလအတွင်း ဝန်ဆောင်မှုလက်ခံသူသည် ဝန်ဆောင်မှုပေးသူအား ငွေကြေးတစ်စုံတစ်ရာ ပေးချေရန် မလိုအပ်ပါ၊ နောင်တွင် ဝန်ဆောင်မှုလက်ခံသူသည် ကူးသန်းရောင်းဝယ်ရေးဆိုင်ရာ အသုံးပြုလိုပါက နှစ်ဖက်စလုံးသည် "စနစ်ငှားရမ်းအသုံးပြုမှု သဘောတူညီချက်" သီးခြားချုပ်ဆိုရမည်',
                ],
                [
                    'type' => 'warning',
                    'title' => 'တာဝန်ပြေလွတ်ကြောင်းနှင့် အာမမခံကြောင်း ကြေညာချက် (Disclaimer &amp; No Warranty)',
                    'body' => 'ဝန်ဆောင်မှုပေးသူသည် ဝန်ဆောင်မှုလက်ခံသူအား အသိပေးပြီး ဝန်ဆောင်မှုလက်ခံသူသည် ရှင်းလင်းစွာ သဘောတူပါသည်:
                        <ol class="mb-0 mt-2">
                            <li><strong>ဒေတာမည်သည့်အရာမျှ အာမမခံပါ</strong> — ဝန်ဆောင်မှုလက်ခံသူ စမ်းသပ်စနစ်တွင် သိမ်းဆည်းထားသော ဒေတာများသည် ဝန်ဆောင်မှုပေးသူမှ ကြိုတင်အသိပေးခြင်းမရှိဘဲ အချိန်မရွေး ဆုံးရှုံး၊ ပျက်စီး၊ သို့မဟုတ် ဖျက်ပစ်ခံရနိုင်သည်</li>
                            <li><strong>စနစ်အသင့်ရှိမှုကို အာမမခံပါ (No SLA)</strong> — စမ်းသပ်ဆာဗာသည် နှေးကွေးမှု (Latency)၊ Production ဆာဗာနှင့် မတူညီသော တုံ့ပြန်မှုအမြန်နှုန်း၊ သို့မဟုတ် ပြုပြင်ထိန်းသိမ်းမှုအတွက် အချိန်မရွေး ရပ်တန့်နိုင်သည်</li>
                            <li>စမ်းသပ်စနစ် အသုံးပြုမှုကြောင့် ဖြစ်ပေါ်လာနိုင်သည့် တိုက်ရိုက် သို့မဟုတ် သွယ်ဝိုက်သော ပျက်စီးမှု၊ စီးပွားရေးဆိုင်ရာ ဆုံးရှုံးမှု၊ ဒေတာဆုံးရှုံးမှု သို့မဟုတ် အခြားအကြောင်းအရာများအတွက် <strong>တာဝန်မယူပါ</strong></li>
                            <li>ဝန်ဆောင်မှုလက်ခံသူသည် စမ်းသပ်စနစ်တွင် စစ်မှန်သော ပုဂ္ဂိုလ် သို့မဟုတ် စစ်မှန်သော ဖောက်သည်၏ <strong>အစစ်အမှန်ဒေတာ (Real Data)</strong> ကို အသုံးမပြုရန် သဘောတူပြီး နမူနာဒေတာ သို့မဟုတ် စမ်းသပ်ဒေတာကိုသာ အသုံးပြုသင့်သည်</li>
                        </ol>',
                ],
                [
                    'type' => 'exclusion',
                    'title' => 'ဝန်ဆောင်မှုတွင် မပါဝင်သော နယ်ပယ် — ငွေကြေး Feature (Finance Feature Exclusion)',
                    'body' => 'ဝန်ဆောင်မှုလက်ခံသူသည် <strong>ငွေကြေး Module (Finance Module)</strong> — ဈေးနှုန်းပြသလွှာစနစ်၊ ငွေတောင်းခံလွှာ၊ အခွန်ဘောင်ချာ၊ ငွေလက်ခံဖြေ၊ ဗီအေတီ (VAT) တွက်ချက်မှု၊ ရင်းမြစ်မှ နုတ်ယူသောအခွန် (WHT)၊ ภ.พ.30၊ ภ.ง.ด.3/53၊ အခွန်အစီရင်ခံစာများ၊ ဘဏ်ချိန်ညှိခြင်း၊ Ledger နှင့် ငွေကြေးအစီရင်ခံစာများအားလုံးအပါအဝင် — ဤအရာများသည် <strong>ဤစမ်းသပ်သဘောတူညီချက်၏ နယ်ပယ်တွင် မပါဝင်ကြောင်း</strong> နှင့် စမ်းသပ်ကာလအတွင်း <strong>ဖွင့်ပေးမည် မဟုတ်ကြောင်း</strong> ရှင်းလင်းစွာ သဘောတူပါသည်၊ အကြောင်းမှာ ယင်း Module သည် အလွန်သတိထားရသော Module ဖြစ်ပြီး အခွန်၊ စာရင်းကိုင်စနစ်နှင့် ဥပဒေစည်းမျဉ်းများနှင့် အတူတကွ လုပ်ဆောင်ရသောကြောင့် သီးခြားဝန်ဆောင်မှုအဖြစ် စီစဉ်ထားပါသည်၊ ဝန်ဆောင်မှုလက်ခံသူသည် နောင်တွင် ငွေကြေး Feature ကို အသုံးပြုလိုပါက ထပ်လောင်းသဘောတူညီချက် (Add-on Agreement) ကို ပြုလုပ်ရမည်',
                ],
                [
                    'type' => 'clause',
                    'title' => 'လျှို့ဝှက်ချက် ထိန်းသိမ်းခြင်း',
                    'body' => 'နှစ်ဖက်စလုံးသည် စမ်းသပ်အသုံးပြုမှုမှ သိရှိရသော လုပ်ငန်းအချက်အလက်၊ စနစ်၊ Feature နှင့် နည်းပညာများကို လျှို့ဝှက်ထိန်းသိမ်းရန် သဘောတူသည်၊ ဝန်ဆောင်မှုလက်ခံသူသည် ဝန်ဆောင်မှုပေးသူ၏ အချက်အလက်ကို ထုတ်ဖော်ခြင်း၊ ကူးယူခြင်း သို့မဟုတ် ကူးသန်းရောင်းဝယ်ရေးဆိုင်ရာ အကျိုးအမြတ် ရယူခြင်း မပြုရ၊ ဝန်ဆောင်မှုပေးသူ၏ ဉာဏပစ္စည်း မူပိုင်ခွင့်ကို ချိုးဖောက်သည့် မည်သည့်လုပ်ဆောင်ချက်ကိုမျှ မပြုလုပ်ရ',
                ],
                [
                    'type' => 'clause',
                    'title' => 'သဘောတူညီချက် ကုန်ဆုံးခြင်း',
                    'body' => 'ဤသဘောတူညီချက်သည် အပိုဒ် ၂ တွင်ဖော်ပြထားသော ကာလကုန်ဆုံးသောအခါ သို့မဟုတ် နှစ်ဖက်အနက် တစ်ဖက်ဖက်က အလုပ်လုပ်ရက် ၃ ရက် ကြိုတင်၍ စာဖြင့် အသိပေးကာ သဘောတူညီချက်ကို ဖျက်သိမ်းသောအခါ အလိုအလျောက် ကုန်ဆုံးမည်၊ သဘောတူညီချက် ကုန်ဆုံးသောအခါ ဝန်ဆောင်မှုပေးသူသည် စမ်းသပ်စနစ်ရှိ ဒေတာအားလုံးကို ဘက်ကပ်သိမ်းဆည်းရန် မလိုဘဲ ဖျက်ပိုင်ခွင့် ရှိသည်',
                ],
            ],
            'closing_paragraph' => 'ဤသဘောတူညီချက်ကို မိတ္တူနှစ်စောင် ပြုလုပ်ထားပြီး အကြောင်းအရာ တူညီပါသည်၊ နှစ်ဖက်စလုံးသည် စည်းကမ်းချက်များကို ဖတ်ရှုပြီး အပြည့်အဝ နားလည်ကြသဖြင့် သက်သေများရှေ့တွင် လက်မှတ်ရေးထိုးကြပါသည်',
            'signature_labels' => [
                'provider_role' => 'ဝန်ဆောင်မှုပေးသူ',
                'customer_role' => 'ဝန်ဆောင်မှုလက်ခံသူ',
                'witness_1' => 'သက်သေ ၁',
                'witness_2' => 'သက်သေ ၂',
                'position_placeholder' => 'ရာထူး',
            ],
        ],

    ],

    'service' => [

        'th' => [
            'doc_title' => 'สัญญาเช่าใช้บริการระบบบริหารจัดการแรงงาน',
            'doc_id_label' => 'เลขที่สัญญา:',
            'made_at_label' => 'ทำที่',
            'date_label' => 'วันที่',
            'intro' => 'สัญญาฉบับนี้จัดทำขึ้นระหว่าง <strong>{{PROVIDER_NAME}}</strong> ซึ่งต่อไปนี้จะเรียกว่า <em>"ผู้ให้บริการ"</em> ฝ่ายหนึ่ง กับ <strong>{{CUSTOMER_NAME}}</strong> ซึ่งต่อไปนี้จะเรียกว่า <em>"ผู้รับบริการ"</em> อีกฝ่ายหนึ่ง โดยทั้งสองฝ่ายตกลงเช่าใช้บริการระบบบนหลักการและเงื่อนไขดังต่อไปนี้',
            'provider_box_title' => 'ผู้ให้บริการ',
            'customer_box_title' => 'ผู้รับบริการ',
            'party_labels' => [
                'company_name' => 'ชื่อบริษัท:',
                'address' => 'ที่อยู่:',
                'tax_id' => 'เลขผู้เสียภาษี:',
                'phone_email' => 'โทรศัพท์ / อีเมล:',
            ],
            'package_box_title' => 'รายละเอียดแพ็คเกจ',
            'package_labels' => [
                'tier' => 'แพ็คเกจ:',
                'setup_fee' => 'ค่าแรกเข้า (ครั้งเดียว):',
                'annual_fee' => 'ค่ารายปี:',
                'start' => 'เริ่มสัญญา:',
                'end' => 'สิ้นสุดสัญญา:',
            ],
            'currency_unit' => 'บาท',
            'year_unit' => 'ปี',
            'clause_prefix' => 'ข้อ :n.',
            'clauses' => [
                [
                    'type' => 'clause',
                    'title' => 'คำนิยาม (Definitions)',
                    'body' => '<ol>
                        <li><strong>"ระบบ"</strong> หมายถึง ระบบบริหารจัดการแรงงานต่างด้าว Pro-Worker ที่ผู้ให้บริการพัฒนาขึ้น รวมถึงโมดูลย่อยทั้งหมด ได้แก่ จัดการนายจ้าง, ลูกจ้าง, มติลงทะเบียน, มติต่ออายุ, MOU, Workflow, Documents PDF, ระบบแจ้งเตือน, Activity Log, ฯลฯ <strong>ยกเว้นโมดูลที่ระบุไว้ในข้อ 8</strong></li>
                        <li><strong>"การให้บริการ"</strong> หมายถึง การให้สิทธิเข้าถึงและใช้งานระบบในรูปแบบ Software-as-a-Service (SaaS)</li>
                        <li><strong>"ข้อมูล"</strong> หมายถึง ข้อมูลทั้งหมดที่ผู้รับบริการนำเข้าหรือสร้างขึ้นในระบบ</li>
                    </ol>',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ขอบเขตการให้บริการ',
                    'body' => 'ผู้ให้บริการตกลงให้สิทธิแก่ผู้รับบริการในการเข้าถึงและใช้งานระบบบนสภาพแวดล้อม Production ตามแพ็คเกจที่ระบุข้างต้น โดยรวมถึงการสนับสนุนการใช้งานพื้นฐาน (Basic Support) ผ่านช่องทางอีเมลหรือช่องทางที่ผู้ให้บริการกำหนด',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ระยะเวลาและการต่ออายุ',
                    'body' => 'สัญญานี้มีผลตั้งแต่วันที่ <strong>{{SERVICE_START}}</strong> ถึงวันที่ <strong>{{SERVICE_END}}</strong> รวมระยะเวลา <strong>{{SERVICE_YEARS}}</strong> และจะต่ออายุอัตโนมัติเป็นรายปี เว้นแต่ฝ่ายใดฝ่ายหนึ่งจะแจ้งความประสงค์ไม่ต่ออายุ เป็นลายลักษณ์อักษรล่วงหน้าไม่น้อยกว่า 30 วันก่อนวันสิ้นสุดสัญญา',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ค่าบริการและการชำระเงิน',
                    'body' => 'ผู้รับบริการตกลงชำระค่าบริการตามแพ็คเกจที่ระบุข้างต้น โดยแยกเป็น <strong>ค่าแรกเข้า</strong> (ชำระครั้งเดียวเมื่อเริ่มสัญญา) และ <strong>ค่ารายปี</strong> (ชำระล่วงหน้าก่อนเริ่มแต่ละรอบปี) ราคาดังกล่าวยังไม่รวมภาษีมูลค่าเพิ่ม (VAT 7%) การชำระล่าช้าเกิน 15 วันนับจากวันครบกำหนด ผู้ให้บริการสงวนสิทธิ์ระงับการเข้าถึงระบบจนกว่าจะชำระเสร็จสิ้น',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ระดับการให้บริการ (Service Level Agreement)',
                    'body' => 'ผู้ให้บริการมุ่งมั่นให้บริการระบบมีความพร้อมใช้งาน (Uptime) ไม่น้อยกว่า <strong>99.5% ต่อเดือน</strong> ยกเว้นช่วงบำรุงรักษาที่กำหนดล่วงหน้า (Planned Maintenance) ซึ่งจะแจ้งให้ผู้รับบริการทราบไม่น้อยกว่า 48 ชั่วโมง และเหตุสุดวิสัย (Force Majeure) ที่อยู่นอกเหนือการควบคุม',
                ],
                [
                    'type' => 'security',
                    'title' => 'มาตรฐานความปลอดภัยและการป้องกันข้อมูล',
                    'body' => 'ผู้ให้บริการจัดมาตรการความปลอดภัยตามมาตรฐานสากล ดังนี้:
                        <ul class="mb-0 mt-1">
                            <li><strong>การเข้ารหัสในการสื่อสาร (TLS 1.2+)</strong> สำหรับการรับ-ส่งข้อมูลทุกครั้ง</li>
                            <li><strong>การเข้ารหัสข้อมูลที่จัดเก็บ (Encryption at Rest)</strong> สำหรับฐานข้อมูลและไฟล์แนบ</li>
                            <li><strong>การควบคุมการเข้าถึงตามบทบาท (Role-Based Access Control / RBAC)</strong> ผ่าน Spatie Permission</li>
                            <li><strong>การสำรองข้อมูลรายวัน (Daily Backup)</strong> เก็บย้อนหลังอย่างน้อย 30 วัน</li>
                            <li><strong>บันทึกร่องรอย (Audit Log)</strong> ทุกการเปลี่ยนแปลงข้อมูลถูกบันทึกพร้อม timestamp + user</li>
                            <li><strong>นโยบายรหัสผ่าน</strong> (bcrypt hashing, ขั้นต่ำ 8 ตัวอักษร, lockout ป้องกัน brute-force)</li>
                            <li><strong>การป้องกัน CSRF, XSS, SQL Injection</strong> ตามแนวทาง OWASP Top 10</li>
                            <li>มุ่งสู่ความสอดคล้องกับมาตรฐาน <strong>ISO/IEC 27001</strong> (Information Security Management)</li>
                        </ul>',
                ],
                [
                    'type' => 'clause',
                    'title' => 'การคุ้มครองข้อมูลส่วนบุคคล (PDPA Compliance)',
                    'body' => 'การประมวลผลข้อมูลส่วนบุคคลภายในระบบเป็นไปตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) และสอดคล้องกับแนวทาง General Data Protection Regulation (GDPR) ของสหภาพยุโรป โดยผู้ให้บริการทำหน้าที่เป็น <strong>ผู้ประมวลผลข้อมูล (Data Processor)</strong> และผู้รับบริการเป็น <strong>ผู้ควบคุมข้อมูล (Data Controller)</strong> ผู้ให้บริการจะไม่นำข้อมูลของผู้รับบริการไปใช้นอกเหนือจากวัตถุประสงค์ของสัญญา',
                ],
                [
                    'type' => 'exclusion',
                    'title' => 'ขอบเขตที่ไม่รวมในการให้บริการ — ฟีเจอร์การเงิน (Finance Feature Exclusion)',
                    'body' => 'คู่สัญญาทั้งสองฝ่ายตกลงโดยชัดแจ้งว่า <strong>โมดูลฟีเจอร์การเงิน (Finance Module)</strong> ซึ่งรวมถึงแต่ไม่จำกัดเพียง:
                        <ul class="mb-2 mt-1">
                            <li>ระบบใบเสนอราคา / ใบแจ้งหนี้ / ใบกำกับภาษี / ใบเสร็จรับเงิน (Quotation / Invoice / Tax Invoice / Receipt)</li>
                            <li>การคำนวณภาษีมูลค่าเพิ่ม (VAT) และภาษีหัก ณ ที่จ่าย (Withholding Tax / WHT)</li>
                            <li>รายงานภาษี ภ.พ.30, ภ.ง.ด.3, ภ.ง.ด.53</li>
                            <li>ใบรับรองหัก ณ ที่จ่าย (WHT Certificate)</li>
                            <li>การกระทบยอดบัญชีธนาคาร (Bank Reconciliation)</li>
                            <li>สมุดบัญชี (Ledger) และรายงานการเงินทั้งหมด</li>
                            <li>Monthly Bundle และ Audit Log ของฟีเจอร์การเงิน</li>
                        </ul>
                        <strong>ไม่รวมอยู่ในขอบเขตของสัญญาฉบับนี้</strong> และ <strong>จะไม่เปิดให้ใช้งาน</strong> ตลอดระยะเวลาสัญญา เนื่องจากฟีเจอร์ดังกล่าวเป็นโมดูลที่มีความละเอียดอ่อน ต้องทำงานควบคู่กับเรื่องภาษี ระบบบัญชี ข้อบังคับทางกฎหมายและภาษีในแต่ละประเทศ จึงจัดเป็นบริการแยกต่างหาก (Separate Package) หากผู้รับบริการประสงค์จะใช้งานฟีเจอร์การเงินในภายหลัง คู่สัญญาจะต้องจัดทำ <strong>สัญญาเพิ่มเติม (Add-on Service Agreement)</strong> และตกลงค่าบริการแยกต่างหาก ผู้ให้บริการสงวนสิทธิ์ในการปิดการเข้าถึงโมดูลการเงินผ่านระบบตั้งค่าของระบบ',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ทรัพย์สินทางปัญญา (Intellectual Property)',
                    'body' => 'สิทธิในทรัพย์สินทางปัญญาทั้งหมดของระบบ ซอร์สโค้ด การออกแบบ UI/UX โลโก้ และฟีเจอร์ เป็นกรรมสิทธิ์ของผู้ให้บริการแต่เพียงผู้เดียว ข้อมูลที่ผู้รับบริการนำเข้าระบบเป็นกรรมสิทธิ์ของผู้รับบริการ ผู้ให้บริการไม่มีสิทธิ์นำไปใช้นอกเหนือจากการให้บริการ',
                ],
                [
                    'type' => 'clause',
                    'title' => 'การรักษาความลับ (Confidentiality)',
                    'body' => 'ทั้งสองฝ่ายตกลงรักษาความลับเกี่ยวกับข้อมูลทางธุรกิจ ระบบ และเทคโนโลยีของอีกฝ่ายหนึ่ง แม้สัญญาจะสิ้นสุดลงแล้ว ข้อผูกพันในการรักษาความลับยังคงมีผลต่อไปอีก 3 ปี',
                ],
                [
                    'type' => 'clause',
                    'title' => 'การจำกัดความรับผิด (Limitation of Liability)',
                    'body' => 'ความรับผิดของผู้ให้บริการต่อผู้รับบริการ ไม่ว่าจะเกิดจากการละเมิดสัญญา การกระทำละเมิด หรือมูลเหตุอื่นใด รวมแล้วในแต่ละรอบปีสัญญา จะไม่เกิน <strong>มูลค่าค่าบริการที่ผู้รับบริการชำระจริงในรอบปีนั้น</strong> และผู้ให้บริการไม่รับผิดต่อความเสียหายทางอ้อม (Indirect / Consequential Damages)',
                ],
                [
                    'type' => 'clause',
                    'title' => 'การสิ้นสุดสัญญาและกฎหมายที่ใช้บังคับ',
                    'body' => 'สัญญานี้สิ้นสุดลงเมื่อ (ก) ครบกำหนดระยะเวลาและไม่ได้ต่ออายุ (ข) ฝ่ายใดฝ่ายหนึ่งผิดสัญญาอย่างมีนัยสำคัญและไม่แก้ไขภายใน 30 วันหลังได้รับหนังสือบอกกล่าว (ค) ฝ่ายใดฝ่ายหนึ่งถูกฟ้องล้มละลายหรือเลิกกิจการ เมื่อสัญญาสิ้นสุด ผู้ให้บริการจะให้ผู้รับบริการ Export ข้อมูลภายใน 30 วันก่อนลบข้อมูลออกจากระบบอย่างถาวร สัญญานี้อยู่ภายใต้บังคับ <strong>กฎหมายแห่งราชอาณาจักรไทย</strong> ข้อพิพาทใดๆ ที่ไม่อาจระงับได้โดยการเจรจา ให้นำเสนอต่อ <strong>ศาลในกรุงเทพมหานคร</strong>',
                ],
            ],
            'closing_paragraph' => 'สัญญานี้ทำขึ้นเป็นสองฉบับ มีข้อความถูกต้องตรงกัน คู่สัญญาทั้งสองฝ่ายได้อ่านและเข้าใจข้อความโดยตลอดแล้ว จึงได้ลงลายมือชื่อพร้อมประทับตรา (ถ้ามี) ไว้ต่อหน้าพยานเป็นสำคัญ',
            'signature_labels' => [
                'provider_role' => 'ผู้ให้บริการ',
                'customer_role' => 'ผู้รับบริการ',
                'witness_1' => 'พยาน 1',
                'witness_2' => 'พยาน 2',
                'position_placeholder' => 'ตำแหน่ง',
            ],
        ],

        'en' => [
            'doc_title' => 'Software-as-a-Service (SaaS) Subscription Agreement',
            'doc_id_label' => 'Contract No.:',
            'made_at_label' => 'Made at',
            'date_label' => 'Date',
            'intro' => 'This Agreement is made between <strong>{{PROVIDER_NAME}}</strong>, hereinafter referred to as the <em>"Provider"</em>, of the one part, and <strong>{{CUSTOMER_NAME}}</strong>, hereinafter referred to as the <em>"Customer"</em>, of the other part, whereby both parties agree to subscribe to the system on the following terms and conditions',
            'provider_box_title' => 'Service Provider',
            'customer_box_title' => 'Customer',
            'party_labels' => [
                'company_name' => 'Company Name:',
                'address' => 'Address:',
                'tax_id' => 'Tax ID:',
                'phone_email' => 'Phone / Email:',
            ],
            'package_box_title' => 'Package Details',
            'package_labels' => [
                'tier' => 'Package:',
                'setup_fee' => 'Setup Fee (one-time):',
                'annual_fee' => 'Annual Fee:',
                'start' => 'Contract Start:',
                'end' => 'Contract End:',
            ],
            'currency_unit' => 'Baht',
            'year_unit' => 'year(s)',
            'clause_prefix' => 'Clause :n.',
            'clauses' => [
                [
                    'type' => 'clause',
                    'title' => 'Definitions',
                    'body' => '<ol>
                        <li><strong>"System"</strong> means the Pro-Worker migrant labor management system developed by the Provider, including all sub-modules such as Employer Management, Employee Management, Registration Resolution, Renewal Resolution, MOU, Workflow, PDF Documents, the Notification system, Activity Log, etc., <strong>excluding the module specified in Clause 8</strong>.</li>
                        <li><strong>"Service"</strong> means the granting of access to and use of the System as a Software-as-a-Service (SaaS).</li>
                        <li><strong>"Data"</strong> means all data entered into or generated within the System by the Customer.</li>
                    </ol>',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Scope of Service',
                    'body' => 'The Provider agrees to grant the Customer the right to access and use the System on the Production environment according to the package specified above, including Basic Support via email or such other channel as designated by the Provider.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Term and Renewal',
                    'body' => 'This Agreement is effective from <strong>{{SERVICE_START}}</strong> to <strong>{{SERVICE_END}}</strong>, a total period of <strong>{{SERVICE_YEARS}}</strong>, and shall automatically renew annually unless either party gives written notice of non-renewal at least 30 days before the expiry date.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Fees and Payment',
                    'body' => 'The Customer agrees to pay the fees specified in the package above, divided into a <strong>Setup Fee</strong> (paid once at the start of the Agreement) and an <strong>Annual Fee</strong> (paid in advance before the start of each annual cycle). The above prices exclude Value Added Tax (VAT 7%). If payment is overdue by more than 15 days from the due date, the Provider reserves the right to suspend system access until payment is completed.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Service Level Agreement',
                    'body' => 'The Provider strives to maintain system uptime of not less than <strong>99.5% per month</strong>, excluding pre-announced Planned Maintenance (notified to the Customer at least 48 hours in advance) and Force Majeure events beyond its control.',
                ],
                [
                    'type' => 'security',
                    'title' => 'Security Standards and Data Protection',
                    'body' => 'The Provider maintains security measures in line with international standards, as follows:
                        <ul class="mb-0 mt-1">
                            <li><strong>Encryption in transit (TLS 1.2+)</strong> for every data transmission</li>
                            <li><strong>Encryption at rest</strong> for the database and attached files</li>
                            <li><strong>Role-Based Access Control (RBAC)</strong> via Spatie Permission</li>
                            <li><strong>Daily Backup</strong>, retained for at least 30 days</li>
                            <li><strong>Audit Log</strong> — every data change is recorded with a timestamp and user</li>
                            <li><strong>Password policy</strong> (bcrypt hashing, minimum 8 characters, lockout against brute-force attacks)</li>
                            <li><strong>Protection against CSRF, XSS, and SQL Injection</strong> per OWASP Top 10 guidelines</li>
                            <li>Working towards alignment with the <strong>ISO/IEC 27001</strong> standard (Information Security Management)</li>
                        </ul>',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Personal Data Protection (PDPA Compliance)',
                    'body' => 'Processing of personal data within the System complies with the Personal Data Protection Act B.E. 2562 (PDPA) and is aligned with the EU General Data Protection Regulation (GDPR). The Provider acts as the <strong>Data Processor</strong> and the Customer acts as the <strong>Data Controller</strong>. The Provider shall not use the Customer\'s data beyond the purpose of this Agreement.',
                ],
                [
                    'type' => 'exclusion',
                    'title' => 'Finance Feature Exclusion',
                    'body' => 'Both parties expressly agree that the <strong>Finance Module</strong>, including but not limited to:
                        <ul class="mb-2 mt-1">
                            <li>Quotation / Invoice / Tax Invoice / Receipt system</li>
                            <li>Value Added Tax (VAT) and Withholding Tax (WHT) calculation</li>
                            <li>Tax reports ภ.พ.30, ภ.ง.ด.3, ภ.ง.ด.53</li>
                            <li>WHT Certificate</li>
                            <li>Bank Reconciliation</li>
                            <li>The Ledger and all financial reports</li>
                            <li>The Monthly Bundle and Audit Log of the Finance feature</li>
                        </ul>
                        is <strong>not included in the scope of this Agreement</strong> and <strong>will not be enabled</strong> throughout the term of this Agreement, as this module is sensitive and must operate in conjunction with tax, accounting, and country-specific legal and tax requirements, and is therefore offered as a Separate Package. Should the Customer wish to use the Finance feature at a later date, the parties must execute an <strong>Add-on Service Agreement</strong> and separately agree on fees. The Provider reserves the right to disable access to the Finance module via the system\'s settings.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Intellectual Property',
                    'body' => 'All intellectual property rights in the System, source code, UI/UX design, logo, and features are the sole property of the Provider. Data entered into the System by the Customer is the property of the Customer, and the Provider has no right to use it beyond the provision of the Service.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Confidentiality',
                    'body' => 'Both parties agree to keep confidential the business information, systems, and technology of the other party. Confidentiality obligations shall survive termination of this Agreement for a further 3 years.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Limitation of Liability',
                    'body' => 'The Provider\'s liability to the Customer, whether arising from breach of contract, tort, or any other cause, shall in aggregate for each contract year not exceed <strong>the amount of fees actually paid by the Customer for that year</strong>, and the Provider shall not be liable for Indirect or Consequential Damages.',
                ],
                [
                    'type' => 'clause',
                    'title' => 'Termination and Governing Law',
                    'body' => 'This Agreement terminates when (a) its term expires and is not renewed; (b) either party materially breaches the Agreement and fails to remedy it within 30 days of receiving written notice; or (c) either party is adjudicated bankrupt or ceases operations. Upon termination, the Provider shall allow the Customer to export its data within 30 days before permanently deleting it from the System. This Agreement is governed by <strong>the laws of the Kingdom of Thailand</strong>. Any dispute that cannot be resolved through negotiation shall be submitted to the <strong>courts of Bangkok</strong>.',
                ],
            ],
            'closing_paragraph' => 'This Agreement is made in two counterparts of identical content. Both parties have read and fully understood the terms and have accordingly signed and affixed their seal (if any) in the presence of witnesses.',
            'signature_labels' => [
                'provider_role' => 'Provider',
                'customer_role' => 'Customer',
                'witness_1' => 'Witness 1',
                'witness_2' => 'Witness 2',
                'position_placeholder' => 'Position',
            ],
        ],

        'zh' => [
            'doc_title' => '劳务管理系统软件即服务(SaaS)订阅协议',
            'doc_id_label' => '合同编号:',
            'made_at_label' => '签订地点',
            'date_label' => '日期',
            'intro' => '本协议由 <strong>{{PROVIDER_NAME}}</strong>(以下简称"服务提供者")与 <strong>{{CUSTOMER_NAME}}</strong>(以下简称"客户")共同签订,双方同意按以下条款订阅并使用本系统',
            'provider_box_title' => '服务提供者',
            'customer_box_title' => '客户',
            'party_labels' => [
                'company_name' => '公司名称:',
                'address' => '地址:',
                'tax_id' => '纳税人识别号:',
                'phone_email' => '电话 / 邮箱:',
            ],
            'package_box_title' => '套餐详情',
            'package_labels' => [
                'tier' => '套餐:',
                'setup_fee' => '开通费(一次性):',
                'annual_fee' => '年费:',
                'start' => '合同起始日:',
                'end' => '合同终止日:',
            ],
            'currency_unit' => '泰铢',
            'year_unit' => '年',
            'clause_prefix' => '第:n条',
            'clauses' => [
                [
                    'type' => 'clause',
                    'title' => '定义',
                    'body' => '<ol>
                        <li><strong>"系统"</strong>指服务提供者开发的 Pro-Worker 外籍劳务管理系统,包括雇主管理、雇员管理、登记决议、续签决议、MOU、Workflow、PDF文件、通知系统、操作日志等全部子模块,<strong>但第8条所列模块除外</strong>。</li>
                        <li><strong>"服务"</strong>指以软件即服务(SaaS)方式授予访问及使用本系统的权利。</li>
                        <li><strong>"数据"</strong>指客户在本系统中录入或生成的全部数据。</li>
                    </ol>',
                ],
                [
                    'type' => 'clause',
                    'title' => '服务范围',
                    'body' => '服务提供者同意按上述套餐,授予客户在生产环境(Production)中访问及使用本系统的权利,并提供基础技术支持(Basic Support),支持渠道为电子邮件或服务提供者指定的其他方式。',
                ],
                [
                    'type' => 'clause',
                    'title' => '期限与续约',
                    'body' => '本协议自 <strong>{{SERVICE_START}}</strong> 起至 <strong>{{SERVICE_END}}</strong> 止生效,合计期限为 <strong>{{SERVICE_YEARS}}</strong>,除非任一方于合同期满前不少于30天以书面方式通知不予续约,否则本协议将按年自动续约。',
                ],
                [
                    'type' => 'clause',
                    'title' => '服务费用与付款',
                    'body' => '客户同意按上述套餐支付费用,分为<strong>开通费</strong>(合同开始时一次性支付)及<strong>年费</strong>(每个年度周期开始前预先支付)。上述价格未含增值税(VAT 7%)。若付款逾期超过到期日15天,服务提供者保留中止系统访问权限的权利,直至付款完成为止。',
                ],
                [
                    'type' => 'clause',
                    'title' => '服务水平协议(Service Level Agreement)',
                    'body' => '服务提供者致力于将系统正常运行时间(Uptime)维持在<strong>每月不低于99.5%</strong>,但预先安排的计划性维护(将提前至少48小时通知客户)及不可抗力(Force Majeure)事件除外。',
                ],
                [
                    'type' => 'security',
                    'title' => '安全标准与数据保护',
                    'body' => '服务提供者依照国际标准采取以下安全措施:
                        <ul class="mb-0 mt-1">
                            <li>每次数据传输均采用<strong>传输加密(TLS 1.2+)</strong></li>
                            <li>数据库及附件均采用<strong>静态数据加密(Encryption at Rest)</strong></li>
                            <li>通过 Spatie Permission 实现<strong>基于角色的访问控制(RBAC)</strong></li>
                            <li><strong>每日备份(Daily Backup)</strong>,至少保留30天</li>
                            <li><strong>操作日志(Audit Log)</strong>——所有数据变更均记录时间戳及操作用户</li>
                            <li><strong>密码策略</strong>(bcrypt 加密、最少8位字符、防暴力破解锁定机制)</li>
                            <li>依照 OWASP Top 10 指引<strong>防范 CSRF、XSS、SQL 注入</strong></li>
                            <li>致力于符合<strong>ISO/IEC 27001</strong>(信息安全管理)标准</li>
                        </ul>',
                ],
                [
                    'type' => 'clause',
                    'title' => '个人数据保护(PDPA Compliance)',
                    'body' => '本系统内个人数据的处理遵守《2019年个人数据保护法》(PDPA),并符合欧盟《通用数据保护条例》(GDPR)的相关要求。服务提供者担任<strong>数据处理者(Data Processor)</strong>,客户担任<strong>数据控制者(Data Controller)</strong>。服务提供者不得将客户数据用于本协议目的以外的用途。',
                ],
                [
                    'type' => 'exclusion',
                    'title' => '服务范围排除条款 — 财务功能(Finance Feature Exclusion)',
                    'body' => '双方明确同意,<strong>财务功能模块(Finance Module)</strong>,包括但不限于:
                        <ul class="mb-2 mt-1">
                            <li>报价单 / 发票 / 税务发票 / 收据(Quotation / Invoice / Tax Invoice / Receipt)系统</li>
                            <li>增值税(VAT)及预扣税(Withholding Tax / WHT)计算</li>
                            <li>税务报表 ภ.พ.30、ภ.ง.ด.3、ภ.ง.ด.53</li>
                            <li>预扣税凭证(WHT Certificate)</li>
                            <li>银行对账(Bank Reconciliation)</li>
                            <li>账簿(Ledger)及全部财务报表</li>
                            <li>财务功能之 Monthly Bundle 及 Audit Log</li>
                        </ul>
                        <strong>均不包含在本协议范围之内</strong>,且在本协议有效期内<strong>不予开放使用</strong>。由于该模块具有较高敏感性,须与各国税务、会计制度及法律法规配合运作,因此作为独立套餐(Separate Package)提供。若客户日后希望使用财务功能,双方须另行签订<strong>附加服务协议(Add-on Service Agreement)</strong>并另行商定费用。服务提供者保留通过系统设置关闭财务模块访问权限的权利。',
                ],
                [
                    'type' => 'clause',
                    'title' => '知识产权(Intellectual Property)',
                    'body' => '本系统的源代码、UI/UX 设计、标志及全部功能的知识产权,均归服务提供者单独所有。客户导入系统的数据归客户所有,服务提供者无权将其用于提供服务以外的用途。',
                ],
                [
                    'type' => 'clause',
                    'title' => '保密条款(Confidentiality)',
                    'body' => '双方同意对另一方的业务信息、系统及技术予以保密。即使本协议终止,保密义务仍将继续有效3年。',
                ],
                [
                    'type' => 'clause',
                    'title' => '责任限制(Limitation of Liability)',
                    'body' => '服务提供者对客户承担的责任,无论因违约、侵权或其他任何原因引起,每个合同年度累计不超过<strong>客户当年度实际支付的服务费用金额</strong>,且服务提供者不对间接损害或衍生性损害(Indirect / Consequential Damages)承担责任。',
                ],
                [
                    'type' => 'clause',
                    'title' => '协议终止与适用法律',
                    'body' => '本协议在下列情形终止:(甲)合同期满且未续约;(乙)任一方实质性违反本协议,且在收到书面通知后30天内未予纠正;(丙)任一方被裁定破产或停止营业。协议终止后,服务提供者应允许客户在数据被永久删除前的30天内导出其数据。本协议适用<strong>泰王国法律</strong>。任何无法通过协商解决的争议,应提交<strong>曼谷法院</strong>管辖。',
                ],
            ],
            'closing_paragraph' => '本协议一式两份,双方各执一份,内容完全一致。双方已阅读并充分理解本协议条款,特此在见证人见证下签字并加盖印章(如适用)确认。',
            'signature_labels' => [
                'provider_role' => '服务提供者',
                'customer_role' => '客户',
                'witness_1' => '见证人 1',
                'witness_2' => '见证人 2',
                'position_placeholder' => '职位',
            ],
        ],

        'my' => [
            'doc_title' => 'လုပ်သားစီမံခန့်ခွဲမှုစနစ် ဝန်ဆောင်မှုငှားရမ်းမှု သဘောတူညီချက်',
            'doc_id_label' => 'သဘောတူညီချက်အမှတ်:',
            'made_at_label' => 'ချုပ်ဆိုသည့်နေရာ',
            'date_label' => 'ရက်စွဲ',
            'intro' => 'ဤသဘောတူညီချက်ကို <strong>{{PROVIDER_NAME}}</strong> (ယခုမှစ၍ <em>"ဝန်ဆောင်မှုပေးသူ"</em> ဟု ခေါ်ဆိုမည်) တစ်ဖက်နှင့် <strong>{{CUSTOMER_NAME}}</strong> (ယခုမှစ၍ <em>"ဝန်ဆောင်မှုလက်ခံသူ"</em> ဟု ခေါ်ဆိုမည်) တစ်ဖက်တို့အကြား ချုပ်ဆိုပြီး၊ နှစ်ဖက်စလုံးသည် အောက်ပါစည်းကမ်းချက်များနှင့်အညီ စနစ်ကို ငှားရမ်းအသုံးပြုရန် သဘောတူညီကြသည်',
            'provider_box_title' => 'ဝန်ဆောင်မှုပေးသူ',
            'customer_box_title' => 'ဝန်ဆောင်မှုလက်ခံသူ',
            'party_labels' => [
                'company_name' => 'ကုမ္ပဏီအမည်:',
                'address' => 'လိပ်စာ:',
                'tax_id' => 'အခွန်ထမ်းအမှတ်:',
                'phone_email' => 'ဖုန်း / အီးမေးလ်:',
            ],
            'package_box_title' => 'အစီအစဉ် အသေးစိတ်',
            'package_labels' => [
                'tier' => 'အစီအစဉ်:',
                'setup_fee' => 'စတင်ဝန်ဆောင်ခ (တစ်ကြိမ်တည်း):',
                'annual_fee' => 'နှစ်စဉ်ဝန်ဆောင်ခ:',
                'start' => 'သဘောတူညီချက် စတင်ရက်:',
                'end' => 'သဘောတူညီချက် ကုန်ဆုံးရက်:',
            ],
            'currency_unit' => 'ဘတ်',
            'year_unit' => 'နှစ်',
            'clause_prefix' => 'အပိုဒ် :n။',
            'clauses' => [
                [
                    'type' => 'clause',
                    'title' => 'အဓိပ္ပါယ်ဖွင့်ဆိုချက်များ',
                    'body' => '<ol>
                        <li><strong>"စနစ်"</strong> ဆိုသည်မှာ ဝန်ဆောင်မှုပေးသူ တီထွင်ထားသော Pro-Worker နိုင်ငံခြားသား လုပ်သားစီမံခန့်ခွဲမှုစနစ် ဖြစ်ပြီး အလုပ်ရှင်စီမံခန့်ခွဲမှု၊ ဝန်ထမ်းစီမံခန့်ခွဲမှု၊ မှတ်ပုံတင် ဆုံးဖြတ်ချက်၊ သက်တမ်းတိုး ဆုံးဖြတ်ချက်၊ MOU၊ Workflow၊ PDF စာရွက်စာတမ်းများ၊ အသိပေးစနစ်၊ Activity Log အစရှိသည့် sub-module အားလုံး ပါဝင်သည်၊ <strong>အပိုဒ် ၈ တွင် ဖော်ပြထားသော module မှလွဲ၍</strong> ဖြစ်သည်</li>
                        <li><strong>"ဝန်ဆောင်မှု"</strong> ဆိုသည်မှာ Software-as-a-Service (SaaS) ပုံစံဖြင့် စနစ်ကို ဝင်ရောက်အသုံးပြုခွင့် ပေးအပ်ခြင်းကို ဆိုလိုသည်</li>
                        <li><strong>"ဒေတာ"</strong> ဆိုသည်မှာ ဝန်ဆောင်မှုလက်ခံသူ စနစ်တွင် ထည့်သွင်း သို့မဟုတ် ဖန်တီးထားသော ဒေတာအားလုံးကို ဆိုလိုသည်</li>
                    </ol>',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ဝန်ဆောင်မှု နယ်ပယ်',
                    'body' => 'ဝန်ဆောင်မှုပေးသူသည် အထက်ဖော်ပြပါ အစီအစဉ်အတိုင်း Production ပတ်ဝန်းကျင်ပေါ်တွင် စနစ်ကို ဝင်ရောက်အသုံးပြုခွင့်ကို ဝန်ဆောင်မှုလက်ခံသူအား ပေးအပ်ရန် သဘောတူသည်၊ အီးမေးလ် သို့မဟုတ် ဝန်ဆောင်မှုပေးသူ သတ်မှတ်ထားသော လမ်းကြောင်းမှတစ်ဆင့် အခြေခံ ပံ့ပိုးမှု (Basic Support) အပါအဝင် ဖြစ်သည်',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ကာလနှင့် သက်တမ်းတိုးခြင်း',
                    'body' => 'ဤသဘောတူညီချက်သည် <strong>{{SERVICE_START}}</strong> မှ <strong>{{SERVICE_END}}</strong> အထိ သက်ရောက်မှုရှိပြီး၊ စုစုပေါင်းကာလမှာ <strong>{{SERVICE_YEARS}}</strong> ဖြစ်သည်၊ နှစ်ဖက်အနက် တစ်ဖက်ဖက်က သဘောတူညီချက် ကုန်ဆုံးရက်မတိုင်မီ ရက် ၃၀ ကြိုတင်၍ သက်တမ်းမတိုးလိုကြောင်း စာဖြင့် အသိမပေးလျှင် နှစ်စဉ် အလိုအလျောက် သက်တမ်းတိုးမည်',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ဝန်ဆောင်ခနှင့် ငွေပေးချေမှု',
                    'body' => 'ဝန်ဆောင်မှုလက်ခံသူသည် အထက်ဖော်ပြပါ အစီအစဉ်အတိုင်း ဝန်ဆောင်ခကို ပေးချေရန် သဘောတူပြီး <strong>စတင်ဝန်ဆောင်ခ</strong> (သဘောတူညီချက်စတင်ချိန်တွင် တစ်ကြိမ်တည်း ပေးချေမည်) နှင့် <strong>နှစ်စဉ်ဝန်ဆောင်ခ</strong> (နှစ်စဉ်စက်ဝန်း တစ်ခုစီ မစတင်မီ ကြိုတင်ပေးချေမည်) ဟူ၍ ခွဲထားသည်၊ အထက်ပါ စျေးနှုန်းများသည် ဗီအေတီ (VAT 7%) မပါဝင်သေးပါ၊ ငွေပေးချေမှု သတ်မှတ်ရက်မှ ၁၅ ရက်ကျော် နောက်ကျပါက ဝန်ဆောင်မှုပေးသူသည် ငွေပေးချေမှု အပြီးသတ်သည်အထိ စနစ်ဝင်ရောက်ခွင့်ကို ရပ်ဆိုင်းပိုင်ခွင့် ရှိသည်',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ဝန်ဆောင်မှုအဆင့် သဘောတူညီချက် (Service Level Agreement)',
                    'body' => 'ဝန်ဆောင်မှုပေးသူသည် စနစ်၏ အသင့်ရှိမှု (Uptime) ကို <strong>လစဉ် ၉၉.၅% အနည်းဆုံး</strong> ဖြစ်စေရန် ကြိုးပမ်းသည်၊ ကြိုတင်စီစဉ်ထားသော ပြုပြင်ထိန်းသိမ်းမှု (Planned Maintenance — ဝန်ဆောင်မှုလက်ခံသူအား အနည်းဆုံး ၄၈ နာရီ ကြိုတင်အသိပေးမည်) နှင့် ထိန်းချုပ်၍မရသော မလွှဲမရှောင်သာသည့် ကိစ္စရပ်များ (Force Majeure) မှလွဲ၍ ဖြစ်သည်',
                ],
                [
                    'type' => 'security',
                    'title' => 'လုံခြုံရေးစံနှုန်းနှင့် ဒေတာကာကွယ်ရေး',
                    'body' => 'ဝန်ဆောင်မှုပေးသူသည် နိုင်ငံတကာစံနှုန်းများနှင့်အညီ အောက်ပါ လုံခြုံရေးအစီအမံများကို စီစဉ်ထားသည်:
                        <ul class="mb-0 mt-1">
                            <li>ဒေတာပေးပို့မှုတိုင်းအတွက် <strong>ဆက်သွယ်ရေး ကုဒ်ဝှက်ခြင်း (TLS 1.2+)</strong></li>
                            <li>ဒေတာဘေ့စ်နှင့် ပူးတွဲဖိုင်များအတွက် <strong>သိမ်းဆည်းထားသော ဒေတာ ကုဒ်ဝှက်ခြင်း (Encryption at Rest)</strong></li>
                            <li>Spatie Permission မှတစ်ဆင့် <strong>အခန်းကဏ္ဍအလိုက် ဝင်ရောက်ခွင့် ထိန်းချုပ်မှု (RBAC)</strong></li>
                            <li>အနည်းဆုံး ရက် ၃၀ သိမ်းဆည်းထားသော <strong>နေ့စဉ် ဘက်ကပ် (Daily Backup)</strong></li>
                            <li>ဒေတာပြောင်းလဲမှုတိုင်းကို timestamp + user နှင့်တကွ မှတ်တမ်းတင်သော <strong>Audit Log</strong></li>
                            <li><strong>စကားဝှက်မူဝါဒ</strong> (bcrypt hashing, အနည်းဆုံး စာလုံး ၈ လုံး, brute-force ကာကွယ်ရန် lockout)</li>
                            <li>OWASP Top 10 လမ်းညွှန်ချက်အတိုင်း <strong>CSRF, XSS, SQL Injection ကာကွယ်ခြင်း</strong></li>
                            <li><strong>ISO/IEC 27001</strong> (Information Security Management) စံနှုန်းနှင့် ကိုက်ညီရန် ရည်မှန်းသည်</li>
                        </ul>',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ကိုယ်ရေးကိုယ်တာ ဒေတာကာကွယ်ရေး (PDPA Compliance)',
                    'body' => 'စနစ်အတွင်းရှိ ကိုယ်ရေးကိုယ်တာ ဒေတာ လုပ်ဆောင်မှုသည် ကိုယ်ရေးကိုယ်တာ ဒေတာကာကွယ်ရေးဥပဒေ ခရစ်နှစ် ၂၀၁၉ (PDPA) နှင့်အညီ ဖြစ်ပြီး ဥရောပသမဂ္ဂ၏ General Data Protection Regulation (GDPR) နှင့်လည်း ကိုက်ညီသည်၊ ဝန်ဆောင်မှုပေးသူသည် <strong>ဒေတာလုပ်ဆောင်သူ (Data Processor)</strong> အဖြစ်၊ ဝန်ဆောင်မှုလက်ခံသူသည် <strong>ဒေတာထိန်းချုပ်သူ (Data Controller)</strong> အဖြစ် လုပ်ဆောင်သည်၊ ဝန်ဆောင်မှုပေးသူသည် ဝန်ဆောင်မှုလက်ခံသူ၏ ဒေတာကို သဘောတူညီချက်၏ ရည်ရွယ်ချက်မှလွဲ၍ အသုံးပြုမည် မဟုတ်ပါ',
                ],
                [
                    'type' => 'exclusion',
                    'title' => 'ဝန်ဆောင်မှုတွင် မပါဝင်သော နယ်ပယ် — ငွေကြေး Feature (Finance Feature Exclusion)',
                    'body' => 'သဘောတူညီချက်ချုပ်ဆိုသူ နှစ်ဖက်စလုံးသည် <strong>ငွေကြေး Module (Finance Module)</strong> — အောက်ပါအရာများ အပါအဝင်:
                        <ul class="mb-2 mt-1">
                            <li>ဈေးနှုန်းပြသလွှာ / ငွေတောင်းခံလွှာ / အခွန်ဘောင်ချာ / ငွေလက်ခံဖြေ (Quotation / Invoice / Tax Invoice / Receipt) စနစ်</li>
                            <li>ဗီအေတီ (VAT) နှင့် ရင်းမြစ်မှ နုတ်ယူသောအခွန် (Withholding Tax / WHT) တွက်ချက်မှု</li>
                            <li>အခွန်အစီရင်ခံစာများ ภ.พ.30၊ ภ.ง.ด.3၊ ภ.ง.ด.53</li>
                            <li>ရင်းမြစ်မှ နုတ်ယူကြောင်း အထောက်အထား (WHT Certificate)</li>
                            <li>ဘဏ်ချိန်ညှိခြင်း (Bank Reconciliation)</li>
                            <li>Ledger နှင့် ငွေကြေးအစီရင်ခံစာများ အားလုံး</li>
                            <li>ငွေကြေး Feature ၏ Monthly Bundle နှင့် Audit Log</li>
                        </ul>
                        <strong>ဤသဘောတူညီချက်၏ နယ်ပယ်တွင် မပါဝင်ကြောင်း</strong> နှင့် သဘောတူညီချက် သက်တမ်းတစ်လျှောက်လုံး <strong>ဖွင့်ပေးမည် မဟုတ်ကြောင်း</strong> ရှင်းလင်းစွာ သဘောတူပါသည်၊ အကြောင်းမှာ ယင်း Module သည် အလွန်သတိထားရသော Module ဖြစ်ပြီး နိုင်ငံအလိုက် အခွန်၊ စာရင်းကိုင်စနစ်၊ ဥပဒေစည်းမျဉ်းနှင့် အခွန်စည်းမျဉ်းများနှင့် အတူတကွ လုပ်ဆောင်ရသောကြောင့် သီးခြားအစီအစဉ် (Separate Package) အဖြစ် စီစဉ်ထားပါသည်၊ ဝန်ဆောင်မှုလက်ခံသူသည် နောင်တွင် ငွေကြေး Feature ကို အသုံးပြုလိုပါက နှစ်ဖက်စလုံးသည် <strong>ထပ်လောင်းဝန်ဆောင်မှု သဘောတူညီချက် (Add-on Service Agreement)</strong> ကို ပြုလုပ်ပြီး ဝန်ဆောင်ခကို သီးခြားသဘောတူညီရမည်၊ ဝန်ဆောင်မှုပေးသူသည် စနစ်၏ ဆက်တင်များမှတစ်ဆင့် ငွေကြေး module ဝင်ရောက်ခွင့်ကို ပိတ်ပိုင်ခွင့် ရှိသည်',
                ],
                [
                    'type' => 'clause',
                    'title' => 'ဉာဏပစ္စည်း မူပိုင်ခွင့် (Intellectual Property)',
                    'body' => 'စနစ်၏ source code၊ UI/UX ဒီဇိုင်း၊ လိုဂိုနှင့် Feature အားလုံး၏ ဉာဏပစ္စည်း မူပိုင်ခွင့်အားလုံးသည် ဝန်ဆောင်မှုပေးသူ တစ်ဦးတည်း၏ ပိုင်ဆိုင်မှု ဖြစ်သည်၊ ဝန်ဆောင်မှုလက်ခံသူ စနစ်ထဲသို့ ထည့်သွင်းသော ဒေတာသည် ဝန်ဆောင်မှုလက်ခံသူ၏ ပိုင်ဆိုင်မှု ဖြစ်ပြီး ဝန်ဆောင်မှုပေးသူသည် ဝန်ဆောင်မှုပေးခြင်းမှလွဲ၍ အသုံးပြုပိုင်ခွင့် မရှိပါ',
                ],
                [
                    'type' => 'clause',
                    'title' => 'လျှို့ဝှက်ချက် ထိန်းသိမ်းခြင်း (Confidentiality)',
                    'body' => 'နှစ်ဖက်စလုံးသည် အခြားတစ်ဖက်၏ လုပ်ငန်းအချက်အလက်၊ စနစ်နှင့် နည်းပညာများကို လျှို့ဝှက်ထိန်းသိမ်းရန် သဘောတူသည်၊ သဘောတူညီချက် ကုန်ဆုံးသွားသော်လည်း လျှို့ဝှက်ထိန်းသိမ်းရန် တာဝန်ဝတ္တရားများသည် နောက်ထပ် ၃ နှစ် ဆက်လက် သက်ရောက်မည်',
                ],
                [
                    'type' => 'clause',
                    'title' => 'တာဝန် ကန့်သတ်ချက် (Limitation of Liability)',
                    'body' => 'ဝန်ဆောင်မှုပေးသူ၏ ဝန်ဆောင်မှုလက်ခံသူအပေါ် တာဝန်သည် သဘောတူညီချက် ချိုးဖောက်မှု၊ ချိုးဖောက်မှုဆိုင်ရာ လုပ်ဆောင်ချက် သို့မဟုတ် အခြားအကြောင်းရင်းတစ်စုံတစ်ရာမှ ဖြစ်ပေါ်စေကာမူ၊ သဘောတူညီချက် နှစ်စဉ်စက်ဝန်းတစ်ခုစီအတွက် <strong>ထိုနှစ်စဉ်စက်ဝန်းအတွင်း ဝန်ဆောင်မှုလက်ခံသူ အမှန်တကယ် ပေးချေခဲ့သော ဝန်ဆောင်ခ တန်ဖိုး</strong>ထက် မကျော်လွန်ရ၊ ဝန်ဆောင်မှုပေးသူသည် သွယ်ဝိုက်သော ပျက်စီးမှု သို့မဟုတ် ဆက်စပ်ပျက်စီးမှု (Indirect / Consequential Damages) များအတွက် တာဝန်မယူပါ',
                ],
                [
                    'type' => 'clause',
                    'title' => 'သဘောတူညီချက် ကုန်ဆုံးခြင်းနှင့် သက်ဆိုင်သော ဥပဒေ',
                    'body' => 'ဤသဘောတူညီချက်သည် (က) ကာလကုန်ဆုံးပြီး သက်တမ်းမတိုးသောအခါ၊ (ခ) နှစ်ဖက်အနက် တစ်ဖက်ဖက်က သိသာထင်ရှားစွာ ချိုးဖောက်ပြီး စာဖြင့်အသိပေးချက် ရရှိပြီးနောက် ရက် ၃၀ အတွင်း မပြင်ဆင်သောအခါ၊ (ဂ) နှစ်ဖက်အနက် တစ်ဖက်ဖက်က ဒေဝါလီခံရ သို့မဟုတ် လုပ်ငန်းရပ်ဆိုင်းသောအခါတို့တွင် ကုန်ဆုံးမည်၊ သဘောတူညီချက် ကုန်ဆုံးသောအခါ ဝန်ဆောင်မှုပေးသူသည် ဒေတာများကို စနစ်မှ အပြီးတိုင် မဖျက်မီ ရက် ၃၀ အတွင်း ဝန်ဆောင်မှုလက်ခံသူအား Export လုပ်ခွင့် ပေးမည်၊ ဤသဘောတူညီချက်သည် <strong>ထိုင်းနိုင်ငံတော်၏ ဥပဒေများ</strong>အောက်တွင် ရှိသည်၊ ညှိနှိုင်းမှုဖြင့် မဖြေရှင်းနိုင်သော အငြင်းပွားမှုများကို <strong>ဘန်ကောက် တရားရုံး</strong>သို့ တင်ပြရမည်',
                ],
            ],
            'closing_paragraph' => 'ဤသဘောတူညီချက်ကို မိတ္တူနှစ်စောင် ပြုလုပ်ထားပြီး အကြောင်းအရာ တူညီပါသည်၊ နှစ်ဖက်စလုံးသည် စည်းကမ်းချက်များကို ဖတ်ရှုပြီး အပြည့်အဝ နားလည်ကြသဖြင့် သက်သေများရှေ့တွင် တံဆိပ် (ရှိပါက) နှင့်တကွ လက်မှတ်ရေးထိုးကြပါသည်',
            'signature_labels' => [
                'provider_role' => 'ဝန်ဆောင်မှုပေးသူ',
                'customer_role' => 'ဝန်ဆောင်မှုလက်ခံသူ',
                'witness_1' => 'သက်သေ ၁',
                'witness_2' => 'သက်သေ ၂',
                'position_placeholder' => 'ရာထူး',
            ],
        ],

    ],

];
