<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    public function __construct(
        private PointService $pointService,
        private PointSettingService $pointSettingService,
    ) {}

    public function quote(): array
    {
        return [
            'points' => $this->pointSettingService->getMembershipPoints(),
            'days' => $this->pointSettingService->getMembershipDays(),
        ];
    }

    public function status(User $user): array
    {
        $quote = $this->quote();
        $end = $user->participe_end_date;
        $isActive = $end && $end->copy()->endOfDay()->isFuture();

        return [
            'participe_end_date' => $end?->toDateString(),
            'is_active' => (bool) $isActive,
            'points' => $quote['points'],
            'days' => $quote['days'],
            'can_subscribe' => true,
        ];
    }

    public function subscribe(User $user): array
    {
        $points = $this->pointSettingService->getMembershipPoints();
        $days = $this->pointSettingService->getMembershipDays();

        DB::beginTransaction();
        try {
            if ($points > 0) {
                $this->pointService->debit(
                    $user->id,
                    $points,
                    'spend',
                    User::class,
                    (string) $user->id,
                    'اشتراك أو تمديد عضوية الإعارة'
                );
            }

            $from = $user->participe_end_date && $user->participe_end_date->copy()->endOfDay()->isFuture()
                ? $user->participe_end_date->copy()
                : now();

            $user->update([
                'participe_end_date' => $from->addDays($days)->toDateString(),
            ]);

            DB::commit();

            return [
                'user' => new UserResource($user->fresh()),
                'membership' => $this->status($user->fresh()),
                'balance' => $this->pointService->getBalance($user->id),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
