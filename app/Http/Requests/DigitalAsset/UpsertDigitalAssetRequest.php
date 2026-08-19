<?php

namespace App\Http\Requests\DigitalAsset;

use App\Helpers\ResponseHelper;
use App\Models\DigitalAsset;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpsertDigitalAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['is_free', 'remove_pdf', 'remove_audio'] as $field) {
            if ($this->exists($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $this->boolean($field),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'pdf' => 'sometimes|nullable|file|mimetypes:application/pdf|max:51200',
            'audio' => 'sometimes|nullable|file|mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,audio/mp4,audio/x-m4a,audio/aac,audio/x-aac|max:102400',
            'is_free' => 'sometimes|boolean',
            'remove_pdf' => 'sometimes|boolean',
            'remove_audio' => 'sometimes|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isbn = (string) $this->route('isbn');
            $exists = DigitalAsset::query()->where('book_ISBN', $isbn)->exists();
            if ($exists) {
                return;
            }
            if (! $this->hasFile('pdf') && ! $this->hasFile('audio')) {
                $validator->errors()->add('pdf', 'ارفع ملف PDF أو ملف صوتي على الأقل');
            }
        });
    }

    public function messages(): array
    {
        return [
            'pdf.file' => 'ملف PDF غير صالح',
            'pdf.mimetypes' => 'يجب أن يكون الملف بصيغة PDF',
            'pdf.max' => 'حجم ملف PDF لا يتجاوز 50 ميغابايت',
            'audio.file' => 'الملف الصوتي غير صالح',
            'audio.mimetypes' => 'صيغة الملف الصوتي يجب أن تكون: mp3, wav, ogg, m4a, aac',
            'audio.max' => 'حجم الملف الصوتي لا يتجاوز 100 ميغابايت',
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
