<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalesLeadEmployee;
use App\Models\SystemConfig;
use App\Models\WorkPermitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Super Admin CRUD for "ประเภทใบอนุญาตทำงาน" (Work Permit / MOU Group
 * types) — see WorkPermitType / its migration for the full design
 * rationale. Only reachable by super-admin (route middleware).
 */
class WorkPermitTypeController extends Controller
{
    public function index()
    {
        $types = WorkPermitType::ordered()->get()->map(function ($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'sort_order' => $type->sort_order,
                'is_default' => $type->is_default,
                'usage_count' => $type->usageCount(),
            ];
        });

        return response()->json(['success' => true, 'types' => $types]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('work_permit_types', 'name')->whereNull('deleted_at')],
        ]);

        $type = WorkPermitType::create(['name' => trim($validated['name'])]);

        ActivityLogHelper::logAction('create', 'เพิ่มประเภทใบอนุญาตทำงาน: ' . $type->name, WorkPermitType::class, $type->id, ['name' => $type->name]);

        return response()->json(['success' => true, 'message' => 'เพิ่มประเภทใบอนุญาตทำงานเรียบร้อยแล้ว', 'type' => $type]);
    }

    /**
     * Renaming cascades to every place the OLD name is stored as a plain
     * string, so exports and reports always show the corrected name — the
     * whole point of this feature. Covers: Employee, SalesLeadEmployee,
     * and the Workflow/Registration/Renewal "Auto Settings" mou_group
     * config values (stored in system_configs, keyed by pattern — see
     * ApplyWorkflowSettings / UpdateResolutionData).
     */
    public function update(Request $request, WorkPermitType $workPermitType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('work_permit_types', 'name')->whereNull('deleted_at')->ignore($workPermitType->id)],
        ]);

        $oldName = $workPermitType->name;
        $newName = trim($validated['name']);

        if ($oldName === $newName) {
            return response()->json(['success' => true, 'message' => 'ไม่มีการเปลี่ยนแปลง', 'type' => $workPermitType]);
        }

        $employeeCount = 0;
        $salesLeadCount = 0;
        $configCount = 0;

        DB::transaction(function () use ($workPermitType, $oldName, $newName, &$employeeCount, &$salesLeadCount, &$configCount) {
            $workPermitType->update(['name' => $newName]);

            $employeeCount = Employee::where('workPermitMOUGroup', $oldName)->update(['workPermitMOUGroup' => $newName]);
            $salesLeadCount = SalesLeadEmployee::where('workPermitMOUGroup', $oldName)->update(['workPermitMOUGroup' => $newName]);

            // Auto Settings config values (Workflow: workflow_auto_mou_group_{workTypeId};
            // Registration/Renewal: {group}_auto_mou_group[__tab_{tabId}]) — both store
            // the raw group name as `value`, so any row whose value exactly
            // matches the old name is renaming the same "pointer".
            $configCount = SystemConfig::where('value', $oldName)
                ->where(function ($q) {
                    $q->where('key', 'like', 'workflow_auto_mou_group_%')
                      ->orWhere('key', 'like', '%_auto_mou_group%');
                })
                ->update(['value' => $newName]);
        });

        ActivityLogHelper::logAction('update', "เปลี่ยนชื่อประเภทใบอนุญาตทำงาน '{$oldName}' เป็น '{$newName}' (ลูกจ้าง {$employeeCount} คน, ตั้งค่าอัตโนมัติ {$configCount} รายการ)", WorkPermitType::class, $workPermitType->id, [
            'old_name' => $oldName,
            'new_name' => $newName,
            'employees_updated' => $employeeCount,
            'sales_leads_updated' => $salesLeadCount,
            'auto_settings_updated' => $configCount,
        ]);

        return response()->json([
            'success' => true,
            'message' => "เปลี่ยนชื่อเรียบร้อยแล้ว — อัพเดตลูกจ้าง {$employeeCount} คน" . ($configCount > 0 ? " และตั้งค่าอัตโนมัติ {$configCount} รายการ" : ''),
            'type' => $workPermitType,
        ]);
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'type_ids' => 'required|array',
            'type_ids.*' => 'exists:work_permit_types,id',
        ]);

        foreach ($validated['type_ids'] as $index => $id) {
            WorkPermitType::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'เรียงลำดับเรียบร้อยแล้ว']);
    }

    /**
     * Blocked if this is one of the 4 original defaults, or if any
     * employee/sales-lead-employee currently carries this name — a type
     * that vanishes from the dropdown while still referenced would leave
     * those records pointing at an option nobody can see or re-select.
     */
    public function destroy(WorkPermitType $workPermitType)
    {
        if ($workPermitType->is_default) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบประเภทเริ่มต้นของระบบได้'], 422);
        }

        $usage = $workPermitType->usageCount();
        if ($usage > 0) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถลบได้ เนื่องจากมีลูกจ้าง/ผู้สมัครงานใช้ประเภทนี้อยู่ {$usage} รายการ กรุณาเปลี่ยนประเภทของรายการเหล่านั้นก่อน",
            ], 422);
        }

        ActivityLogHelper::logAction('delete', 'ลบประเภทใบอนุญาตทำงาน: ' . $workPermitType->name, WorkPermitType::class, $workPermitType->id, ['name' => $workPermitType->name]);

        $workPermitType->delete();

        return response()->json(['success' => true, 'message' => 'ลบประเภทใบอนุญาตทำงานเรียบร้อยแล้ว']);
    }
}
