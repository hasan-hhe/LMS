<?php

namespace App\Http\Requests\Member;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'required|email|unique:users,email',
            'phone'              => 'required|string|unique:users,phone|regex:/^[0-9]+$/',
            'identity_number'    => 'required|string|unique:users,identity_number|regex:/^[0-9]+$/',
            'password'           => 'required|min:8|confirmed',
            'adress'             => 'nullable|string|max:255',
            'photo_image'        => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'participe_end_date' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'         => 'الاسم الأول مطلوب',
            'last_name.required'          => 'الاسم الأخير مطلوب',
            'email.required'              => 'البريد الإلكتروني مطلوب',
            'email.email'                 => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.unique'                => 'البريد الإلكتروني مستخدم مسبقاً',
            'phone.required'              => 'رقم الهاتف مطلوب',
            'phone.unique'                => 'رقم الهاتف مستخدم مسبقاً',
            'phone.regex'                 => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
            'identity_number.required'    => 'رقم الهوية مطلوب',
            'identity_number.unique'      => 'رقم الهوية مستخدم مسبقاً',
            'identity_number.regex'       => 'رقم الهوية يجب أن يحتوي على أرقام فقط',
            'password.required'           => 'كلمة المرور مطلوبة',
            'password.min'                => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed'          => 'تأكيد كلمة المرور غير متطابق',
            'participe_end_date.after'    => 'تاريخ انتهاء العضوية يجب أن يكون في المستقبل',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات العضو غير صحيحة')
        );
    }
}
