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
        'type', // 'employer' or 'independent'
        'project_name',
        'description',
        'status',
        'financial_data',
        'created_by',
        'document_ready_at',
        'document_ready_by',
        'waiting_for_documents',
        'missing_documents',
        'financial_approved_at',
        'financial_approved_by',
    ];

    protected $casts = [
        'financial_data' => 'array',
        'document_ready_at' => 'datetime',
        'financial_approved_at' => 'datetime',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(ProductionItem::class);
    }
}
