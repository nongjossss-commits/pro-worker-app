<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'file_path',
        'error_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
