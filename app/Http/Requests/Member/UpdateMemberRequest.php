<?php

namespace App\Http\Requests\Member;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memberId = $this->route('member') ?? $this->route('id');

        return [
            'first_name'         => 'sometimes|string|max:100',
            'last_name'          => 'sometimes|string|max:100',
            'email'              => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($memberId)],
            'phone'              => ['sometimes', 'string', 'regex:/^[0-9]+$/', Rule::unique('users', 'phone')->ignore($memberId)],
            'adress'             => 'nullable|string|max:255',
            'photo_image'        => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'participe_end_date' => 'nullable|date',
            'state'              => 'sometimes|in:ACTIVE,PAUSED,CANCLED',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'   => 'البريد الإلكتروني مستخدم مسبقاً',
            'phone.unique'   => 'رقم الهاتف مستخدم مسبقاً',
            'phone.regex'    => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات تعديل العضو غير صحيحة')
        );
    }
}
