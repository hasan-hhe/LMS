<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DigitalAsset extends Model
{
    public const DISK = 'local';

    public const FILE_URL_TTL_HOURS = 24;

    protected $fillable = [
        'book_ISBN',
        'pdf_url',
        'audio_url',
        'is_free',
    ];

    protected $casts = [
        'is_free' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (DigitalAsset $asset) {
            $asset->deleteStoredFiles();
        });
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_ISBN', 'ISBN');
    }

    public function isAccessibleBy(?User $user): bool
    {
        if ($this->is_free) {
            return true;
        }
        if (! $user) {
            return false;
        }
        if (in_array($user->role, ['ADMIN', 'LIBRARIAN'], true)) {
            return true;
        }

        return OrderItem::query()
            ->where('book_ISBN', $this->book_ISBN)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereHas('state', fn ($state) => $state->where('state', 'confirmed')))
            ->exists();
    }

    public function hasPdf(): bool
    {
        return filled($this->pdf_url);
    }

    public function hasAudio(): bool
    {
        return filled($this->audio_url);
    }

    public function accessiblePdfUrl(): ?string
    {
        return $this->accessibleFileUrl('pdf', $this->pdf_url);
    }

    public function accessibleAudioUrl(): ?string
    {
        return $this->accessibleFileUrl('audio', $this->audio_url);
    }

    public function storedFileSize(string $type): ?int
    {
        $path = $type === 'audio' ? $this->audio_url : $this->pdf_url;
        if (! filled($path) || self::isRemotePath($path)) {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        return $disk->exists($path) ? $disk->size($path) : null;
    }

    public function storedPath(string $type): ?string
    {
        $path = $type === 'audio' ? $this->audio_url : $this->pdf_url;
        if (! filled($path) || self::isRemotePath($path)) {
            return null;
        }

        return $path;
    }

    public function deleteStoredFiles(): void
    {
        $this->deleteStoredFile($this->pdf_url);
        $this->deleteStoredFile($this->audio_url);
    }

    public function deleteStoredFile(?string $path): void
    {
        if (! filled($path) || self::isRemotePath($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public static function isRemotePath(?string $path): bool
    {
        return is_string($path) && preg_match('#^https?://#i', $path) === 1;
    }

    private function accessibleFileUrl(string $type, ?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }
        if (self::isRemotePath($path)) {
            return $path;
        }

        return URL::temporarySignedRoute('digital.file', now()->addHours(self::FILE_URL_TTL_HOURS), [
            'isbn' => $this->book_ISBN,
            'type' => $type,
        ]);
    }
}
