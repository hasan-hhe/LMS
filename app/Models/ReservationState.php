<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationState extends Model
{
    protected $table = 'reservation_states';

    public $timestamps = false;

    protected $fillable = ['state'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'state_id');
    }
}
