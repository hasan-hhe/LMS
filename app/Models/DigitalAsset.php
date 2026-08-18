<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalAsset extends Model
{
    protected $fillable = [
        'book_ISBN',
        'pdf_url',
        'audio_url',
        'is_free',
    ];

    protected $casts = [
        'is_free' => 'boolean',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_ISBN', 'ISBN');
    }
}
