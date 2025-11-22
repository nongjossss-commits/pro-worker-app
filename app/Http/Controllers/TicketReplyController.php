<?php

// app/Http/Controllers/TicketReplyController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketReplyRequest;
use App\Models\JobTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class TicketReplyController extends Controller
{
/**
* Store a newly created reply in storage.
*/
public function store(StoreTicketReplyRequest $request, JobTicket $ticket): RedirectResponse
{
$user = Auth::user();
// V2.4-S11: Validation handles the complex structure. Data is prepared (JSON decoded) by the Request.
$validated = $request->validated();

// --- Authorization and Status Check (No Changes) ---
if (in_array($ticket->status, ['resolved', 'rejected'])) {
return back()->with('error', 'ไม่สามารถตอบกลับได้ เนื่องจากตั๋วงานนี้ถูกปิดแล้ว');
}

$isStaff = $user->can('manage-tickets');
$isOwner = $ticket->employer_user_id === $user->id;

if (!$isStaff && !$isOwner) {
abort(403, 'Unauthorized action.');
}

// --- V2.4-S11: Processing Logic (Transaction & File Management) ---
$attachments = $validated['attachments'] ?? [];
$movedFiles = [];
$storageDisk = 'public';
$permanentBasePath = "ticket_attachments/{$ticket->id}";

try {
DB::beginTransaction();

// 1. Process Message (comment)
if (!empty($validated['message'])) {
$ticket->messages()->create([
'user_id' => $user->id,
'message_type' => 'comment',
'body' => $validated['message'],
]);
}

// 2. Process File Attachments (attachment_file) (Existing Logic)
if (!empty($attachments['files'])) {
foreach ($attachments['files'] as $fileData) {
$tempPath = $fileData['path'];
$permanentPath = $permanentBasePath . '/files/' . basename($tempPath);

if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) {
$movedFiles[] = $permanentPath;
$ticket->messages()->create([
'user_id' => $user->id,
'message_type' => 'attachment_file',
'body' => json_encode([
'path' => $permanentPath,
'name' => $fileData['name'],
'size' => $fileData['size'],
]),
]);
} else {
throw new Exception("Failed to move reply file from {$tempPath} to {$permanentPath}");
}
}
}

// V2.4-S11: 3. Process Existing Employees (attachment_employee) (New Logic)
if (!empty($attachments['existing_employees'])) {
foreach ($attachments['existing_employees'] as $employeeId) {
$ticket->messages()->create([
'user_id' => $user->id,
'message_type' => 'attachment_employee',
'body' => $employeeId, // Store just the ID
]);
}
}

// V2.5-S17: Process External Employees (attachment_employee)
// Similar to existing employees, but these come from global search
if (!empty($attachments['external_employees'])) {
foreach ($attachments['external_employees'] as $employeeId) {
$ticket->messages()->create([
'user_id' => $user->id,
'message_type' => 'attachment_employee', // Same type as affiliated employees
'body' => $employeeId,
]);
}
}

// V2.4-S11: 4. Process New Employees (attachment_new_employee) (New Logic)
if (!empty($attachments['new_employees'])) {
// Data is already decoded arrays thanks to StoreTicketReplyRequest preparation.
foreach ($attachments['new_employees'] as $index => $newEmployeeData) {

// Use Passport as a subfolder identifier for organization
$employeeIdentifier = preg_replace('/[^A-Za-z0-9\-]/', '_', $newEmployeeData['employeePassport'] ?? "Index_{$index}");

// 4a. Move associated files
$fileFields = [
    'employeePhoto',
    'insurance_document_path_social',
    'insurance_document_path_hospital',
    'insurance_document_path_private'
];
for ($i = 1; $i <= 12; $i++) {
    $fileFields[] = 'employee_doc_' . $i;
}

foreach ($fileFields as $field) {
if (!empty($newEmployeeData[$field])) {
$tempPath = $newEmployeeData[$field];
// Use 'new_employees' subdirectory and include the identifier
$permanentPath = $permanentBasePath . "/new_employees/{$employeeIdentifier}/" . basename($tempPath);

// Ensure the directory exists before moving (Robustness)
Storage::disk($storageDisk)->makeDirectory(dirname($permanentPath));

if (Storage::disk($storageDisk)->move($tempPath, $permanentPath)) {
$movedFiles[] = $permanentPath;
// Update the path in the data array
$newEmployeeData[$field] = $permanentPath;
} else {
throw new Exception("Failed to move new employee file ({$field}) from {$tempPath} to {$permanentPath}");
}
}
}

// 4b. Create the message record
$ticket->messages()->create([
'user_id' => $user->id,
'message_type' => 'attachment_new_employee',
// Store the updated data (with permanent paths) as JSON
'body' => json_encode($newEmployeeData),
]);
}
}


// 5. Workflow Automation: Update Ticket Status & Unread Counts
$newStatus = $isStaff ? 'pending_employer' : 'pending_staff';

$updateData = ['status' => $newStatus];

if ($isStaff) {
    // Staff replied -> Increment Employer's unread count
    $updateData['employer_unread_count'] = DB::raw('employer_unread_count + 1');
} else {
    // Employer replied -> Increment Admin's unread count
    $updateData['admin_unread_count'] = DB::raw('admin_unread_count + 1');
}

$ticket->update($updateData);

DB::commit();

return back()->with('success', 'ส่งข้อความตอบกลับเรียบร้อยแล้ว');

} catch (Exception $e) {
DB::rollBack();

// Cleanup moved files on failure
if (count($movedFiles) > 0) {
Log::warning('Ticket reply transaction failed (S11). Cleaning up moved files.', ['files' => $movedFiles]);
Storage::disk($storageDisk)->delete($movedFiles);
}

Log::error('Ticket reply failed (S11): ' . $e->getMessage(), ['user_id' => $user->id, 'ticket_id' => $ticket->id, 'trace' => $e->getTraceAsString()]);

// Redirect back with input data restored (Alpine restoreOldInput handles restoration)
return back()->withInput()->with('error', 'เกิดข้อผิดพลาดในการส่งข้อความตอบกลับ กรุณาลองใหม่อีกครั้ง. (Error: ' . $e->getMessage() . ')');
}
}
}
