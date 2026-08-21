<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'state_id',
        'total_prices',
        'total_points',
        'total_amount',
        'pickup_expires_at',
    ];

    protected $casts = [
        'total_prices' => 'float',
        'total_points' => 'integer',
        'total_amount' => 'integer',
        'pickup_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function state()
    {
        return $this->belongsTo(OrderState::class, 'state_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
