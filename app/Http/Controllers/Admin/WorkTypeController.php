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
 * A custom tab (is_system = false) can also be deleted. Deleting is a SOFT
 * delete (see WorkType's `use SoftDeletes`) — the tab just disappears from
 * every listing (Eloquent excludes trashed rows automatically), while every
 * ProductionOrder/ProductionItem/financial row ever processed under it
 * stays completely untouched in the database, exactly as it was the moment
 * before deletion. This used to force-delete every order under the tab
 * (cascading via the DB's own FKs to wipe production_items etc.) — changed
 * because that destroyed real production data an office may still need to
 * look back on, which is never what "remove this tab from the list" should
 * mean. Employees are never deleted either way; at most they lose one job
 * assignment's *visibility in the tab list*, not the underlying record.
 * The 4 built-in tabs (is_system = true) can never be deleted, only renamed.
 *
 * `allow_multiple_orders` (settable at create/edit time here) decides
 * whether a given employer can have more than one active order under this
 * tab at once — see WorkflowController's order-creation branch, which used
 * to hardcode this to "true only for slug mou/mou_import" and now reads
 * this column instead. `show_mou_fields` is NOT settable here at all — it
 * stays true only for the pre-existing MOU Import tab, so a new custom tab
 * never inherits the MOU nationality/gender-count/import-type fields just
 * because it also allows multiple orders.
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
                'allow_multiple_orders' => (bool) $t->allow_multiple_orders,
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
            'allow_multiple_orders' => ['nullable', 'boolean'],
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
            // Deliberately never set from the request — see the class
            // docblock above. Only the pre-existing MOU Import tab has
            // this true.
            'show_mou_fields' => false,
            'allow_multiple_orders' => $request->boolean('allow_multiple_orders'),
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
            'allow_multiple_orders' => ['nullable', 'boolean'],
        ]);

        $oldName = $workType->name;
        $oldAllowMultiple = $workType->allow_multiple_orders;
        // A system tab's card-mode is fixed (MOU Import stays "multiple",
        // everything else stays "single") — this form field is only ever
        // shown for custom tabs anyway, but guard it here too in case of a
        // direct request.
        $updateData = ['name' => trim($validated['name'])];
        if (!$workType->is_system && $request->has('allow_multiple_orders')) {
            $updateData['allow_multiple_orders'] = $request->boolean('allow_multiple_orders');
        }
        $workType->update($updateData);

        ActivityLogHelper::logAction('update', "เปลี่ยนชื่อแท็บงาน '{$oldName}' เป็น '{$workType->name}'", WorkType::class, $workType->id, [
            'old_name' => $oldName,
            'new_name' => $workType->name,
            'old_allow_multiple_orders' => $oldAllowMultiple,
            'new_allow_multiple_orders' => $workType->allow_multiple_orders,
        ]);

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

        // Soft delete only — see the class docblock above. Every order
        // under this tab (and their items/financial rows) is left
        // completely untouched; only the WorkType row itself gets
        // deleted_at set, so it drops out of every normal listing.
        $workType->delete();

        ActivityLogHelper::logAction('delete', "ลบแท็บงาน: {$name} (มีงานที่เคยดำเนินการอยู่ {$ordersCount} งาน — ข้อมูลเดิมไม่ถูกลบ)", WorkType::class, null, ['name' => $name, 'slug' => $slug, 'orders_count' => $ordersCount]);

        return response()->json(['success' => true, 'message' => "ลบแท็บ '{$name}' เรียบร้อยแล้ว"]);
    }
}
