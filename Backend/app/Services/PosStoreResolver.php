<?php

namespace App\Services;

use App\Models\Store;
use App\Models\UserShift;

class PosStoreResolver
{
    public function storeIdForUser(int $userId): int
    {
        $shift = UserShift::query()
            ->with('cashRegister:id,store_id')
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->first();
        return (int) ($shift?->cashRegister?->store_id ?? Store::query()->findOrFail(1)->id);
    }

    public function storeIdForShift(UserShift $shift): ?int
    {
        $shift->loadMissing('cashRegister:id,store_id');
        return $shift->cashRegister?->store_id;
    }
}
