<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;

final class MedicalRecordPolicy
{
    public function view(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id && $prontuario->isDraft();
    }

    public function finalize(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id && $prontuario->isDraft();
    }
}
