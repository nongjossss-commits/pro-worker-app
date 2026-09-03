<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employer_id',
        'resolution_tab_id',
        'sales_lead_id',
        'work_type_id',
        'manual_bill_type', // 'quotation' | 'invoice' | null — only set for work_type_id=null manual bills
        'type', // 'employer' or 'independent'
        'project_name',
        'description',
        'status',
        'remarks',
        'financial_data',
        'created_by',
        'updated_by', // NEW
        'document_ready_at',
        'document_ready_by',
        'waiting_for_documents',
        'missing_documents',
        'financial_approved_at',
        'financial_approved_by',
        // MOU Import demand card fields (เฉพาะ work_type=mou_import)
        'mou_nationality',
        'mou_male_count',
        'mou_female_count',
        'mou_import_type', // 'return' | 'new' | null (ยังไม่ระบุ)
    ];

    protected $casts = [
        'financial_data' => 'array',
        'document_ready_at' => 'datetime',
        'financial_approved_at' => 'datetime',
        'mou_male_count' => 'integer',
        'mou_female_count' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // Mirrors Employee::employerTenancy (app/Models/Employee.php:18-38)
        // verbatim — ProductionOrder already carries a direct employer_id
        // column, so no relation-chasing is needed like ProductionItem's
        // own (deliberately local/opt-in) scopeVisibleToUser() needs.
        // Being a GLOBAL scope, this automatically protects every
        // ProductionOrder::-based query app-wide (Workflow, Pre-Production,
        // Finance Hub, the main Dashboard, PDF document downloads, etc.)
        // without those files needing individual changes — the same
        // "automatic everywhere" guarantee Employee/Employer already give
        // for their own data.
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

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function salesLead()
    {
        return $this->belongsTo(\App\Models\SalesLead::class, 'sales_lead_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function workType()
    {
        return $this->belongsTo(WorkType::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionItem::class);
    }

    public function financialGroups()
    {
        return $this->hasMany(ProductionFinancialGroup::class);
    }

    public function customFields()
    {
        return $this->morphMany(ProductionCustomField::class, 'model');
    }
}
