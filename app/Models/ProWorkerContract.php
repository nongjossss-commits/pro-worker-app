<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One issued Pro Worker <-> Employer contract — the anti-forgery record.
 * contract_no is `PWC-YYYY-` + a random 9-character alphanumeric suffix
 * (see ProWorkerContractService::generateContractNo() — deliberately
 * unguessable so knowing one contract's number reveals nothing about any
 * other) and is permanent once set, along with issued_at/issued_by/
 * labor_team_id. field_values/file_path/worker_count/employer_id/
 * employer_name_snapshot CAN be corrected later via
 * ProWorkerContractService::update() (see LaborContractController::edit/
 * update) since this contract carries no financial consequence — it only
 * feeds issuance statistics. There is deliberately no delete/cancel route
 * anywhere for this model — a corrected contract keeps its original
 * number so counts/statistics never lose a row. labor_team_id is
 * captured at issuance time, not resolved live off the issuer's current
 * team — reuses LaborTeam since this feature lives inside the Pro Walker
 * Labor module.
 *
 * employer_id/employer_name_snapshot are deliberately independent of the
 * template's field_mapping/field_values (see LaborContractController's
 * docblock) — contract-level metadata for search/reporting only, never
 * fed into the PDF's drag-and-drop fields, so there is no risk of it
 * landing in the wrong place on a freeform template. employer_id links to
 * a real Employer when the issuer has main-app access; external teams
 * with no Employer records of their own only ever set the free-text
 * employer_name_snapshot.
 */
class ProWorkerContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_no',
        'pro_worker_contract_template_id',
        'issued_by',
        'labor_team_id',
        'employer_id',
        'employer_name_snapshot',
        'field_values',
        'file_path',
        'worker_count',
        'issued_at',
    ];

    protected $casts = [
        'field_values' => 'array',
        'worker_count' => 'integer',
        'issued_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(ProWorkerContractTemplate::class, 'pro_worker_contract_template_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function team()
    {
        return $this->belongsTo(LaborTeam::class, 'labor_team_id');
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class, 'employer_id');
    }
}
