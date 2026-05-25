<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    protected $table = 'publishers';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'location',
    ];

    public function books()
    {
        return $this->hasMany(Book::class, 'publisher_id');
    }
}
