<?php

namespace App\Http\Requests\Reservation;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'          => 'required|integer|exists:users,id',
            'book_instance_id' => 'required|integer|exists:book_instances,id',
            'cause'            => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'          => 'معرف المستخدم مطلوب',
            'user_id.exists'            => 'المستخدم المحدد غير موجود',
            'book_instance_id.required' => 'معرف نسخة الكتاب مطلوب',
            'book_instance_id.exists'   => 'نسخة الكتاب المحددة غير موجودة',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات الحجز غير صحيحة')
        );
    }
}
