<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_order_id',
        'employee_id', // Nullable (if ghost)
        'new_employee_data', // JSON
        'current_barrier_id',
        'custom_field_values', // JSON
    ];

    protected $casts = [
        'new_employee_data' => 'array',
        'custom_field_values' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function currentBarrier()
    {
        return $this->belongsTo(WorkflowBarrier::class, 'current_barrier_id');
    }

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the display name (Real or Ghost).
     */
    public function getDisplayNameAttribute()
    {
        if ($this->employee_id && $this->employee) {
            return $this->employee->fullname_th;
        }
        $data = $this->new_employee_data ?? [];
        return ($data['name_th'] ?? '') . ' ' . ($data['surname_th'] ?? '');
    }

    /**
     * Get the passport (Real or Ghost).
     */
    public function getPassportNumberAttribute()
    {
        if ($this->employee_id && $this->employee) {
            return $this->employee->employeePassport;
        }
        $data = $this->new_employee_data ?? [];
        return $data['passport_no'] ?? '-';
    }

     /**
     * Get the photo URL (Real or Ghost).
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->employee_id && $this->employee) {
            return $this->employee->avatar; // Assuming accessor on Employee
        }
        $data = $this->new_employee_data ?? [];
        return $data['photo_url'] ?? asset('images/default-avatar.png');
    }
}
