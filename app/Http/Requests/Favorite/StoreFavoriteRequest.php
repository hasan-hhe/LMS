<?php

namespace App\Http\Requests\Favorite;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => 'required|string|exists:books,ISBN',
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.required' => 'رقم ISBN للكتاب مطلوب',
            'isbn.exists' => 'الكتاب المحدد غير موجود',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات المفضلة غير صحيحة')
        );
    }
}
