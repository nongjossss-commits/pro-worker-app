<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'logo_path',
    ];
}
