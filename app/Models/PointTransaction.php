<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'points', 'type', 'reference_type', 'reference_id', 'note'];

    protected $casts = ['points' => 'integer', 'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
