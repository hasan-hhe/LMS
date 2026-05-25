<?php

namespace App\Http\Requests\Book;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auther_id'          => 'sometimes|integer|exists:authers,id',
            'catagory_id'        => 'sometimes|integer|exists:catagories,id',
            'publisher_id'       => 'sometimes|integer|exists:publishers,id',
            'title'              => 'sometimes|string|max:255',
            'discription'        => 'sometimes|string',
            'price'              => 'sometimes|numeric|min:0',
            'amount'             => 'sometimes|integer|min:0',
            'year_of_publishing' => 'sometimes|string|max:4',
            'number_edition'     => 'sometimes|string|max:50',
            'cover_image'        => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'auther_id.exists'      => 'المؤلف المحدد غير موجود',
            'catagory_id.exists'    => 'التصنيف المحدد غير موجود',
            'publisher_id.exists'   => 'دار النشر المحددة غير موجودة',
            'price.numeric'         => 'سعر الكتاب يجب أن يكون رقماً',
            'price.min'             => 'سعر الكتاب يجب أن يكون أكبر من أو يساوي صفر',
            'amount.integer'        => 'كمية الكتب يجب أن تكون رقماً صحيحاً',
            'cover_image.image'     => 'الغلاف يجب أن يكون صورة',
            'cover_image.mimes'     => 'صيغة الغلاف يجب أن تكون: png, jpg, jpeg',
            'cover_image.max'       => 'حجم الغلاف لا يتجاوز 2 ميغابايت',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات تعديل الكتاب غير صحيحة')
        );
    }
}
