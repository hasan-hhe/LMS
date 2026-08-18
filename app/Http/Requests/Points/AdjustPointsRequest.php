<?php

namespace App\Http\Requests\Points;

class AdjustPointsRequest extends PointsRequest
{
    public function rules(): array
    {
        return ['member_id' => 'required|integer|exists:users,id', 'points' => 'required|integer|not_in:0', 'note' => 'required|string|max:500'];
    }

    public function messages(): array
    {
        return ['member_id.required' => 'العضو مطلوب', 'member_id.exists' => 'العضو غير موجود', 'points.required' => 'عدد النقاط مطلوب', 'points.not_in' => 'يجب ألا يكون التعديل صفراً', 'note.required' => 'سبب التعديل مطلوب'];
    }
}
