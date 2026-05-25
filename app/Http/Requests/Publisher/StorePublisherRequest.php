<?php

namespace App\Http\Requests\Publisher;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePublisherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'اسم دار النشر مطلوب',
            'location.required' => 'موقع دار النشر مطلوب',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات دار النشر غير صحيحة')
        );
    }
}
