# รายงานตรวจสอบป๊อปอัพและหน้าต่าง UI (Popup / Modal Audit)

สำรวจจากโค้ดใน `resources/views` และ `public/js` เมื่อ 26/09/2026 — รายการสร้างจากโค้ดจริงพร้อมเลขบรรทัด

## สรุป

| ประเภท | จำนวน | สถานะการออกแบบ |
|---|---|---|
| ป๊อปอัพของเบราว์เซอร์ (`alert` / `confirm` / `prompt`) | 118 จุด ใน 58 ไฟล์ | **เสร็จแล้ว** — `confirm` 47 จุดแปลงเป็น `data-confirm` / `appConfirm()` · `alert` ทั้งหมดแสดงเป็นป๊อปอัพธีมแบรนด์ผ่านตัวครอบกลาง (เหลือ `confirm` 1 จุดใน `financial/tabs/overview.blade.php` ที่เป็นทางสำรองเมื่อ SweetAlert ไม่โหลด) |
| ป๊อปอัพ SweetAlert (`Swal.fire`) | 399 จุด ใน 51 ไฟล์ | **เสร็จแล้ว** — ธีมกลางใน `partials/_ui_theme_styles.blade.php` (สีแบรนด์ ฟอนต์ระบบ มุมโค้ง) จุดที่กำหนดสีปุ่มเองยังคงสีเดิม |
| หน้าต่าง Bootstrap Modal หัวเรียบ (ไม่มีการตกแต่ง) | 110 หน้าต่าง | **เสร็จแล้ว (ระดับธีม)** — ได้แถบสีแบรนด์ มุมโค้ง เงา ระยะห่างมาตรฐาน · ยังไม่ได้จัดฟอร์มภายในใหม่ (ดูข้อ 4) |
| หน้าต่าง Bootstrap Modal ที่ตกแต่งหัวแล้ว | 53 หน้าต่าง | ได้มุมโค้งและเงาใหม่ · หัวที่ตกแต่งไว้แล้วคงไว้ตามเดิม (ยังใช้สีตายตัว) |
| หน้า Error ของ Laravel (401, 403, 404, 419, 429, 500, 503) | 7 หน้า | **เสร็จแล้ว** — `resources/views/errors/minimal.blade.php` แทน layout ดั้งเดิมของ Laravel ตามสีและโลโก้แบรนด์ แสดงข้อความจาก `abort()` เหมือนเดิม และมีเวลาที่เกิดปัญหาไว้แจ้งผู้ดูแล |
| หน้ากรอกรหัสผ่าน (Login, ปลดล็อกเมนู, ลืม/ตั้งรหัสผ่าน, ยืนยันรหัส, ยืนยันอีเมล, ลงทะเบียน) | 7 หน้า | **ออกแบบใหม่แล้ว** ในรอบนี้ — ตามสีและโลโก้จาก Super Admin → Branding |

## แนวทางที่แนะนำ (เรียงตามความคุ้มค่า)

1. ✅ **ธีมกลางของ SweetAlert** — แก้ที่เดียวในไฟล์ layout หลัก ป๊อปอัพ Swal ทั้งหมด 399 จุดจะเปลี่ยนเป็นสีแบรนด์ ฟอนต์เดียวกับระบบ มุมโค้ง และปุ่มยืนยันลบเป็นสีแดง โดยไม่ต้องแก้ทีละจุด รวมถึงป๊อปอัพปลดล็อกเมนูการเงิน (`public/js/financial-security.js`) และป๊อปอัพ "No bank account selected"
2. ✅ **ธีมกลางของ Bootstrap Modal** — ใส่ CSS กลางให้ `.modal-header` ที่ยังเรียบอยู่ (110 หน้าต่าง) มีแถบสีแบรนด์ มุมโค้ง และระยะห่างมาตรฐาน โดยไม่แตะหน้าต่างที่ตกแต่งไว้แล้ว
3. ✅ **เปลี่ยนป๊อปอัพของเบราว์เซอร์ทั้ง 118 จุด** ให้ใช้ฟังก์ชันกลาง เช่น `appConfirm()` / `appAlert()` / `appToast()` ที่ใช้ธีมข้อ 1 — ต้องแก้ทีละไฟล์ ใช้เวลามากที่สุด ควรทำตามลำดับความสำคัญในหัวข้อถัดไป
4. ⏳ **ออกแบบหน้าต่างสำคัญทีละหน้า** (ยังไม่ได้ทำ) — หน้าต่างที่ใช้บ่อยและมีผลต่อข้อมูล เช่น แจ้งออกลูกจ้าง, ย้ายนายจ้าง, ยืนยันการทำรายการ, การเงิน/รับชำระ ควรจัดวางฟอร์มใหม่ให้อ่านง่าย (ไม่ใช่แค่เปลี่ยนสี)

## หน้าต่างสำคัญที่ควรออกแบบใหม่ก่อน

| หน้าต่าง | ไฟล์ | หมายเหตุ |
|---|---|---|
| แจ้งออกลูกจ้าง (Terminate Employee) | `partials/_employee_action_modals.blade.php` | มีผลกับสถานะลูกจ้าง ควรมีสรุปผลกระทบและปุ่มสีแดง |
| ย้ายนายจ้างสำหรับลูกจ้าง | `partials/_employee_action_modals.blade.php` |  |
| ยืนยันการทำรายการ (Confirm Action) | `partials/_employee_action_modals.blade.php` | ใช้ร่วมหลายปุ่มในหน้าลูกจ้าง |
| ประวัติการจ้างงาน (Employment History) | `partials/_employee_action_modals.blade.php` |  |
| Payment History & Details / Update Payment | `production/partials/financial-tab.blade.php` | หัวเรียบ 7 หน้าต่างในไฟล์เดียว ใช้ทุกเมนูที่มีการเงิน |
| Finance: (ชื่อนายจ้าง) | `production/registration, production/renewal, workflow/index, production/index` | หน้าต่างการเงินของแต่ละเมนู |
| ยืนยันการยกเลิก / ยืนยันการลบ / ต่ออายุการแจ้งเตือน | `layouts/app.blade.php, notifications/_renew_modal.blade.php` | ใช้ทั้งระบบ |
| บันทึกรายรับ-รายจ่าย / แก้ไขรายการวันที่ปิดบัญชีแล้ว | `financial/books/_quick_entry.blade.php, financial/books/show.blade.php` |  |
| เพิ่ม/แก้ไขที่อยู่ | `partials/_address_management.blade.php` | ใช้ในนายจ้าง ผู้นำเข้า ผู้รับมอบอำนาจ |

## 1) ป๊อปอัพของเบราว์เซอร์ — รายการเดิมก่อนแปลง (บันทึกไว้อ้างอิง) (118 จุด)

### ยืนยันการลบ / ยกเลิก (ควรเป็นป๊อปอัพสีแดง) — 37 จุด

| เมนู | ไฟล์:บรรทัด | ข้อความ |
|---|---|---|
| PDF Templates | `pdf_templates/index.blade.php:852` | Are you sure you want to delete this witness? |
| Pro Walker Labor | `labor/books/show.blade.php:211` | Delete this transaction? |
| Pro Walker Labor | `labor/charges/index.blade.php:246` | Remove this charge? |
| Pro Walker Labor | `labor/company_documents/index.blade.php:83` | Remove this document? |
| Pro Walker Labor | `labor/contract_templates/index.blade.php:77` | Delete this template? |
| Pro Walker Labor | `labor/customers/index.blade.php:69` | Remove this customer? Existing invoices keep their record. |
| Pro Walker Labor | `labor/tax-invoices/show.blade.php:88` | Delete this draft? |
| Pro Walker Labor | `labor/team-members/index.blade.php:55` | Remove this member? Their recorded entries stay, just unlinked from this name. |
| Pro Walker Labor | `labor/teams/show.blade.php:261` | Remove this entry? |
| Pro Walker Labor | `labor/wht-certificates/show.blade.php:80` | Delete this draft? |
| Super Admin | `super-admin/partials/_contract_status.blade.php:250` | ลบไฟล์แนบช่องที่ {{ $slot }}? |
| Super Admin | `super-admin/settings.blade.php:571` | Delete this logo? |
| Super Admin | `super-admin/settings.blade.php:671` | Reset all theme colors to factory defaults? |
| การขาย / ใบเสนอราคา | `sales/partials/_manage_employees_modal.blade.php:61` | ยืนยันการลบลูกจ้าง? |
| การเงิน | `financial/books/show.blade.php:211` | Delete this transaction? |
| การเงิน | `financial/credit_notes/show.blade.php:35` | Delete this draft credit note? |
| การเงิน | `financial/expenses/index.blade.php:85` | Are you sure you want to delete this expense? The bank balance will be restored. |
| การเงิน | `financial/income_categories/index.blade.php:71` | Delete this category? |
| การเงิน | `financial/ledger/show.blade.php:17` | Delete this entry? Balance will be restored. |
| การเงิน | `financial/reconciliation/account.blade.php:18` | {{ __('Reset current_balance to the expected value re-computed from the ledger? This  |
| การเงิน | `financial/tax_invoices/show.blade.php:36` | Delete this draft invoice? |
| การเงิน | `financial/wht_certificates/show.blade.php:36` | Delete? |
| การแจ้งเตือน | `notifications/_notification_item.blade.php:204` | คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร? |
| การแจ้งเตือน | `notifications/_notification_table_row.blade.php:144` | คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร? |
| นายจ้าง | `employers/create.blade.php:530` | Delete this type? |
| นายจ้าง | `employers/create.blade.php:708` | Are you sure you want to delete this address? |
| นายจ้าง | `employers/edit.blade.php:1320` | Delete this type? |
| ผู้ดูแลระบบ | `admin/tickets/index.blade.php:108` | {{ __('Are you sure you want to delete this job ticket from the view? It will reappea |
| ผู้นำเข้า | `importers/create.blade.php:346` | Are you sure you want to delete this address? |
| ผู้นำเข้า | `importers/edit.blade.php:285` | Are you sure you want to delete this address? |
| ผู้รับมอบอำนาจ | `delegates/create.blade.php:342` | Are you sure you want to delete this address? |
| ผู้รับมอบอำนาจ | `delegates/edit.blade.php:274` | Are you sure you want to delete this address? |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/partials/order_fields_drawer.blade.php:73` | Delete this field? |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/registration/partials/offcanvas_drawer.blade.php:115` | Delete this field? |
| ส่วนกลาง (components) | `components/job-check-widget.blade.php:89` | Cancel Job Check Mode without exporting a report? |
| ส่วนกลาง (layout) | `layouts/app.blade.php:1733` | Are you sure you want to delete this job owner? |
| ส่วนกลาง (layout) | `layouts/app.blade.php:1744` | Are you sure you want to delete this job owner? |

### ยืนยันการทำรายการ — 10 จุด

| เมนู | ไฟล์:บรรทัด | ข้อความ |
|---|---|---|
| Pro Walker Labor | `labor/tax-invoices/show.blade.php:84` | Issue this invoice? The number will be locked. |
| Pro Walker Labor | `labor/users/index.blade.php:73` | Change this account\'s status? |
| Super Admin | `super-admin/partials/_contract_status.blade.php:154` | ปิดโหมดชั่วคราวและกลับไปยึดตามวันสิ้นสุดสัญญาจริงหรือไม่? |
| การขาย / ใบเสนอราคา | `sales/partials/_history_modal.blade.php:49` | ยืนยันการกู้คืนรายการนี้ เพื่อกลับไปหน้า เสนอราคา หรือไม่? |
| การขาย / ใบเสนอราคา | `sales/partials/_lead_card.blade.php:30` | ย้ายรายการนี้ลงประวัติ/ถังขยะ ใช่หรือไม่? |
| การเงิน | `financial/credit_notes/show.blade.php:31` | Issue this credit note? The number will be locked and the bill\'s balance will be red |
| การเงิน | `financial/tabs/overview.blade.php:616` | {{ __('You have not selected a bank account. This payment will not be posted to any b |
| การเงิน | `financial/tax_invoices/show.blade.php:32` | Issue this invoice? The number will be locked. |
| การเงิน | `financial/wht_certificates/show.blade.php:42` | Mark as submitted to กรมสรรพากร? |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/prepare.blade.php:20` | Confirm sending this project to Workflow? This will activate tracking and confirm pen |

### แจ้งข้อผิดพลาด — 47 จุด

| เมนู | ไฟล์:บรรทัด | ข้อความ |
|---|---|---|
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:194` | Cannot load Image Processing Engine (OpenCV). Basic features only. |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:224` | Cannot load file for editing: " + e.message); |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:299` | เกิดข้อผิดพลาดในการนำเข้าไฟล์: " + err.message); |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:408` | Cannot access camera: " + err.message); |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:1070` | Error generating layout: " + e.message); |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:1480` | Image Processing Engine (OpenCV) is not loaded. Cannot save edits. |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:1540` | Failed to process image rotation: " + innerError.message); |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:1547` | Failed to load source image for processing. |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:1553` | Failed to start save process. |
| JavaScript ส่วนกลาง (public/js) | `public/js/document-scanner.js:2059` | Failed to save documents: " + e.message); |
| Pro Walker Labor | `labor/contract_templates/builder.blade.php:829` | Error saving template: ' + error.message); |
| Pro Walker Labor | `labor/contract_templates/form_order.blade.php:150` | Error saving order: ' + error.message); |
| Workflow | `workflow/board.blade.php:211` | Failed to create fields |
| Workflow | `workflow/index.blade.php:1027` | Save failed: ' + e.message); |
| การขาย / ใบเสนอราคา | `sales/index.blade.php:212` | Error updating status |
| การขาย / ใบเสนอราคา | `sales/partials/_manage_employees_modal.blade.php:310` | เกิดข้อผิดพลาด: ' + err.message); |
| การขาย / ใบเสนอราคา | `sales/partials/_manage_employees_modal.blade.php:485` | เกิดข้อผิดพลาดในการนำเข้า |
| การเงิน | `financial/tabs/overview.blade.php:556` | Bootstrap not loaded yet — please try again in a moment. |
| การเงิน | `financial/tabs/overview.blade.php:670` | Error saving payment: ' + err.message); |
| ผู้นำเข้า | `importers/edit.blade.php:322` | Error saving address |
| ผู้นำเข้า | `importers/edit.blade.php:327` | An error occurred |
| ผู้รับมอบอำนาจ | `delegates/edit.blade.php:311` | Error saving address |
| ผู้รับมอบอำนาจ | `delegates/edit.blade.php:316` | An error occurred |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/prepare.blade.php:394` | Security Error: CSRF Token missing. Please reload the page. |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/prepare.blade.php:431` | Error: ' + (data.message // 'Update failed |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/prepare.blade.php:437` | Network error: ' + error.message); |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/registration/index.blade.php:2510` | Failed: ' + error.message); |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/registration/partials/modals/add_custom_field.blade.php:204` | error.message); |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/renewal/index.blade.php:2452` | Failed: ' + error.message); |
| ลูกจ้าง | `employees/bulk_edit_form.blade.php:501` | Failed to process image: ' + err.message); |
| ลูกจ้าง | `employees/bulk_edit_form.blade.php:524` | Cropper.js not loaded. |
| ลูกจ้าง | `employees/create.blade.php:298` | Failed to process image: ' + err.message); |
| ลูกจ้าง | `employees/import.blade.php:685` | Update failed |
| ลูกจ้าง | `employees/import.blade.php:693` | An error occurred |
| ลูกจ้าง | `employees/import.blade.php:739` | Error saving data. Please check required fields. |
| ลูกจ้าง | `employees/modals/select_target_employer_modal.blade.php:103` | Missing data |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:57` | ไม่สามารถโหลดรูปภาพได้: ' + error.message); |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:97` | ไม่สามารถโหลดเครื่องมือตัดภาพได้ (Cropper.js) กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:123` | เกิดข้อผิดพลาดในการเริ่มทำงาน Cropper: ' + err.message); |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:204` | Failed to process image: ' + err.message); |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:378` | ไม่สามารถอ่านรูปภาพปัจจุบันได้ |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:420` | เกิดข้อผิดพลาด: ' + err.message); |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:509` | เบราว์เซอร์นี้ไม่รองรับ FaceDetector — โปรดใช้ Chrome/Edge เวอร์ชั่นล่าสุด |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:610` | เกิดข้อผิดพลาดในการตัดภาพ (Canvas creation failed). กรุณาลองใหม่อีกครั้ง |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:965` | Failed to start refine mode: ' + e.message); |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:1498` | Magic Wand failed: " + err.message + "\nCheck console for details. |
| ส่วนกลาง (layout) | `layouts/app.blade.php:1809` | data.message // 'Cannot delete |

### แจ้งเตือน / ตรวจสอบข้อมูล — 20 จุด

| เมนู | ไฟล์:บรรทัด | ข้อความ |
|---|---|---|
| PDF Templates | `pdf_templates/generate_modal.blade.php:582` | No employees selected. |
| การขาย / ใบเสนอราคา | `sales/partials/_create_modal.blade.php:218` | กรุณากรอกชื่อลูกค้า (ไทย) |
| การขาย / ใบเสนอราคา | `sales/partials/_create_modal.blade.php:225` | กรุณาเลือกลูกค้าเดิม |
| การขาย / ใบเสนอราคา | `sales/partials/_manage_employees_modal.blade.php:229` | กรุณาเลือกลูกจ้างเดิม |
| การขาย / ใบเสนอราคา | `sales/partials/_manage_employees_modal.blade.php:274` | กรุณากรอกชื่อ-นามสกุล (ภาษาอังกฤษ) |
| การแจ้งเตือน | `notifications/index.blade.php:719` | Download function not ready. |
| นายจ้าง | `employers/edit.blade.php:1122` | Please select a file to upload. |
| ผู้นำเข้า | `importers/create.blade.php:304` | Please fill in complete address information |
| ผู้รับมอบอำนาจ | `delegates/create.blade.php:300` | Please fill in complete address information |
| ลูกจ้าง | `employees/bulk_edit_form.blade.php:474` | No image selected |
| ลูกจ้าง | `employees/bulk_edit_form.blade.php:634` | No employees selected. |
| ลูกจ้าง | `employees/create.blade.php:267` | No image selected |
| ลูกจ้าง | `employees/index.blade.php:318` | @json($qe['message'])); |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:156` | No image selected |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:597` | กรุณารอให้เครื่องมือตัดภาพทำงาน หรือลองเลือกไฟล์ใหม่ |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:701` | การปรับความชัดล้มเหลว: " + err.message); |
| ลูกจ้าง | `employees/partials/_edit_scripts.blade.php:894` | No image to refine. |
| ส่วนกลาง (components) | `components/job-check-widget.blade.php:484` | Something went wrong. Please try again. |
| ส่วนกลาง (components) | `components/job-check-widget.blade.php:500` | Something went wrong. Please try again. |
| ส่วนกลาง (layout) | `layouts/app.blade.php:1748` | Please select a job owner to delete |

### แจ้งสำเร็จ (ควรเป็น toast) — 4 จุด

| เมนู | ไฟล์:บรรทัด | ข้อความ |
|---|---|---|
| Workflow | `workflow/index.blade.php:1023` | Saved |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/registration/partials/edit_modal_script.blade.php:97` | Employee updated successfully |
| ลูกจ้าง | `employees/import.blade.php:637` | Bulk update successful |
| ลูกจ้าง | `employees/import.blade.php:682` | Bulk update successful |

## 2) หน้าต่าง Modal หัวเรียบที่ยังไม่ได้ออกแบบ (110 หน้าต่าง)

หน้าต่างที่ไม่มีชื่อ (—) คือหัวหน้าต่างที่ตั้งชื่อด้วย JavaScript ตอนเปิด

| เมนู | ชื่อหน้าต่าง | ไฟล์:บรรทัด |
|---|---|---|
| PDF Templates | Item Settings | `pdf_templates/builder.blade.php:328` |
| PDF Templates | Upload Image | `pdf_templates/builder.blade.php:452` |
| PDF Templates | ตั้งค่าพยาน (Template Witnesses) | `pdf_templates/builder.blade.php:478` |
| PDF Templates | Template Settings | `pdf_templates/builder.blade.php:515` |
| PDF Templates | Import Templates | `pdf_templates/index.blade.php:616` |
| PDF Templates | ตั้งค่ารายชื่อพยาน (Manage Witnesses) | `pdf_templates/index.blade.php:642` |
| Pro Walker Labor | Void Bill {{ $bill->bill_no }} | `labor/bills/index.blade.php:193` |
| Pro Walker Labor | Generate Bill | `labor/bills/index.blade.php:231` |
| Pro Walker Labor | Record Payment | `labor/bills/show.blade.php:173` |
| Pro Walker Labor | Edit Account | `labor/books/index.blade.php:88` |
| Pro Walker Labor | Add Book Account | `labor/books/index.blade.php:231` |
| Pro Walker Labor | Manage Expense Categories | `labor/books/index.blade.php:276` |
| Pro Walker Labor | Edit Expense Category | `labor/books/index.blade.php:356` |
| Pro Walker Labor | Edit Transaction | `labor/books/show.blade.php:226` |
| Pro Walker Labor | Add Transaction | `labor/books/show.blade.php:318` |
| Pro Walker Labor | Edit Charge | `labor/charges/index.blade.php:286` |
| Pro Walker Labor | Record Charge | `labor/charges/index.blade.php:380` |
| Pro Walker Labor | Manage Charge Types | `labor/charges/index.blade.php:466` |
| Pro Walker Labor | Edit Charge Type | `labor/charges/index.blade.php:533` |
| Pro Walker Labor | Field Settings | `labor/contract_templates/builder.blade.php:201` |
| Pro Walker Labor | — | `labor/contract_templates/builder.blade.php:330` |
| Pro Walker Labor | Import Templates | `labor/contract_templates/index.blade.php:102` |
| Pro Walker Labor | Download Selected Contracts (0) | `labor/contracts/_bulk_download_modal.blade.php:7` |
| Pro Walker Labor | Include Contractor Signature & Stamp? | `labor/contracts/_download_choice_modal.blade.php:4` |
| Pro Walker Labor | Edit Customer | `labor/customers/index.blade.php:83` |
| Pro Walker Labor | Add Customer | `labor/customers/index.blade.php:159` |
| Pro Walker Labor | Void Invoice {{ $invoice->invoice_no }} | `labor/tax-invoices/show.blade.php:120` |
| Pro Walker Labor | Edit Member #{{ $member->id }} | `labor/team-members/index.blade.php:69` |
| Pro Walker Labor | Register Member | `labor/team-members/index.blade.php:130` |
| Pro Walker Labor | Edit Team | `labor/teams/index.blade.php:53` |
| Pro Walker Labor | New Team | `labor/teams/index.blade.php:91` |
| Pro Walker Labor | Edit Entry | `labor/teams/show.blade.php:277` |
| Pro Walker Labor | Add Entry — {{ $team->name }} | `labor/teams/show.blade.php:340` |
| Super Admin | Configure: {{ $label }} | `super-admin/settings.blade.php:118` |
| Ticket | เลือกลูกจ้างที่มีอยู่ | `tickets/partials/_existing_employee_modal.blade.php:6` |
| Ticket | กรอกข้อมูลลูกจ้างใหม่ (แจ้งเข้า) | `tickets/partials/_new_employee_modal.blade.php:5` |
| Ticket | Change Ticket Assignment | `tickets/show.blade.php:604` |
| Workflow | Add Field () | `workflow/board.blade.php:121` |
| Workflow | ครอบตัดรูปภาพ | `workflow/dashboard.blade.php:283` |
| Workflow | Finance: {{ $order->project_name }} | `workflow/index.blade.php:781` |
| Workflow | Add New Field | `workflow/item_detail.blade.php:91` |
| Workflow | Add Employees | `workflow/partials/add_employee_modal.blade.php:5` |
| Workflow | — | `workflow/partials/create_modal.blade.php:35` |
| กลุ่ม / ทีม | Create New Group | `groups/manage.blade.php:374` |
| กลุ่ม / ทีม | Edit Group | `groups/manage.blade.php:398` |
| กลุ่ม / ทีม | Edit Team | `groups/manage.blade.php:423` |
| กลุ่ม / ทีม | Create New Team | `groups/manage.blade.php:446` |
| กลุ่ม / ทีม | — | `groups/manage.blade.php:468` |
| การเงิน | Edit Bank Account | `financial/bank_accounts/index.blade.php:83` |
| การเงิน | Add Bank Account | `financial/bank_accounts/index.blade.php:119` |
| การเงิน | Record Income/Expense | `financial/books/_quick_entry.blade.php:34` |
| การเงิน | Correct a Closed-Day Entry | `financial/books/show.blade.php:232` |
| การเงิน | Edit Category | `financial/expense_categories/index.blade.php:74` |
| การเงิน | Add Expense Category | `financial/expense_categories/index.blade.php:139` |
| การเงิน | Record New Expense | `financial/expenses/index.blade.php:115` |
| การเงิน | Edit Income Category | `financial/income_categories/index.blade.php:84` |
| การเงิน | Add Income Category | `financial/income_categories/index.blade.php:120` |
| การเงิน | Export Monthly Report | `financial/index.blade.php:121` |
| การเงิน | Add General Expense | `financial/tabs/expenses.blade.php:59` |
| การเงิน (WHT) | Upload WHT Document | `finance/wht_inbox.blade.php:167` |
| การแจ้งเตือน | Renew Notification | `notifications/_renew_modal.blade.php:5` |
| นายจ้าง | Manage Business Types | `employers/create.blade.php:412` |
| นายจ้าง | Manage Business Types | `employers/edit.blade.php:841` |
| นายจ้าง | Signature Settings | `employers/edit.blade.php:871` |
| ผู้ดูแลระบบ | รายละเอียดการเปลี่ยนแปลง (ID: {{ $log->id }}) | `admin/activity_logs/day.blade.php:135` |
| ผู้ดูแลระบบ | รายละเอียดการเปลี่ยนแปลง — {{ $log->created_at->format('d/m/Y H:i:s') }} | `admin/activity_logs/subject_history.blade.php:218` |
| ผู้ดูแลระบบ | — | `admin/settings/financial.blade.php:55` |
| ผู้ดูแลระบบ | Change Ticket Assignment | `admin/tickets/show.blade.php:741` |
| ผู้ดูแลระบบ | Trash Retention Settings | `admin/trash/partials/_settings_modal.blade.php:5` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Finance: {{ $order->employer->employerNameTh ?? $order->project_name }} | `production/index.blade.php:581` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Document Header Settings | `production/partials/financial-tab.blade.php:682` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Assign Employees to Price Tier | `production/partials/financial-tab.blade.php:757` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Bill To (Customer) Settings | `production/partials/financial-tab.blade.php:900` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Add Installment | `production/partials/financial-tab.blade.php:989` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Payment History & Details | `production/partials/financial-tab.blade.php:1124` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Select Items | `production/partials/financial-tab.blade.php:1462` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | — | `production/partials/financial-tab.blade.php:1522` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Add Existing Employee | `production/prepare.blade.php:282` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Create New Employee (External/Import) | `production/prepare.blade.php:318` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Finance: {{ $employer->employerNameTh }} | `production/registration/index.blade.php:838` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Job Order / Notes | `production/registration/index.blade.php:920` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Add Custom Field | `production/registration/partials/modals/add_custom_field.blade.php:12` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Finance: {{ $employer->employerNameTh }} | `production/renewal/index.blade.php:813` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Job Order / Notes | `production/renewal/index.blade.php:976` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Add Employee (Renewal Resolution) | `production/renewal/partials/add_employee_modal.blade.php:35` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Add Note | `production/workflow_item_timeline.blade.php:126` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Add Date | `production/workflow_item_timeline.blade.php:155` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Attach File | `production/workflow_item_timeline.blade.php:188` |
| ลูกจ้าง | Saving Changes | `employees/bulk_edit_form.blade.php:752` |
| ลูกจ้าง | Edit | `employees/import.blade.php:321` |
| ลูกจ้าง | Advanced Export (Max 15 items) | `employees/modals/advanced_export.blade.php:9` |
| ลูกจ้าง | Select Target Employer | `employees/modals/select_target_employer_modal.blade.php:5` |
| ส่วนกลาง (components) | Crop Image | `components/cropper-modal.blade.php:4` |
| ส่วนกลาง (components) | Download Employee Files | `components/download-modals.blade.php:5` |
| ส่วนกลาง (components) | Download Center | `components/download-modals.blade.php:183` |
| ส่วนกลาง (components) | Job Check Mode | `components/job-check-widget.blade.php:24` |
| ส่วนกลาง (components) | Confirm Finish | `components/job-check-widget.blade.php:114` |
| ส่วนกลาง (components) | Job Check History | `components/job-check-widget.blade.php:161` |
| ส่วนกลาง (components) | Deep Summary Report | `components/job-check-widget.blade.php:181` |
| ส่วนกลาง (layout) | Preview Data | `layouts/_app_scripts.blade.php:22` |
| ส่วนกลาง (layout) | PDF Preview | `layouts/_app_scripts.blade.php:41` |
| ส่วนกลาง (layout) | Renew Notification | `layouts/app.blade.php:996` |
| ส่วนกลาง (layout) | Confirm Cancellation | `layouts/app.blade.php:1036` |
| ส่วนกลาง (layout) | Manage Job Owners | `layouts/app.blade.php:1057` |
| ส่วนกลาง (layout) | Confirm Deletion | `layouts/app.blade.php:1087` |
| ส่วนกลาง (partials) | เพิ่ม/แก้ไขที่อยู่ | `partials/_address_management.blade.php:4` |
| ส่วนกลาง (partials) | Confirm Action | `partials/_employee_action_modals.blade.php:7` |
| ส่วนกลาง (partials) | Employment History | `partials/_employee_action_modals.blade.php:72` |
| ส่วนกลาง (partials) | ย้ายนายจ้างสำหรับลูกจ้าง | `partials/_employee_action_modals.blade.php:120` |
| ส่วนกลาง (partials) | Terminate Employee | `partials/_employee_action_modals.blade.php:152` |

## 3) หน้าต่าง Modal ที่ตกแต่งแล้ว แต่สีไม่ตามแบรนด์ (53 หน้าต่าง)

| เมนู | ชื่อหน้าต่าง | ไฟล์:บรรทัด |
|---|---|---|
| PDF Templates | พิมพ์เอกสาร (Quick Print) | `pdf_templates/index.blade.php:281` |
| Ticket | — | `tickets/partials/_new_employee_preview_modal.blade.php:5` |
| Workflow | — | `workflow/dashboard.blade.php:179` |
| Workflow | Edit Employee | `workflow/dashboard.blade.php:264` |
| Workflow | — | `workflow/index.blade.php:836` |
| Workflow | Notification Settings | `workflow/index.blade.php:1040` |
| Workflow | Manage Workflow Team | `workflow/index.blade.php:1076` |
| Workflow | Edit Employee | `workflow/index.blade.php:1113` |
| Workflow | Job History | `workflow/index.blade.php:1135` |
| Workflow | Trash Bin | `workflow/index.blade.php:1152` |
| การขาย / ใบเสนอราคา | สร้างรายการเสนอราคาใหม่ | `sales/partials/_create_modal.blade.php:7` |
| การขาย / ใบเสนอราคา | แก้ไขข้อมูลลูกจ้าง — {{ $emp->employeeNameEn }} | `sales/partials/_edit_employee_modal.blade.php:7` |
| การขาย / ใบเสนอราคา | แก้ไขข้อมูลลูกค้าใหม่ (ชั่วคราว) | `sales/partials/_edit_employer_modal.blade.php:7` |
| การขาย / ใบเสนอราคา | ประวัติรายการเสนอราคา | `sales/partials/_history_modal.blade.php:5` |
| การขาย / ใบเสนอราคา | id }}">จัดการลูกจ้าง - {{ $lead->employerNameTh }} | `sales/partials/_manage_employees_modal.blade.php:5` |
| การขาย / ใบเสนอราคา | เพิ่มลูกจ้างใหม่ (ชั่วคราว) - {{ $lead->employerNameTh }} | `sales/partials/_manage_employees_modal.blade.php:191` |
| การขาย / ใบเสนอราคา | id }}"> | `sales/partials/_quotation_modal.blade.php:5` |
| การขาย / ใบเสนอราคา | id }}">ส่งต่องาน (Transition) - {{ $lead->employerNameTh }} | `sales/partials/_transition_modal.blade.php:7` |
| การเงิน | Void Credit Note | `financial/credit_notes/show.blade.php:153` |
| การเงิน | Record Income | `financial/ledger/index.blade.php:164` |
| การเงิน | Record Expense | `financial/ledger/index.blade.php:188` |
| การเงิน | — | `financial/tabs/overview.blade.php:453` |
| การเงิน | — | `financial/tabs/overview.blade.php:693` |
| การเงิน | Financial Data | `financial/tabs/registration.blade.php:123` |
| การเงิน | Financial Data | `financial/tabs/renewal.blade.php:124` |
| การเงิน | Void Invoice | `financial/tax_invoices/show.blade.php:155` |
| การเงิน (WHT) | No WHT Certificate | `finance/wht_inbox.blade.php:203` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Edit Employee | `production/index.blade.php:638` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Manage Team / Batch | `production/index.blade.php:660` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Trash Bin (Pre-Production) | `production/index.blade.php:704` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Manage Team | `production/partials/manage_team_modal.blade.php:16` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Edit Employee | `production/registration/index.blade.php:883` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | — | `production/registration/index.blade.php:949` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Notification Settings | `production/registration/index.blade.php:995` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | — | `production/registration/index.blade.php:1020` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Job History | `production/registration/index.blade.php:1110` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Trash Bin | `production/registration/index.blade.php:1127` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Import Employees by Expiry | `production/renewal/index.blade.php:848` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Edit Employee | `production/renewal/index.blade.php:890` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | — | `production/renewal/index.blade.php:918` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | — | `production/renewal/index.blade.php:1005` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Job History | `production/renewal/index.blade.php:1095` |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | Trash Bin | `production/renewal/index.blade.php:1112` |
| ลูกจ้าง | — | `employees/import.blade.php:253` |
| ลูกจ้าง | — | `employees/modals/import_review.blade.php:8` |
| ส่วนกลาง (components) | Move Attachment Files | `components/bulk-move-attachments-modal.blade.php:29` |
| ส่วนกลาง (components) | — | `components/edit-employee-modal.blade.php:15` |
| ส่วนกลาง (components) | — | `components/help-button.blade.php:52` |
| ส่วนกลาง (components) | — | `components/job-check-widget.blade.php:60` |
| ส่วนกลาง (components) | Job Check Mode finished | `components/job-check-widget.blade.php:135` |
| ส่วนกลาง (layout) | — | `layouts/app.blade.php:1114` |
| ส่วนกลาง (layout) | — | `layouts/app.blade.php:1432` |
| ส่วนกลาง (partials) | — | `partials/view_selected_modal.blade.php:4` |

## 4) ป๊อปอัพ SweetAlert แยกตามไฟล์ (399 จุด)

ทั้งหมดจะได้รับการปรับพร้อมกันเมื่อทำธีมกลาง (แนวทางข้อ 1) ไม่ต้องแก้ทีละไฟล์

| เมนู | ไฟล์ | จำนวน |
|---|---|---|
| Workflow | `workflow/index.blade.php` | 56 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/registration/index.blade.php` | 42 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/renewal/index.blade.php` | 41 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/_index_scripts.blade.php` | 34 |
| JavaScript ส่วนกลาง (public/js) | `public/js/financial-manager.js` | 27 |
| ส่วนกลาง (components) | `components/resolution-tab-bar.blade.php` | 15 |
| Super Admin | `super-admin/settings.blade.php` | 14 |
| ส่วนกลาง (components) | `components/work-type-tab-scripts.blade.php` | 12 |
| Workflow | `workflow/partials/_item_card.blade.php` | 10 |
| ส่วนกลาง (components) | `components/chat-widget.blade.php` | 9 |
| ส่วนกลาง (components) | `components/hybrid-attachment-scripts.blade.php` | 9 |
| การเงิน (WHT) | `finance/profiles/builder.blade.php` | 9 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/partials/manage_team_modal.blade.php` | 8 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/registration/dashboard.blade.php` | 8 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/renewal/dashboard.blade.php` | 8 |
| นายจ้าง | `employers/edit.blade.php` | 7 |
| ลูกจ้าง | `employees/import.blade.php` | 6 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/registration/_employee_card.blade.php` | 6 |
| ส่วนกลาง (components) | `components/bulk-move-attachments-modal.blade.php` | 5 |
| ผู้ดูแลระบบ | `admin/trash/index.blade.php` | 4 |
| ตัวแทน | `agents/index.blade.php` | 4 |
| ผู้รับมอบอำนาจ | `delegates/index.blade.php` | 4 |
| กลุ่ม / ทีม | `groups/manage.blade.php` | 4 |
| ผู้นำเข้า | `importers/index.blade.php` | 4 |
| Pro Walker Labor | `labor/charges/index.blade.php` | 4 |
| Workflow | `workflow/partials/day_appointments_list.blade.php` | 4 |
| Workflow | `workflow/partials/trash_list.blade.php` | 4 |
| JavaScript ส่วนกลาง (public/js) | `public/js/financial-security.js` | 3 |
| ลูกจ้าง | `employees/history.blade.php` | 3 |
| ส่วนกลาง (layout) | `layouts/_app_scripts.blade.php` | 3 |
| PDF Templates | `pdf_templates/generate_modal.blade.php` | 3 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/renewal/partials/add_employee_modal.blade.php` | 3 |
| Workflow | `workflow/partials/add_employee_modal.blade.php` | 3 |
| ส่วนกลาง (components) | `components/manage-steps-scripts.blade.php` | 2 |
| ลูกจ้าง | `employees/index.blade.php` | 2 |
| PDF Templates | `pdf_templates/builder.blade.php` | 2 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/index.blade.php` | 2 |
| การขาย / ใบเสนอราคา | `sales/partials/_manage_employees_modal.blade.php` | 2 |
| ผู้ดูแลระบบ | `admin/tickets/employers.blade.php` | 1 |
| ผู้ดูแลระบบ | `admin/tickets/show.blade.php` | 1 |
| ส่วนกลาง (components) | `components/edit-employee-modal.blade.php` | 1 |
| นายจ้าง | `employers/index.blade.php` | 1 |
| การเงิน | `financial/tabs/overview.blade.php` | 1 |
| ส่วนกลาง (layout) | `layouts/app.blade.php` | 1 |
| ส่วนกลาง (partials) | `partials/_employee_action_modals.blade.php` | 1 |
| ส่วนกลาง (partials) | `partials/_employee_card.blade.php` | 1 |
| PDF Templates | `pdf_templates/index.blade.php` | 1 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/registration/partials/day_appointments_list.blade.php` | 1 |
| มติลงทะเบียน / มติต่ออายุ / Pre-Production | `production/renewal/partials/day_appointments_list.blade.php` | 1 |
| Ticket | `tickets/index.blade.php` | 1 |
| Ticket | `tickets/show.blade.php` | 1 |
