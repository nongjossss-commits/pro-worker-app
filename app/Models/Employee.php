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
