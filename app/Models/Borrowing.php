<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    protected $table = 'borrowings';

    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'librarian_id',
        'book_instance_id',
        'start_date',
        'end_date',
        'returned_at',
        'due_date',
        'borrowing_cast',
        'is_paid',
        'paid_at',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'due_date'     => 'date',
        'returned_at'  => 'datetime',
        'paid_at'      => 'datetime',
        'is_paid'      => 'boolean',
        'borrowing_cast' => 'float',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function librarian()
    {
        return $this->belongsTo(User::class, 'librarian_id');
    }

    public function bookInstance()
    {
        return $this->belongsTo(BookInstance::class, 'book_instance_id');
    }

    public function lateFine()
    {
        return $this->hasOne(LateFine::class, 'borrowing_id');
    }

    public function editions()
    {
        return $this->hasMany(BorrowingEdition::class, 'borrowing_id');
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    public function isOverdue(): bool
    {
        return !$this->isReturned() && $this->end_date < now();
    }
}
