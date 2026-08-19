<?php

namespace App\Services;

use App\Models\Book;
use App\Models\DigitalAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DigitalAssetService
{
    public function upsert(string $isbn, array $data, ?UploadedFile $pdfFile = null, ?UploadedFile $audioFile = null): DigitalAsset
    {
        return DB::transaction(function () use ($isbn, $data, $pdfFile, $audioFile) {
            if (! Book::whereKey($isbn)->exists()) {
                throw new \Exception('الكتاب غير موجود');
            }

            $asset = DigitalAsset::query()->firstOrNew(['book_ISBN' => $isbn]);

            if (! empty($data['remove_pdf'])) {
                $asset->deleteStoredFile($asset->pdf_url);
                $asset->pdf_url = null;
            }
            if (! empty($data['remove_audio'])) {
                $asset->deleteStoredFile($asset->audio_url);
                $asset->audio_url = null;
            }

            if ($pdfFile) {
                $asset->deleteStoredFile($asset->pdf_url);
                $asset->pdf_url = $pdfFile->store('digital/pdfs', DigitalAsset::DISK);
            }
            if ($audioFile) {
                $asset->deleteStoredFile($asset->audio_url);
                $asset->audio_url = $audioFile->store('digital/audio', DigitalAsset::DISK);
            }

            if (array_key_exists('is_free', $data)) {
                $asset->is_free = (bool) $data['is_free'];
            }

            $asset->save();

            return $asset->fresh('book');
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
