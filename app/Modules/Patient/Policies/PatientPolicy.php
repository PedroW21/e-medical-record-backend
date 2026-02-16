<?php

declare(strict_types=1);

namespace App\Modules\Patient\Policies;

use App\Models\User;
use App\Modules\Patient\Models\Paciente;

final class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Paciente $patient): bool
    {
        return $user->id === $patient->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Paciente $patient): bool
    {
        return $user->id === $patient->user_id;
    }

    public function delete(User $user, Paciente $patient): bool
    {
        return $user->id === $patient->user_id;
    }
}
