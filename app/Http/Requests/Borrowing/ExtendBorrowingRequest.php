<?php

namespace App\Http\Requests\Borrowing;

use App\Helpers\ResponseHelper;
use App\Models\Borrowing;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExtendBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_end_date' => 'required|date|after:today',
            'cause' => 'nullable|string|max:255',
            'administrative' => 'sometimes|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('new_end_date')) {
            return;
        }

        $borrowing = Borrowing::find($this->route('id'));
        if ($borrowing?->end_date) {
            $this->merge([
                'new_end_date' => $borrowing->end_date->copy()->addDays(7)->toDateString(),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'new_end_date.required' => 'تاريخ التمديد الجديد مطلوب',
            'new_end_date.after' => 'تاريخ التمديد يجب أن يكون في المستقبل',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات التمديد غير صحيحة')
        );
    }
}
