<?php

namespace App\Http\Requests\Category;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:100',
            'discription' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'اسم التصنيف مطلوب',
            'discription.required' => 'وصف التصنيف مطلوب',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات التصنيف غير صحيحة')
        );
    }
}
