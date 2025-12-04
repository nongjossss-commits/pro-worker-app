<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketStatusController extends Controller
{
    /**
     * Mark the specified ticket as Resolved.
     */
    public function resolve(JobTicket $ticket)
    {
        // 1. ตรวจสอบสิทธิ์ (เผื่อไว้)
        if (!Auth::user()->can('manage-tickets')) {
            abort(403, 'Unauthorized action.');
        }

        // 2. อัปเดตสถานะ
        $ticket->update(['status' => 'resolved']);

        // 3. (ทางเลือก) เพิ่มข้อความ System ลงในแชท (ตามพิมพ์เขียว)
        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message_type' => 'system_activity',
            'body' => 'Ticket marked as Resolved by ' . Auth::user()->name,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Ticket marked as Resolved.');
    }

    /**
     * Mark the specified ticket as Rejected.
     */
    public function reject(JobTicket $ticket)
    {
        // 1. ตรวจสอบสิทธิ์
        if (!Auth::user()->can('manage-tickets')) {
            abort(403, 'Unauthorized action.');
        }

        // 2. อัปเดตสถานะ
        $ticket->update(['status' => 'rejected']);

        // 3. (ทางเลือก) เพิ่มข้อความ System ลงในแชท
        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message_type' => 'system_activity',
            'body' => 'Ticket marked as Rejected by ' . Auth::user()->name,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Ticket marked as Rejected.');
    }

    /**
     * Mark the specified ticket as In Progress (Forwarded to Workflow).
     */
    public function forward(JobTicket $ticket)
    {
        if (!Auth::user()->can('manage-tickets')) {
            abort(403, 'Unauthorized action.');
        }

        // 1. อัปเดตสถานะเป็น in_progress
        $ticket->update(['status' => 'in_progress']);

        // 2. เพิ่มข้อความ System ลงในแชท
        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message_type' => 'system_activity',
            'body' => 'Ticket status changed to In Progress (Forwarded) by ' . Auth::user()->name,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Ticket forwarded to workflow.');
    }

    /**
     * Mark the specified ticket as In Progress (Accept Job).
     */
    public function inProgress(JobTicket $ticket)
    {
        if (!Auth::user()->can('manage-tickets')) {
            abort(403, 'Unauthorized action.');
        }

        // 1. อัปเดตสถานะเป็น in_progress
        $ticket->update(['status' => 'in_progress']);

        // 2. เพิ่มข้อความ System ลงในแชท
        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message_type' => 'system_activity',
            'body' => 'Ticket accepted (In Progress) by ' . Auth::user()->name,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Ticket status changed to In Progress.');
    }
}
