<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SendDailyExpiryReport extends Command
{
    protected $signature = 'app:send-daily-expiry-report';
    protected $description = 'Send a daily summary of expiring documents to the Community Chat';

    public function handle()
    {
        $this->info('Starting Daily Expiry Report...');

        // 1. Ensure fresh data (optional, but requested "re-check")
        // We call the existing check-expiries command
        $this->call('app:check-expiries');

        // 2. Aggregate Notifications
        $stats = Notification::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->get()
            ->pluck('total', 'type');

        if ($stats->isEmpty()) {
            $this->info('No notifications found. Skipping report.');
            return;
        }

        // 3. Format Message
        $today = Carbon::now()->format('d/m/Y');
        $message = "📢 **รายงานเอกสารหมดอายุประจำวัน ($today)**\n\n";

        $typeMap = [
            'passport_expiry' => 'หนังสือเดินทาง (Passport)',
            'ci_renewal' => 'CI Renewal',
            'work_permit_mou' => 'Work Permit (MOU)',
            'resolution_renewal' => 'มติ ครม. (Resolution)',
            'new_registration_renewal' => 'ขึ้นทะเบียนใหม่',
            'visa_expiry' => 'วีซ่า (Visa)',
            'ninety_day_report' => 'รายงานตัว 90 วัน',
            'pink_card_missing' => 'ยังไม่มีบัตรชมพู',
            'residence_permit_missing' => 'ยังไม่มีใบแจ้งที่พักอาศัย',
            'employer_document_expiry' => 'เอกสารนายจ้าง',
            'employee_insurance_expiry' => 'ประกันลูกจ้าง',
        ];

        $totalCount = 0;
        foreach ($stats as $type => $count) {
            $label = $typeMap[$type] ?? $type;
            $message .= "🔹 **{$label}**: {$count} รายการ\n";
            $totalCount += $count;
        }

        $message .= "\n⚠️ **รวมทั้งหมด: {$totalCount} รายการ**";
        $message .= "\n\n(กรุณาตรวจสอบรายละเอียดในเมนู Notifications)";

        // 4. Find Community Group
        $communityGroup = ChatGroup::where('type', 'community')->first();

        if (!$communityGroup) {
             $this->error('Community Chat Group not found!');
             return;
        }

        // 5. Send Message (as System Admin or Bot)
        // Assuming ID 1 is Admin, or we create a Bot user. Let's use ID 1 for now.
        $senderId = 1;

        ChatMessage::create([
            'chat_group_id' => $communityGroup->id,
            'sender_id' => $senderId,
            'message' => $message,
            'is_read' => false,
        ]);

        $this->info('Report sent successfully to Community Chat.');
    }
}
