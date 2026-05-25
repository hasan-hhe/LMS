<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookInstance extends Model
{
    protected $table = 'book_instances';

    public $timestamps = false;

    protected $fillable = [
        'book_ISBN',
        'state_id',
        'condition',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_ISBN', 'ISBN');
    }

    public function state()
    {
        return $this->belongsTo(InstanceState::class, 'state_id');
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'book_instance_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'book_instance_id');
    }

    public function isAvailable(): bool
    {
        return $this->state?->state === 'available';
    }
}
