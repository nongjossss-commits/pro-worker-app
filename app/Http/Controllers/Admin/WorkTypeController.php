<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\WorkType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Super Admin CRUD for Pre-Production/Workflow "แท็บ" (WorkType) — the 4
 * built-in tabs (แจ้งเข้า/เปลี่ยนนายจ้าง, แจ้งออก, MOU นำเข้า, ต่ออายุ MOU)
 * plus any custom tabs an office adds for extra services they offer.
 *
 * Renaming is safe and needs no cascading update anywhere: every part of
 * the app that behaves differently per tab (order bucketing, the notify_out
 * date/reason editor, MOU demand-card fields, finalize side effects) keys
 * off `work_type_id` or the stable `slug` — never the display `name` — so
 * changing `name` only ever changes the label. A brand new custom tab
 * simply doesn't match any of those `slug`-specific branches, which is
 * exactly the plain "add employees, track steps, finish/cancel, bill,
 * export" behavior it should have — it never accidentally inherits
 * notify_out's termination side effect or notify_in/mou_import's employer
 * reassignment, which are NOT generically appropriate for an arbitrary
 * custom service.
 *
 * A custom tab (is_system = false) can also be deleted. Deleting cascades
 * (same shape as ResolutionTabController::forceDelete()): every order under
 * the tab is force-deleted, which lets the DB's own ON DELETE CASCADE
 * foreign keys wipe production_items, production_item_step,
 * production_financial_groups, and financial_transaction_items for those
 * orders — nothing is left dangling pointing at a tab that no longer
 * exists. Employees themselves are never deleted; they simply lose that
 * one job assignment, exactly as if they'd never been added to the tab.
 * The 4 built-in tabs (is_system = true) can never be deleted, only renamed.
 */
class WorkTypeController extends Controller
{
    public function index()
    {
        $types = WorkType::orderBy('order')->orderBy('id')->withCount('orders')->get()->map(function ($t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'order' => $t->order,
                'is_system' => (bool) $t->is_system,
                'orders_count' => $t->orders_count,
            ];
        });

        return response()->json(['success' => true, 'types' => $types]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('work_types', 'name')],
        ]);

        $name = trim($validated['name']);
        // A Thai-only name slugifies to an empty string (Str::slug strips
        // non-ASCII) — always append a random suffix, same as
        // ResolutionTab/WorkPermitType, so this never degrades to a bare
        // "tab" that repeats for every all-Thai tab name.
        $slug = (Str::slug($name) ?: 'tab') . '-' . Str::random(6);

        $maxOrder = (int) (WorkType::max('order') ?? 0);

        $type = WorkType::create([
            'name' => $name,
            'slug' => $slug,
            'is_system' => false,
            'order' => $maxOrder + 1,
            'notify_days_advance' => 3,
        ]);

        ActivityLogHelper::logAction('create', "เพิ่มแท็บงานใหม่: {$type->name}", WorkType::class, $type->id, ['name' => $type->name, 'slug' => $type->slug]);

        return response()->json(['success' => true, 'message' => 'สร้างแท็บใหม่เรียบร้อยแล้ว', 'type' => $type]);
    }

    public function update(Request $request, WorkType $workType)
    {
        if (!$request->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('work_types', 'name')->ignore($workType->id)],
        ]);

        $oldName = $workType->name;
        $workType->update(['name' => trim($validated['name'])]);

        ActivityLogHelper::logAction('update', "เปลี่ยนชื่อแท็บงาน '{$oldName}' เป็น '{$workType->name}'", WorkType::class, $workType->id, ['old_name' => $oldName, 'new_name' => $workType->name]);

        return response()->json(['success' => true, 'message' => 'อัพเดทชื่อแท็บเรียบร้อยแล้ว', 'type' => $workType]);
    }

    public function reorder(Request $request)
    {
        if (!$request->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'type_ids' => 'required|array',
            'type_ids.*' => 'exists:work_types,id',
        ]);

        foreach ($validated['type_ids'] as $index => $id) {
            WorkType::where('id', $id)->update(['order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'เรียงลำดับแท็บเรียบร้อยแล้ว']);
    }

    public function destroy(Request $request, WorkType $workType)
    {
        if (!$request->user()->hasRole('super-admin')) {
            abort(403);
        }

        if ($workType->is_system) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบแท็บหลักของระบบได้ ทำได้แค่เปลี่ยนชื่อ'], 422);
        }

        $name = $workType->name;
        $slug = $workType->slug;
        $ordersCount = $workType->orders()->count();

        // Cascade cleanup: force-delete every order under this tab so the
        // DB's ON DELETE CASCADE foreign keys wipe production_items,
        // production_item_step, production_financial_groups, and
        // financial_transaction_items with it — same pattern as
        // ResolutionTabController::forceDelete(). Employees are untouched;
        // they just lose this one job assignment.
        $workType->orders()->forceDelete();

        $workType->delete();

        ActivityLogHelper::logAction('delete', "ลบแท็บงาน: {$name} (ลบงานที่ค้างอยู่ไปด้วย {$ordersCount} งาน)", WorkType::class, null, ['name' => $name, 'slug' => $slug, 'orders_deleted' => $ordersCount]);

        return response()->json(['success' => true, 'message' => "ลบแท็บ '{$name}' เรียบร้อยแล้ว"]);
    }
}
