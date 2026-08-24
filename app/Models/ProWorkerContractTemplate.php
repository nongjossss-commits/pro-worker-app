<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A Pro Worker <-> Employer contract template — see the "ที่อยู่"/free-label
 * field_mapping shape documented on the 2026_10_10_000003 migration.
 * Deliberately separate from App\Models\PdfTemplate (Employee/Employer
 * data-bound templates) — this one's fields carry no real data binding,
 * only an admin-authored label filled in ad-hoc at issuance time.
 */
class ProWorkerContractTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'field_mapping',
        'meta_data',
        'created_by',
    ];

    protected $casts = [
        'field_mapping' => 'array',
        'meta_data' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contracts()
    {
        return $this->hasMany(ProWorkerContract::class, 'pro_worker_contract_template_id');
    }
}
