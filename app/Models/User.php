<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $authPasswordName = 'password_hash';  // Laravel 11+

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'role',
        'state',
        'adress',
        'photo_url',
        'identity_number',
        'participe_end_date',
        'email',
        'password_hash',
        'participe_end_date',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'participe_end_date'  => 'date',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function fullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isLibrarian(): bool
    {
        return $this->role === 'LIBRARIAN';
    }

    public function isMember(): bool
    {
        return $this->role === 'MEMBER';
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'member_id');
    }

    public function librarianBorrowings()
    {
        return $this->hasMany(Borrowing::class, 'librarian_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
