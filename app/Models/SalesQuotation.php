<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesQuotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sales_lead_id',
        'financial_profile_id',
        'quotation_date',
        'payment_terms',
        'grand_total',
        'total_paid',
        'payment_status',
        'items_data',
        'payments_data',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'items_data' => 'array',
        'payments_data' => 'array',
    ];

    public function salesLead()
    {
        return $this->belongsTo(SalesLead::class);
    }
}
