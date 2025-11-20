<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'type',
        'file_path',
        'error_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
