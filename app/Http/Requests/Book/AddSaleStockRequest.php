<?php

namespace App\Http\Requests\Book;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddSaleStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_ISBN' => 'required|string|exists:books,ISBN',
            'copies_count' => 'required|integer|min:1|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'book_ISBN.required' => 'الكتاب مطلوب',
            'book_ISBN.exists' => 'الكتاب المحدد غير موجود',
            'copies_count.required' => 'عدد نسخ البيع مطلوب',
            'copies_count.integer' => 'عدد نسخ البيع يجب أن يكون رقماً صحيحاً',
            'copies_count.min' => 'أضف نسخة بيع واحدة على الأقل',
            'copies_count.max' => 'لا يمكن إضافة أكثر من 500 نسخة دفعة واحدة',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات نسخ البيع غير صحيحة')
        );
    }
}
