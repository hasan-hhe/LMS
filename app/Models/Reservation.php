<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservations';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'book_instance_id',
        'state_id',
        'cause',
        'notified_at',
        'reserved_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'reserved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookInstance()
    {
        return $this->belongsTo(BookInstance::class, 'book_instance_id');
    }

    public function state()
    {
        return $this->belongsTo(ReservationState::class, 'state_id');
    }
}
