<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalAssetFileController extends Controller
{
    public function show(string $isbn, string $type): StreamedResponse
    {
        $asset = DigitalAsset::query()->with('book')->where('book_ISBN', $isbn)->first();
        $path = $asset?->storedPath($type);
        if (! $asset || ! $path || ! Storage::disk(DigitalAsset::DISK)->exists($path)) {
            abort(404, 'الملف غير موجود');
        }

        $title = $asset->book?->title ?: 'book';
        $downloadName = $type === 'audio'
            ? $title.'.'.(pathinfo($path, PATHINFO_EXTENSION) ?: 'mp3')
            : $title.'.pdf';
        $mime = $type === 'pdf'
            ? 'application/pdf'
            : (Storage::disk(DigitalAsset::DISK)->mimeType($path) ?: 'audio/mpeg');

        return Storage::disk(DigitalAsset::DISK)->response($path, $downloadName, [
            'Content-Type' => $mime,
        ]);
    }
}
