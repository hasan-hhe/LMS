<?php

namespace App\Http\Requests\Review;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => 'required|string|exists:books,ISBN',
            'rate' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('rating') && ! $this->filled('rate')) {
            $this->merge(['rate' => $this->input('rating')]);
        }
    }

    public function messages(): array
    {
        return [
            'isbn.required' => 'رقم ISBN للكتاب مطلوب',
            'isbn.exists' => 'الكتاب المحدد غير موجود',
            'rate.required' => 'التقييم مطلوب',
            'rate.integer' => 'يجب أن يكون التقييم رقماً صحيحاً',
            'rate.between' => 'يجب أن يكون التقييم بين 1 و5',
            'comment.max' => 'يجب ألا يتجاوز التعليق 1000 محرف',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات التقييم غير صحيحة')
        );
    }
}
