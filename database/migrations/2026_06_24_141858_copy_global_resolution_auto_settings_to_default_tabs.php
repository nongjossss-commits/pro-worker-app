<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrate global Resolution Auto Settings → per-tab keys.
 *
 * Before:  system_settings has GLOBAL keys per group:
 *            renewal_auto_visa_expiry / renewal_auto_work_permit_expiry / renewal_auto_mou_group
 *            registration_auto_visa_expiry / registration_auto_work_permit_expiry / registration_auto_mou_group
 *
 * After:   Each tab has its own set, key format: {original_key}__tab_{tabId}
 *            e.g. renewal_auto_visa_expiry__tab_2
 *
 * Strategy: copy each existing global value into the DEFAULT tab of that group
 *           so the user keeps their current setting on the tab that's-marked default.
 *           Global keys are preserved as legacy fallback (not deleted).
 *           Already-per-tab keys are not touched (re-running is safe).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['renewal', 'registration'] as $group) {
            $defaultTabId = DB::table('resolution_tabs')
                ->where('type', $group)
                ->where('is_default', true)
                ->whereNull('deleted_at')
                ->value('id');

            if (!$defaultTabId) {
                // Fall back to oldest tab of this type if no default flag is set
                $defaultTabId = DB::table('resolution_tabs')
                    ->where('type', $group)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->value('id');
            }

            if (!$defaultTabId) continue; // no tabs at all for this group — nothing to migrate

            foreach (['visa_expiry', 'work_permit_expiry', 'mou_group'] as $field) {
                $globalKey = "{$group}_auto_{$field}";
                $perTabKey = "{$globalKey}__tab_{$defaultTabId}";

                // Skip if per-tab key already exists (don't overwrite)
                $exists = DB::table('system_settings')->where('key', $perTabKey)->exists();
                if ($exists) continue;

                $globalRow = DB::table('system_settings')->where('key', $globalKey)->first();
                if (!$globalRow) continue;

                DB::table('system_settings')->insert([
                    'key'   => $perTabKey,
                    'value' => $globalRow->value,
                    'group' => $group,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove only the per-tab keys we created; leave originals + manually-added per-tab keys alone.
        foreach (['renewal', 'registration'] as $group) {
            DB::table('system_settings')
                ->where('group', $group)
                ->where('key', 'like', "{$group}_auto_%__tab_%")
                ->delete();
        }
    }
};
