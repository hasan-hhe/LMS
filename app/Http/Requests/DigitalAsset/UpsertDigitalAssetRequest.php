<?php

namespace App\Http\Requests\DigitalAsset;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpsertDigitalAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pdf_url' => 'sometimes|nullable|url|max:2048',
            'audio_url' => 'sometimes|nullable|url|max:2048',
            'is_free' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'pdf_url.url' => 'رابط ملف PDF غير صالح',
            'audio_url.url' => 'رابط الملف الصوتي غير صالح',
            'is_free.boolean' => 'قيمة الإتاحة المجانية غير صحيحة',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات المحتوى الرقمي غير صحيحة')
        );
    }
}
