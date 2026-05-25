<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderState extends Model
{
    protected $table = 'order_states';

    public $timestamps = false;

    protected $fillable = ['state'];

    public function orders()
    {
        return $this->hasMany(Order::class, 'state_id');
    }
}
