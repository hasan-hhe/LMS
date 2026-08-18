<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopUpCode extends Model
{
    protected $fillable = ['code', 'points_value', 'user_id', 'is_used', 'expires_at', 'used_at', 'used_by', 'created_by'];

    protected $casts = [
        'points_value' => 'integer',
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function boundUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
