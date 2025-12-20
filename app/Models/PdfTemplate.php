<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdfTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'type',
        'employer_id',
        'field_mapping',
        'created_by',
    ];

    protected $casts = [
        'field_mapping' => 'array',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
