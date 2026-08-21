<?php

namespace App\Http\Requests\Book;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('has_borrow_points') && ! $this->boolean('has_borrow_points')) {
            $this->merge(['borrow_points' => 0]);
        }
    }

    public function rules(): array
    {
        return [
            'ISBN' => 'required|string|unique:books,ISBN',
            'auther_id' => 'required|integer|exists:authers,id',
            'catagory_id' => 'required|integer|exists:catagories,id',
            'publisher_id' => 'required|integer|exists:publishers,id',
            'title' => 'required|string|max:255',
            'discription' => 'required|string',
            'price' => 'required|numeric|min:0',
            'price_points' => 'nullable|integer|min:0',
            'has_borrow_points' => 'sometimes|boolean',
            'borrow_points' => $this->boolean('has_borrow_points')
                ? 'required|integer|min:1'
                : 'nullable|integer|min:0',
            'amount' => 'required|integer|min:0',
            'copies_count' => 'required|integer|min:0|max:500',
            'year_of_publishing' => 'required|string|max:4',
            'number_edition' => 'required|string|max:50',
            'cover_image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'ISBN.required' => 'رقم ISBN مطلوب',
            'ISBN.unique' => 'رقم ISBN موجود مسبقاً',
            'auther_id.required' => 'المؤلف مطلوب',
            'auther_id.exists' => 'المؤلف المحدد غير موجود',
            'catagory_id.required' => 'التصنيف مطلوب',
            'catagory_id.exists' => 'التصنيف المحدد غير موجود',
            'publisher_id.required' => 'دار النشر مطلوبة',
            'publisher_id.exists' => 'دار النشر المحددة غير موجودة',
            'title.required' => 'عنوان الكتاب مطلوب',
            'discription.required' => 'وصف الكتاب مطلوب',
            'price.required' => 'سعر الكتاب مطلوب',
            'price.numeric' => 'سعر الكتاب يجب أن يكون رقماً',
            'price.min' => 'سعر الكتاب يجب أن يكون أكبر من أو يساوي صفر',
            'price_points.integer' => 'سعر الكتاب بالنقاط يجب أن يكون رقماً صحيحاً',
            'borrow_points.required' => 'حدد عدد نقاط الاستعارة',
            'borrow_points.integer' => 'نقاط الاستعارة يجب أن تكون رقماً صحيحاً',
            'borrow_points.min' => 'إذا كانت الاستعارة عليها نقاط فيجب أن تكون نقطة واحدة على الأقل',
            'amount.required' => 'عدد نسخ البيع مطلوب',
            'amount.integer' => 'عدد نسخ البيع يجب أن يكون رقماً صحيحاً',
            'amount.min' => 'عدد نسخ البيع لا يمكن أن يكون سالباً',
            'copies_count.required' => 'عدد نسخ الاستعارة مطلوب',
            'copies_count.integer' => 'عدد نسخ الاستعارة يجب أن يكون رقماً صحيحاً',
            'copies_count.min' => 'عدد نسخ الاستعارة لا يمكن أن يكون سالباً',
            'copies_count.max' => 'عدد نسخ الاستعارة لا يمكن أن يتجاوز 500 نسخة',
            'year_of_publishing.required' => 'سنة النشر مطلوبة',
            'number_edition.required' => 'رقم الطبعة مطلوب',
            'cover_image.image' => 'الغلاف يجب أن يكون صورة',
            'cover_image.mimes' => 'صيغة الغلاف يجب أن تكون: png, jpg, jpeg',
            'cover_image.max' => 'حجم الغلاف لا يتجاوز 2 ميغابايت',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات الكتاب غير صحيحة')
        );
    }
}
