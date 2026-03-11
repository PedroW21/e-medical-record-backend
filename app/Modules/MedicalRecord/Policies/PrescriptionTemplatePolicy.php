<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;

final class PrescriptionTemplatePolicy
{
    public function view(User $user, ModeloPrescricao $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function update(User $user, ModeloPrescricao $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function delete(User $user, ModeloPrescricao $template): bool
    {
        return $user->id === $template->user_id;
    }
}
