<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user() ?: $request->user('sanctum');
        $canAccess = $this->isAccessibleBy($user);

        return [
            'has_digital' => true,
            'has_pdf' => $this->hasPdf(),
            'has_audio' => $this->hasAudio(),
            'locked' => ! $canAccess,
            'is_free' => $this->is_free,
            'pdf_url' => $canAccess ? $this->accessiblePdfUrl() : null,
            'audio_url' => $canAccess ? $this->accessibleAudioUrl() : null,
            'pdf_size' => $canAccess ? $this->storedFileSize('pdf') : null,
            'audio_size' => $canAccess ? $this->storedFileSize('audio') : null,
        ];
    }
}
