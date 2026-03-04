<?php

namespace App\Services;

use App\Models\ProductionOrder;
use App\Models\Employee;
use Illuminate\Support\Collection;

class FinancialStatusService
{
    /**
     * Calculate financial status for a list of employees in the context of a specific ProductionOrder.
     *
     * @param ProductionOrder|null $order The active production order for the current context (menu/workflow).
     * @param Collection|array $employeeIds A collection or array of employee IDs to calculate status for.
     * @return array Associative array mapping employee_id to their financial status.
     *               Possible statuses: 'none', 'priced', 'installment_created', 'partial', 'paid'
     */
    public static function calculateStatusForEmployees(?ProductionOrder $order, $employeeIds): array
    {
        $statuses = [];
        // Extract array of integer IDs
        if ($employeeIds instanceof Collection) {
            $ids = $employeeIds->map(fn($item) => is_object($item) ? $item->id : $item)->toArray();
        } else {
            $ids = is_array($employeeIds) ? array_map(fn($item) => is_object($item) ? $item->id : $item, $employeeIds) : (array)$employeeIds;
        }

        foreach ($ids as $id) {
            $statuses[$id] = 'none'; // Default status
        }

        if (!$order) {
            return $statuses;
        }

        // Preload relationships to avoid N+1 queries
        $order->loadMissing('financialGroups.transactions.items', 'items.employee');

        $itemToEmployeeMap = $order->items->pluck('employee_id', 'id')->toArray();
        $employeeToItemMap = $order->items->pluck('id', 'employee_id')->toArray();

        foreach ($order->financialGroups as $group) {
            $groupData = is_array($group->financial_data) ? $group->financial_data : json_decode($group->financial_data, true);

            $pricingMode = $groupData['pricing_mode'] ?? 'lump_sum';
            $pricingTiers = collect($groupData['pricing_tiers'] ?? []);

            // 1. Determine which employees are priced in this group
            if ($pricingMode === 'per_head') {
                foreach ($ids as $empId) {
                    $itemId = $employeeToItemMap[$empId] ?? null;
                    if (!$itemId) continue;

                    // Find if the item/employee is assigned to any tier
                    $tier = $pricingTiers->first(function ($t) use ($itemId, $empId) {
                        $itemIds = array_map('strval', $t['item_ids'] ?? []);
                        return in_array(strval($itemId), $itemIds, true) ||
                               in_array('emp_' . $itemId, $itemIds, true) ||
                               in_array('emp_' . $empId, $itemIds, true) ||
                               in_array(strval($empId), $itemIds, true);
                    });

                    // If assigned to a tier (or there's a default tier), they are 'priced'
                    if ($tier || $pricingTiers->contains(fn($t) => empty($t['item_ids']) || ($t['name'] ?? '') === 'Default Tier')) {
                        if ($statuses[$empId] === 'none') {
                            $statuses[$empId] = 'priced';
                        }
                    }
                }
            } else {
                // lump_sum applies to everyone in the order (or those selected for the group)
                $selectedItems = $groupData['selected_item_ids'] ?? [];
                if (!empty($selectedItems)) {
                    foreach ($selectedItems as $itemId) {
                        $empId = $itemToEmployeeMap[$itemId] ?? null;
                        if ($empId && in_array($empId, $ids) && $statuses[$empId] === 'none') {
                            $statuses[$empId] = 'priced';
                        }
                    }
                } else {
                    // Applies to all items in the order
                    foreach ($ids as $empId) {
                        if ($statuses[$empId] === 'none') {
                            $statuses[$empId] = 'priced';
                        }
                    }
                }
            }

            // For manual bills (work_type_id is null), the pricing logic is usually directly on the items
            if ($order->work_type_id === null) {
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
        }

        // 2. Map Transactions to Employees
        $employeeTransactions = [];

        foreach ($order->financialGroups as $group) {
            foreach ($group->transactions as $transaction) {
                foreach ($transaction->items as $item) {
                    $empId = $item->employee_id;
                    if ($empId && in_array($empId, $ids)) {
                        if (!isset($employeeTransactions[$empId])) {
                            $employeeTransactions[$empId] = [];
                        }
                        $employeeTransactions[$empId][] = $transaction;
                    }
                }
            }
        }

        // 3. Evaluate 'installment_created', 'partial', 'paid'
        foreach ($employeeTransactions as $empId => $transactions) {
            if (empty($transactions)) {
                // Should not happen as we only added transactions, but safety check
                continue;
            }

            $allPaid = true;
            $anyPaidOrPartial = false;

            foreach ($transactions as $txn) {
                if ($txn->status === 'paid') {
                    $anyPaidOrPartial = true;
                } elseif ($txn->status === 'partial' || $txn->paid_amount > 0) {
                    $anyPaidOrPartial = true;
                    $allPaid = false;
                } else {
                    // pending or overdue, and 0 paid
                    $allPaid = false;
                }
            }

            if ($allPaid) {
                $statuses[$empId] = 'paid';
            } elseif ($anyPaidOrPartial) {
                $statuses[$empId] = 'partial';
            } else {
                // Transactions exist, but none are paid or partial
                $statuses[$empId] = 'installment_created';
            }
        }

        return $statuses;
    }
}
