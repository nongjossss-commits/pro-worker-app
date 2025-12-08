<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type', // 'employer' or 'independent'
        'employer_id',
        'project_name',
        'description',
        'status',
        'financial_data',
        'custom_field_definitions', // JSON
        'created_by',
    ];

    protected $casts = [
        'financial_data' => 'array',
        'custom_field_definitions' => 'array',
    ];

    /**
     * Scope a query to only include pre-production orders.
     */
    public function scopePreProduction($query)
    {
        return $query->where('status', 'pre_production');
    }

    /**
     * Scope a query to only include active workflow orders.
     */
    public function scopeActiveWorkflow($query)
    {
        return $query->where('status', '!=', 'pre_production');
    }

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
