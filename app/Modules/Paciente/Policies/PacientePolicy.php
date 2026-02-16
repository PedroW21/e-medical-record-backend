<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Policies;

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

final class PacientePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Paciente $paciente): bool
    {
        return $user->id === $paciente->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Paciente $paciente): bool
    {
        return $user->id === $paciente->user_id;
    }

    public function delete(User $user, Paciente $paciente): bool
    {
        return $user->id === $paciente->user_id;
    }
}
