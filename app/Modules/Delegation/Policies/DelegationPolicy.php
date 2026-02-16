<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Policies;

use App\Models\User;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Delegation\Models\Delegacao;

final class DelegationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Doctor;
    }

    public function delete(User $user, Delegacao $delegation): bool
    {
        return $user->id === $delegation->medico_id;
    }
}
