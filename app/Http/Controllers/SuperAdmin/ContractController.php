<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ServiceContract;
use App\Models\ServiceContractExtension;
use App\Services\ContractStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Super Admin CRUD for the system rental/service contract.
 *
 * Endpoints:
 *   - saveContract()       : create or update the current contract row
 *   - enableGrace()        : open temporary access for N days from today
 *                            (or extend current grace by N more days)
 *   - stopGrace()          : end grace immediately
 *   - uploadAttachment()   : store PDF/JPG/PNG for one of 3 slots
 *   - deleteAttachment()   : remove file from a slot
 *   - downloadAttachment() : stream a stored file back to the SA
 *
 * All contract mutations bust the ContractStatusService cache so the
 * banner + middleware pick up the new state on the very next request.
 */
class ContractController extends Controller
{
    private const ATTACHMENT_DISK = 'public';
    private const ATTACHMENT_FOLDER = 'service_contracts';
    private const MAX_SIZE_KB = 10240; // 10 MB
    private const ALLOWED_MIMES = 'pdf,jpg,jpeg,png';

    public function saveContract(Request $request)
    {
        $data = $request->validate([
            'contract_type' => 'required|in:service,trial',
            'customer_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $contract = ServiceContract::current();
        $isNew = false;

        if (!$contract) {
            $contract = new ServiceContract();
            $isNew = true;
        }

        // If the SA moves end_date forward past today, this counts as a
        // renewal — log it so the audit trail records who renewed.
        $previousEnd = $contract->end_date?->format('Y-m-d');

        $contract->fill($data);

        // Extending end_date past current grace makes grace redundant — clear it.
        if ($contract->grace_end_date && $contract->end_date
            && $contract->end_date->greaterThanOrEqualTo($contract->grace_end_date)) {
            $contract->grace_end_date = null;
        }

        $contract->save();

        if (!$isNew && $previousEnd !== $data['end_date']) {
            ServiceContractExtension::create([
                'service_contract_id' => $contract->id,
                'extended_by_user_id' => $request->user()->id,
                'action' => 'contract_renewed',
                'previous_end' => $previousEnd,
                'new_end' => $data['end_date'],
                'days_added' => $previousEnd
                    ? (int) Carbon::parse($previousEnd)->diffInDays(Carbon::parse($data['end_date']), false)
                    : null,
                'reason' => 'บันทึกสัญญา / ต่ออายุจากหน้า Super Admin',
            ]);
        }

        ContractStatusService::forgetCache();
        return back()->with('success', $isNew ? 'บันทึกสัญญาใหม่เรียบร้อย' : 'อัพเดตสัญญาเรียบร้อย');
    }

    public function enableGrace(Request $request)
    {
        $data = $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'nullable|string|max:1000',
        ]);

        // $request->validate() returns raw request strings even when the rule
        // is `integer`. Cast explicitly so Carbon::addDays() (int|float) and
        // the days_added column both receive a real int.
        $days = (int) $data['days'];

        $contract = ServiceContract::current();
        if (!$contract) {
            return back()->with('error', 'ต้องบันทึกข้อมูลสัญญาก่อนจึงจะเปิดใช้ชั่วคราวได้');
        }

        $today = now()->startOfDay();
        $baseline = $contract->grace_end_date && $contract->grace_end_date->greaterThan($today)
            ? $contract->grace_end_date
            : ($contract->end_date && $contract->end_date->greaterThan($today) ? $contract->end_date : $today);

        $previous = $contract->grace_end_date?->format('Y-m-d');
        $newGrace = $baseline->copy()->addDays($days);
        $contract->grace_end_date = $newGrace;
        $contract->save();

        ServiceContractExtension::create([
            'service_contract_id' => $contract->id,
            'extended_by_user_id' => $request->user()->id,
            'action' => $previous ? 'grace_extended' : 'grace_enabled',
            'previous_end' => $previous,
            'new_end' => $newGrace->format('Y-m-d'),
            'days_added' => $days,
            'reason' => $data['reason'] ?? null,
        ]);

        ContractStatusService::forgetCache();
        return back()->with('success', 'เปิดใช้งานชั่วคราวถึง ' . $newGrace->format('d/m/Y'));
    }

    public function stopGrace(Request $request)
    {
        $contract = ServiceContract::current();
        if (!$contract || !$contract->grace_end_date) {
            return back()->with('error', 'ไม่มีการเปิดใช้ชั่วคราวที่ต้องปิด');
        }

        $previous = $contract->grace_end_date->format('Y-m-d');
        $contract->grace_end_date = null;
        $contract->save();

        ServiceContractExtension::create([
            'service_contract_id' => $contract->id,
            'extended_by_user_id' => $request->user()->id,
            'action' => 'grace_stopped',
            'previous_end' => $previous,
            'new_end' => null,
            'days_added' => null,
            'reason' => $request->input('reason'),
        ]);

        ContractStatusService::forgetCache();
        return back()->with('success', 'ปิดโหมดชั่วคราวเรียบร้อย — ระบบยึดตามวันสิ้นสุดสัญญาจริง');
    }

    public function uploadAttachment(Request $request, int $slot)
    {
        abort_unless(in_array($slot, [1, 2, 3], true), 404);

        $request->validate([
            'file' => 'required|file|mimes:' . self::ALLOWED_MIMES . '|max:' . self::MAX_SIZE_KB,
        ]);

        $contract = ServiceContract::current() ?? new ServiceContract([
            'contract_type' => 'trial',
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]);
        if (!$contract->exists) $contract->save();

        // Replace old file if any
        $pathAttr = "attachment_{$slot}_path";
        if ($contract->{$pathAttr}) {
            Storage::disk(self::ATTACHMENT_DISK)->delete($contract->{$pathAttr});
        }

        $file = $request->file('file');
        $filename = Str::random(16) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs(self::ATTACHMENT_FOLDER . '/' . $contract->id, $filename, self::ATTACHMENT_DISK);

        $contract->{$pathAttr} = $path;
        $contract->{"attachment_{$slot}_original"} = $file->getClientOriginalName();
        $contract->{"attachment_{$slot}_uploaded_at"} = now();
        $contract->save();

        ContractStatusService::forgetCache();
        return back()->with('success', "อัพโหลดไฟล์แนบช่องที่ {$slot} เรียบร้อย");
    }

    public function deleteAttachment(Request $request, int $slot)
    {
        abort_unless(in_array($slot, [1, 2, 3], true), 404);

        $contract = ServiceContract::current();
        if (!$contract) return back();

        $pathAttr = "attachment_{$slot}_path";
        if ($contract->{$pathAttr}) {
            Storage::disk(self::ATTACHMENT_DISK)->delete($contract->{$pathAttr});
        }
        $contract->{$pathAttr} = null;
        $contract->{"attachment_{$slot}_original"} = null;
        $contract->{"attachment_{$slot}_uploaded_at"} = null;
        $contract->save();

        return back()->with('success', "ลบไฟล์แนบช่องที่ {$slot} เรียบร้อย");
    }

    public function downloadAttachment(Request $request, int $slot)
    {
        abort_unless(in_array($slot, [1, 2, 3], true), 404);

        $contract = ServiceContract::current();
        abort_unless($contract, 404);

        $path = $contract->{"attachment_{$slot}_path"};
        $original = $contract->{"attachment_{$slot}_original"} ?? "contract_slot_{$slot}";
        abort_unless($path && Storage::disk(self::ATTACHMENT_DISK)->exists($path), 404);

        return Storage::disk(self::ATTACHMENT_DISK)->download($path, $original);
    }
}
