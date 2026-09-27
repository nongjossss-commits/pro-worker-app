<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Employer;
use App\Models\EmployeeAppointment;
use App\Models\EmployeeRequestNumber;
use App\Models\EmployeeTeamAssignment;
use App\Models\Notification;
use App\Models\ProductionItem;
use App\Models\ResolutionTab;
use App\Models\SystemConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the data an employee / employer card sends outside the program
 * (drag into LINE etc., copy as text, copy/share as a card image).
 *
 * Only the fields an admin allowed (Admin → "Share settings", stored in
 * SystemConfig 'share_fields') ever leave the server — the name is the only
 * field that is always included, so the receiver knows who it is.
 */
class ShareCardService
{
    public const CONFIG_KEY = 'share_fields';

    /** key => [label (translation key), emoji] — order is the order shown. */
    public const EMPLOYEE_FIELDS = [
        'photo' => ['Photo', ''],
        'name_th' => ['Name (TH)', '👤'],
        'nationality' => ['Nationality', '🌏'],
        'dob' => ['Date of Birth', '🎂'],
        'employer' => ['Employer', '🏢'],
        'job_title' => ['Job Title', '💼'],
        'phone' => ['Phone', '📞'],
        'email' => ['Email', '✉️'],
        'passport_no' => ['Passport No.', '🛂'],
        'passport_expiry' => ['Passport Expiry', '🛂'],
        'visa_type' => ['Visa Type', '🛃'],
        'visa_expiry' => ['Visa Expiry', '🛃'],
        'work_permit_no' => ['Work Permit No.', '📄'],
        'work_permit_expiry' => ['Work Permit Expiry', '📄'],
        'mou_group' => ['Work Permit Type', '📄'],
        'ninety_day' => ['90-Day Report', '📅'],
        'pink_card' => ['Pink Card No.', '🪪'],
        'name_list_number' => ['RA No. (outsource)', '📋'],
        'request_number' => ['Request No.', '📋'],
        'reference_id' => ['Reference ID', '🔖'],
        'outsource_code' => ['Outsource Code', '🔑'],
        'employer_employee_id' => ['Employer-Employee ID', '🔖'],
        'id_number' => ['ID Number', '🆔'],
        'tax_id_number' => ['Tax ID', '🧾'],
        'social_security' => ['Social Security No.', '🏥'],
        'insurance_type' => ['Insurance Type', '🏥'],
        'start_date' => ['Start Date', '🗓️'],
        'appointment' => ['Appointment', '📆'],
        'team' => ['Team', '👥'],
        'remarks' => ['Remarks', '📝'],
    ];

    /**
     * Fields whose value depends on the menu the card was shared from
     * (request number, appointment, team, remarks) — see contextValues().
     */
    public const CONTEXT_FIELDS = ['request_number', 'appointment', 'team', 'remarks'];

    public const EMPLOYER_FIELDS = [
        'name_en' => ['Name (EN)', '🏢'],
        'employer_id' => ['Employer ID', '🆔'],
        'tax_id' => ['Employer Tax ID', '🧾'],
        're_code' => ['RE Code', '🔖'],
        'business_type' => ['Business Type', '🏭'],
        'signer' => ['Authorized Signatory', '✍️'],
        'phone' => ['Phone', '📞'],
        'email' => ['Email', '✉️'],
        'address' => ['Address', '📍'],
        'employee_count' => ['Employees', '👥'],
    ];

    /** What a fresh install shares until an admin changes it. */
    public const DEFAULTS = [
        'employee' => ['photo', 'name_th', 'nationality', 'dob', 'employer', 'passport_no', 'passport_expiry', 'visa_expiry', 'work_permit_expiry'],
        'employer' => ['name_en', 'employer_id', 'phone', 'address'],
    ];

    /** @return array{employee: string[], employer: string[]} */
    public static function allowedFields(): array
    {
        $saved = json_decode((string) optional(SystemConfig::where('key', self::CONFIG_KEY)->first())->value, true);
        $out = [];
        foreach (['employee' => self::EMPLOYEE_FIELDS, 'employer' => self::EMPLOYER_FIELDS] as $type => $all) {
            $list = is_array($saved) && isset($saved[$type]) && is_array($saved[$type]) ? $saved[$type] : self::DEFAULTS[$type];
            $out[$type] = array_values(array_intersect(array_keys($all), $list));
        }
        return $out;
    }

    public static function saveAllowedFields(array $employee, array $employer): void
    {
        SystemConfig::updateOrCreate(['key' => self::CONFIG_KEY], ['value' => json_encode([
            'employee' => array_values(array_intersect(array_keys(self::EMPLOYEE_FIELDS), $employee)),
            'employer' => array_values(array_intersect(array_keys(self::EMPLOYER_FIELDS), $employer)),
        ])]);
    }

    /**
     * @param string|null $context where the card was shared from:
     *   "item:{production_item id}" — Workflow / Pre-Production card (the job's own request no., appointment, team, remarks)
     *   "tab:{resolution_tab id}"  — Registration / Renewal tab (that tab's request no., appointment, team, remarks)
     *   null                       — Employees menu etc.: the employee record itself
     */
    public function forEmployee(Employee $employee, ?Notification $notification = null, bool $withPhoto = false, ?string $context = null): array
    {
        $allowed = self::allowedFields()['employee'];
        $employee->loadMissing('employer');
        $ctx = $this->contextValues($employee, $context);

        $title = trim(($employee->employeeTitleEn ?? '') . ' ' . ($employee->employeeNameEn ?? ''))
            ?: trim(($employee->employeeTitleTh ?? '') . ' ' . ($employee->employeeNameTh ?? ''))
            ?: __('Employee') . ' #' . $employee->id;

        $values = [
            'name_th' => trim(($employee->employeeTitleTh ?? '') . ' ' . ($employee->employeeNameTh ?? '')),
            'nationality' => $employee->employeeNationality,
            'dob' => $this->date($employee->employeeDob, true),
            'employer' => $employee->employer?->employerNameTh ?: $employee->employer?->employerNameEn,
            'job_title' => $employee->job_title ?: $employee->employeePosition,
            'phone' => $employee->employeePhone,
            'email' => $employee->email,
            'passport_no' => $employee->employeePassport,
            'passport_expiry' => $this->date($employee->passportExpiryDate),
            'visa_type' => $employee->visaType,
            'visa_expiry' => $this->date($employee->visaExpiryDate),
            'work_permit_no' => $employee->employeeWorkPermit,
            'work_permit_expiry' => $this->date($employee->workPermitExpiryDate),
            'mou_group' => $employee->workPermitMOUGroup === 'อื่นๆ' && $employee->workPermitMOUGroupOther ? $employee->workPermitMOUGroupOther : $employee->workPermitMOUGroup,
            'ninety_day' => $this->date($employee->ninetyDayReportDate),
            'pink_card' => $employee->pinkCardNo,
            'name_list_number' => $employee->name_list_number,
            'reference_id' => $employee->employee_reference_id,
            'outsource_code' => $employee->outsource_code,
            'employer_employee_id' => $employee->employer_employee_id,
            'id_number' => $employee->employee_id_number,
            'tax_id_number' => $employee->tax_id_number,
            'social_security' => $employee->social_security_number,
            'insurance_type' => $employee->insurance_type,
            'start_date' => $this->date($employee->startDate),
        ] + $ctx;

        $lines = $this->lines(self::EMPLOYEE_FIELDS, $allowed, $values);

        // Notification context: the reason this card was shared (e.g. visa expiry).
        $alert = null;
        if ($notification && $notification->employee_id === $employee->id) {
            $alert = $this->notificationLine($notification);
        }

        $photo = null;
        if ($withPhoto && in_array('photo', $allowed, true) && $employee->employeePhoto) {
            $photo = $this->photoDataUrl($employee->employeePhoto);
        }

        return $this->bundle('employee', $employee->id, $title, $lines, $alert, $photo, [
            'type' => 'employee',
            'id' => $employee->id,
            'title' => $employee->employeeNameTh ?: $title,
            'subtitle' => $employee->employeeNameEn,
            'url' => route('employees.show', $employee->id),
        ], $allowed);
    }

    public function forEmployer(Employer $employer, ?Notification $notification = null): array
    {
        $allowed = self::allowedFields()['employer'];
        $title = $employer->employerNameTh ?: $employer->employerNameEn ?: __('Employer') . ' #' . $employer->id;
        $address = optional($employer->addresses()->first())->full_address;

        $values = [
            'name_en' => $employer->employerNameEn,
            'employer_id' => $employer->employerId,
            'tax_id' => $employer->employerTaxId,
            're_code' => $employer->outsource_re_code,
            'business_type' => $employer->businessType,
            'signer' => $employer->signerNameTh,
            'phone' => $employer->employerPhone,
            'email' => $employer->employerEmail,
            'address' => $address,
            'employee_count' => in_array('employee_count', $allowed, true) ? (string) $employer->employees()->count() : null,
        ];

        $alert = ($notification && $notification->employer_id === $employer->id && !$notification->employee_id)
            ? $this->notificationLine($notification) : null;

        return $this->bundle('employer', $employer->id, $title, $this->lines(self::EMPLOYER_FIELDS, $allowed, $values), $alert, null, [
            'type' => 'employer',
            'id' => $employer->id,
            'title' => $title,
            'subtitle' => $employer->employerId,
            'url' => route('employers.edit', $employer->id),
        ], $allowed);
    }

    // ------------------------------------------------------------------

    protected function lines(array $defs, array $allowed, array $values): array
    {
        $lines = [];
        foreach ($defs as $key => [$label, $icon]) {
            if ($key === 'photo' || !in_array($key, $allowed, true)) {
                continue;
            }
            // Every ticked field is shown, empty ones as "-", so what goes out
            // always matches the admin's settings. (Menu-only fields are
            // absent from $values outside that menu — skipped.)
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $value = trim((string) ($values[$key] ?? ''));
            $lines[] = ['key' => $key, 'icon' => $icon, 'label' => __($label), 'value' => $value !== '' ? $value : '-'];
        }
        return $lines;
    }

    protected function bundle(string $type, int $id, string $title, array $lines, ?array $alert, ?string $photo, array $chat, array $allowed): array
    {
        $text = [($type === 'employee' ? '👤 ' : '🏢 ') . $title];
        foreach ($lines as $line) {
            $text[] = "{$line['icon']} {$line['label']}: {$line['value']}";
        }
        if ($alert) {
            $text[] = "⚠️ {$alert['label']}: {$alert['value']}";
        }

        $brand = BrandService::current();

        return [
            'type' => $type,
            'id' => $id,
            'title' => $title,
            'lines' => $lines,
            'alert' => $alert,
            'photo' => $photo,
            'text' => implode("\n", $text),
            'fields' => $allowed,
            'brand' => ['name' => $brand['app_name'], 'color' => $brand['primary_color'], 'accent' => $brand['accent_color']],
            'chat' => $chat,
        ];
    }

    /**
     * Request number / appointment / team / remarks as shown on the card the
     * user shared from. Workflow & Pre-Production keep them on the job
     * (production_items); Registration & Renewal keep them per tab
     * (employee_request_numbers, employee_appointments,
     * employee_team_assignments — see HasResolutionTab). Without a context
     * (Employees menu, notifications …) the employee record's own values.
     */
    protected function contextValues(Employee $employee, ?string $context): array
    {
        [$kind, $id] = array_pad(explode(':', (string) $context, 2), 2, null);
        $id = (int) $id;

        if ($kind === 'item' && $id) {
            $item = ProductionItem::where('id', $id)->where('employee_id', $employee->id)->first();
            if ($item) {
                return [
                    'request_number' => $item->request_number,
                    'appointment' => $this->appointment($item->appointment_date, $item->appointment_location),
                    'team' => $item->group_name,
                    'remarks' => $item->remarks,
                ];
            }
        }

        if ($kind === 'tab' && $id && ($tab = ResolutionTab::find($id))) {
            $appointment = EmployeeAppointment::where('employee_id', $employee->id)->where('resolution_tab_id', $tab->id)->first();
            return [
                'request_number' => EmployeeRequestNumber::where('employee_id', $employee->id)->where('resolution_tab_id', $tab->id)->value('request_number'),
                'appointment' => $appointment ? $this->appointment($appointment->appointment_date, $appointment->appointment_location) : null,
                'team' => EmployeeTeamAssignment::where('employee_id', $employee->id)->where('resolution_tab_id', $tab->id)->value('team_name'),
                'remarks' => $tab->type === 'renewal' ? $employee->renewal_remarks : $employee->registration_remarks,
            ];
        }

        // Appointment / team / remarks only exist inside a menu — left out here
        // (not shown as "-").
        return ['request_number' => $employee->request_number];
    }

    protected function appointment($date, ?string $location): ?string
    {
        if (!$date) {
            return $location ?: null;
        }
        try {
            $d = $date instanceof Carbon ? $date : Carbon::parse($date);
        } catch (\Throwable $e) {
            return $location ?: null;
        }
        $when = $d->format('H:i') === '00:00' ? $d->format('d/m/Y') : $d->format('d/m/Y H:i');
        return trim($when . ($location ? ' — ' . $location : ''));
    }

    protected function notificationLine(Notification $n): ?array
    {
        $labels = [
            'ninety_day_report' => 'ครบกำหนดรายงานตัว 90 วัน',
            'work_permit_expiry' => 'ใบอนุญาตทำงานหมดอายุ',
            'visa_expiry' => 'วีซ่าหมดอายุ',
            'passport_expiry' => 'หนังสือเดินทางหมดอายุ',
            'pink_card_missing' => 'ไม่มีข้อมูลบัตรชมพู',
            'residence_permit_missing' => 'ไม่มีข้อมูลใบอนุญาตพำนัก',
            'work_permit_mou' => 'Work Permit (MOU)',
            'employer_document_expiry' => 'เอกสารนายจ้างหมดอายุ',
            'insurance_expiry' => 'ประกันหมดอายุ',
        ];
        $label = $labels[$n->type] ?? ucfirst(str_replace('_', ' ', (string) $n->type));

        if (in_array($n->type, ['pink_card_missing', 'residence_permit_missing'], true) || !$n->due_date) {
            return ['label' => $label, 'value' => __('Needs update')];
        }

        $days = (int) $n->days_remaining;
        $when = $days < 0
            ? __('overdue :days days', ['days' => abs($days)])
            : __(':days days left', ['days' => $days]);

        return ['label' => $label, 'value' => $this->date($n->due_date) . " ({$when})"];
    }

    protected function date($value, bool $withAge = false): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
        $out = $date->format('d/m/Y');
        if ($withAge && $date->isPast()) {
            $out .= ' (' . __(':age yrs', ['age' => $date->age]) . ')';
        }
        return $out;
    }

    /** Small square JPEG as a data: URL so the browser can draw it on a canvas (no cross-origin taint). */
    protected function photoDataUrl(string $path): ?string
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path)) {
                return null;
            }
            $src = @imagecreatefromstring($disk->get($path));
            if (!$src) {
                return null;
            }
            $w = imagesx($src);
            $h = imagesy($src);
            $side = min($w, $h);
            // Portrait photos: keep the top part (the face), not the centre.
            $sx = (int) (($w - $side) / 2);
            $sy = $h > $w ? (int) (($h - $side) * 0.15) : (int) (($h - $side) / 2);
            $size = 360;
            $dst = imagecreatetruecolor($size, $size);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $size, $size, $side, $side);
            ob_start();
            imagejpeg($dst, null, 85);
            $jpeg = ob_get_clean();
            imagedestroy($src);
            imagedestroy($dst);
            return 'data:image/jpeg;base64,' . base64_encode($jpeg);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
