<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminJobTicketRequest;
use App\Models\JobTicket;
use App\Models\TicketMessage;
use App\Models\User;
// V2.5-PATCH: ไม่ต้องการ Employee Model ที่นี่
// use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
// V2.5-PATCH: เพิ่ม Log และ Exception
use Illuminate\Support\Facades\Log;
use Exception;


class AdminJobTicketController extends Controller
{
    /**
     * Show the form for creating a new job ticket by an admin.
     */
    public function create(): View
    {
        $employers = User::role('employer')
            ->with('employer')
            ->whereHas('employer')
            ->get()
            ->sortBy('employer.employerNameTh');

        return view('admin.tickets.create', compact('employers'));
    }

    /**
     * Store a new job ticket created by an admin.
     * V2.5-PATCH: เขียนทับ Store Method ทั้งหมดให้ใช้โลจิกเดียวกับ TicketController@store
     */
    public function store(StoreAdminJobTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $adminUser = Auth::user();
        $attachments = $validated['attachments'] ?? [];
        
        $movedFiles = [];
        $storageDisk = 'public';

        try {
            DB::beginTransaction();

            // 1. Create the Job Ticket
            $ticket = JobTicket::create([
                'employer_user_id' => $validated['employer_user_id'], // ผู้ใช้นายจ้างที่ Admin เลือก
                'subject' => $validated['subject'],
                'status' => 'pending_staff', // สถานะเริ่มต้น
                'assigned_staff_id' => $adminUser->id, // มอบหมายให้ Admin ที่สร้างตั๋วนี้
                'employer_unread_count' => 1, // Admin creates -> Employer has 1 unread message
            ]);

            // V2.6: Un-hide any previously hidden tickets for this employer
            JobTicket::where('employer_user_id', $validated['employer_user_id'])
                ->whereNotNull('hidden_by_admin_at')
                ->update(['hidden_by_admin_at' => null]);

            // Define the permanent storage directory for this ticket
            $permanentBasePath = "ticket_attachments/{$ticket->id}";

            // 2. Create the Initial Message (Admin's message)
            // (StoreTicketRequest บังคับให้ message ต้องมี)
            $ticket->messages()->create([
                'user_id' => $adminUser->id, // ผู้สร้างข้อความคือ Admin
                'message_type' => 'comment',
                'body' => $validated['message'],
            ]);

            // --- V2.5-PATCH: Attachment Processing Logic (เหมือน TicketController@store) ---

            // 3. Process General Files (attachment_file)
            if (!empty($attachments['files'])) {
                foreach ($attachments['files'] as $fileData) {
                    $tempPath = $fileData['path'];
                    $permanentPath = $permanentBasePath . '/files/' . basename($tempPath);

                    if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) {
                        $movedFiles[] = $permanentPath;
                        $ticket->messages()->create([
                            'user_id' => $adminUser->id, // Admin คือผู้แนบ
                            'message_type' => 'attachment_file',
                            'body' => json_encode([
                                'path' => $permanentPath,
                                'name' => $fileData['name'],
                                'size' => $fileData['size'],
                            ]),
                        ]);
                    } else {
                        throw new Exception("Failed to move file from {$tempPath} to {$permanentPath}");
                    }
                }
            }

            // 4. Process Existing Employees (attachment_employee)
            if (!empty($attachments['existing_employees'])) {
                $messagesToInsert = [];
                foreach ($attachments['existing_employees'] as $employeeId) {
                    $messagesToInsert[] = [
                        'job_ticket_id' => $ticket->id,
                        'user_id' => $adminUser->id, // Admin คือผู้แนบ
                        'message_type' => 'attachment_employee',
                        'body' => $employeeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                TicketMessage::insert($messagesToInsert);
            }

            // 5. Process External Employees (attachment_employee - V2.5-S17)
            // Stored as regular employee attachments, categorization happens in the Model/View
            if (!empty($attachments['external_employees'])) {
                $messagesToInsert = [];
                foreach ($attachments['external_employees'] as $employeeId) {
                    $messagesToInsert[] = [
                        'job_ticket_id' => $ticket->id,
                        'user_id' => $adminUser->id, // Admin is attacher
                        'message_type' => 'attachment_employee', // Same type
                        'body' => $employeeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                TicketMessage::insert($messagesToInsert);
            }

            // 6. Process New Employees (attachment_new_employee)
            if (!empty($attachments['new_employees'])) {
                // Define all possible file fields that need to be processed
                $fileFields = [
                    'employeePhoto',
                    'insurance_document_path_social',
                    'insurance_document_path_hospital',
                    'insurance_document_path_private',
                    'employee_doc_1', 'employee_doc_2', 'employee_doc_3',
                    'employee_doc_4', 'employee_doc_5', 'employee_doc_6',
                    'employee_doc_7', 'employee_doc_8', 'employee_doc_9',
                    'employee_doc_10', 'employee_doc_11', 'employee_doc_12'
                ];

                // V2.5-PATCH: $validated['attachments']['new_employees'] คือ array of arrays (ไม่ใช่ JSON string)
                foreach ($attachments['new_employees'] as $newEmployeeData) {
                    
                    // V2.5-FIX: Double check if data is string (defensive coding against request modification failure)
                    if (is_string($newEmployeeData)) {
                        $decoded = json_decode($newEmployeeData, true);
                        $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
                    } else {
                        $data = $newEmployeeData;
                    }

                    // Skip if data is invalid
                    if (empty($data) || !is_array($data)) {
                        continue;
                    }

                    foreach ($fileFields as $field) {
                        if (isset($data[$field]) && $data[$field] && str_starts_with($data[$field], 'temp_uploads/')) {
                            $tempPath = $data[$field];
                            $permanentPath = $permanentBasePath . '/new_employees/' . basename($tempPath);

                            if (Storage::disk($storageDisk)->exists($tempPath)) {
                                if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) {
                                    $movedFiles[] = $permanentPath;
                                    $data[$field] = $permanentPath; // อัปเดต path ใน array
                                } else {
                                    throw new Exception("Failed to move new employee file from {$tempPath} to {$permanentPath}");
                                }
                            } else {
                                $data[$field] = null;
                            }
                        }
                    }

                    // Create the message record with the updated JSON
                    $ticket->messages()->create([
                        'user_id' => $adminUser->id, // Admin คือผู้แนบ
                        'message_type' => 'attachment_new_employee',
                        'body' => json_encode($data), // บันทึก array (ที่อัปเดต path แล้ว) เป็น JSON
                    ]);
                }
            }

            // --- End of Attachment Processing ---

            DB::commit();

            return redirect()->route('admin.tickets.show', $ticket->id)
                ->with('success', 'สร้างตั๋วงานสำหรับ ' . $ticket->employerUser->name . ' เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            if (!empty($movedFiles)) {
                Storage::disk('public')->delete($movedFiles);
            }
            // V2.5-PATCH: เพิ่ม Log
            Log::error('Admin Ticket creation failed (V2.5 Patch): ' . $e->getMessage(), [
                'user_id' => $adminUser->id,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with('danger', 'Failed to create ticket: ' . $e->getMessage());
        }
    }

    // V2.5-PATCH: ลบเมธอด processAttachments() และ moveAttachment() ที่ผิดพลาดทิ้งทั้งหมด
}
