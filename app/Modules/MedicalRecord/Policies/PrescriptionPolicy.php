<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;

final class PrescriptionPolicy
{
    public function view(User $user, Prescricao $prescription): bool
    {
        return $user->id === $prescription->prontuario->user_id;
    }

    public function create(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, Prescricao $prescription): bool
    {
        return $user->id === $prescription->prontuario->user_id;
    }

    public function delete(User $user, Prescricao $prescription): bool
    {
        return $user->id === $prescription->prontuario->user_id;
    }
}
