<?php

namespace App\Http\Requests\Order;

use App\Helpers\ResponseHelper;
use App\Models\OrderState;
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
            'reason' => 'nullable|string|min:3|max:500',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $state = OrderState::find($this->input('state_id'));
            if ($state && in_array($state->state, ['cancelled', 'rejected'], true) && blank($this->input('reason'))) {
                $validator->errors()->add('reason', 'سبب الرفض أو الإلغاء مطلوب');
            }
        });
    }

    public function messages(): array
    {
        return [
            'state_id.required' => 'حالة الطلب مطلوبة',
            'state_id.exists' => 'الحالة المحددة غير موجودة',
            'reason.min' => 'سبب الرفض أو الإلغاء يجب أن يكون 3 أحرف على الأقل',
            'reason.max' => 'سبب الرفض أو الإلغاء لا يتجاوز 500 حرف',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseHelper::validationError($validator->errors(), 'بيانات تحديث حالة الطلب غير صحيحة')
        );
    }
}
