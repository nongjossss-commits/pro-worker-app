<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One employee "sent" from Workflow to another Workflow tab, waiting there
 * until someone adds them to an employer card (received) or dismisses it.
 * See WorkflowHandoffController.
 */
class WorkflowHandoff extends Model
{
    public const PENDING = 'pending';
    public const RECEIVED = 'received';
    public const DISMISSED = 'dismissed';

    protected $fillable = [
        'target_work_type_id', 'employee_id', 'employer_id', 'source_work_type_id',
        'status', 'sent_by', 'handled_by', 'handled_at', 'production_item_id',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function targetWorkType()
    {
        return $this->belongsTo(WorkType::class, 'target_work_type_id');
    }

    public function sourceWorkType()
    {
        return $this->belongsTo(WorkType::class, 'source_work_type_id')->withTrashed();
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Pending rows the current user may see: the employee still exists and
     * is visible to them (Employee's employerTenancy scope applies inside
     * whereHas, so an employer account only sees its own people).
     */
    public function scopeVisiblePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING)->whereHas('employee');
    }

    /** [work_type_id => number waiting] for the tab badges. */
    public static function pendingCounts(): array
    {
        return static::visiblePending()
            ->selectRaw('target_work_type_id, COUNT(*) as c')
            ->groupBy('target_work_type_id')
            ->pluck('c', 'target_work_type_id')
            ->map(fn ($c) => (int) $c)
            ->all();
    }
}
