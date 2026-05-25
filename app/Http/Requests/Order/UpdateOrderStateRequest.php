<?php

namespace App\Http\Requests\Order;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'state_id' => 'required|integer|exists:order_states,id',
        ];
    }

    public function messages(): array
    {
        return [
            'state_id.required' => 'حالة الطلب مطلوبة',
            'state_id.exists'   => 'الحالة المحددة غير موجودة',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات تحديث حالة الطلب غير صحيحة')
        );
    }
}
