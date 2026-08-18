<?php

namespace App\Http\Requests\Points;

class UpdatePointSettingsRequest extends PointsRequest
{
    public function rules(): array
    {
        return [
            'syp_per_point' => 'sometimes|required|numeric|min:0.01',
            'reward_return_on_time' => 'sometimes|required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return ['syp_per_point.min' => 'سعر تحويل النقطة يجب أن يكون أكبر من صفر', 'reward_return_on_time.min' => 'مكافأة الإعادة لا يمكن أن تكون سالبة'];
    }
}
