<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employer_id',
        'resolution_tab_id',
        'sales_lead_id',
        'work_type_id',
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
