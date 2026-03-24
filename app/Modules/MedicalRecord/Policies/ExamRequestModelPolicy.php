<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloSolicitacaoExame;

final class ExamRequestModelPolicy
{
    public function view(User $user, ModeloSolicitacaoExame $model): bool
    {
        if ($model->user_id === null) {
            return true;
        }

        return $user->id === $model->user_id;
    }

    public function update(User $user, ModeloSolicitacaoExame $model): bool
    {
        return $user->id === $model->user_id;
    }

    public function delete(User $user, ModeloSolicitacaoExame $model): bool
    {
        return $user->id === $model->user_id;
    }
}
