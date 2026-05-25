<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowingEdition extends Model
{
    protected $table = 'borrowing_editions';

    public $timestamps = false;

    protected $fillable = [
        'borrowing_id',
        'new_end_date',
        'taxe',
        'cause',
    ];

    protected $casts = [
        'new_end_date' => 'date',
        'taxe'         => 'float',
    ];

    public function borrowing()
    {
        return $this->belongsTo(Borrowing::class, 'borrowing_id');
    }
}
