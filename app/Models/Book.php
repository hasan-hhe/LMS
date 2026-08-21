<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'books';

    protected $primaryKey = 'ISBN';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ISBN',
        'auther_id',
        'catagory_id',
        'publisher_id',
        'title',
        'discription',
        'price',
        'price_points',
        'borrow_points',
        'amount',
        'rate_avg',
        'cover_url',
        'year_of_publishing',
        'number_edition',
    ];

    protected $casts = [
        'price' => 'float',
        'price_points' => 'integer',
        'borrow_points' => 'integer',
        'rate_avg' => 'float',
        'amount' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(Author::class, 'auther_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'catagory_id');
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class, 'publisher_id');
    }

    public function instances()
    {
        return $this->hasMany(BookInstance::class, 'book_ISBN', 'ISBN');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'book_ISBN', 'ISBN');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'book_ISBN', 'ISBN');
    }

    public function digitalAsset()
    {
        return $this->hasOne(DigitalAsset::class, 'book_ISBN', 'ISBN');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'book_ISBN', 'ISBN');
    }

    public function availableInstances()
    {
        return $this->instances()->whereHas('state', function ($query) {
            $query->where('state', 'available');
        });
    }
}
