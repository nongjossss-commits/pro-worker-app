<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogActivity;

class Employee extends Model
{
    use HasFactory, SoftDeletes, LogActivity;

    protected static function booted(): void
    {
        static::addGlobalScope('employerTenancy', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->hasRole('employer')) {
                    $employer = $user->employer;
                    if ($employer) {
                        $builder->where('employer_id', $employer->id);
                    } else {
                        $builder->whereRaw('1 = 0');
                    }
                } elseif ($user->hasRole('caretaker')) {
                    $builder->whereHas('employer', function ($q) use ($user) {
                        $q->whereHas('caretakers', function ($q2) use ($user) {
                            $q2->where('users.id', $user->id);
                        });
                    });
                }
            }
        });

        // Enforce the Super-Admin-configured employee cap at the model
        // layer so every insert path (admin form, import, sales-lead
        // transition, workflow add-employee, etc.) is guarded uniformly.
        // EmployeeQuotaExceededException renders itself as JSON 422 or
        // a flash-back depending on the request type.
        static::creating(function ($employee) {
            \App\Services\EmployeeQuotaService::ensureCanCreate();

            // Auto-apply default "other_doc_N_desc" from SuperAdminSetting
            // ONLY for slots not already set on this employee (preserve any explicit value).
            // After creation, the description sticks — Super Admin must re-press the per-slot
            // update button to re-apply changes to existing records.
            try {
                $keys = [];
                for ($i = 1; $i <= 10; $i++) {
                    $keys[] = "employee_other_{$i}_desc";
                }
                $defaults = \App\Models\SuperAdminSetting::whereIn('key', $keys)
                    ->pluck('value', 'key');
                for ($i = 1; $i <= 10; $i++) {
                    $col = "other_doc_{$i}_desc";
                    $key = "employee_other_{$i}_desc";
                    if (empty($employee->{$col}) && !empty($defaults[$key])) {
                        $employee->{$col} = $defaults[$key];
                    }
                }
            } catch (\Throwable $e) {
                // Never block creation if defaults lookup fails
                \Log::warning('Employee other_doc defaults autofill failed: ' . $e->getMessage());
            }
        });

        // Restore-from-trash also adds to the active count, but `creating`
        // does not fire for it — Laravel raises `restoring` instead. Guard
        // it the same way so the cap can't be bypassed by deleting and
        // then un-deleting an employee. Bust the cache first so the count
        // reflects any creates/deletes that happened in the last minute.
        static::restoring(function ($employee) {
            \App\Services\EmployeeQuotaService::forgetCache();
            // Skip the check if this restore would re-activate a still-
            // terminated employee — they won't count toward the cap
            // anyway (the quota service excludes terminated_at IS NOT NULL).
            if ($employee->terminated_at !== null) {
                return;
            }
            \App\Services\EmployeeQuotaService::ensureCanCreate();
        });

        // Bust the cached active-count whenever the population changes,
        // so the badge on /employees and the bouncer below stay accurate
        // without waiting for the 60-second TTL.
        $bust = fn () => \App\Services\EmployeeQuotaService::forgetCache();
        static::created($bust);
        static::updated($bust);
        static::deleted($bust);
        static::restored($bust);
    }

    protected $fillable = [
        'employer_id',
        'resolution_tab_id',
        'status', // Added status
        'signature_path', // Added for persistence
        'english_prefix',
        'employeeNameTh',
        'employeeNameEn',
        'name_suffix',
        'employeeNationality',
        'employeePassport',
        'passportExpiryDate',
        'employeeWorkPermit',
        'workPermitExpiryDate',
        'visaExpiryDate',
        'ninetyDayReportDate',
        'employeeTitleTh',
        'employeeTitleEn',
        'employeeDob',
        'passportType',
        'pinkCardNo',
        'designatedHospital',
        'startDate',
        'employeePhone',
        'email',
        'password',
        'employeePosition',
        'workPermitMOUGroup',
        'workPermitMOUGroupOther',
        'employeePhoto',
        'nature_of_work',
        'terminated_at',
        'termination_reason',
        'name_list_number',
        'request_number',
        'registration_request_number',
        'renewal_request_number',
        'employee_id_number',
        'tax_id_number',
        'employer_employee_id',
        'department',
        'employee_reference_id',
        'father_name',
        'mother_name',
        'height',
        'weight',
        'passport_issue_date',
        'passport_issue_place',
        'passport_type_cambodia',
        'insurance_type',
        'insurance_detail',
        'insurance_expiry_date',
        'insurance_detail_hospital',
        'insurance_expiry_date_hospital',
        'insurance_detail_private',
        'insurance_expiry_date_private',
        'social_security_number',
        'sso_issue_date',
        'sso_expiry_date',
        'visaType',
        'employee_doc_1',
        'employee_doc_2',
        'employee_doc_3',
        'employee_doc_4',
        'employee_doc_5',
        'employee_doc_6',
        'employee_doc_7',
        'employee_doc_8',
        'employee_doc_9',
        'employee_doc_10',
        'employee_doc_11',
        'employee_doc_12',
        'employee_doc_13',
        'employee_doc_14',
        'employee_doc_15',
        'employee_doc_16',
        'employee_doc_17',
        'employee_doc_18',
        'other_doc_1_desc',
        'other_doc_2_desc',
        'other_doc_3_desc',
        'other_doc_4_desc',
        'other_doc_5_desc',
        'other_doc_6_desc',
        'other_doc_7_desc',
        'other_doc_8_desc',
        'other_doc_9_desc',
        'other_doc_10_desc',
        'insurance_document_path',
        'insurance_document_path_private',
        'job_title',
        'job_description',
        'insurance_company',
        'hospital_name',
        'passport_file_path',
        'visa_file_path',
        'visa_issue_place',
        'work_permit_file_path',
        'pink_card_file_path',
        'medical_certificate_path',
        'medical_hospital_name',
        'outsource_code',
        'bank_name',
        'bank_account_number',
        'biometrics_collected_at',
        'appointment_date',
        'appointment_location',
        'appointment_completed_at',
        'appointment_updated_by',
        'appointment_updated_at',
        'resolution_completed_at',
        'daily_check_enabled',
        'registration_remarks',
        'renewal_remarks',
        'last_daily_checked_at',
        'operator_id',
        'custom_operator_name',
    ];

    protected $appends = ['has_visa'];

    protected $casts = [
        'passportExpiryDate' => 'date:Y-m-d',
        'workPermitExpiryDate' => 'date:Y-m-d',
        'biometrics_collected_at' => 'datetime',
        'appointment_date' => 'datetime',
        'appointment_completed_at' => 'datetime',
        'appointment_updated_at' => 'datetime',
        'resolution_completed_at' => 'datetime',
        'last_daily_checked_at' => 'datetime',
        'daily_check_enabled' => 'boolean',
        'visaExpiryDate' => 'date:Y-m-d',
        'ninetyDayReportDate' => 'date:Y-m-d',
        'employeeDob' => 'date:Y-m-d',
        'startDate' => 'date:Y-m-d',
        'passport_issue_date' => 'date:Y-m-d',
        'insurance_expiry_date' => 'date:Y-m-d',
        'insurance_expiry_date_hospital' => 'date:Y-m-d',
        'insurance_expiry_date_private' => 'date:Y-m-d',
        'sso_issue_date' => 'date:Y-m-d',
        'sso_expiry_date' => 'date:Y-m-d',
        'terminated_at' => 'datetime',
    ];

    /**
     * Renewal-progress state for a Registration/Renewal Resolution card.
     *
     * Returns one of:
     *   - 'completed'      — finalize() pressed (status = registration_completed / renewal_completed)
     *   - 'both'           — both visa AND work-permit expiry are >= target (ready to finalize)
     *   - 'visa_only'      — only visa expiry is >= target
     *   - 'work_permit_only' — only work-permit expiry is >= target
     *   - 'none'           — neither has been renewed yet (default colour)
     *
     * The "target" for each side is the SystemSetting value the admin saved
     * for the current tab group (registration_auto_visa_expiry /
     * registration_auto_work_permit_expiry — and the same for renewal).
     *
     * Settings are loaded on demand, so calling code can override them by
     * setting `$this->renewalProgressTargets = ['visa' => ..., 'wp' => ...]`
     * to avoid an N+1 SystemSetting lookup per card. The index controllers
     * use that override path.
     */
    public ?array $renewalProgressTargets = null;

    public function getRenewalProgressAttribute(): string
    {
        if (in_array($this->status, ['registration_completed', 'renewal_completed'], true)) {
            return 'completed';
        }

        $targets = $this->renewalProgressTargets ?? $this->resolveRenewalTargetsFromSettings();
        $visaTarget = $targets['visa'] ?? null;
        $wpTarget = $targets['wp'] ?? null;

        if (!$visaTarget && !$wpTarget) {
            return 'none';
        }

        $visaRenewed = $visaTarget && $this->visaExpiryDate
            && \Carbon\Carbon::parse($this->visaExpiryDate)->gte(\Carbon\Carbon::parse($visaTarget));
        $wpRenewed = $wpTarget && $this->workPermitExpiryDate
            && \Carbon\Carbon::parse($this->workPermitExpiryDate)->gte(\Carbon\Carbon::parse($wpTarget));

        if ($visaRenewed && $wpRenewed) return 'both';
        if ($visaRenewed)               return 'visa_only';
        if ($wpRenewed)                 return 'work_permit_only';
        return 'none';
    }

    protected function resolveRenewalTargetsFromSettings(): array
    {
        $group = match (true) {
            in_array($this->status, ['registration_pending', 'registration_completed', 'registration_cancelled'], true) => 'registration',
            in_array($this->status, ['renewal_pending', 'renewal_completed', 'renewal_cancelled'], true)                => 'renewal',
            default => null,
        };
        if (!$group) return ['visa' => null, 'wp' => null];

        $rows = \App\Models\SystemSetting::where('group', $group)
            ->whereIn('key', ["{$group}_auto_visa_expiry", "{$group}_auto_work_permit_expiry"])
            ->pluck('value', 'key');

        return [
            'visa' => $rows["{$group}_auto_visa_expiry"] ?? null,
            'wp'   => $rows["{$group}_auto_work_permit_expiry"] ?? null,
        ];
    }

    protected function employeeNameEn(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // Idempotent: skip the concat if the raw column already
                // ends with "(suffix)" so display never doubles up — even
                // on legacy rows where a corrupted save embedded the
                // suffix into the column itself.
                $suffix = $this->attributes['name_suffix'] ?? null;
                if (!$suffix || !$value) return $value;
                if (str_ends_with(trim($value), '(' . $suffix . ')')) {
                    return $value;
                }
                return $value . ' (' . $suffix . ')';
            },
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->employeePhoto && Storage::disk('public')->exists($this->employeePhoto)) {
                    return Storage::disk('public')->url($this->employeePhoto);
                }
                $name = urlencode($this->employeeNameTh ?? $this->employeeNameEn ?? 'User');
                return "https://ui-avatars.com/api/?name={$name}&color=FFFFFF&background=F97316&size=128";
            }
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->employeeDob ? $this->employeeDob->age : 'N/A',
        );
    }

    protected function workAge(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->startDate) return __('N/A');
                $diff = $this->startDate->diff(now());
                $years = $diff->y;
                $months = $diff->m;
                $days = $diff->d;

                $result = [];
                if ($years > 0) $result[] = $years . ' ' . __('Years');
                if ($months > 0) $result[] = $months . ' ' . __('Months');
                $result[] = $days . ' ' . __('Days');

                return implode(' ', $result);
            }
        );
    }

    protected function gender(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->employeeTitleTh === 'นาย') {
                    return 'ชาย';
                }
                if (in_array($this->employeeTitleTh, ['นาง', 'นางสาว'])) {
                    return 'หญิง';
                }
                return 'N/A';
            }
        );
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function teams()
    {
        return $this->belongsToMany(EmployeeTeam::class, 'employee_team_members', 'employee_id', 'employee_team_id')
                    ->withTimestamps();
    }

    public function resolutionTab()
    {
        return $this->belongsTo(\App\Models\ResolutionTab::class);
    }

    // --- New Relationships for Registration Process ---
    public function registrationSteps()
    {
        return $this->belongsToMany(RegistrationStep::class, 'employee_registration_status')
                    ->withPivot('completed_at')
                    ->withTimestamps();
    }

    public function productionItems()
    {
        return $this->hasMany(ProductionItem::class);
    }

    public function getHasVisaAttribute()
    {
        return !empty($this->visaExpiryDate);
    }

    /**
     * Request-level cache of ResolutionTab lookups — avoids repeated DB queries
     * when `active_workflows` is called for many employees in the same request.
     */
    protected static array $resolutionTabCache = [];

    protected static function getCachedResolutionTab(string $type, ?int $tabId): ?\App\Models\ResolutionTab
    {
        $cacheKey = $type . ':' . ($tabId ?? 'default');

        if (array_key_exists($cacheKey, self::$resolutionTabCache)) {
            return self::$resolutionTabCache[$cacheKey];
        }

        $tab = $tabId
            ? \App\Models\ResolutionTab::find($tabId)
            : \App\Models\ResolutionTab::where('type', $type)->where('is_default', true)->first();

        self::$resolutionTabCache[$cacheKey] = $tab;
        return $tab;
    }

    public function getActiveWorkflowsAttribute()
    {
        // --- Perf: Use eager-loaded productionItems if available to avoid N+1 queries ---
        // Controllers should eager load: productionItems.order.workType
        if ($this->relationLoaded('productionItems')) {
            $items = $this->productionItems->filter(function ($item) {
                if (in_array($item->status, ['completed', 'cancelled'])) return false;
                if (!$item->order) return false;
                if (in_array($item->order->status, ['completed', 'cancelled'])) return false;
                if (!$item->order->work_type_id) return false;
                return true;
            });
        } else {
            $items = $this->productionItems()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereHas('order', function ($query) {
                    $query->whereNotIn('status', ['completed', 'cancelled'])
                          ->whereNotNull('work_type_id');
                })
                ->with(['order.workType'])
                ->get();
        }

        $workflows = $items->map(function ($item) {
                $isPreProduction = $item->order->status === 'pre_production';
                // Concise, translatable status labels
                $statusLabel = $isPreProduction
                    ? __('Preparing')
                    : __('Processing');

                $routeName = $isPreProduction ? 'production.index' : 'workflow.index';
                $url = route($routeName, [
                    'tab' => $item->order->workType->slug ?? '',
                    'order' => $item->production_order_id,
                    'item' => $item->id,
                    'highlight_employer_id' => $this->employer_id,
                    'highlight_employee_id' => $this->id
                ]);

                return (object) [
                    'name' => $item->order->workType->name ?? __('Unknown'),
                    'status_label' => $statusLabel,
                    'is_pre_production' => $isPreProduction,
                    'is_registration' => false,
                    'is_renewal' => false,
                    'order_id' => $item->production_order_id,
                    'item_id' => $item->id,
                    'tab_slug' => $item->order->workType->slug ?? '',
                    'url' => $url,
                ];
            });

        // Check if resolution was completed more than 24 hours ago
        $resolutionCompletedOlderThan24h = $this->resolution_completed_at && $this->resolution_completed_at->diffInHours(now()) >= 24;

        // Add Registration Resolution (Purple) — show resolution tab NAME (concise)
        if (in_array($this->status, ['registration_pending', 'registration_completed'])) {
            if (!($this->status === 'registration_completed' && $resolutionCompletedOlderThan24h)) {
                // Perf: Use in-memory cache (request-level) — avoid repeated ResolutionTab lookups across many employees
                $regTab = self::getCachedResolutionTab('registration', $this->resolution_tab_id);

                $regTabId = $regTab?->id;
                $regTabName = $regTab?->name ?? __('Registration Resolution');

                $workflows->push((object)[
                    'name' => $regTabName, // tab's own name — e.g. "มติลงทะเบียน31/03/2027"
                    'status_label' => null, // no prefix — badge shows tab name directly
                    'is_pre_production' => false,
                    'is_registration' => true,
                    'is_renewal' => false,
                    'url' => route('production.registration.operations', [
                        'resolutionTab' => $regTabId,
                        'highlight_employer_id' => $this->employer_id,
                        'highlight_employee_id' => $this->id
                    ]),
                ]);
            }
        }

        // Add Renewal Resolution (Pink) — show resolution tab NAME (concise)
        if (in_array($this->status, ['renewal_pending', 'renewal_completed'])) {
            if (!($this->status === 'renewal_completed' && $resolutionCompletedOlderThan24h)) {
                // Perf: Use in-memory cache (request-level) — avoid repeated ResolutionTab lookups across many employees
                $renTab = self::getCachedResolutionTab('renewal', $this->resolution_tab_id);

                $renTabId = $renTab?->id;
                $renTabName = $renTab?->name ?? __('Renewal Resolution');

                $workflows->push((object)[
                    'name' => $renTabName, // tab's own name — e.g. "มติต่ออายุ11/12/2026"
                    'status_label' => null, // no prefix — badge shows tab name directly
                    'is_pre_production' => false,
                    'is_registration' => false,
                    'is_renewal' => true,
                    'url' => route('production.renewal.operations', [
                        'resolutionTab' => $renTabId,
                        'highlight_employer_id' => $this->employer_id,
                        'highlight_employee_id' => $this->id
                    ]),
                ]);
            }
        }

        return $workflows;
    }

    public function customFields()
    {
        return $this->hasMany(EmployeeCustomField::class);
    }

    public function generatedDocuments()
    {
        return $this->hasMany(EmployeeGeneratedDocument::class);
    }

    protected function daysSinceTermination(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->terminated_at) {
                    return 0;
                }
                return floor(now()->diffInDays($this->terminated_at));
            }
        );
    }

    protected function isDailyCheckPending(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->daily_check_enabled) {
                    return false;
                }
                if (is_null($this->last_daily_checked_at)) {
                    return true;
                }
                return !$this->last_daily_checked_at->isToday();
            }
        );
    }

    protected function daysSinceLastDailyCheck(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (is_null($this->last_daily_checked_at)) {
                    return null; // Never checked
                }
                return floor(now()->diffInDays($this->last_daily_checked_at));
            }
        );
    }
}
