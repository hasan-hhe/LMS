<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LateFine extends Model
{
    protected $table = 'late_fines';

    public $timestamps = false;

    protected $fillable = [
        'borrowing_id',
        'type',
        'days_late',
        'fine',
        'fine_points',
        'accrued_until',
        'is_paid',
        'paid_at',
        'paid_via',
    ];

    protected $casts = [
        'days_late' => 'integer',
        'fine' => 'float',
        'fine_points' => 'integer',
        'accrued_until' => 'date',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function borrowing()
    {
        return $this->belongsTo(Borrowing::class, 'borrowing_id');
    }
}
