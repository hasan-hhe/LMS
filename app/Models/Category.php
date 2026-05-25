<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'catagories';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'discription',
    ];

    public function books()
    {
        return $this->hasMany(Book::class, 'catagory_id');
    }
}
