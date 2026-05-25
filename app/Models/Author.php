<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'authers';

    public $timestamps = false;

    protected $fillable = [
        'firstname',
        'lastname',
        'nationality',
    ];

    public function fullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function books()
    {
        return $this->hasMany(Book::class, 'auther_id');
    }
}
