<?php

namespace App\Http\Requests\Order;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'        => 'required|integer|exists:users,id',
            'items'          => 'required|array|min:1',
            'items.*.isbn'   => 'required|string|exists:books,ISBN',
            'items.*.count'  => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'        => 'معرف المستخدم مطلوب',
            'user_id.exists'          => 'المستخدم المحدد غير موجود',
            'items.required'          => 'عناصر الطلب مطلوبة',
            'items.min'               => 'يجب أن يحتوي الطلب على عنصر واحد على الأقل',
            'items.*.isbn.required'   => 'رقم ISBN للكتاب مطلوب',
            'items.*.isbn.exists'     => 'أحد الكتب المحددة غير موجود',
            'items.*.count.required'  => 'الكمية مطلوبة',
            'items.*.count.min'       => 'الكمية يجب أن تكون واحداً على الأقل',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات الطلب غير صحيحة')
        );
    }
}
