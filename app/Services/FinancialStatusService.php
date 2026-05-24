<?php

namespace App\Services;

use App\Models\ProductionOrder;
use Illuminate\Support\Collection;

class FinancialStatusService
{
    /**
     * คำนวณสถานะทางการเงินของลูกจ้างแต่ละคนในบริบทของ ProductionOrder ที่กำหนด
     *
     * @param ProductionOrder|null $order  Order ของเมนู/workflow ปัจจุบัน
     * @param Collection|array     $employeeIds  รายชื่อ employee IDs (รับได้ทั้ง Collection / array / object)
     * @return array  [employee_id => status]
     *                สถานะที่เป็นไปได้: 'none' | 'priced' | 'installment_created' | 'partial' | 'paid'
     */
    public static function calculateStatusForEmployees(?ProductionOrder $order, $employeeIds): array
    {
        $ids = self::normalizeEmployeeIds($employeeIds);
        $statuses = array_fill_keys($ids, 'none');

        if (!$order) {
            return $statuses;
        }

        // Preload relations กัน N+1 queries
        $order->loadMissing('financialGroups.transactions.items', 'items.employee');

        // Phase 1: ทำเครื่องหมายลูกจ้างที่ถูก "ตั้งราคา" แล้ว → 'priced'
        self::markPricedEmployees($order, $ids, $statuses);

        // Phase 2: รวบรวม transactions ที่เกี่ยวกับลูกจ้างแต่ละคน
        $employeeTransactions = self::collectEmployeeTransactions($order, $ids);

        // Phase 3: ยกระดับ status เป็น installment_created/partial/paid ตามสถานะการจ่าย
        self::evaluatePaymentStatuses($employeeTransactions, $statuses);

        return $statuses;
    }

    /**
     * แปลง input ให้เป็น array ของ integer IDs
     * รองรับ Collection / array ของ object (มี ->id) / array ของ scalar / scalar เดี่ยว
     */
    private static function normalizeEmployeeIds($employeeIds): array
    {
        if ($employeeIds instanceof Collection) {
            return $employeeIds->map(fn($item) => is_object($item) ? $item->id : $item)->toArray();
        }

        if (is_array($employeeIds)) {
            return array_map(fn($item) => is_object($item) ? $item->id : $item, $employeeIds);
        }

        return (array) $employeeIds;
    }

    /**
     * Phase 1 (orchestrator): วน FinancialGroup ทุกตัวของ order แล้ว delegate ไปยัง mode handler
     * อัพเดท $statuses[$empId] = 'priced' (in-place ด้วย reference)
     */
    private static function markPricedEmployees(ProductionOrder $order, array $ids, array &$statuses): void
    {
        $itemToEmployeeMap = $order->items->pluck('employee_id', 'id')->toArray();
        // ใช้ groupBy เพื่อรองรับลูกจ้าง 1 คนที่มีหลาย ProductionItem (เช่น Workflow ที่ load
        // merged items ข้าม order — Pre-Prod's item + Workflow's item ของลูกจ้างเดียวกัน)
        $employeeItemsMap = $order->items->groupBy('employee_id');

        foreach ($order->financialGroups as $group) {
            $groupData = is_array($group->financial_data)
                ? $group->financial_data
                : json_decode($group->financial_data, true);

            $pricingMode = $groupData['pricing_mode'] ?? 'lump_sum';
            $pricingTiers = collect($groupData['pricing_tiers'] ?? []);

            if ($pricingMode === 'per_head') {
                self::markPerHeadPriced($ids, $employeeItemsMap, $pricingTiers, $statuses);
            } else {
                self::markLumpSumPriced($ids, $itemToEmployeeMap, $groupData, $statuses);
            }

            // กรณี manual bill (ไม่มี work_type) — ราคาอยู่ใน items[].employee_ids ของ groupData
            if ($order->work_type_id === null) {
                self::markManualBillPriced($ids, $groupData, $statuses);
            }
        }
    }

    /**
     * โหมด per_head: ลูกจ้างถือว่า priced ถ้า item ใดๆ ของลูกจ้างคนนี้อยู่ใน tier['item_ids']
     * รองรับ 4 รูปแบบของ id ใน tier['item_ids']: integer ProductionItem.id, "emp_X", string ของ id หรือ employee_id
     * รองรับลูกจ้างที่มีหลาย ProductionItem (กรณี shared groups ข้าม Pre-Prod ↔ Workflow)
     * นอกจากนี้ ถ้ามี "Default Tier" หรือ tier ที่ item_ids ว่าง ถือว่าทุกคน priced ด้วย
     */
    private static function markPerHeadPriced(array $ids, Collection $employeeItemsMap, Collection $pricingTiers, array &$statuses): void
    {
        $hasDefaultTier = $pricingTiers->contains(
            fn($t) => empty($t['item_ids']) || ($t['name'] ?? '') === 'Default Tier'
        );

        foreach ($ids as $empId) {
            $itemsForEmp = $employeeItemsMap->get($empId);
            if (!$itemsForEmp || $itemsForEmp->isEmpty()) continue;

            $tier = $pricingTiers->first(function ($t) use ($itemsForEmp, $empId) {
                $tierItemIds = array_map('strval', $t['item_ids'] ?? []);

                // ลอง match ผ่าน item.id ทุกตัวของลูกจ้าง (รองรับ shared groups ข้าม order)
                foreach ($itemsForEmp as $item) {
                    if (in_array(strval($item->id), $tierItemIds, true)
                        || in_array('emp_' . $item->id, $tierItemIds, true)) {
                        return true;
                    }
                }

                // fallback: tier เก็บแบบ emp_{employee_id} หรือ employee_id ตรงๆ
                return in_array('emp_' . $empId, $tierItemIds, true)
                    || in_array(strval($empId), $tierItemIds, true);
            });

            if (($tier || $hasDefaultTier) && $statuses[$empId] === 'none') {
                $statuses[$empId] = 'priced';
            }
        }
    }

    /**
     * โหมด lump_sum: ใช้ selected_item_ids ถ้าระบุไว้ ไม่งั้นให้ทุกคนใน order เป็น priced
     */
    private static function markLumpSumPriced(array $ids, array $itemToEmployeeMap, array $groupData, array &$statuses): void
    {
        $selectedItems = $groupData['selected_item_ids'] ?? [];

        if (!empty($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $empId = $itemToEmployeeMap[$itemId] ?? null;
                if ($empId && in_array($empId, $ids) && $statuses[$empId] === 'none') {
                    $statuses[$empId] = 'priced';
                }
            }
            return;
        }

        // ไม่มี selected_item_ids → apply ทุกคนใน order
        foreach ($ids as $empId) {
            if ($statuses[$empId] === 'none') {
                $statuses[$empId] = 'priced';
            }
        }
    }

    /**
     * Manual bill: ลูกจ้าง priced ตาม items[].employee_ids ใน groupData (ไม่ผ่าน pricing tier)
     */
    private static function markManualBillPriced(array $ids, array $groupData, array &$statuses): void
    {
        $manualItems = $groupData['items'] ?? [];
        $pricedEmpIds = [];

        foreach ($manualItems as $mItem) {
            if (!empty($mItem['employee_ids'])) {
                foreach ($mItem['employee_ids'] as $eId) {
                    $pricedEmpIds[] = $eId;
                }
            }
        }

        foreach (array_unique($pricedEmpIds) as $empId) {
            if (in_array($empId, $ids) && $statuses[$empId] === 'none') {
                $statuses[$empId] = 'priced';
            }
        }
    }

    /**
     * Phase 2: รวบรวม transactions ที่เกี่ยวกับลูกจ้างแต่ละคน
     * คืน [empId => [transaction1, transaction2, ...]]
     */
    private static function collectEmployeeTransactions(ProductionOrder $order, array $ids): array
    {
        $employeeTransactions = [];

        foreach ($order->financialGroups as $group) {
            foreach ($group->transactions as $transaction) {
                foreach ($transaction->items as $item) {
                    $empId = $item->employee_id;
                    if ($empId && in_array($empId, $ids)) {
                        $employeeTransactions[$empId][] = $transaction;
                    }
                }
            }
        }

        return $employeeTransactions;
    }

    /**
     * Phase 3: ประเมิน status สุดท้ายจาก transactions
     *  - ทุก txn จ่ายครบ                                    → 'paid'
     *  - มีการจ่ายบางส่วน (status=paid/partial หรือ paid_amount>0) → 'partial'
     *  - มี txn แต่ยังไม่มีการจ่ายเลย                        → 'installment_created'
     */
    private static function evaluatePaymentStatuses(array $employeeTransactions, array &$statuses): void
    {
        foreach ($employeeTransactions as $empId => $transactions) {
            if (empty($transactions)) continue;

            $allPaid = true;
            $anyPaidOrPartial = false;

            foreach ($transactions as $txn) {
                if ($txn->status === 'paid') {
                    $anyPaidOrPartial = true;
                } elseif ($txn->status === 'partial' || $txn->paid_amount > 0) {
                    $anyPaidOrPartial = true;
                    $allPaid = false;
                } else {
                    // pending หรือ overdue และยังไม่มีการจ่าย
                    $allPaid = false;
                }
            }

            if ($allPaid) {
                $statuses[$empId] = 'paid';
            } elseif ($anyPaidOrPartial) {
                $statuses[$empId] = 'partial';
            } else {
                $statuses[$empId] = 'installment_created';
            }
        }
    }
}
