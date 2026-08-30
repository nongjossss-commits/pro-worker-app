<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResolutionTab;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ResolutionTabController extends Controller
{
    /**
     * List all tabs for a given type (JSON for AJAX).
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|in:registration,renewal',
        ]);

        $tabs = ResolutionTab::withTrashed()
            ->where('type', $request->type)
            ->ordered()
            ->get()
            ->map(function ($tab) {
                return [
                    'id' => $tab->id,
                    'name' => $tab->name,
                    'slug' => $tab->slug,
                    'type' => $tab->type,
                    'sort_order' => $tab->sort_order,
                    'is_default' => $tab->is_default,
                    'badge_enabled' => $tab->badge_enabled,
                    'is_deleted' => $tab->trashed(),
                    'deleted_at' => $tab->deleted_at?->format('d/m/Y H:i'),
                    'cooldown_days_remaining' => $tab->cooldown_days_remaining,
                    'employees_count' => $tab->employees()->count(),
                    'created_at' => $tab->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json(['success' => true, 'tabs' => $tabs]);
    }

    /**
     * Create a new resolution tab.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:registration,renewal',
        ]);

        $tab = ResolutionTab::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างแถบใหม่เรียบร้อยแล้ว',
            'tab' => $tab,
        ]);
    }

    /**
     * Update a tab's name.
     */
    public function update(Request $request, ResolutionTab $resolutionTab)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $resolutionTab->update(['name' => $validated['name']]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทชื่อแถบเรียบร้อยแล้ว',
            'tab' => $resolutionTab,
        ]);
    }

    /**
     * Toggle whether this tab's employees show the purple/pink "อยู่ในกลุ่ม
     * มติ..." card badge and notification badge. Employee data itself is
     * never affected — this only controls that one visual/notification
     * cue, for a tab that's been prepared ahead of time but isn't "live" yet.
     */
    public function toggleBadge(ResolutionTab $resolutionTab)
    {
        $resolutionTab->update(['badge_enabled' => !$resolutionTab->badge_enabled]);

        return response()->json([
            'success' => true,
            'badge_enabled' => $resolutionTab->badge_enabled,
            'message' => $resolutionTab->badge_enabled
                ? 'เปิดป้ายแจ้งเตือนของแถบนี้แล้ว'
                : 'ปิดป้ายแจ้งเตือนของแถบนี้แล้ว',
        ]);
    }

    /**
     * Reorder tabs.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'tab_ids' => 'required|array',
            'tab_ids.*' => 'exists:resolution_tabs,id',
        ]);

        foreach ($validated['tab_ids'] as $index => $id) {
            ResolutionTab::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'เรียงลำดับแถบเรียบร้อยแล้ว']);
    }

    /**
     * Soft delete a tab (7-day cooldown).
     */
    public function destroy(ResolutionTab $resolutionTab)
    {
        if ($resolutionTab->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบแถบหลัก (Default) ได้',
            ], 422);
        }

        $resolutionTab->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'แถบถูกลบแล้ว จะถูกลบถาวรหลังจาก 7 วัน',
        ]);
    }

    /**
     * Restore a soft-deleted tab (cancel the deletion).
     */
    public function restore($id)
    {
        $tab = ResolutionTab::onlyTrashed()->findOrFail($id);
        $tab->restore();

        return response()->json([
            'success' => true,
            'message' => 'กู้คืนแถบเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Force delete a tab (only if past 7-day cooldown).
     */
    public function forceDelete($id)
    {
        $tab = ResolutionTab::onlyTrashed()->findOrFail($id);

        if (!$tab->isPurgeable()) {
            return response()->json([
                'success' => false,
                'message' => 'ยังไม่สามารถลบถาวรได้ ต้องรอ Cooldown อีก ' . $tab->cooldown_days_remaining . ' วัน',
            ], 422);
        }

        // Cascade: set employees' resolution_tab_id to NULL
        $tab->employees()->update(['resolution_tab_id' => null]);

        // Delete related steps
        $tab->steps()->delete();

        // Delete related settings
        $tab->systemSettings()->delete();
        $tab->notificationSettings()->delete();

        // Delete the legacy "Import by Expiry" SystemConfig row (no FK relation,
        // key is `{type}_target_expiry_date_{tabId}` — see EmployeeObserver::findMatchingRenewalTab()).
        // Without this it survives as orphaned data that a tab-existence check
        // must guard against forever instead of it simply being gone.
        \App\Models\SystemConfig::where('key', $tab->type . '_target_expiry_date_' . $tab->id)->delete();

        // Delete production orders
        $tab->productionOrders()->forceDelete();

        // Delete employer pivot
        \DB::table('employer_resolution_tab')->where('resolution_tab_id', $tab->id)->delete();

        // Finally, force delete the tab itself
        $tab->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'ลบแถบถาวรเรียบร้อยแล้ว',
        ]);
    }
}
