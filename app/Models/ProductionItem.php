<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProductionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_order_id',
        'employee_id',
        'request_number',
        'group_name', // NEW: Group/Batch name
        'appointment_date',
        'appointment_location', // NEW
        'appointment_completed_at', // NEW: Appointment finished
        'appointment_updated_by',
        'appointment_updated_at',
        'status',
        'completed_at', // NEW: Workflow finished
        'is_transfer_processed', // NEW: Flag to prevent duplicate delayed transfers
        'workflow_settings_applied', // Flag for ApplyWorkflowSettings (24h MOU auto-apply)
        'new_employee_data', // JSON for temp employees
        'operator_id',
        'custom_operator_name', // NEW: Assigned Operator
        'remarks', // Added remarks
        'notify_out_date',    // Required-before-complete for notify_out items
        'notify_out_reason',  // Optional reason text, saved to employee.termination_reason on finalize
    ];

    protected $appends = ['has_visa'];

    /**
     * Bump the parent ProductionOrder's updated_at whenever this item changes.
     * Lets the 4 menus (Pre-Prod / Workflow / Registration / Renewal) sort
     * employer cards by latest activity on the next page load — not in real
     * time, so the user's current click flow doesn't get reshuffled mid-edit.
     */
    protected $touches = ['order'];

    protected $casts = [
        'new_employee_data' => 'array',
        'appointment_date' => 'datetime',
        'appointment_completed_at' => 'datetime',
        'appointment_updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'workflow_settings_applied' => 'boolean',
        'notify_out_date' => 'date',
    ];

    protected static function booted()
    {
        $callback = function ($item) {
            if ($item->order && auth()->check()) {
                // Update parent order timestamp and updated_by
                $item->order->update(['updated_by' => auth()->id()]);
            }
        };

        static::created($callback);
        static::updated($callback);
        static::deleted($callback);
    }

    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    /**
     * Mirrors Employee::employerTenancy (app/Models/Employee.php) — same
     * "employer role sees only their own employer_id, caretaker role sees
     * only employers they're assigned to as caretaker" restriction — but
     * ProductionItem has no direct employer_id column (only reachable via
     * order->employer_id). This started as a deliberately LOCAL scope
     * (called explicitly via `ProductionItem::visibleToUser()`, not an
     * automatic global scope) so the first fix stayed narrowly confined to
     * the appointment-reminder endpoints that were reported. It has since
     * been applied to WorkflowController's remaining unscoped direct
     * item-lookup methods too (updateRemarks, toggleStep, finalizeItem,
     * fetchTrash, etc.) as a follow-up round — see ProductionOrder's own
     * new employerTenancy GLOBAL scope for the sibling fix on Order-level
     * lookups, which now protects those automatically everywhere instead.
     *
     * `->withTrashed()` on the `order` relation lookup is required here
     * (not just cosmetic) because this scope is also used by the trash
     * endpoints (fetchTrash/restoreTrash/forceDeleteTrash), where the
     * ProductionItem itself is soft-deleted and its parent ProductionOrder
     * is very often soft-deleted too — without it, `whereHas('order', ...)`
     * would silently exclude those legitimately-visible trashed items
     * (ProductionOrder's own SoftDeletes scope would otherwise still apply
     * inside the closure).
     */
    public function scopeVisibleToUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return $query;
        }

        if ($user->hasRole('employer')) {
            $employer = $user->employer;
            if ($employer) {
                $query->whereHas('order', fn ($q) => $q->withTrashed()->where('employer_id', $employer->id));
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('caretaker')) {
            $query->whereHas('order', fn ($q) => $q->withTrashed()->whereHas('employer', fn ($q2) => $q2->whereHas('caretakers', fn ($q3) => $q3->where('users.id', $user->id))));
        }

        return $query;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    // Legacy / Ad-hoc fields
    public function steps()
    {
        // Ordered chronologically
        return $this->hasMany(WorkflowStep::class)->orderBy('created_at', 'asc');
    }

    // NEW: Checklist Steps from WorkType
    public function completedWorkTypeSteps()
    {
        return $this->belongsToMany(WorkTypeStep::class, 'production_item_step')
                    ->withPivot('completed_at', 'completed_by')
                    ->withTimestamps();
    }

    public function transactions()
    {
        return $this->belongsToMany(FinancialTransaction::class, 'financial_transaction_items');
    }

    // ACCESSORS

    public function getHasVisaAttribute()
    {
        return $this->employee ? !empty($this->employee->visaExpiryDate) : false;
    }

}
