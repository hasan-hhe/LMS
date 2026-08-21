<?php

namespace App\Http\Requests\Notification;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'audience' => ['required', 'in:members,selected'],
            'user_ids' => ['required_if:audience,selected', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'send_email' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الإشعار مطلوب',
            'title.max' => 'عنوان الإشعار يجب ألا يتجاوز 150 حرفاً',
            'body.required' => 'نص الإشعار مطلوب',
            'body.max' => 'نص الإشعار يجب ألا يتجاوز 2000 حرف',
            'audience.required' => 'جهة الإرسال مطلوبة',
            'audience.in' => 'جهة الإرسال غير صحيحة',
            'user_ids.required_if' => 'يجب اختيار مستلم واحد على الأقل',
            'user_ids.min' => 'يجب اختيار مستلم واحد على الأقل',
            'user_ids.*.exists' => 'أحد المستخدمين المحددين غير موجود',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات الإشعار غير صحيحة')
        );
    }
}
