<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

final class LabResultPolicy
{
    public function view(User $user, ValorLaboratorial $labResult): bool
    {
        return $user->id === $labResult->prontuario->user_id;
    }

    public function create(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, ValorLaboratorial $labResult): bool
    {
        return $user->id === $labResult->prontuario->user_id;
    }

    public function delete(User $user, ValorLaboratorial $labResult): bool
    {
        return $user->id === $labResult->prontuario->user_id;
    }
}
