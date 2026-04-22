<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;

final class AttachmentPolicy
{
    public function viewAnyForProntuario(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function view(User $user, Anexo $attachment): bool
    {
        return $user->id === $attachment->prontuario->user_id;
    }

    public function create(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, Anexo $attachment): bool
    {
        return $user->id === $attachment->prontuario->user_id;
    }

    public function delete(User $user, Anexo $attachment): bool
    {
        return $user->id === $attachment->prontuario->user_id;
    }
}
