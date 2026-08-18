<?php

namespace App\Services;

use App\Models\Book;
use App\Models\DigitalAsset;
use Illuminate\Support\Facades\DB;

class DigitalAssetService
{
    public function upsert(string $isbn, array $data): DigitalAsset
    {
        return DB::transaction(function () use ($isbn, $data) {
            if (! Book::whereKey($isbn)->exists()) {
                throw new \Exception('الكتاب غير موجود');
            }

            return DigitalAsset::updateOrCreate(
                ['book_ISBN' => $isbn],
                $data
            )->fresh('book');
        });
    }

    public function findByIsbn(string $isbn): DigitalAsset
    {
        $asset = DigitalAsset::query()->with('book')->where('book_ISBN', $isbn)->first();
        if (! $asset) {
            throw new \Exception('لا يوجد أصل رقمي لهذا الكتاب');
        }

        return $asset;
    }

    public function delete(string $isbn): void
    {
        DB::transaction(function () use ($isbn) {
            $asset = DigitalAsset::query()->where('book_ISBN', $isbn)->lockForUpdate()->first();
            if (! $asset) {
                throw new \Exception('لا يوجد أصل رقمي لهذا الكتاب');
            }
            $asset->delete();
        });
    }
}
