<?php

namespace App\Http\Requests\Points;

class GenerateTopUpCodesRequest extends PointsRequest
{
    public function rules(): array
    {
        return [
            'count' => 'required|integer|min:1|max:1000',
            'points_value' => 'required|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'user_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return ['count.required' => 'عدد الرموز مطلوب', 'count.max' => 'الحد الأقصى ألف رمز في الدفعة', 'points_value.required' => 'قيمة النقاط مطلوبة', 'expires_at.after' => 'تاريخ الانتهاء يجب أن يكون مستقبلياً', 'user_id.exists' => 'العضو المحدد غير موجود'];
    }
}
