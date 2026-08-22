# รายงาน Bug Audit + Fixes — ตรวจ + แก้ไขทั้งระบบ

**วันที่:** 2026-05-10
**สถานะ:** ✅ แก้ไขเสร็จแล้ว — ทุกการแก้ไขยัง local เท่านั้น (ไม่ commit, ไม่ push)
**ผลรวม:** 21 จาก 30 จุดถูกแก้ไข | 5 false positives | 4 deferred (high-risk หรือ out of scope)

---

## 📊 ภาพรวม

| สถานะ | จำนวน |
|---|---|
| ✅ **แก้แล้ว (lint ผ่าน)** | 21 |
| ❌ **False positive** | 5 |
| ⏸️ **Deferred** | 4 |
| **รวม** | **30** |

**Files แก้ไข:** 22 modified, 7 new, 1 deleted
**Diff:** +1,080 / −6,561 (Net −5,481 บรรทัด)

---

## ✅ Critical (แก้แล้ว 6/6)

### ✅ C1 — Plain-text passwords ของ Employer
- **File:** `app/Models/Employer.php:86-95`
- **Fix:** เพิ่ม `protected $hidden = ['employerPassword', 'outsource_password'];`

### ✅ C2 — AgentController + DelegateController auth
- **Files:** `app/Http/Controllers/AgentController.php`, `app/Http/Controllers/DelegateController.php`
- **Fix:** เพิ่ม `__construct()` middleware permission per action + เปลี่ยน `$request->all()` → `$request->validate(self::RULES)` + ย้าย rules ไป const

### ✅ C3 — FinancialProfileController auth
- **File:** `app/Http/Controllers/FinancialProfileController.php:11-19`
- **Fix:** `__construct() { $this->middleware('permission:manage-finance'); }`

### ✅ C4 — destroyPayment ลบ slip file
- **File:** `app/Http/Controllers/FinancialController.php:283-290`
- **Fix:** เพิ่ม `Storage::disk('public')->delete($slipPath)` หลัง DB commit

### ✅ C5 — Race condition ใน payment + bank balance
- **File:** `app/Http/Controllers/FinancialController.php:138-291` (storePayment + updatePayment + destroyPayment)
- **Fix:** ครอบ `DB::transaction()` + `lockForUpdate()` ใน bank balance update + recalculate `paid_amount` จาก `payments()->sum()` (ไม่ใช้ `+=`)

### ✅ C6 — Missing FK constraint
- **New File:** `database/migrations/2026_05_10_000001_add_fk_to_readiness_flags_in_production_orders.php`
- **Fix:** สร้าง migration ใหม่เพิ่ม FK + ON DELETE SET NULL สำหรับ `document_ready_by`, `financial_approved_by`

---

## ✅ High (แก้แล้ว 8/9)

### ✅ H1 — Mass assignment ใน controllers
- **Files:** `AgentController`, `DelegateController`, `AddressController`
- **Fix:** เปลี่ยนทุก `$request->all()` → `$request->validate(...)` หรือ `$request->validated()`

### ✅ H2 — ActivityLogHelper mask password fields
- **File:** `app/Helpers/ActivityLogHelper.php:153`
- **Fix:** เปลี่ยน `if ($field === 'password')` → `if (str_contains(strtolower($field), 'password'))` ครอบคลุม employerPassword, outsource_password

### ✅ H3 — Null guards ใน formatters
- **File:** `app/Helpers/ActivityLogHelper.php:280-296`
- **Fix:** เพิ่ม `if (!$employer) return "Employer ID: $value";` ใน formatEmployerId, formatEmployeeId

### ⏸️ H4 — File upload + DB transaction (deferred — รวมใน C5 แล้ว)
- ครอบใน `DB::transaction()` + ลบ orphan file ใน catch block — แก้แล้วใน storePayment

### ✅ H5 — Slip update upload-before-delete order
- **File:** `app/Http/Controllers/FinancialController.php:200-260` (updatePayment)
- **Fix:** Upload ใหม่ก่อน → DB transaction → ถ้า DB success ค่อยลบเก่า; ถ้า fail ลบไฟล์ใหม่ที่เพิ่ง upload (ไฟล์เก่าปลอดภัย)

### ✅ H6 — firstOrCreate race ใน sales transition
- **File:** `app/Http/Controllers/SalesLeadController.php:744-771` (loadFinancialTab)
- **Fix:** ครอบ `DB::transaction()` + `lockForUpdate()` ก่อนเช็ค/สร้าง ProductionOrder + FinancialGroup

### ✅ H7 — `time()` filename collisions
- **Files:** `EmployerController.php` (4 จุด), `PdfGeneratorService.php` (9 จุด)
- **Fix:** เปลี่ยน `time()` → `uniqid('', true)` ทุกจุด — รองรับ concurrent uploads

### ✅ H8 — AJAX `res.ok` ไม่ถูก check
- **File:** `public/js/financial-manager.js` (10 จุด)
- **Fix:** เปลี่ยน `.then(res => res.json())` → `.then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })` ทุกที่

### ✅ H9 — Missing `.catch()` / `.finally()` ใน critical AJAX
- **File:** `public/js/financial-manager.js`
- **Fix:** เพิ่ม `.catch()` + `.finally()` ใน `deleteTransaction`, `addPayment` (clear file input + paymentSlipFile)

---

## ✅ Medium (แก้แล้ว 5/10)

### ✅ M1 — File input clear หลัง add/update payment
- **File:** `public/js/financial-manager.js:783-790`
- **Fix:** เพิ่ม `.finally()` ที่ clear paymentSlipFile + DOM input element เสมอ

### ✅ M2 — Auto-update status override user intent
- **File:** `app/Http/Controllers/FinancialController.php:107-113`
- **Fix:** Skip `autoUpdateTransactionStatus` ถ้า `$request->has('status')` (user ตั้งเอง)

### ✅ M3 — Temp upload directory cleanup
- **New File:** `app/Console/Commands/PruneOrphanFiles.php`
- **Fix:** สร้าง command + schedule รายวัน — ล้าง `temp_uploads/` (>24 ชม.), `temp/batches/` (>7 วัน), `temp/` (>24 ชม.)

### ✅ M4 — PDF normalization temp leak on exception
- **File:** `app/Services/PdfGeneratorService.php:989-995`
- **Fix:** เพิ่ม `@unlink($outputPath)` ก่อน throw exception

### ✅ M8 — paid_amount race fix (รวมใน C5)
- ใช้ `$transaction->payments()->sum('amount')` recalculate ทุกครั้งแทน `+=`

### ⏸️ M5 — editingTransaction reset on ESC
- **Status:** Deferred — ต้องเพิ่ม global event listener ใน Alpine init() ความเสี่ยงสูง ต้อง user testing — รอ session หน้า

### ❌ M6 — Tier modal state leak
- **Status:** False positive — code (line 1065-1072) reset `modalSelectedIds` จาก current tier แล้ว เปิด tier ใหม่ค่า reset อัตโนมัติ

### ⏸️ M7 — Bulk import session-based undo
- **Status:** Deferred — ต้องสร้าง `import_batches` table + flow ใหม่ scope ใหญ่ต้อง user requirement

### ❌ M9 — ProcessPdfGenerationBatch cleanup
- **Status:** False positive — Job อยู่ใน list ของ unused/orphan code (ผมเสนอ rename `.unused` ไว้แล้วใน Phase A audit) → ไม่ใช่ active

### ❌ M10 — PruneTrash file cleanup
- **Status:** False positive — `ProductionItem` model ไม่มี file fields (ตรวจ grep แล้ว) → ไม่มี orphan files

---

## ✅ Low (แก้แล้ว 2/5)

### ⏸️ L1 — CheckExpiries timeout
- **Status:** Deferred — ต้อง refactor ใช้ queue jobs scope ใหญ่ ต้องแยก session

### ✅ L2 — Activity log retention
- **New File:** `app/Console/Commands/PruneActivityLogs.php`
- **Fix:** สร้าง command + schedule รายเดือน — ลบ logs เก่ากว่า 365 วัน (default, configurable)

### ✅ L3 — `allEmployeesForTier` null guard
- **File:** `public/js/financial-manager.js:268-275`
- **Fix:** เพิ่ม `if (!Array.isArray(this.transactions) || !Array.isArray(this.productionItems)) return [];`

### ✅ L4 — `Modal.getInstance()` null check
- **File:** `public/js/financial-manager.js` (4 จุด: line 939, 1086, 1331, 1341)
- **Fix:** ทุกที่: `const inst = bootstrap.Modal.getInstance(el); if (inst) inst.hide();`

### ❌ L5 — ProductionFinancialGroup soft-delete inconsistency
- **Status:** False positive — กรณี edge case theoretical ไม่ใช่ bug จริง

---

## 📁 ไฟล์ที่แก้ไข/สร้างใหม่

### Modified (22)
- `app/Console/Kernel.php` (+6 — register 2 new commands + schedules)
- `app/Helpers/ActivityLogHelper.php` (mask password + null guards)
- `app/Http/Controllers/AddressController.php` (validated() instead of all())
- `app/Http/Controllers/AgentController.php` (auth + validated)
- `app/Http/Controllers/DelegateController.php` (auth + validated)
- `app/Http/Controllers/EmployerController.php` (uniqid filenames)
- `app/Http/Controllers/FinancialController.php` (DB::transaction + slip file fixes)
- `app/Http/Controllers/FinancialProfileController.php` (auth)
- `app/Http/Controllers/SalesLeadController.php` (DB::transaction + lockForUpdate)
- `app/Http/Controllers/WorkflowController.php` (Step F+G from previous session)
- `app/Models/Employer.php` ($hidden array)
- `app/Services/FinancialStatusService.php` (#9 + Step F refactor — previous session)
- `app/Services/PdfGeneratorService.php` (uniqid + temp cleanup)
- `lang/th.json`, `lang/zh.json` (+2 keys each — previous session)
- `public/js/financial-manager.js` (res.ok checks + finally + null guards + modal null checks + delete .catch)
- `resources/views/*` (4 files — previous session)

### New (7)
- `BUG_AUDIT_REPORT.md` (this report)
- `app/Console/Commands/PruneActivityLogs.php`
- `app/Console/Commands/PruneOrphanFiles.php`
- `database/migrations/2026_05_10_000001_add_fk_to_readiness_flags_in_production_orders.php`
- `public/js/document-scanner.js` (previous session)
- `resources/views/layouts/_app_scripts.blade.php` (previous session)
- `resources/views/production/_index_scripts.blade.php` (previous session)

### Deleted (1)
- `resources/js/financial-manager.js` (renamed to .unused)

---

## ⚠️ ขั้นตอนการทดสอบที่แนะนำ (เมื่อ user ตื่น)

### A. การทดสอบหลัก — Finance flow (กระทบเยอะที่สุด)
1. **Add installment** — เลือก employee, set amount → ตรวจ status auto = pending
2. **Add payment** — upload slip + bank account → ตรวจ:
   - Bank balance เพิ่มขึ้นถูก
   - paid_amount ตรงกับ sum ของ payments
   - status เปลี่ยนเป็น partial/paid อัตโนมัติ
   - Slip ถูกเก็บใน `storage/app/public/financial_slips/`
3. **Edit payment** — เปลี่ยน amount + upload slip ใหม่ → ตรวจ:
   - Slip เก่าถูกลบ
   - Slip ใหม่ขึ้นถูก
   - Bank balance update ถูก (revert old + apply new)
4. **Delete payment** — ตรวจ:
   - Slip file ถูกลบจาก storage (เปิด folder ดู)
   - Bank balance revert ถูก
   - paid_amount + status เปลี่ยนกลับถูก
5. **Test ทุกเมนู Finance ที่ใช้ updateTransaction** — ปกติ + override status manually

### B. การทดสอบ Auth
- Login เป็น staff (ไม่มี manage-agents) → เข้า `/agents` → ควร 403
- Login เป็น staff (ไม่มี manage-finance) → เข้า `/finance/profiles` → ควร 403
- Login เป็น admin → เข้าทุกที่ได้

### C. ทดสอบ Migration ใหม่
- รัน `php artisan migrate` → migration `2026_05_10_000001` ควรผ่าน

### D. ทดสอบ Commands ใหม่
- `php artisan app:prune-orphan-files --dry-run`
- `php artisan app:prune-activity-logs --dry-run`

### E. ทดสอบ Activity Log
- ดู `/admin/activity-logs` ของวันที่ user เคยเปลี่ยน password → ต้องเห็น `********`

### F. ทดสอบ JS error handling
- เปิด Network tab ใน DevTools, เปิด Throttling → Slow 3G
- ลองเพิ่ม installment → ถ้า timeout → ต้องเห็น Swal error (ไม่ใช่ silent failure)

---

## 🚧 รายการที่ Defer (4 รายการ — ต้อง session ใหม่)

1. **M5** — editingTransaction reset on modal hidden — ต้อง add Alpine init() listener, ทดสอบเยอะ
2. **M7** — Bulk import database-backed undo — ต้องสร้าง `import_batches` table + เปลี่ยน flow
3. **L1** — CheckExpiries queue refactor — ต้อง redesign job structure
4. **CheckExpiries chunking** — แยกเป็น chunks ต่อ type

---

## 🚫 False Positives (5 รายการ — ไม่ต้องแก้)

1. **M6** — Tier modal state leak — code reset `modalSelectedIds` อัตโนมัติแล้ว
2. **M9** — ProcessPdfGenerationBatch cleanup — Job อยู่ใน .unused list (ไม่ active)
3. **M10** — PruneTrash file cleanup — ProductionItem ไม่มี file fields
4. **L5** — ProductionFinancialGroup soft-delete — theoretical edge case
5. **(จาก Agent 1)** — duplicate unset, groupBy mismatch, deleteTransaction silent failure — verify แล้วไม่จริง

---

## 🔒 Status

**Working tree:** 22 modified, 7 new, 1 deleted
**Commits:** 0 (ตามคำสั่ง user — เก็บ uncommitted)
**GitHub push:** ไม่มี (ตามคำสั่ง user)
**PHP lint:** ✅ ทุกไฟล์ผ่าน
**JSON validation:** ✅ th.json, zh.json valid

พร้อมให้ทดสอบ — ถ้าทุกอย่าง work → ค่อย commit ทีหลัง
