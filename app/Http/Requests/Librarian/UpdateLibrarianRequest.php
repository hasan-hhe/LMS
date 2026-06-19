<?php

namespace App\Http\Requests\Librarian;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLibrarianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $librarianId = $this->route('librarian');

        return [
            'first_name'  => 'sometimes|string|max:100',
            'last_name'   => 'sometimes|string|max:100',
            'email'       => "sometimes|email|unique:users,email,{$librarianId}",
            'phone'       => "sometimes|string|unique:users,phone,{$librarianId}|regex:/^[0-9]+$/",
            'adress'      => 'nullable|string|max:255',
            'photo_image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'state'       => 'sometimes|in:ACTIVE,PAUSED,CANCLED',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقاً',
            'phone.regex'  => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات تعديل أمين المكتبة غير صحيحة')
        );
    }
}
