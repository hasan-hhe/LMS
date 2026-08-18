<?php

namespace App\Http\Requests\Favorite;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListFavoritesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => 'required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => 'معرف العضو مطلوب',
            'member_id.exists' => 'العضو المحدد غير موجود',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات العضو غير صحيحة')
        );
    }
}
