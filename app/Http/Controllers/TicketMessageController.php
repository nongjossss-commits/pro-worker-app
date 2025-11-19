<?php

namespace App\Http\Controllers;

use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TicketMessageController extends Controller
{
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TicketMessage  $message
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TicketMessage $message)
    {
        $ticket = $message->ticket;
        $isClosed = in_array($ticket->status, ['resolved', 'rejected']);
        $user = Auth::user();

        // Authorization Check
        if ($isClosed) {
            return back()->with('error', 'ไม่สามารถลบข้อความได้ เนื่องจากตั๋วงานนี้ปิดไปแล้ว');
        }

        if (!($user->id === $message->user_id || $user->can('manage-tickets'))) {
             return back()->with('error', 'คุณไม่มีสิทธิ์ลบข้อความนี้');
        }

        // Handle file deletion if the message is a file attachment
        if ($message->message_type === 'attachment_file' || $message->message_type === 'attachment_new_employee') {
            $data = json_decode($message->body);
            if ($data) {
                $pathsToDelete = [];

                if ($message->message_type === 'attachment_file' && isset($data->path)) {
                    $pathsToDelete[] = $data->path;
                }

                if ($message->message_type === 'attachment_new_employee') {
                    if (isset($data->employeePhoto)) {
                        $pathsToDelete[] = $data->employeePhoto;
                    }
                    if (isset($data->document_1)) {
                        $pathsToDelete[] = $data->document_1;
                    }
                }

                foreach ($pathsToDelete as $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        }

        $message->delete();

        return back()->with('success', 'ลบรายการสำเร็จ');
    }
}
