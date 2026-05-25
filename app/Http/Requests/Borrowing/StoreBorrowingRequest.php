<?php

namespace App\Http\Requests\Borrowing;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id'        => 'required|integer|exists:users,id',
            'book_instance_id' => 'required|integer|exists:book_instances,id',
            'end_date'         => 'required|date|after:today',
            'borrowing_cost'   => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required'        => 'معرف العضو مطلوب',
            'member_id.exists'          => 'العضو المحدد غير موجود',
            'book_instance_id.required' => 'معرف نسخة الكتاب مطلوب',
            'book_instance_id.exists'   => 'نسخة الكتاب المحددة غير موجودة',
            'end_date.required'         => 'تاريخ انتهاء الاستعارة مطلوب',
            'end_date.after'            => 'تاريخ انتهاء الاستعارة يجب أن يكون في المستقبل',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات الاستعارة غير صحيحة')
        );
    }
}
