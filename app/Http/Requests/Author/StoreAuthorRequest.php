<?php

namespace App\Http\Requests\Author;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname'   => 'required|string|max:100',
            'lastname'    => 'required|string|max:100',
            'nationality' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required'   => 'الاسم الأول للمؤلف مطلوب',
            'lastname.required'    => 'الاسم الأخير للمؤلف مطلوب',
            'nationality.required' => 'جنسية المؤلف مطلوبة',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات المؤلف غير صحيحة')
        );
    }
}
