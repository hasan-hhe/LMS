<?php

namespace App\Http\Requests\Points;

class UpdatePointSettingsRequest extends PointsRequest
{
    public function rules(): array
    {
        return [
            'syp_per_point' => 'sometimes|required|numeric|min:0.01',
            'reward_return_on_time' => 'sometimes|required|integer|min:0',
            'fine_per_day_syp' => 'sometimes|required|numeric|min:0.01',
            'fine_per_day_points' => 'sometimes|required|integer|min:0',
            'extension_per_day_points' => 'sometimes|required|integer|min:0',
            'loan_period_days' => 'sometimes|required|integer|min:1',
            'membership_points' => 'sometimes|required|integer|min:0',
            'membership_days' => 'sometimes|required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'syp_per_point.min' => 'سعر تحويل النقطة يجب أن يكون أكبر من صفر',
            'reward_return_on_time.min' => 'مكافأة الإعادة لا يمكن أن تكون سالبة',
            'fine_per_day_syp.min' => 'غرامة اليوم بالليرة يجب أن تكون أكبر من صفر',
            'fine_per_day_points.min' => 'غرامة اليوم بالنقاط لا يمكن أن تكون سالبة',
            'extension_per_day_points.min' => 'نقاط تمديد اليوم لا يمكن أن تكون سالبة',
            'loan_period_days.min' => 'مدة الاستعارة يجب أن تكون يوماً واحداً على الأقل',
            'membership_points.min' => 'نقاط الاشتراك بالعضوية لا يمكن أن تكون سالبة',
            'membership_days.min' => 'مدة العضوية يجب أن تكون يوماً واحداً على الأقل',
        ];
    }
}
