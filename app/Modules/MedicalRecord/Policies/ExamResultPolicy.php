<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Model;

final class ExamResultPolicy
{
    /**
     * Determine whether the user can list exam results for a medical record.
     */
    public function viewAny(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    /**
     * Determine whether the user can create an exam result for a medical record.
     */
    public function create(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    /**
     * Determine whether the user can update an exam result.
     *
     * Ownership is checked via the prontuario relationship on the result model.
     */
    public function update(User $user, Model $examResult): bool
    {
        return $user->id === $examResult->prontuario->user_id;
    }

    /**
     * Determine whether the user can delete an exam result.
     *
     * Ownership is checked via the prontuario relationship on the result model.
     */
    public function delete(User $user, Model $examResult): bool
    {
        return $user->id === $examResult->prontuario->user_id;
    }
}
