<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'type', 'message', 'due_date', 'status'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
