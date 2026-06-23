<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\JobOwner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogActivity;
use App\Traits\SearchableByAddress;

class Employer extends Model
{
    use HasFactory, SoftDeletes, LogActivity, SearchableByAddress;

    protected static function booted(): void
    {
        static::addGlobalScope('employerTenancy', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->hasRole('employer')) {
                    // This user is an 'employer'. Filter their view to *only*
                    // the Employer record linked to their user_id.
                    $builder->where('user_id', $user->id);
                } elseif ($user->hasRole('caretaker')) {
                    // This user is a 'caretaker'. Filter their view to *only*
                    // Employer records where they are one of the assigned caretakers.
                    $builder->whereHas('caretakers', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
                }
            }
        });

        // Auto-apply default "employer_doc_other_N_desc" from SuperAdminSetting
        // ONLY for slots not already set on this employer (preserve any explicit value).
        // After creation, the description sticks — Super Admin must re-press the per-slot
        // update button to re-apply changes to existing records.
        static::creating(function ($employer) {
            try {
                $keys = [];
                for ($i = 1; $i <= 3; $i++) {
                    $keys[] = "employer_other_{$i}_desc";
                }
                $defaults = \App\Models\SuperAdminSetting::whereIn('key', $keys)
                    ->pluck('value', 'key');
                for ($i = 1; $i <= 3; $i++) {
                    $col = "employer_doc_other_{$i}_desc";
                    $key = "employer_other_{$i}_desc";
                    if (empty($employer->{$col}) && !empty($defaults[$key])) {
                        $employer->{$col} = $defaults[$key];
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Employer other_doc defaults autofill failed: ' . $e->getMessage());
            }
        });
    }

    protected $fillable = [
        'employerNameTh',
        'employerNameEn',
        'name_suffix',
        'employerId',
        'employerTaxId',
        'employerEmail',
        'employerPassword',
        'employerPhone',
        'outsource_re_code',
        'outsource_password',
        'socialSecurityHospital',
        'businessType',
        'signerNameTh',
        'signerNameEn',
        'signer_2_name_th',
        'signer_2_name_en',
        'signature_1_path',
        'signature_2_path',
        'employer_stamp_path',
        'businessTypeEn',
        'regCapital',
        'regDate',
        'minimum_wage',
        'employer_doc_company',
        'employer_doc_company_expiry',
        'employer_doc_lease',
        'employer_doc_construction',
        'employer_doc_other_1',
        'employer_doc_other_1_desc',
        'employer_doc_other_2',
        'employer_doc_other_2_desc',
        'employer_doc_other_3',
        'employer_doc_other_3_desc',
        'job_owner_id',
        'user_id',
        'assigned_staff_id',
        'registration_resolution_status',
        'registration_resolution_note',
        'renewal_resolution_note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'regDate' => 'date:Y-m-d',
    ];

    /**
     * ฟิลด์ที่ห้ามหลุดใน JSON / API response / serialization (เช่น ตอน $employer->toJson())
     * Password และ outsource_password ต้อง mask ที่ระดับ model — ห้ามเปิดเผย plain-text
     */
    protected $hidden = [
        'employerPassword',
        'outsource_password',
    ];

    protected function employerNameTh(): Attribute
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

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function resolutionTabs()
    {
        return $this->belongsToMany(\App\Models\ResolutionTab::class, 'employer_resolution_tab')
                    ->withPivot('resolution_status', 'resolution_note')
                    ->withTimestamps();
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function jobOwner()
    {
        return $this->belongsTo(JobOwner::class, 'job_owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function caretakers()
    {
        return $this->belongsToMany(User::class, 'employer_user', 'employer_id', 'user_id')
                    ->withTimestamps();
    }

    public function customFields()
    {
        return $this->morphMany(ProductionCustomField::class, 'model');
    }

    public function getMatchedAddressLabels($province, $district = null, $subDistrict = null)
    {
        if (!$province) return [];
        $labels = [];
        // Ensure addresses are loaded or load them
        $addresses = $this->relationLoaded('addresses') ? $this->addresses : $this->addresses()->get();

        foreach ($addresses as $address) {
            if ($address->addrProvince === $province) {
                if ($district && $address->addrDistrict !== $district) continue;
                if ($subDistrict && $address->addrSubDistrict !== $subDistrict) continue;
                $labels[] = ($address->type === 'registered') ? '(ที่อยู่ตามทะเบียนบ้าน)' : '(ที่อยู่สถานที่ทำงาน)';
            }
        }
        return array_unique($labels);
    }

    public function getMatchedAddresses($province, $district = null, $subDistrict = null)
    {
        if (!$province) return collect();
        // Ensure addresses are loaded or load them
        $addresses = $this->relationLoaded('addresses') ? $this->addresses : $this->addresses()->get();

        return $addresses->filter(function ($address) use ($province, $district, $subDistrict) {
            if ($address->addrProvince !== $province) return false;
            if ($district && $address->addrDistrict !== $district) return false;
            if ($subDistrict && $address->addrSubDistrict !== $subDistrict) return false;
            return true;
        });
    }
}
