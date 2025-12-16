<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id',
        'field_name',
        'field_type',
        'field_value',
        'file_path',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
