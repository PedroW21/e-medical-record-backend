<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\SolicitacaoExame;

final class ExamRequestPolicy
{
    public function view(User $user, SolicitacaoExame $examRequest): bool
    {
        $prontuario = $examRequest->relationLoaded('prontuario')
            ? $examRequest->prontuario
            : $examRequest->prontuario()->firstOrFail();

        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, SolicitacaoExame $examRequest): bool
    {
        $prontuario = $examRequest->relationLoaded('prontuario')
            ? $examRequest->prontuario
            : $examRequest->prontuario()->firstOrFail();

        return $user->id === $prontuario->user_id;
    }

    public function delete(User $user, SolicitacaoExame $examRequest): bool
    {
        $prontuario = $examRequest->relationLoaded('prontuario')
            ? $examRequest->prontuario
            : $examRequest->prontuario()->firstOrFail();

        return $user->id === $prontuario->user_id;
    }
}
