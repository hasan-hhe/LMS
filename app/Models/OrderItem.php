<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'book_ISBN',
        'price_once',
        'count',
    ];

    protected $casts = [
        'price_once' => 'float',
        'count'      => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_ISBN', 'ISBN');
    }

    public function totalPrice(): float
    {
        return $this->price_once * $this->count;
    }
}
