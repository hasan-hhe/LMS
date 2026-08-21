<?php

namespace App\Http\Requests\Borrowing;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReturnBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outcome' => 'nullable|in:ok,damaged,lost',
        ];
    }

    public function messages(): array
    {
        return [
            'outcome.in' => 'حالة الإرجاع يجب أن تكون: سليم، تالف، أو مفقود',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات الإرجاع غير صحيحة')
        );
    }
}
