<?php

namespace App\Support;

use App\Models\ProductionItem;

/**
 * An employee changing employer can have two open Workflow jobs at once: a
 * "แจ้งออก" job on the OLD employer's card and a "แจ้งเข้า / เปลี่ยนนายจ้าง" job
 * on the NEW employer's card. Each job card shows a note pointing at the other
 * one (workflow/partials/_item_card), so the notify-out job is never mistaken
 * for a forgotten one and stays until someone finishes it (2026-11-19).
 *
 * Loaded once per batch of cards (prime()) — a card rendered on its own loads
 * just its employee.
 */
class ChangeEmployerLinks
{
    /** @var array<int, array<string, \App\Models\ProductionItem[]>> */
    private static array $cache = [];

    public static function prime(iterable $employeeIds): void
    {
        $ids = collect($employeeIds)->filter()->map(fn ($id) => (int) $id)->unique()
            ->reject(fn ($id) => array_key_exists($id, self::$cache))->values();
        if ($ids->isEmpty()) {
            return;
        }
        foreach ($ids as $id) {
            self::$cache[$id] = ['notify_in' => [], 'notify_out' => []];
        }
        ProductionItem::query()
            ->whereIn('employee_id', $ids)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('order', fn ($o) => $o->withoutGlobalScope('employerTenancy')
                ->where('status', '!=', 'cancelled')
                ->whereHas('workType', fn ($w) => $w->whereIn('slug', ['notify_in', 'notify_out'])))
            ->with(['order' => fn ($o) => $o->withoutGlobalScope('employerTenancy')->with(['employer', 'workType'])])
            ->latest('id')
            ->get()
            ->each(function (ProductionItem $i) {
                $slug = $i->order?->workType?->slug;
                if ($slug) {
                    self::$cache[(int) $i->employee_id][$slug][] = $i;
                }
            });
    }

    /**
     * The OTHER open change-employer job for this card's employee: on a
     * "แจ้งออก" card → their open "แจ้งเข้า / เปลี่ยนนายจ้าง" job at another
     * employer, and the other way round. Null when there is none.
     */
    public static function counterpart(ProductionItem $item, $order): ?ProductionItem
    {
        $slug = $order?->workType?->slug;
        $want = ['notify_out' => 'notify_in', 'notify_in' => 'notify_out'][$slug] ?? null;
        if (! $want || ! $item->employee_id || in_array($item->status, ['completed', 'cancelled'], true)) {
            return null;
        }
        self::prime([$item->employee_id]);
        foreach (self::$cache[(int) $item->employee_id][$want] ?? [] as $other) {
            if ((int) $other->order->employer_id !== (int) $order->employer_id) {
                return $other;
            }
        }

        return null;
    }

    /** Tests / long-running workers: forget what was loaded. */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
