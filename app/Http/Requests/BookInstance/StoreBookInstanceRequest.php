<?php

namespace App\Http\Requests\BookInstance;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_ISBN' => 'required|string|exists:books,ISBN',
            'state_id'  => 'required|integer|exists:instance_states,id',
            'condition' => 'required|in:new,worn,almost_new',
        ];
    }

    public function messages(): array
    {
        return [
            'book_ISBN.required' => 'رقم ISBN للكتاب مطلوب',
            'book_ISBN.exists'   => 'الكتاب المحدد غير موجود',
            'state_id.required'  => 'حالة النسخة مطلوبة',
            'state_id.exists'    => 'الحالة المحددة غير موجودة',
            'condition.required' => 'وضع النسخة مطلوب',
            'condition.in'       => 'وضع النسخة يجب أن يكون: جديد، مستعمل، أو شبه جديد',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات نسخة الكتاب غير صحيحة')
        );
    }
}
