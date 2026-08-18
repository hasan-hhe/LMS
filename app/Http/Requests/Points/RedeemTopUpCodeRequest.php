<?php

namespace App\Http\Requests\Points;

class RedeemTopUpCodeRequest extends PointsRequest
{
    public function rules(): array
    {
        return ['code' => 'required|string|max:32', 'member_id' => 'nullable|integer|exists:users,id'];
    }

    public function messages(): array
    {
        return ['code.required' => 'رمز شحن النقاط مطلوب', 'member_id.exists' => 'العضو المحدد غير موجود'];
    }
}
