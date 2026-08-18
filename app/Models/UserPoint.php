<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPoint extends Model
{
    protected $fillable = ['user_id', 'balance'];

    protected $casts = ['balance' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
